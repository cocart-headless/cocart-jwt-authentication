<?php
/**
 * CoCart JWT Authentication core setup.
 *
 * @author   Sébastien Dumont
 * @category Package
 * @license  GPL-2.0+
 */

namespace CoCart\JWTAuthentication;

use WC_Validation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main CoCart JWT Authentication class.
 *
 * @class CoCart\JWTAuthentication\Plugin
 */
final class Plugin {

	/**
	 * Plugin Version
	 *
	 * @access public
	 *
	 * @static
	 */
	public static $version = '1.0.1';

	/**
	 * JWT algorithm to generate signature.
	 *
	 * @var string
	 */
	private static $algorithm = 'HS256';

	/**
	 * Initiate CoCart JWT Authentication.
	 *
	 * @access public
	 *
	 * @static
	 */
	public static function init() {
		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ), 0 );

		// Filter in first before anyone else.
		add_filter( 'cocart_authenticate', array( __CLASS__, 'perform_jwt_authentication' ), 0, 3 );

		// Send token to login response.
		add_filter( 'cocart_login_extras', array( __CLASS__, 'send_token' ) );
	} // END init()

	/**
	 * Return the name of the package.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'CoCart JWT Authentication';
	} // END get_name()

	/**
	 * Return the version of the package.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::$version;
	} // END get_version()

	/**
	 * Return the path to the package.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	} // END get_path()

	/**
	 * JWT Authentication.
	 *
	 * Validates a token passed via the authentication header and authenticates the user.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param int    $user_id The user ID if authentication was successful.
	 * @param bool            Determines if the site is secure.
	 * @param object $auth    The Authentication class.
	 *
	 * @return int $user_id The user ID returned if authentication was successful.
	 */
	public static function perform_jwt_authentication( int $user_id, bool $ssl, $auth ) {
		$auth->set_method( 'jwt_auth' );

		$auth_header = self::get_auth_header();

		// Validating authorization header and token.
		if ( isset( $auth_header ) && 0 === stripos( $auth_header, 'bearer ' ) ) {
			/**
			 * The HTTP_AUTHORIZATION is present, verify the format.
			 * If the format is wrong return the user.
			 */
			list($token) = sscanf( $auth_header, 'Bearer %s' );

			// If the token is malformed then set error.
			if ( ! $token ) {
				$auth->set_error( new \WP_Error( 'cocart_jwt_auth_bad_auth_header', __( 'Authorization header malformed. Please check the authentication token and try again.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );

				return false;
			}

			$secret_key = defined( 'COCART_JWT_AUTH_SECRET_KEY' ) ? COCART_JWT_AUTH_SECRET_KEY : false;

			// First thing, check the secret key if not exist return a error.
			if ( ! $secret_key ) {
				$auth->set_error( new \WP_Error( 'cocart_jwt_auth_bad_config', __( 'JWT is not configured properly.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );

				return false;
			}

			// Check if the token is valid using the secret key.
			if ( ! self::validate_token( $token, $secret_key ) ) {
				$auth->set_error( new \WP_Error( 'cocart_jwt_auth_not_valid', __( 'JWT Token is not valid or has expired.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );

				return false;
			}

			// Decode token provided using the secret key.
			$payload = self::decode_token( $token, $secret_key );
			$payload = $payload->payload;

			// The token is decoded now validate the iss.
			if ( $payload->iss !== self::get_iss() ) {
				$auth->set_error( new \WP_Error( 'cocart_jwt_auth_bad_iss', __( 'The token issuer does not match with this server.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );

				return false;
			}

			// Check the user id existence in the token.
			if ( ! isset( $payload->data->user->id ) ) {
				$auth->set_error( new \WP_Error( 'cocart_jwt_auth_user_missing', __( 'The token does not identify any user.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );

				return false;
			}

			// So far so good, check if the given user id exists in database.
			$user = get_user_by( 'id', $payload->data->user->id );

			if ( ! $user ) {
				$auth->set_error( new \WP_Error( 'cocart_jwt_auth_no_user', __( "The user doesn't exist.", 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );

				return false;
			}

			$username = isset( $payload->data->user->username ) ? $payload->data->user->username : '';
			$password = isset( $payload->data->user->password ) ? $payload->data->user->password : '';

			// Authenticate user once token is decoded.
			$auth_user = wp_authenticate( $username, $password );

			// If the authentication is failed return error response.
			if ( is_wp_error( $auth_user ) ) {
				$auth->set_error( new \WP_Error( 'cocart_jwt_authentication_error', __( 'Authentication is invalid. Please check the authentication information is correct and try again. Authentication also only works on a secure connection.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );

				return false;
			}

			return $auth_user->ID;
		}

		return $user_id;
	} // END perform_jwt_authentication()

	/**
	 * Get the token issuer.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return string The token issuer (iss).
	 */
	public static function get_iss() {
		return get_bloginfo( 'url' );
	} // END get_iss()

	/**
	 * Generates a token for user.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $secret_key Secret Key to use for encoding the token.
	 */
	public static function generate_token( string $secret_key ) {
		$auth_header = self::get_auth_header();

		// Validating authorization header and get username and password.
		if ( isset( $auth_header ) && 0 === stripos( $auth_header, 'basic ' ) ) {
			$exploded = explode( ':', base64_decode( substr( $auth_header, 6 ) ), 2 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			list( $username, $password ) = $exploded;

			// Check if the username provided is a billing phone number and return the username if true.
			if ( WC_Validation::is_phone( $username ) ) {
				$username = self::get_user_by_phone( $username ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			// Check if the username provided was an email address and return the username if true.
			if ( is_email( $username ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$user     = get_user_by( 'email', $username ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$username = $user->user_login; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			// Generate a token from provided data and secret key.
			$user      = get_user_by( 'login', $username );
			$issued_at = time();
			$expire    = $issued_at + intval( apply_filters( 'cocart_jwt_auth_expire', DAY_IN_SECONDS * 2 ) );
			$header    = self::to_base_64_url( self::generate_header() );
			$payload   = self::to_base_64_url( wp_json_encode( array(
				'iss'  => self::get_iss(),
				'iat'  => $issued_at,
				'exp'  => $expire,
				'data' => array(
					'user'       => array(
						'id'       => $user->ID,
						'username' => $username,
						'password' => $password,
					),
					'secret_key' => $secret_key,
				),
			) ) );
			$signature = self::to_base_64_url( self::generate_signature( $header . '.' . $payload, $secret_key ) );

			return $header . '.' . $payload . '.' . $signature;
		}

		return time();
	} // END generate_token()

	/**
	 * Finds a user based on a matching billing phone number.
	 *
	 * @access protected
	 *
	 * @static
	 *
	 * @param numeric $phone The billing phone number to check.
	 *
	 * @return string The username returned if found.
	 */
	protected static function get_user_by_phone( $phone ) {
		$matchingUsers = get_users( array(
			'meta_key'     => 'billing_phone',
			'meta_value'   => $phone,
			'meta_compare' => '=',
		) );

		$username = ! empty( $matchingUsers ) && is_array( $matchingUsers ) ? $matchingUsers[0]->user_login : $phone;

		return $username;
	} // END get_user_by_phone()

	/**
	 * Get the authorization header.
	 *
	 * @access protected
	 *
	 * @static
	 *
	 * @return string $auth_header
	 */
	protected static function get_auth_header() {
		$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : false;

		// Double check for different auth header string (server dependent).
		if ( ! $auth_header ) {
			$auth_header = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) : false;
		}

		return $auth_header;
	} // END get_auth_header()

	/**
	 * Sends the generated token to the login response.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param array $extras
	 *
	 * @return array $extras
	 */
	public static function send_token( $extras ) {
		$secret_key = defined( 'COCART_JWT_AUTH_SECRET_KEY' ) ? COCART_JWT_AUTH_SECRET_KEY : false;

		if ( $secret_key ) {
			$extras['jwt_token'] = self::generate_token( $secret_key );
		}

		return $extras;
	} // END send_token()

	/**
	 * Validates a provided token against the provided secret key.
	 * Checks for format, valid header for our class, expiration claim validity and signature.
	 * https://datatracker.ietf.org/doc/html/rfc7519#section-7.2
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $token  Full token string.
	 * @param string $secret The secret key used to generate the signature.
	 *
	 * @return bool
	 */
	public static function validate_token( string $token, string $secret_key ) {
		/**
		 * Confirm the structure of a JSON Web Token, it has three parts separated
		 * by dots and complies with Base64URL standards.
		 */
		if ( preg_match( '/^[a-zA-Z\d\-_=]+\.[a-zA-Z\d\-_=]+\.[a-zA-Z\d\-_=]+$/', $token ) !== 1 ) {
			return false;
		}

		$parts = self::decode_token( $token );

		// Check if header declares a supported JWT by this class.
		if (
			! is_object( $parts->header ) ||
			! property_exists( $parts->header, 'typ' ) ||
			! property_exists( $parts->header, 'alg' ) ||
			'JWT' !== $parts->header->typ ||
			self::$algorithm !== $parts->header->alg
		) {
			return false;
		}

		// Check if token is expired.
		if ( ! property_exists( $parts->payload, 'exp' ) || time() > (int) $parts->payload->exp ) {
			return false;
		}

		// Check if the token is based on our secret key.
		$encoded_regenerated_signature = self::to_base_64_url(
			self::generate_signature( $parts->header_encoded . '.' . $parts->payload_encoded, $secret_key )
		);

		return hash_equals( $encoded_regenerated_signature, $parts->signature_encoded );
	} // END validate_token()

	/**
	 * Returns the decoded/encoded header, payload and signature from a token string.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $token Full token string.
	 *
	 * @return object
	 */
	public static function decode_token( string $token ) {
		$parts = explode( '.', $token );

		return (object) array(
			'header'            => json_decode( self::from_base_64_url( $parts[0] ) ),
			'header_encoded'    => $parts[0],
			'payload'           => json_decode( self::from_base_64_url( $parts[1] ) ),
			'payload_encoded'   => $parts[1],
			'signature'         => self::from_base_64_url( $parts[2] ),
			'signature_encoded' => $parts[2],
		);
	} // END decode_token()

	/**
	 * Generates the json formatted header for our HS256 JWT token.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @return string|bool
	 */
	private static function generate_header() {
		return wp_json_encode(
			array(
				'alg' => self::$algorithm,
				'typ' => 'JWT',
			)
		);
	} // END generate_header()

	/**
	 * Generates a sha256 signature for the provided string using the provided secret.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @param string $string Header + Payload token substring.
	 * @param string $secret The secret used to generate the signature.
	 *
	 * @return false|string
	 */
	private static function generate_signature( string $string, string $secret ) {
		return hash_hmac(
			'sha256',
			$string,
			$secret,
			true
		);
	} // END generate_signature()

	/**
	 * Encodes a string to url safe base64.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @param string $string The string to be encoded.
	 *
	 * @return string
	 */
	private static function to_base_64_url( string $string ) {
		return str_replace(
			array( '+', '/', '=' ),
			array( '-', '_', '' ),
			base64_encode( $string ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		);
	} // END to_base_64_url()

	/**
	 * Decodes a string encoded using url safe base64, supporting auto padding.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @param string $string the string to be decoded.
	 *
	 * @return string
	 */
	private static function from_base_64_url( string $string ) {
		/**
		 * Add padding to base64 strings which require it. Some base64 URL strings
		 * which are decoded will have missing padding which is represented by the
		 * equals sign.
		 */
		if ( strlen( $string ) % 4 !== 0 ) {
			return self::from_base_64_url( $string . '=' );
		}

		return base64_decode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			str_replace(
				array( '-', '_' ),
				array( '+', '/' ),
				$string
			)
		);
	} // END from_base_64_url()

	/**
	 * Load the plugin translations if any ready.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/cocart-jwt-authentication/cocart-jwt-authentication-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/cocart-jwt-authentication-LOCALE.mo
	 *
	 * @access public
	 *
	 * @static
	 */
	public static function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'cocart-jwt-authentication' );

		unload_textdomain( 'cocart-jwt-authentication' );
		load_textdomain( 'cocart-jwt-authentication', WP_LANG_DIR . '/cocart-jwt-authentication/cocart-jwt-authentication-' . $locale . '.mo' );
		load_plugin_textdomain( 'cocart-jwt-authentication', false, plugin_basename( dirname( COCART_JWT_AUTHENTICATION_FILE ) ) . '/languages' );
	} // END load_plugin_textdomain()

} // END class

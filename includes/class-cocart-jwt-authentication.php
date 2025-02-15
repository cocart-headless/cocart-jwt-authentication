<?php
/**
 * CoCart JWT Authentication core setup.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @license GPL-3.0
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
	 *
	 * @var string
	 */
	public static $version = '2.0.0';

	/**
	 * JWT algorithm to generate signature.
	 *
	 * Default is HS256
	 *
	 * @var string
	 */
	private static $algorithm = 'HS256';

	/**
	 * All possible HTTP headers that represent the
	 * User-Agent string.
	 *
	 * @access protected
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @var array
	 */
	protected static array $user_agent_headers = array(
		// The default User-Agent string.
		'HTTP_USER_AGENT',
		// Header can occur on devices using Opera Mini.
		'HTTP_X_OPERAMINI_PHONE_UA',
		// Vodafone specific header: http://www.seoprinciple.com/mobile-web-community-still-angry-at-vodafone/24/
		'HTTP_X_DEVICE_USER_AGENT',
		'HTTP_X_ORIGINAL_USER_AGENT',
		'HTTP_X_SKYFIRE_PHONE',
		'HTTP_X_BOLT_PHONE_UA',
		'HTTP_DEVICE_STOCK_UA',
		'HTTP_X_UCBROWSER_DEVICE_UA',
	);

	/**
	 * Supported algorithms to sign the token.
	 *
	 * @access private
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @see https://www.rfc-editor.org/rfc/rfc7518#section-3
	 *
	 * @var array|string[]
	 */
	private static array $supported_algorithms = [
		'HS256',
		'HS384',
		'HS512',
		'RS256',
		'RS384',
		'RS512',
		'ES256',
		'ES384',
		'ES512',
		'PS256',
		'PS384',
		'PS512'
	];

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
		add_filter( 'cocart_login_extras', array( __CLASS__, 'send_token' ), 0, 2 );

		// Delete token when user logs out.
		add_action( 'wp_logout', array( __CLASS__, 'destroy_token' ) );
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
	 * @since 1.0.0 Introduced.
	 *
	 * @param int    $user_id The user ID if authentication was successful.
	 * @param bool   $ssl     Determines if the site is secure.
	 * @param object $auth    The Authentication class.
	 *
	 * @return int $user_id The user ID returned if authentication was successful.
	 */
	public static function perform_jwt_authentication( int $user_id, bool $ssl, $auth ) {
		$auth->set_method( 'jwt_auth' );

		$auth_header = \CoCart_Authentication::get_auth_header();

		// Validating authorization header and token.
		if ( ! empty( $auth_header ) && 0 === stripos( $auth_header, 'bearer ' ) ) {
			/**
			 * The HTTP_AUTHORIZATION is present, verify the format.
			 * If the format is wrong return the user.
			 */
			list($token) = sscanf( $auth_header, 'Bearer %s' );

			// If the token is malformed then return error.
			if ( ! $token ) {
				// Error: Authorization header malformed. Please check the authentication token and try again.
				$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
				return false;
			}

			$secret_key = defined( 'COCART_JWT_AUTH_SECRET_KEY' ) ? COCART_JWT_AUTH_SECRET_KEY : false;

			// First thing, check the secret key, if not exist return error.
			if ( ! $secret_key ) {
				// Error: JWT is not configured properly.
				$auth->set_error( new \WP_Error( 'cocart_jwt_auth_bad_config', __( 'JWT configuration error.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );

				return false;
			}

			// Check if the token is valid using the secret key.
			if ( ! self::validate_token( $token, $secret_key ) ) {
				// Error: JWT Token is not valid or has expired.
				$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
				return false;
			}

			// Decode token provided using the secret key.
			$payload = self::decode_token( $token, $secret_key );
			$payload = $payload->payload;

			// The token is decoded now validate the iss.
			if ( self::get_iss() !== $payload->iss ) {
				// Error: The token issuer does not match with this server.
				$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
				return false;
			}

			// Check the user id existence in the token.
			if ( ! isset( $payload->data->user->id ) ) {
				// Error: The token does not identify any user.
				$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
				return false;
			}

			// So far so good, check if the given user id exists in database.
			$user = get_user_by( 'id', $payload->data->user->id );

			if ( ! $user ) {
				// Error: The user doesn't exist.
				$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
				return false;
			}

			// Validate IP or Device.
			if ( \CoCart_Authentication::get_ip_address() !== $payload->data->user->ip || sanitize_text_field( wp_unslash( self::get_user_agent_header() ) ) !== $payload->data->user->device ) {
				// Error: IP or Device mismatch.
				$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			}

			// User is authenticated.
			return $user->ID;
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
	 * Generates a token for user otherwise returns a timestamp.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $secret_key Secret Key to use for encoding the token.
	 */
	public static function generate_token( string $secret_key ) {
		$auth_header = \CoCart_Authentication::get_auth_header();

		// Validating authorization header and get username and password.
		if ( ! empty( $auth_header ) && 0 === stripos( $auth_header, 'basic ' ) ) {
			$exploded = explode( ':', base64_decode( substr( $auth_header, 6 ) ), 2 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			list( $username, $password ) = $exploded;

			$username = \CoCart_Authentication::get_username( $username );

			// Generate a token from provided data and secret key.
			$user      = get_user_by( 'login', $username );
			$issued_at = time();

			/**
			 * Authorization expires not before the time the token was created.
			 *
			 * @since 2.0.0 Introduced.
			 */
			$not_before = apply_filters( 'cocart_jwt_auth_not_before', $issued_at );

			/**
			 * Authorization expiration.
			 *
			 * Expires after 10 days by default.
			 *
			 * @since 1.0.0 Introduced.
			 */
			$auth_expires = apply_filters( 'cocart_jwt_auth_expire', DAY_IN_SECONDS * 10 );

			$expire    = $issued_at + intval( $auth_expires );
			$header    = self::to_base_64_url( self::generate_header() );
			$payload   = self::to_base_64_url( wp_json_encode( array(
				'iss'  => self::get_iss(),
				'iat'  => $issued_at,
				'nbf'  => $not_before,
				'exp'  => $expire,
				'data' => array(
					'user'       => array(
						'id'       => $user->ID,
						'username' => $username,
						'ip'       => \CoCart_Authentication::get_ip_address(),
						'device'   => ! empty( self::get_user_agent_header() ) ? sanitize_text_field( wp_unslash( self::get_user_agent_header() ) ) : '',
					),
					'secret_key' => $secret_key,
				),
			) ) );

			/** Let the user modify the token data before the sign. */
			$algorithm = self::get_algorithm();

			if ( $algorithm === false ) {
				// See https://www.rfc-editor.org/rfc/rfc7518#section-3
				return new \WP_Error(
					'cocart_authentication_error',
					__( 'Algorithm not supported', 'cocart-jwt-authentication' ),
					array( 'status' => 401 )
				);
			}

			/** The token is signed, now generate the signature to the response */
			$signature = self::to_base_64_url( self::generate_signature( $header . '.' . $payload, $secret_key ) );

			return $header . '.' . $payload . '.' . $signature;
		}

		return time();
	} // END generate_token()

	/**
	 * Get the algorithm used to sign the token and validate that
	 * the algorithm is in the supported list.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @return false|mixed|null
	 */
	private static function get_algorithm() {
		/**
		 * Filter the algorithm to one of the supported.
		 *
		 * @since 2.0.0 Introduced.
		 */
		$algorithm = apply_filters( 'cocart_jwt_auth_algorithm', self::$algorithm );

		if ( ! in_array( $algorithm, self::$supported_algorithms ) ) {
			return false;
		}

		return $algorithm;
	} // END get_algorithm()

	/**
	 * Generate refresh token.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int User ID
	 */
	public static function generate_refresh( $user_id ) {
		$refresh_token = bin2hex( random_bytes( 64 ) ); // Generates a random 64-byte string.
		update_user_meta( $user_id, 'cocart_jwt_refresh_token', $refresh_token );

		return $refresh_token;
	} // END generate_refresh()

	/**
	 * Finds a user based on a matching billing phone number.
	 *
	 * @access protected
	 *
	 * @deprecated 2.0.0 Moved function to the core of CoCart.
	 *
	 * @static
	 *
	 * @param numeric $phone The billing phone number to check.
	 *
	 * @return string The username returned if found.
	 */
	protected static function get_user_by_phone( $phone ) {
		cocart_deprecated_function( 'CoCart\JWTAuthentication\Plugin::get_user_by_phone', '2.0.0', 'CoCart_Authentication::get_user_by_phone' );

		$matching_users = get_users( array(
			'meta_key'     => 'billing_phone', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'   => $phone,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_compare' => '=',
		) );

		$username = ! empty( $matching_users ) && is_array( $matching_users ) ? $matching_users[0]->user_login : $phone;

		return $username;
	} // END get_user_by_phone()

	/**
	 * Get the authorization header.
	 *
	 * Returns the value from the authorization header.
	 *
	 * On certain systems and configurations, the Authorization header will be
	 * stripped out by the server or PHP. Typically this is then used to
	 * generate `PHP_AUTH_USER`/`PHP_AUTH_PASS` but not passed on. We use
	 * `getallheaders` here to try and grab it out instead.
	 *
	 * @access protected
	 *
	 * @deprecated 2.0.0 Moved function to the core of CoCart.
	 *
	 * @static
	 *
	 * @return string $auth_header
	 */
	protected static function get_auth_header() {
		cocart_deprecated_function( 'CoCart\JWTAuthentication\Plugin::get_auth_header', '2.0.0', 'CoCart_Authentication::get_auth_header' );

		$auth_header = ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : '';

		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();
			// Check for the authorization header case-insensitively.
			foreach ( $headers as $key => $value ) {
				if ( 'authorization' === strtolower( $key ) ) {
					$auth_header = $value;
				}
			}
		}

		// Double check for different auth header string if empty (server dependent).
		if ( empty( $auth_header ) ) {
			$auth_header = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) : '';
		}

		/**
		 * Filter allows you to change the authorization header.
		 *
		 * @since 4.1.0 Introduced into the core of CoCart.
		 *
		 * @param string Authorization header.
		 */
		return apply_filters( 'cocart_auth_header', $auth_header );
	} // END get_auth_header()

	/**
	 * Sends the generated token to the login response.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param array  $extras       Other extras filtered with `cocart_login_extras`.
	 * @param object $current_user The current user.
	 *
	 * @return array $extras
	 */
	public static function send_token( $extras, $user ) {
		$secret_key = defined( 'COCART_JWT_AUTH_SECRET_KEY' ) ? COCART_JWT_AUTH_SECRET_KEY : false;

		if ( $secret_key ) {
			$extras['jwt_token']   = self::generate_token( $secret_key );
			$extras['jwt_refresh'] = self::generate_refresh( $user->ID );
		}

		return $extras;
	} // END send_token()

	/**
	 * Destroys the refresh token when the user logs out.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @hooked: wp_logout
	 *
	 * @param int $user_id User ID.
	 */
	public static function destroy_token( $user_id ) {
		delete_user_meta( $user_id, 'cocart_jwt_refresh_token' );
	} // END destroy_token()

	/**
	 * Validates a provided token against the provided secret key.
	 *
	 * Checks for format, valid header for our class, expiration claim validity and signature.
	 * https://datatracker.ietf.org/doc/html/rfc7519#section-7.2
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $token      Full token string.
	 * @param string $secret_key The secret key used to generate the signature.
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
			self::get_algorithm() !== $parts->header->alg
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
				'alg' => self::get_algorithm(),
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
	 * @param string $header_encoded Header + Payload token substring.
	 * @param string $secret         The secret used to generate the signature.
	 *
	 * @return false|string
	 */
	private static function generate_signature( string $header_encoded, string $secret ) {
		return hash_hmac(
			'sha256',
			$header_encoded,
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
	 * @param string $encode_string The string to be encoded.
	 *
	 * @return string
	 */
	private static function to_base_64_url( string $encode_string ) {
		return str_replace(
			array( '+', '/', '=' ),
			array( '-', '_', '' ),
			base64_encode( $encode_string ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		);
	} // END to_base_64_url()

	/**
	 * Decodes a string encoded using url safe base64, supporting auto padding.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @param string $encoded_string the string to be decoded.
	 *
	 * @return string
	 */
	private static function from_base_64_url( string $encoded_string ) {
		/**
		 * Add padding to base64 strings which require it. Some base64 URL strings
		 * which are decoded will have missing padding which is represented by the
		 * equals sign.
		 */
		if ( strlen( $encoded_string ) % 4 !== 0 ) {
			return self::from_base_64_url( $encoded_string . '=' );
		}

		return base64_decode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			str_replace(
				array( '-', '_' ),
				array( '+', '/' ),
				$encoded_string
			)
		);
	} // END from_base_64_url()

	/**
	 * Get all possible HTTP headers that can contain the User-Agent string.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @return array List of HTTP headers.
	 */
	public static function get_user_agent_headers() {
		return self::$user_agent_headers;
	} // END get_user_agent_headers()

	/**
	 * Get the found User-Agent header.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @return string
	 */
	public static function get_user_agent_header() {
		$user_agent_header = '';

		foreach ( self::get_user_agent_headers() as $ua_header ) {
			if ( ! empty( $ua_header ) ) {
				$user_agent_header = $ua_header;
			}
		}

		return $user_agent_header;
	} // END get_user_agent_header()

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

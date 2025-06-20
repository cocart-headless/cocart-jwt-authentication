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
	public static $version = '2.5.0';

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
	 * @see https://datatracker.ietf.org/doc/html/rfc7518#section-3
	 *
	 * @var array|string[]
	 */
	private static array $supported_algorithms = array(
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
		'PS512',
	);

	/**
	 * Bearer token pattern.
	 *
	 * @access private
	 *
	 * @since 2.5.0 Introduced.
	 *
	 * @var string
	 */
	private const BEARER_TOKEN_PATTERN = '/^Bearer\s+([a-zA-Z0-9\-_=]+\.[a-zA-Z0-9\-_=]+\.[a-zA-Z0-9\-_=]+)$/i';

	/**
	 * Initiate CoCart JWT Authentication.
	 *
	 * @access public
	 *
	 * @static
	 */
	public static function init() {
		include_once __DIR__ . '/admin/class-cocart-jwt-wc-admin-system-status.php';

		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ), 0 );

		// Register REST API endpoint to refresh tokens.
		add_action( 'rest_api_init', function () {
			register_rest_route( 'cocart/jwt', '/refresh-token', array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'refresh_token' ),
				'permission_callback' => '__return_true',
			) );
		} );

		// Register REST API endpoint to validate tokens.
		add_action( 'rest_api_init', function () {
			register_rest_route( 'cocart/jwt', '/validate-token', array(
				'methods'             => 'POST',
				'callback'            => function () {
					return rest_ensure_response( array( 'message' => __( 'Token is valid.', 'cocart-jwt-authentication' ) ) );
				},
				'permission_callback' => function () {
					return get_current_user_id() > 0;
				},
			) );
		} );

		// Filter in first before anyone else.
		add_filter( 'cocart_authenticate', array( __CLASS__, 'perform_jwt_authentication' ), 0, 3 );

		// Send tokens to login response.
		add_filter( 'cocart_login_extras', array( __CLASS__, 'send_tokens' ), 0, 2 );

		// Delete tokens when user logs out.
		add_action( 'wp_logout', array( __CLASS__, 'destroy_tokens' ) );

		// Delete tokens when user changes password/email or user is deleted.
		add_action( 'after_password_reset', array( __CLASS__, 'destroy_tokens' ) );
		add_action( 'profile_update', array( __CLASS__, 'maybe_destroy_tokens' ), 10, 2 );
		add_action( 'delete_user', array( __CLASS__, 'destroy_tokens' ), 10, 1 );

		// Add rate limits for JWT refresh token.
		add_filter( 'cocart_api_rate_limit_options', array( __CLASS__, 'jwt_rate_limits' ), 0 );

		// Schedule cron job for cleaning up expired tokens.
		add_action( 'cocart_jwt_cleanup_cron', array( __CLASS__, 'cleanup_expired_tokens' ) );
		register_activation_hook( COCART_JWT_AUTHENTICATION_FILE, array( __CLASS__, 'schedule_cron_job' ) );
		register_deactivation_hook( COCART_JWT_AUTHENTICATION_FILE, array( __CLASS__, 'clear_scheduled_cron_job' ) );

		// WP-CLI Commands.
		self::register_cli_commands();
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
	 * Check if the authorization header contains a Bearer authentication.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 2.5.1 Introduced.
	 *
	 * @param string $auth Authorization header value.
	 *
	 * @return bool
	 */
	private static function is_bearer_auth( string $auth ): bool {
		return ! empty( $auth ) && preg_match( self::BEARER_TOKEN_PATTERN, $auth );
	} // END is_bearer_auth()

	/**
	 * Extract Bearer token from authorization header.
	 *
	 * @access private
	 *
	 * @since 2.5.0 Introduced.
	 *
	 * @param string $auth Authorization header value.
	 *
	 * @return string|null Token if found, null otherwise.
	 */
	private static function extract_bearer_token( string $auth ): ?string {
		if ( preg_match( self::BEARER_TOKEN_PATTERN, $auth, $matches ) ) {
			return $matches[1];
		}

		return null;
	} // END extract_bearer_token()

	/**
	 * Handle Bearer token authentication.
	 *
	 * @access private
	 *
	 * @since 2.5.0 Introduced.
	 *
	 * @param string $auth Authorization header value.
	 *
	 * @return mixed Authentication result.
	 */
	private static function handle_bearer_token( $auth ) {
		$auth_header = \CoCart_Authentication::get_auth_header();

		$token = self::extract_bearer_token( $auth_header );

		// Validating authorization header and token.
		if ( empty( $auth_header ) && is_null( $token ) ) {
			\CoCart_Logger::log( esc_html__( 'Authorization header present but no Bearer token.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', 'Authentication failed.', array( 'status' => 401 ) ) );
		}

		return $token;
	} // END handle_bearer_token()

	/**
	 * Check if the token is valid.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 2.5.0 Introduced.
	 *
	 * @param string $token The JWT token to validate.
	 *
	 * @return \WP_User|false|WP_Error The user object if valid, false otherwise.
	 */
	private static function is_token_valid( $token ) {
		$secret_key = self::get_secret_public_key();

		// First thing, check the secret key, if not exist return error.
		if ( ! $secret_key ) {
			// Error: JWT is not configured properly.
			\CoCart_Logger::log( esc_html__( 'JWT is not configured properly.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_jwt_auth_bad_config', __( 'JWT configuration error.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			return false;
		}

		// Decode token provided.
		$decoded_token = self::decode_token( $token );

		// Check if header declares a supported JWT by this class.
		if (
			! is_object( $decoded_token->header ) ||
			! property_exists( $decoded_token->header, 'typ' ) ||
			! property_exists( $decoded_token->header, 'alg' ) ||
			'JWT' !== $decoded_token->header->typ ||
			self::get_algorithm() !== $decoded_token->header->alg
		) {
			\CoCart_Logger::log( esc_html__( 'Token is malformed.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			return false;
		}

		// Verify signature.
		$encoded_regenerated_signature = self::to_base_64_url(
			self::generate_signature( $decoded_token->header_encoded . '.' . $decoded_token->payload_encoded, $secret_key )
		);

		if ( ! hash_equals( $encoded_regenerated_signature, $decoded_token->signature_encoded ) ) {
			\CoCart_Logger::log( esc_html__( 'Token signature verification failed.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			return false;
		}

		// Check if token is expired.
		if ( ! property_exists( $decoded_token->payload, 'exp' ) || time() > (int) $decoded_token->payload->exp ) {
			\CoCart_Logger::log( esc_html__( 'Token has expired.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			return false;
		}

		// Check if the token matches server.
		if ( self::get_iss() !== $decoded_token->payload->iss ) {
			// Error: The token issuer does not match with this server.
			\CoCart_Logger::log( esc_html__( 'Token is invalid: Does not match with this server.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			return false;
		}

		// Check the user id existence in the token.
		if ( ! isset( $decoded_token->payload->data->user->id ) ) {
			// Error: The token does not identify a user.
			\CoCart_Logger::log( esc_html__( 'Token is malformed: Missing user to identify.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			return false;
		}

		// So far so good, check if the given user id exists in database.
		$user = get_user_by( 'id', $decoded_token->payload->data->user->id );

		if ( ! $user ) {
			// Error: The user doesn't exist.
			\CoCart_Logger::log( esc_html__( 'User associated with token no longer exists.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			return false;
		}

		// Validate IP or Device.
		if ( \CoCart_Authentication::get_ip_address() !== $decoded_token->payload->data->user->ip || ( isset( $_SERVER[ self::get_user_agent_header() ] ) && sanitize_text_field( wp_unslash( $_SERVER[ self::get_user_agent_header() ] ) ) !== $decoded_token->payload->data->user->device ) ) {
			// Error: IP or Device mismatch.
			\CoCart_Logger::log( esc_html__( 'Unable to validate IP or User-Agent.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) ) );
			return false;
		}

		/**
		 * Fires when a token is successfully validated.
		 *
		 * @since 2.1.0 Introduced.
		 *
		 * @param object $decoded_token Payload decoded object.
		 */
		do_action( 'cocart_jwt_auth_token_validated', $decoded_token );

		return $user;
	} // END is_token_valid()

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

		// Handle Bearer token authentication.
		$token = self::handle_bearer_token( $auth );

		if ( ! is_null( $token ) ) {
			$user = self::is_token_valid( $token );

			if ( ! empty( $user ) ) {
				// User is authenticated.
				return $user->ID;
			}
		}

		return $user_id;
	} // END perform_jwt_authentication()

	/**
	 * Get the token issuer (iss claim).
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return string The token issuer (iss).
	 */
	public static function get_iss() {
		/**
		 * Allows you to change the token issuer (iss claim) for multi-site setups or custom API endpoints.
		 *
		 * @since 2.0.0 Introduced.
		 */
		return apply_filters( 'cocart_jwt_auth_issuer', get_bloginfo( 'url' ) );
	} // END get_iss()

	/**
	 * Lookup the username from the authorization header.
	 *
	 * @access protected
	 *
	 * @static
	 *
	 * @return string The username if found.
	 */
	protected static function lookup_username() {
		$auth_header = \CoCart_Authentication::get_auth_header();
		$username    = ''; // Initialize the variable.

		// Validating authorization header and get username and password.
		if ( ! empty( $auth_header ) && 0 === stripos( $auth_header, 'basic ' ) ) {
			$exploded = explode( ':', base64_decode( substr( $auth_header, 6 ) ), 2 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			// If valid return username and password.
			if ( 2 === \count( $exploded ) ) {
				list( $username, $password ) = $exploded;
			}
		} elseif ( ! empty( $_SERVER['PHP_AUTH_USER'] ) && ! empty( $_SERVER['PHP_AUTH_PW'] ) ) {
			// Check that we're trying to authenticate via simple headers.
			$username = trim( sanitize_user( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( ! empty( $_REQUEST['username'] ) && ! empty( $_REQUEST['password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Fallback to check if the username and password was passed via URL.
			$username = trim( sanitize_user( wp_unslash( $_REQUEST['username'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Get username from basic authentication.
		$username = \CoCart_Authentication::get_username( $username );

		return $username;
	} // END lookup_username()

	/**
	 * Validate if the user exists.
	 *
	 * @access protected
	 *
	 * @static
	 *
	 * @param int|string $user The user ID or username.
	 *
	 * @return \WP_User|false The user object if valid, false otherwise.
	 */
	protected static function is_user_valid( $user ) {
		if ( empty( $user ) ) {
			return new \WP_Error(
				'cocart_authentication_error',
				__( 'User unknown!', 'cocart-jwt-authentication' ),
				array( 'status' => 404 )
			);
		}

		$get_user_by = 'login';
		if ( is_numeric( $user ) ) {
			$get_user_by = 'id';
		}

		$user = get_user_by( $get_user_by, $user );

		// Error: The user doesn't exist.
		if ( empty( $user ) ) {
			return false;
		}

		return $user;
	} // END is_user_valid()

	/**
	 * Generates a JWT token for the user.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param int|string $user The user to generate token for.
	 *
	 * @return string Generated JWT token.
	 */
	public static function generate_token( int|string $user = '' ) {
		$secret_key = self::get_secret_private_key();

		if ( ! $secret_key ) {
			return new \WP_Error( 'cocart_jwt_auth_bad_config', __( 'JWT configuration error.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		// See if we can lookup the username if no user provided.
		if ( empty( $user ) ) {
			$user = self::lookup_username();
		}

		// Check if user is valid.
		$user = self::is_user_valid( $user );

		if ( ! $user ) {
			return new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		/**
		 * Allows you to change the token issuance timestamp (iat claim) for token timing synchronization.
		 *
		 * @since 2.0.0 Introduced.
		 */
		$issued_at = apply_filters( 'cocart_jwt_auth_issued_at', time() );

		/**
		 * Authorization expires not before the time the token was created.
		 *
		 * Filter allows you to set when the token becomes valid (nbf claim) for token activation control.
		 *
		 * @since 2.0.0 Introduced.
		 */
		$not_before = apply_filters( 'cocart_jwt_auth_not_before', time(), $issued_at );

		/**
		 * Authorization expiration expires after 10 days by default.
		 *
		 * Filter allows you to customize when the token will expire (exp claim) based on roles or conditions.
		 *
		 * @since 1.0.0 Introduced.
		 * @since 2.0.0 Added `$issued_at` parameter.
		 */
		$auth_expires = apply_filters( 'cocart_jwt_auth_expire', DAY_IN_SECONDS * 10, $issued_at );

		$expire = $issued_at + intval( $auth_expires );
		$header = self::to_base_64_url( self::generate_header() );

		// Prepare the payload.
		$payload = array(
			'iss'  => self::get_iss(),
			'iat'  => $issued_at,
			'nbf'  => $not_before,
			'exp'  => $expire,
			'data' => array(
				'user' => array(
					'id'       => $user->ID,
					'username' => $user->user_login,
					'ip'       => \CoCart_Authentication::get_ip_address(),
					'device'   => ! empty( $_SERVER[ self::get_user_agent_header() ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ self::get_user_agent_header() ] ) ) : '',
				),
			),
		);

		/**
		 * Filter allows additional user data to be applied to the payload before the token is generated.
		 *
		 * @since 2.2.0 Introduced.
		 *
		 * @param \WP_User $user User object.
		 */
		$payload['data']['user'] = array_merge( $payload['data']['user'], apply_filters( 'cocart_jwt_auth_token_user_data', array(), $user ) );

		/**
		 * Filter the token data before the sign.
		 *
		 * @since 2.3.0 Introduced.
		 *
		 * @param array    $payload The token data.
		 * @param \WP_User $user    User object.
		 */
		$payload = apply_filters( 'cocart_jwt_auth_token_before_sign', $payload, $user );

		// Generate a token from provided data.
		$payload = self::to_base_64_url( wp_json_encode( $payload ) );

		$algorithm = self::get_algorithm();

		if ( false === $algorithm ) {
			// See https://datatracker.ietf.org/doc/html/rfc7518#section-3
			return new \WP_Error(
				'cocart_authentication_error',
				__( 'Algorithm not supported', 'cocart-jwt-authentication' ),
				array( 'status' => 401 )
			);
		}

		/** The token is signed, now generate the signature to the response */
		$signature = self::to_base_64_url( self::generate_signature( $header . '.' . $payload, $secret_key ) );

		$token = self::get_token_prefix() . $header . '.' . $payload . '.' . $signature;

		// Save user token.
		update_user_meta( $user->ID, 'cocart_jwt_token', $token );

		/**
		 * Fires when a new JWT token is generated after successful authentication.
		 *
		 * @since 2.1.0 Introduced.
		 *
		 * @param string   $token Refreshed token.
		 * @param \WC_User $user  User object.
		 */
		do_action( 'cocart_jwt_auth_token_generated', $token, $user );

		return $token;
	} // END generate_token()

	/**
	 * Get the token prefix.
	 *
	 * This prefix is added to the token when it is generated.
	 * It is not required to use a prefix, but it can help to distinguish
	 * tokens from different sources or implementations.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 2.5.0 Introduced.
	 *
	 * @return string The token prefix.
	 */
	private static function get_token_prefix() {
		/**
		 * Filter allows you to change the token prefix.
		 *
		 * @since 2.5.0 Introduced.
		 *
		 * @return string The token prefix.
		 */
		return apply_filters( 'cocart_jwt_auth_token_prefix', '' );
	} // END get_token_prefix()

	/**
	 * Check if the token prefix is enabled.
	 *
	 * This is used to determine if the token prefix should be added to the token when it is generated.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 2.5.0 Introduced.
	 *
	 * @return bool True if the prefix is enabled, false otherwise.
	 */
	private static function is_prefix_enabled() {
		return ! empty( self::get_token_prefix() );
	} // END is_prefix_enabled()

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
	 * Get the secret private key for token signing.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 2.3.0 Introduced.
	 *
	 * @return string|null The public key
	 */
	private static function get_secret_private_key() {
		/**
		 * Allows you to set the private key for token signing.
		 *
		 * @since 2.3.0 Introduced.
		 */
		return apply_filters( 'cocart_jwt_auth_secret_private_key', defined( 'COCART_JWT_AUTH_SECRET_KEY' ) ? COCART_JWT_AUTH_SECRET_KEY : null );
	} // END get_secret_private_key()

	/**
	 * Get the public key for token validation.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 2.3.0 Introduced.
	 *
	 * @return string|null The public key
	 */
	private static function get_secret_public_key() {
		/**
		 * Allows you to set the public key for token validation.
		 *
		 * @since 2.3.0 Introduced.
		 */
		return apply_filters( 'cocart_jwt_auth_secret_public_key', defined( 'COCART_JWT_AUTH_SECRET_KEY' ) ? COCART_JWT_AUTH_SECRET_KEY : null );
	} // END get_secret_public_key()

	/**
	 * Generate refresh token.
	 *
	 * By default a random 64-byte string is generated.
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
	public static function generate_refresh_token( $user_id ) {
		/**
		 * Filter allows you to change how refresh tokens are generated.
		 *
		 * @since 2.0.0 Introduced.
		 */
		$refresh_token = apply_filters( 'cocart_jwt_auth_refresh_token_generation', bin2hex( random_bytes( 64 ) ) );
		update_user_meta( $user_id, 'cocart_jwt_refresh_token', $refresh_token );

		/**
		 * Filter allows you to customize refresh token lifetime based on roles or conditions.
		 *
		 * Default is 30 days.
		 *
		 * @since 2.0.0 Introduced.
		 */
		$expiration = time() + apply_filters( 'cocart_jwt_auth_refresh_token_expiration', DAY_IN_SECONDS * 30 );
		update_user_meta( $user_id, 'cocart_jwt_refresh_token_expiration', $expiration );

		return $refresh_token;
	} // END generate_refresh_token()

	/**
	 * Refresh the JWT token.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public static function refresh_token( \WP_REST_Request $request ) {
		$refresh_token = $request->get_param( 'refresh_token' );
		$user_id       = self::validate_refresh_token( $refresh_token );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$secret_key = self::get_secret_public_key();

		if ( ! $secret_key ) {
			return new \WP_Error( 'cocart_jwt_auth_bad_config', __( 'JWT configuration error.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		$user = get_user_by( 'id', $user_id );

		if ( empty( $user ) ) {
			// Error: The user doesn't exist.
			return new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		$token         = self::generate_token( $user->ID );
		$refresh_token = self::generate_refresh_token( $user->ID );

		/**
		 * Fires when a token is refreshed using a refresh token.
		 *
		 * @since 2.1.0 Introduced.
		 *
		 * @param string   $token Refreshed token.
		 * @param \WC_User $user  User object.
		 */
		do_action( 'cocart_jwt_auth_token_refreshed', $token, $user );

		return rest_ensure_response(
			array(
				'token'         => $token,
				'refresh_token' => $refresh_token,
			)
		);
	} // END refresh_token()

	/**
	 * Validate the refresh token.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @param string $refresh_token The refresh token.
	 *
	 * @return int|WP_Error User ID if valid, WP_Error otherwise.
	 */
	private static function validate_refresh_token( string $refresh_token ) {
		$user_query = new \WP_User_Query( array(
			'meta_key'   => 'cocart_jwt_refresh_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $refresh_token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'     => 1,
		) );

		$users = $user_query->get_results();

		if ( empty( $users ) ) {
			return new \WP_Error( 'cocart_authentication_error', __( 'Invalid refresh token.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		$user_id    = $users[0]->ID;
		$expiration = get_user_meta( $user_id, 'cocart_jwt_refresh_token_expiration', true );

		if ( time() > $expiration ) {
			return new \WP_Error( 'cocart_authentication_error', __( 'Refresh token expired.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		return $user_id;
	} // END validate_refresh_token()

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
	 * @param array  $extras Other extras filtered with `cocart_login_extras`.
	 * @param object $user   The current user.
	 *
	 * @return array $extras
	 */
	public static function send_tokens( $extras, $user ) {
		$token = get_user_meta( $user->ID, 'cocart_jwt_token', true );

		if ( empty( $token ) || self::is_token_expired( $token ) ) {
			$token = self::generate_token( $user->ID );
		}

		$secret_key = self::get_secret_public_key();

		if ( $secret_key ) {
			$extras['jwt_token']   = $token;
			$extras['jwt_refresh'] = self::generate_refresh_token( $user->ID );
		}

		return $extras;
	} // END send_tokens()

	/**
	 * Check if the token is expired.
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @param string $token The JWT token.
	 *
	 * @return bool True if expired, false otherwise.
	 */
	private static function is_token_expired( $token ) {
		try {
			$parts = self::decode_token( $token );

			if ( ! property_exists( $parts->payload, 'exp' ) || (int) $parts->payload->exp < time() ) {
				return true;
			}

			return false;
		} catch ( Exception $e ) {
			return true;
		}
	} // END is_token_expired()

	/**
	 * Destroys both the token and refresh token when the user changes.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 * @since 2.3.0 Added filter to allow control over token revocation.
	 *
	 * @hooked: wp_logout
	 *
	 * @param int $user_id User ID.
	 */
	public static function destroy_tokens( $user_id ) {
		// Get current action hook.
		$current_action = current_action();

		/**
		 * Filter allows you to control token revocation based on the action triggered.
		 *
		 * @since 2.3.0 Introduced.
		 *
		 * @param bool         True if allowed to destroy tokens.
		 * @param int $user_id User ID.
		 */
		$allow_action = apply_filters( 'cocart_jwt_auth_revoke_tokens_on_' . $current_action, true, $user_id );

		if ( ! $allow_action ) {
			return;
		}

		delete_user_meta( $user_id, 'cocart_jwt_token' );
		delete_user_meta( $user_id, 'cocart_jwt_refresh_token' );

		/**
		 * Fires when a token is deleted.
		 *
		 * @since 2.1.0 Introduced.
		 *
		 * @param int $user_id User ID.
		 */
		do_action( 'cocart_jwt_auth_token_deleted', $user_id );
	} // END destroy_tokens()

	/**
	 * Maybe destroys tokens when user changes password/email.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 * @since 2.3.0 Added filter to allow control over token revocation.
	 *
	 * @hooked: profile_update
	 *
	 * @param int    $user_id       User ID.
	 * @param object $old_user_data User data.
	 */
	public static function maybe_destroy_tokens( $user_id, $old_user_data ) {
		$new_user_data = get_userdata( $user_id );

		// Check if the email was changed.
		if ( $new_user_data->user_email !== $old_user_data->user_email ) {
			/**
			 * Filter allows you to control token revocation on email changes.
			 *
			 * @since 2.3.0 Introduced.
			 *
			 * @param bool         True if allowed to destroy tokens.
			 * @param int $user_id User ID.
			 */
			$allow_email_change = apply_filters( 'cocart_jwt_auth_revoke_tokens_on_email_change', true, $user_id );

			if ( ! $allow_email_change ) {
				return;
			}

			self::destroy_tokens( $user_id );
		}

		// Check if the password was changed.
		if ( $new_user_data->user_pass !== $old_user_data->user_pass ) {
			/**
			 * Filter allows you to control token revocation on password changes for security policies.
			 *
			 * @since 2.3.0 Introduced.
			 *
			 * @param bool         True if allowed to destroy tokens.
			 * @param int $user_id User ID.
			 */
			$allow_password_change = apply_filters( 'cocart_jwt_auth_revoke_tokens_on_password_change', true, $user_id );

			if ( ! $allow_password_change ) {
				return;
			}

			self::destroy_tokens( $user_id );
		}
	} // END maybe_destroy_tokens()

	/**
	 * Add rate limits for JWT refresh and validate token.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 * @since 2.2.0 Added rate limit for validating token.
	 *
	 * @param array $options Array of option values.
	 *
	 * @return array
	 */
	public static function jwt_rate_limits( $options ) {
		if ( preg_match( '/cocart\/jwt\/refresh-token/', $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$options = array(
				'enabled' => true,
				'limit'   => 10,
				'seconds' => MINUTE_IN_SECONDS,
			);
		}
		if ( preg_match( '/cocart\/jwt\/validate-token/', $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$options = array(
				'enabled' => true,
				'limit'   => 2,
				'seconds' => MINUTE_IN_SECONDS,
			);
		}

		return $options;
	} // END jwt_rate_limits()

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
	 * @since 1.0.0 Introduced.
	 *
	 * @deprecated 2.5.0 Replaced with `is_token_valid()`.
	 *
	 * @param string $token      Full token string.
	 * @param string $secret_key The secret key used to generate the signature.
	 *
	 * @return bool
	 */
	public static function validate_token( string $token, string $secret_key ) {
		cocart_deprecated_function( 'CoCart\JWTAuthentication\Plugin::validate_token', '2.5.0', 'CoCart\JWTAuthentication\Plugin::is_token_valid' );

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
		// Only validate prefix if enabled.
		if ( self::is_prefix_enabled() ) {
			if ( strpos( $token, self::get_token_prefix() ) !== 0 ) {
				\CoCart_Logger::log( esc_html__( 'Token does not have a matching prefix.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
				throw new \Exception( 'Invalid token' );
			}

			// Remove prefix safely.
			$token = substr( $token, strlen( self::get_token_prefix() ) );
		}

		// Validate token structure.
		$parts = explode( '.', $token );

		if ( count( $parts ) !== 3 ) {
			\CoCart_Logger::log( esc_html__( 'Token was incomplete.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			throw new \Exception( 'Invalid token format' );
		}

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
	 * @since 2.3.0 Added support for more advanced RSA-based configuration.
	 *
	 * @param string $header_encoded Header + Payload token substring.
	 * @param string $secret_key     The secret key used to generate the signature.
	 *
	 * @return false|string
	 */
	private static function generate_signature( string $header_encoded, string $secret_key ) {
		$algorithm = self::get_algorithm();

		if ( strpos( $algorithm, 'RS' ) === 0 ) {
			$signature = '';
			openssl_sign( $header_encoded, $signature, $secret_key, OPENSSL_ALGO_SHA256 );
			return $signature;
		}

		return hash_hmac(
			'sha256',
			$header_encoded,
			$secret_key,
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
			if ( ! empty( $_SERVER[ $ua_header ] ) ) {
				$user_agent_header = $ua_header;
			}
		}

		return $user_agent_header;
	} // END get_user_agent_header()

	/**
	 * Clean up expired tokens in batches.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 * @since 2.2.0 Improved to work in batches.
	 *
	 * @param int $batch_size Number of users to process per batch.
	 */
	public static function cleanup_expired_tokens( $batch_size = 100 ) {
		$offset = 0;

		do {
			$users = get_users( array(
				'meta_key'     => 'cocart_jwt_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
				'number'       => $batch_size,
				'offset'       => $offset,
			) );

			$users_count = count( $users );

			foreach ( $users as $user ) {
				$token = get_user_meta( $user->ID, 'cocart_jwt_token', true );

				if ( ! empty( $token ) && self::is_token_expired( $token ) ) {
					delete_user_meta( $user->ID, 'cocart_jwt_token' );
				}
			}

			$offset += $batch_size;
		} while ( $users_count === $batch_size );
	} // END cleanup_expired_tokens()

	/**
	 * Schedule cron job for cleaning up expired tokens.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 */
	public static function schedule_cron_job() {
		if ( ! wp_next_scheduled( 'cocart_jwt_cleanup_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'cocart_jwt_cleanup_cron' );
		}
	} // END schedule_cron_job()

	/**
	 * Clear scheduled cron job on plugin deactivation.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 */
	public static function clear_scheduled_cron_job() {
		$timestamp = wp_next_scheduled( 'cocart_jwt_cleanup_cron' );
		wp_unschedule_event( $timestamp, 'cocart_jwt_cleanup_cron' );
	} // END clear_scheduled_cron_job()

	/**
	 * Clean up expired tokens.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<number>]
	 * : Number of users to process per batch.
	 * ---
	 * default: 100
	 *
	 * [--force]
	 * : Force cleanup of all tokens.
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt cleanup --batch-size=50
	 * wp cocart jwt cleanup --force
	 *
	 * @when after_wp_load
	 * @access public
	 *
	 * @static
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function cli_clean_up_tokens( $args, $assoc_args ) {
		$batch_size  = isset( $assoc_args['batch-size'] ) ? intval( $assoc_args['batch-size'] ) : 100;
		$force       = isset( $assoc_args['force'] ) ? (bool) $assoc_args['force'] : false;
		$total_users = count( get_users( array(
			'meta_key'     => 'cocart_jwt_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
			'fields'       => 'ID',
		) ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Cleaning up tokens', $total_users );

		$offset = 0;

		do {
			$users = get_users( array(
				'meta_key'     => 'cocart_jwt_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
				'number'       => $batch_size,
				'offset'       => $offset,
			) );

			$users_count = count( $users );

			foreach ( $users as $user ) {
				$token = get_user_meta( $user->ID, 'cocart_jwt_token', true );

				if ( $force || ( ! empty( $token ) && self::is_token_expired( $token ) ) ) {
					delete_user_meta( $user->ID, 'cocart_jwt_token' );
				}

				$progress->tick();
			}

			$offset += $batch_size;
		} while ( $users_count === $batch_size );

		$progress->finish();
	} // END cli_clean_up_tokens()

	/**
	 * View details of a JWT token.
	 *
	 * ## OPTIONS
	 *
	 * <token>
	 * : The JWT token to view.
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt view <token>
	 *
	 * @when after_wp_load
	 * @access public
	 *
	 * @static
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function cli_view_token( $args, $assoc_args ) {
		list( $token ) = $args;

		if ( empty( $token ) ) {
			\WP_CLI::error( __( 'Token is required.', 'cocart-jwt-authentication' ) );
		}

		try {
			$decoded_token = self::decode_token( $token );
			\WP_CLI::log( '# Token details' );
			\WP_CLI::log( 'Header Encoded: ' . $decoded_token->header_encoded );

			$table = array();

			// Add header data.
			foreach ( $decoded_token->header as $key => $value ) {
				$table[] = array(
					'Key'   => $key,
					'Value' => $value,
				);
			}

			// Add payload data.
			foreach ( $decoded_token->payload as $key => $value ) {
				if ( 'data' === $key && isset( $value->user ) ) {
					// Handle user data separately.
					foreach ( get_object_vars( $value->user ) as $user_key => $user_value ) {
						$table[] = array(
							'Key'   => "user.$user_key",
							'Value' => $user_value,
						);
					}
				} else {
					$table[] = array(
						'Key'   => $key,
						'Value' => is_scalar( $value ) ? $value : wp_json_encode( $value ),
					);
				}
			}

			\WP_CLI\Utils\format_items( 'table', $table, array( 'Key', 'Value' ) );
		} catch ( Exception $e ) {
			\WP_CLI::error( __( 'Invalid token.', 'cocart-jwt-authentication' ) );
		}
	} // END cli_view_token()

	/**
	 * List all active JWT tokens.
	 *
	 * ## OPTIONS
	 *
	 * [--page=<number>]
	 * : Page number to display.
	 * ---
	 * default: 1
	 *
	 * [--per-page=<number>]
	 * : Number of tokens to display per page.
	 * ---
	 * default: 20
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt list --page=2 --per-page=10
	 *
	 * @when after_wp_load
	 * @access public
	 *
	 * @static
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function cli_list_tokens( $args, $assoc_args ) {
		$page     = isset( $assoc_args['page'] ) ? intval( $assoc_args['page'] ) : 1;
		$per_page = isset( $assoc_args['per-page'] ) ? intval( $assoc_args['per-page'] ) : 20;

		$users = get_users( array(
			'meta_key'     => 'cocart_jwt_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
			'number'       => $per_page,
			'offset'       => ( $page - 1 ) * $per_page,
		) );

		$tokens = array();

		foreach ( $users as $user ) {
			$token = get_user_meta( $user->ID, 'cocart_jwt_token', true );

			if ( ! empty( $token ) && ! self::is_token_expired( $token ) ) {
				$tokens[] = array(
					'user_id' => $user->ID,
					'token'   => $token,
				);
			}
		}

		if ( empty( $tokens ) ) {
			\WP_CLI::log( __( 'No tokens found.', 'cocart-jwt-authentication' ) );
		} else {
			\WP_CLI::log( '# Tokens' );
			\WP_CLI\Utils\format_items( 'table', $tokens, array( 'user_id', 'token' ) );
		}
	} // END cli_list_tokens()

	/**
	 * Generate a new JWT token for a user.
	 *
	 * ## OPTIONS
	 *
	 * --user=<id>
	 * : The user ID to generate the token for.
	 *
	 * [--user-agent=<user-agent>]
	 * : The User Agent to override the server User Agent.
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt create --user=123 --user-agent=<user-agent>
	 *
	 * @when after_wp_load
	 * @access public
	 *
	 * @static
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function cli_create_token( $args, $assoc_args ) {
		$user_ID    = isset( $assoc_args['user_id'] ) ? intval( $assoc_args['user_id'] ) : null;
		$user_agent = isset( $assoc_args['user-agent'] ) ? $assoc_args['user-agent'] : '';

		if ( empty( $user_ID ) ) {
			\WP_CLI::error( __( 'User ID is required.', 'cocart-jwt-authentication' ) );
		}

		$user = get_user_by( 'id', $user_ID );

		if ( ! $user ) {
			\WP_CLI::error( __( 'User not found.', 'cocart-jwt-authentication' ) );
		}

		$existing_token = get_user_meta( $user_ID, 'cocart_jwt_token', true );

		if ( ! empty( $existing_token ) ) {
			\WP_CLI::warning( __( 'Generating another token will kick the user out of session', 'cocart-jwt-authentication' ) );
			\WP_CLI::confirm( __( 'Are you sure you want to generate a new one?', 'cocart-jwt-authentication' ) );
		}

		// Set User agent.
		if ( ! empty( $user_agent ) ) {
			$_SERVER['HTTP_USER_AGENT'] = $user_agent;
		}

		$token = self::generate_token( $user_ID );

		\WP_CLI::success( __( 'Token generated.', 'cocart-jwt-authentication' ) );
		\WP_CLI::log( $token );
	} // END cli_create_token()

	/**
	 * Register WP-CLI command for cleaning up expired tokens with progress bar.
	 *
	 * @access public
	 *
	 * @static
	 */
	public static function register_cli_commands() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command(
				'cocart jwt cleanup',
				array( __CLASS__, 'cli_clean_up_tokens' ),
				array(
					'shortdesc' => __( 'Cleans up expired JWT tokens.', 'cocart-jwt-authentication' ),
					'synopsis'  => array(
						array(
							'type'        => 'assoc',
							'name'        => 'batch-size',
							'description' => __( 'Number of users to process per batch.', 'cocart-jwt-authentication' ),
							'optional'    => true,
							'default'     => 100,
						),
						array(
							'type'        => 'flag',
							'name'        => 'force',
							'description' => __( 'Force cleanup of all tokens.', 'cocart-jwt-authentication' ),
							'optional'    => true,
						),
					),
					'examples'  => array(
						'wp cocart jwt cleanup --batch-size=50',
						'wp cocart jwt cleanup --force',
					),
				)
			);

			\WP_CLI::add_command(
				'cocart jwt view',
				array( __CLASS__, 'cli_view_token' ),
				array(
					'shortdesc' => __( 'Displays details of a JWT token.', 'cocart-jwt-authentication' ),
					'synopsis'  => array(
						array(
							'type'        => 'positional',
							'name'        => 'token',
							'description' => __( 'The JWT token to view.', 'cocart-jwt-authentication' ),
							'optional'    => false,
						),
					),
					'examples'  => array(
						'wp cocart jwt view <token>',
					),
				)
			);

			\WP_CLI::add_command(
				'cocart jwt list',
				array( __CLASS__, 'cli_list_tokens' ),
				array(
					'shortdesc' => __( 'Lists all active JWT tokens.', 'cocart-jwt-authentication' ),
					'synopsis'  => array(
						array(
							'type'        => 'assoc',
							'name'        => 'page',
							'description' => __( 'Page number to display.', 'cocart-jwt-authentication' ),
							'optional'    => true,
							'default'     => 1,
						),
						array(
							'type'        => 'assoc',
							'name'        => 'per-page',
							'description' => __( 'Number of tokens to display per page.', 'cocart-jwt-authentication' ),
							'optional'    => true,
							'default'     => 20,
						),
					),
					'examples'  => array(
						'wp cocart jwt list --page=2 --per-page=10',
					),
				)
			);

			\WP_CLI::add_command(
				'cocart jwt create',
				array( __CLASS__, 'cli_create_token' ),
				array(
					'shortdesc' => __( 'Generates a new JWT token for a user.', 'cocart-jwt-authentication' ),
					'synopsis'  => array(
						array(
							'type'        => 'assoc',
							'name'        => 'user_id',
							'description' => __( 'The user ID to generate the token for.', 'cocart-jwt-authentication' ),
							'optional'    => false,
						),
						array(
							'type'        => 'assoc',
							'name'        => 'user-agent',
							'description' => __( 'The User Agent to override the server User Agent.', 'cocart-jwt-authentication' ),
							'optional'    => true,
						),
					),
					'examples'  => array(
						'wp cocart jwt create --user_id=123',
					),
				)
			);
		}
	} // END register_cli_commands()

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

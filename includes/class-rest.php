<?php
/**
 * CoCart JWT Authentication - REST API.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API class.
 *
 * @class CoCart\JWTAuthentication\REST
 */
class REST extends Tokens {

	/**
	 * Request made flag to prevent multiple authentication attempts.
	 *
	 * @access private
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @var bool
	 */
	private $request_made = false;

	/**
	 * Constructor.
	 *
	 * @access public
	 *
	 * @static
	 */
	public function __construct() {
		// Register REST API endpoint to refresh tokens.
		add_action( 'rest_api_init', function () {
			register_rest_route( 'cocart/jwt', '/refresh-token', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'refresh_token' ),
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
		add_filter( 'cocart_authenticate', array( $this, 'perform_jwt_authentication' ), 0, 3 );

		add_action( 'cocart_jwt_auth_authenticated', array( $this, 'update_jwt_token_access' ), 10, 2 );

		// Send tokens to login response.
		add_filter( 'cocart_login_extras', array( $this, 'send_tokens' ), 0, 2 );

		// Add rate limits for JWT refresh token.
		add_filter( 'cocart_api_rate_limit_options', array( $this, 'jwt_rate_limits' ), 0 );
	} // END init()

	/**
	 * Check if the token is valid.
	 *
	 * @access private
	 *
	 * @since 2.5.0 Introduced.
	 *
	 * @param string $token The JWT token to validate.
	 * @param object $auth  The Authentication class.
	 *
	 * @return \WP_User|false|WP_Error The user object if valid, false otherwise.
	 */
	private function is_token_valid( $token, $auth ) {
		$secret_key = $this->get_secret_public_key();

		// First thing, check the secret key, if not exist return error.
		if ( ! $secret_key ) {
			// Error: JWT is not configured properly.
			\CoCart_Logger::log( esc_html__( 'JWT is not configured properly.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_jwt_auth_bad_config', __( 'JWT configuration error.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		// Decode token provided.
		$decoded_token = $this->decode_token( $token );

		// Check if header declares a supported JWT by this class.
		if (
			! is_object( $decoded_token->header ) ||
			! property_exists( $decoded_token->header, 'typ' ) ||
			! property_exists( $decoded_token->header, 'alg' ) ||
			'JWT' !== $decoded_token->header->typ ||
			$this->get_algorithm() !== $decoded_token->header->alg
		) {
			\CoCart_Logger::log( esc_html__( 'Token is malformed.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		// Verify signature.
		$encoded_regenerated_signature = $this->to_base_64_url(
			$this->generate_signature( $decoded_token->header_encoded . '.' . $decoded_token->payload_encoded, $secret_key )
		);

		if ( ! hash_equals( $encoded_regenerated_signature, $decoded_token->signature_encoded ) ) {
			\CoCart_Logger::log( esc_html__( 'Token signature verification failed.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		// Check if token is expired.
		if ( ! property_exists( $decoded_token->payload, 'exp' ) || time() > (int) $decoded_token->payload->exp ) {
			\CoCart_Logger::log( esc_html__( 'Token has expired.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		// Check if the token matches server.
		if ( $this->get_iss() !== $decoded_token->payload->iss ) {
			// Error: The token issuer does not match with this server.
			\CoCart_Logger::log( esc_html__( 'Token is invalid: Does not match with this server.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		// Check the user id existence in the token.
		if ( ! isset( $decoded_token->payload->data->user->id ) ) {
			// Error: The token does not identify a user.
			\CoCart_Logger::log( esc_html__( 'Token is malformed: Missing user to identify.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		// So far so good, check if the given user id exists in database.
		$user = get_user_by( 'id', $decoded_token->payload->data->user->id );

		if ( ! $user ) {
			// Error: The user doesn't exist.
			\CoCart_Logger::log( esc_html__( 'User associated with token no longer exists.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		// Validate IP or Device.
		if ( \CoCart_Authentication::get_ip_address() !== $decoded_token->payload->data->user->ip || ( isset( $_SERVER[ $this->get_user_agent_header() ] ) && sanitize_text_field( wp_unslash( $_SERVER[ $this->get_user_agent_header() ] ) ) !== $decoded_token->payload->data->user->device ) ) {
			// Error: IP or Device mismatch.
			\CoCart_Logger::log( esc_html__( 'Unable to validate IP or User-Agent.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		$user_tokens = get_user_meta( $user->ID, '_cocart_jwt_tokens', true );

		// If user tokens do no exist then fail.
		if ( ! is_array( $user_tokens ) || empty( $user_tokens ) ) {
			// Error: No tokens in session.
			\CoCart_Logger::log( esc_html__( 'User has no tokens in session.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
			return false;
		}

		// Check if the token PAT exists.
		$pat_array  = get_user_meta( $user->ID, '_cocart_jwt_token_pat' );
		$pat_found  = false;
		$target_pat = property_exists( $decoded_token->payload->data->user, 'pat' ) ? $decoded_token->payload->data->user->pat : '';

		foreach ( $pat_array as $pat_entry ) {
			if ( array_key_exists( $target_pat, $pat_entry ) ) {
				$pat_found = true;
				break;
			}
		}

		// If no PAT found or PAT is empty then fail.
		if ( empty( $target_pat ) || ! $pat_found ) {
			// Error: Token not found in session.
			\CoCart_Logger::log( esc_html__( 'Token not found in session.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			$auth->set_error( new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) ) );
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
	 * @since 1.0.0 Introduced.
	 *
	 * @param int    $user_id The user ID if authentication was successful.
	 * @param bool   $ssl     Determines if the site is secure.
	 * @param object $auth    The Authentication class.
	 *
	 * @return int $user_id The user ID returned if authentication was successful.
	 */
	public function perform_jwt_authentication( int $user_id, bool $ssl, $auth ) {
		// Don't try to authenticate again if we already did.
		if ( $this->request_made ) {
			return $user_id;
		}

		$this->request_made = true;

		$auth->set_method( 'jwt_auth' );

		$auth_header = \CoCart_Authentication::get_auth_header();

		if ( $this->is_bearer_auth( $auth_header ) ) {
			// Extract Bearer token authentication.
			$token = $this->extract_bearer_token( $auth_header );

			if ( ! is_null( $token ) ) {
				$user = $this->is_token_valid( $token, $auth );

				if ( ! empty( $user ) ) {
					/**
					 * Fires when a user is authenticated using a JWT token.
					 *
					 * @since 3.0.0 Introduced.
					 *
					 * @param string   $token The JWT token.
					 * @param \WC_User $user  The authenticated user object.
					 */
					do_action( 'cocart_jwt_auth_authenticated', $token, $user );

					// User is authenticated.
					return $user->ID;
				}
			}
		}

		return $user_id;
	} // END perform_jwt_authentication()

	/**
	 * Refresh the JWT token.
	 *
	 * @access public
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function refresh_token( \WP_REST_Request $request ) {
		$secret_key = $this->get_secret_public_key();

		if ( ! $secret_key ) {
			return new \WP_Error( 'cocart_jwt_auth_bad_config', __( 'JWT configuration error.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) );
		}

		$refresh_token = $request->get_param( 'refresh_token' );
		$user_id       = $this->validate_refresh_token( $refresh_token );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$user = get_user_by( 'id', $user_id );

		if ( empty( $user ) ) {
			// Error: The user doesn't exist.
			\CoCart_Logger::log( esc_html__( 'User associated with refresh token no longer exists.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			return new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) );
		}

		// Get the PAT ID associated with this refresh token.
		$pat_id = $this->get_pat_by_refresh_token( $user->ID, $refresh_token );

		// Destroy the old PAT and associated tokens.
		$this->destroy_pat_session( $user->ID, $pat_id );

		// Generate new tokens.
		$token         = $this->generate_token( $user->ID );
		$refresh_token = $this->generate_refresh_token( $user->ID );

		// Store refresh token reference in new PAT data.
		$this->store_refresh_token_in_pat( $user->ID, $token, $refresh_token );

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
	 * Update the token when it was last used to authenticate with.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param string   $token The JWT token.
	 * @param \WP_User $user  The authenticated user object.
	 *
	 * @return void
	 */
	public function update_jwt_token_access( $token, $user ) {
		\CoCart_Logger::log( sprintf(
			/* translators: 1: User login, 2: User ID. */
			esc_html__( 'User %1$s (ID: %2$d) authenticated at %3$s.', 'cocart-jwt-authentication' ),
			$user->user_login,
			$user->ID,
			date_i18n( 'Y-m-d H:i:s' )
		), 'info', 'cocart-jwt-authentication' );

		// Time is time.
		$time = time();

		// Update last login.
		update_user_meta( $user->ID, 'last_login', $time );

		// Check if the token PAT exists.
		$pat_id   = $this->get_pat_from_token( $token );
		$pat_data = get_user_meta( $user->ID, '_cocart_jwt_token_pat' );

		if ( is_null( $pat_id ) || ! array_key_exists( $pat_id, $pat_data ) ) {
			return;
		}

		$updated_pat_data = $pat_data;

		$updated_pat_data[ $pat_id ]['last-used'] = $time; // Update last used time.

		update_user_meta( $user->ID, '_cocart_jwt_token_pat', $updated_pat_data, $pat_data ); // Update the PAT data.
	} // END update_jwt_token_access()

	/**
	 * Sends the generated token to the login response.
	 *
	 * @access public
	 *
	 * @param array  $extras Other extras filtered with `cocart_login_extras`.
	 * @param object $user   The current user.
	 *
	 * @return array $extras
	 */
	public function send_tokens( $extras, $user ) {
		// Check if request already has a valid Bearer token - if so, don't generate new tokens.
		$auth_header = \CoCart_Authentication::get_auth_header();

		$token  = null;
		$pat_id = null;

		// Request is using JWT authentication, so don't generate new tokens.
		if ( $this->is_bearer_auth( $auth_header ) ) {
			$token  = $this->extract_bearer_token( $auth_header );
			$pat_id = $this->get_pat_from_token( $token );
		}

		$token = is_null( $token ) ? $this->generate_token( $user->ID ) : $token;

		if ( ! is_wp_error( $token ) ) {
			// Find and return existing refresh token for found PAT or generate a new one.
			$refresh_token = is_null( $pat_id ) ? $this->generate_refresh_token( $user->ID ) : $this->get_refresh_token_by_pat( $user->ID, $pat_id );

			// Store refresh token reference in PAT data.
			$this->store_refresh_token_in_pat( $user->ID, $token, $refresh_token );

			$extras['jwt_token']   = $token;
			$extras['jwt_refresh'] = $refresh_token;
		}

		return $extras;
	} // END send_tokens()

	/**
	 * Add rate limits for JWT refresh and validate token.
	 *
	 * @access public
	 *
	 * @since 2.0.0 Introduced.
	 * @since 2.2.0 Added rate limit for validating token.
	 *
	 * @param array $options Array of option values.
	 *
	 * @return array
	 */
	public function jwt_rate_limits( $options ) {
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
} // END class

return new REST();

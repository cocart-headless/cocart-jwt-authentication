<?php
/**
 * CoCart JWT Authentication - Google Authentication REST API.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @since   x.x.x Introduced.
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Authentication REST API class.
 *
 * @class CoCart\JWTAuthentication\GoogleAuthREST
 */
class GoogleAuthREST extends REST {

	/**
	 * Constructor.
	 *
	 * @access public
	 *
	 * @static
	 */
	public function __construct() {
		parent::__construct();

		// Register Google OAuth endpoints.
		add_action( 'rest_api_init', function () {
			register_rest_route( 'cocart/auth', '/google-auth', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'authenticate_with_google' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id_token' => array(
						'required'          => true,
						'type'              => 'string',
						'description'       => __( 'Google ID token from OAuth flow', 'cocart-jwt-authentication' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			) );
		} );

		// Register Google user info endpoint.
		add_action( 'rest_api_init', function () {
			register_rest_route( 'cocart/auth', '/google-user-info', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_google_user_info' ),
				'permission_callback' => function () {
					return get_current_user_id() > 0;
				},
			) );
		} );

		// Add rate limits for Google authentication.
		add_filter( 'cocart_api_rate_limit_options', array( $this, 'google_auth_rate_limits' ), 10 );
	}

	/**
	 * Authenticate user with Google ID token.
	 *
	 * @access public
	 *
	 * @since x.x.x Introduced.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function authenticate_with_google( \WP_REST_Request $request ) {
		$id_token = $request->get_param( 'id_token' );

		if ( empty( $id_token ) ) {
			return new \WP_Error( 'cocart_google_auth_missing_token', __( 'Google ID token is required.', 'cocart-jwt-authentication' ), array( 'status' => 400 ) );
		}

		// Verify the Google ID token.
		$google_user_data = $this->verify_google_id_token( $id_token );

		if ( is_wp_error( $google_user_data ) ) {
			return $google_user_data;
		}

		// Find or create user based on Google data.
		$user = $this->find_or_create_google_user( $google_user_data );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Generate JWT tokens for the authenticated user.
		$token = $this->generate_token( $user->ID );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		// Generate refresh token.
		$refresh_token = $this->generate_refresh_token( $user->ID );

		// Store refresh token reference in PAT data.
		$this->store_refresh_token_in_pat( $user->ID, $token, $refresh_token );

		/**
		 * Fires when a user is authenticated using Google OAuth.
		 *
		 * @since x.x.x Introduced.
		 *
		 * @param string   $token The JWT token.
		 * @param \WP_User $user  The authenticated user object.
		 * @param array    $google_user_data Google user data.
		 */
		do_action( 'cocart_jwt_auth_google_authenticated', $token, $user, $google_user_data );

		\CoCart_Logger::log( sprintf(
			/* translators: 1: User login, 2: User ID, 3: Google ID. */
			esc_html__( 'User %1$s (ID: %2$d) authenticated via Google (Google ID: %3$s) at %4$s.', 'cocart-jwt-authentication' ),
			$user->user_login,
			$user->ID,
			$google_user_data['sub'],
			date_i18n( 'Y-m-d H:i:s' )
		), 'info', 'cocart-jwt-authentication' );

		return rest_ensure_response(
			array(
				'token'         => $token,
				'refresh_token' => $refresh_token,
				'user'          => array(
					'id'           => $user->ID,
					'username'     => $user->user_login,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'google_id'    => $google_user_data['sub'],
				),
				'message'       => __( 'Successfully authenticated with Google.', 'cocart-jwt-authentication' ),
			)
		);
	}

	/**
	 * Get Google user information for authenticated users.
	 *
	 * @access public
	 *
	 * @since x.x.x Introduced.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function get_google_user_info( \WP_REST_Request $request ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) );
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'cocart_user_not_found', __( 'User not found.', 'cocart-jwt-authentication' ), array( 'status' => 404 ) );
		}

		$google_id    = get_user_meta( $user_id, 'google_id', true );
		$google_email = get_user_meta( $user_id, 'google_email', true );

		return rest_ensure_response(
			array(
				'user_id'      => $user->ID,
				'username'     => $user->user_login,
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'google_id'    => $google_id,
				'google_email' => $google_email,
				'linked'       => ! empty( $google_id ),
			)
		);
	}

	/**
	 * Verify Google ID token.
	 *
	 * @access private
	 *
	 * @since x.x.x Introduced.
	 *
	 * @param string $id_token Google ID token.
	 *
	 * @return array|WP_Error Google user data or error.
	 */
	private function verify_google_id_token( $id_token ) {
		$google_client_id = $this->get_google_client_id();

		if ( empty( $google_client_id ) ) {
			return new \WP_Error( 'cocart_google_auth_not_configured', __( 'Google authentication is not properly configured.', 'cocart-jwt-authentication' ), array( 'status' => 500 ) );
		}

		// Verify the token with Google's tokeninfo endpoint.
		$response = wp_remote_get( 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode( $id_token ) );

		if ( is_wp_error( $response ) ) {
			\CoCart_Logger::log( 'Google token verification failed: ' . $response->get_error_message(), 'error', 'cocart-jwt-authentication' );
			return new \WP_Error( 'cocart_google_auth_verification_failed', __( 'Google token verification failed.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 || empty( $data ) ) {
			\CoCart_Logger::log( 'Invalid Google ID token response: ' . $body, 'error', 'cocart-jwt-authentication' );
			return new \WP_Error( 'cocart_google_auth_invalid_token', __( 'Invalid Google ID token.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		// Verify the token is for our application.
		if ( $data['aud'] !== $google_client_id ) {
			\CoCart_Logger::log( 'Google ID token audience mismatch', 'error', 'cocart-jwt-authentication' );
			return new \WP_Error( 'cocart_google_auth_audience_mismatch', __( 'Google token audience mismatch.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		// Check if token is expired.
		if ( isset( $data['exp'] ) && time() > intval( $data['exp'] ) ) {
			return new \WP_Error( 'cocart_google_auth_token_expired', __( 'Google ID token has expired.', 'cocart-jwt-authentication' ), array( 'status' => 401 ) );
		}

		return $data;
	}

	/**
	 * Find or create user based on Google data.
	 *
	 * @access private
	 *
	 * @since x.x.x Introduced.
	 *
	 * @param array $google_user_data Google user data.
	 *
	 * @return \WP_User|WP_Error User object or error.
	 */
	private function find_or_create_google_user( $google_user_data ) {
		$google_id = $google_user_data['sub'];
		$email     = $google_user_data['email'];

		// First, try to find user by Google ID.
		$users = get_users( array(
			'meta_key'   => 'google_id',
			'meta_value' => $google_id,
			'number'     => 1,
		) );

		if ( ! empty( $users ) ) {
			return $users[0];
		}

		// If not found by Google ID, try to find by email.
		$user = get_user_by( 'email', $email );

		if ( $user ) {
			// Link existing user to Google account.
			update_user_meta( $user->ID, 'google_id', $google_id );
			update_user_meta( $user->ID, 'google_email', $email );

			/**
			 * Fires when an existing user is linked to a Google account.
			 *
			 * @since x.x.x Introduced.
			 *
			 * @param \WP_User $user            The user object.
			 * @param array    $google_user_data Google user data.
			 */
			do_action( 'cocart_jwt_auth_google_user_linked', $user, $google_user_data );

			return $user;
		}

		// Check if user creation is allowed.
		if ( ! $this->is_google_user_creation_allowed() ) {
			return new \WP_Error( 'cocart_google_auth_registration_disabled', __( 'User registration via Google is disabled.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) );
		}

		// Create new user.
		$username = $this->generate_username_from_google_data( $google_user_data );

		$user_data = array(
			'user_login'   => $username,
			'user_email'   => $email,
			'display_name' => $google_user_data['name'] ?? $email,
			'first_name'   => $google_user_data['given_name'] ?? '',
			'last_name'    => $google_user_data['family_name'] ?? '',
			'user_pass'    => wp_generate_password( 32, true, true ),
		);

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			\CoCart_Logger::log( 'Failed to create Google user: ' . $user_id->get_error_message(), 'error', 'cocart-jwt-authentication' );
			return new \WP_Error( 'cocart_google_auth_user_creation_failed', __( 'Failed to create user account.', 'cocart-jwt-authentication' ), array( 'status' => 500 ) );
		}

		// Store Google data.
		update_user_meta( $user_id, 'google_id', $google_id );
		update_user_meta( $user_id, 'google_email', $email );

		$user = get_user_by( 'id', $user_id );

		/**
		 * Fires when a new user is created via Google authentication.
		 *
		 * @since x.x.x Introduced.
		 *
		 * @param \WP_User $user            The created user object.
		 * @param array    $google_user_data Google user data.
		 */
		do_action( 'cocart_jwt_auth_google_user_created', $user, $google_user_data );

		return $user;
	}

	/**
	 * Generate username from Google data.
	 *
	 * @access private
	 *
	 * @since x.x.x Introduced.
	 *
	 * @param array $google_user_data Google user data.
	 *
	 * @return string Generated username.
	 */
	private function generate_username_from_google_data( $google_user_data ) {
		$email         = $google_user_data['email'];
		$base_username = sanitize_user( substr( $email, 0, strpos( $email, '@' ) ) );

		// Ensure username is unique.
		$username = $base_username;
		$counter  = 1;

		while ( username_exists( $username ) ) {
			$username = $base_username . $counter;
			++$counter;
		}

		/**
		 * Filter the generated username for Google users.
		 *
		 * @since x.x.x Introduced.
		 *
		 * @param string $username        Generated username.
		 * @param array  $google_user_data Google user data.
		 */
		return apply_filters( 'cocart_jwt_auth_google_generated_username', $username, $google_user_data );
	}

	/**
	 * Get Google Client ID from settings or constants.
	 *
	 * @access private
	 *
	 * @since x.x.x Introduced.
	 *
	 * @return string|null Google Client ID.
	 */
	private function get_google_client_id() {
		/**
		 * Filter allows you to set the Google Client ID.
		 *
		 * @since x.x.x Introduced.
		 *
		 * @param string $client_id Google Client ID.
		 */
		return apply_filters( 'cocart_jwt_auth_google_client_id', defined( 'COCART_GOOGLE_CLIENT_ID' ) ? COCART_GOOGLE_CLIENT_ID : null );
	}

	/**
	 * Check if Google user creation is allowed.
	 *
	 * @access private
	 *
	 * @since x.x.x Introduced.
	 *
	 * @return bool True if allowed, false otherwise.
	 */
	private function is_google_user_creation_allowed() {
		/**
		 * Filter allows you to control whether new users can be created via Google authentication.
		 *
		 * @since x.x.x Introduced.
		 *
		 * @param bool $allowed Default true.
		 */
		return apply_filters( 'cocart_jwt_auth_google_user_creation_allowed', true );
	}

	/**
	 * Add rate limits for Google authentication endpoints.
	 *
	 * @access public
	 *
	 * @since x.x.x Introduced.
	 *
	 * @param array $options Array of option values.
	 *
	 * @return array
	 */
	public function google_auth_rate_limits( $options ) {
		if ( preg_match( '/cocart\/jwt\/google-auth/', $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$options = array(
				'enabled' => true,
				'limit'   => 5,
				'seconds' => MINUTE_IN_SECONDS,
			);
		}
		if ( preg_match( '/cocart\/jwt\/google-user-info/', $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$options = array(
				'enabled' => true,
				'limit'   => 10,
				'seconds' => MINUTE_IN_SECONDS,
			);
		}

		return $options;
	}
}

return new GoogleAuthREST();

<?php
/**
 * CoCart JWT Authentication - Tokens.
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
 * Tokens class.
 *
 * @class CoCart\JWTAuthentication\Tokens
 */
abstract class Tokens {

	/**
	 * JWT algorithm to generate signature.
	 *
	 * Default is HS256
	 *
	 * @var string
	 */
	private $algorithm = 'HS256';

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
	protected array $user_agent_headers = array(
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
	private array $supported_algorithms = array(
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
	 * Check if the authorization header contains a Bearer authentication.
	 *
	 * @access private
	 *
	 * @since 2.5.1 Introduced.
	 *
	 * @param string $auth Authorization header value.
	 *
	 * @return bool
	 */
	protected function is_bearer_auth( string $auth ): bool {
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
	protected function extract_bearer_token( string $auth ): ?string {
		if ( preg_match( self::BEARER_TOKEN_PATTERN, $auth, $matches ) ) {
			return $matches[1];
		}

		return null;
	} // END extract_bearer_token()

	/**
	 * Get the token issuer (iss claim).
	 *
	 * @access public
	 *
	 * @return string The token issuer (iss).
	 */
	public function get_iss() {
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
	 * @return string The username if found.
	 */
	protected function lookup_username() {
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
	 * @param int|string $user The user ID or username.
	 *
	 * @return \WP_User|false The user object if valid, false otherwise.
	 */
	protected function is_user_valid( $user ) {
		if ( empty( $user ) ) {
			return new \WP_Error(
				'cocart_authentication_error',
				__( 'User unknown!', 'cocart-jwt-authentication' ),
				array( 'status' => 404 )
			);
		}

		if ( is_numeric( $user ) ) {
			$get_user_by = 'id';
		} elseif ( is_email( $user ) ) {
			$get_user_by = 'email';
		} else {
			$get_user_by = 'login';
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
	 * @param int|string $user The user to generate token for.
	 *
	 * @return string Generated JWT token.
	 */
	public function generate_token( int|string $user = '' ) {
		$secret_key = $this->get_secret_private_key();

		if ( ! $secret_key ) {
			return new \WP_Error( 'cocart_jwt_auth_bad_config', __( 'JWT configuration error.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) );
		}

		// See if we can lookup the username if no user provided.
		if ( empty( $user ) ) {
			$user = $this->lookup_username();
		}

		// Check if user is valid.
		$user = $this->is_user_valid( $user );

		if ( ! $user ) {
			return new \WP_Error( 'cocart_authentication_error', __( 'Authentication failed.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) );
		}

		$user_tokens = get_user_meta( $user->ID, '_cocart_jwt_tokens', true );

		// If user tokens are not an array, initialize it.
		if ( empty( $user_tokens ) ) {
			$user_tokens = array();
		}

		$count_tokens = count( $user_tokens );

		/**
		 * Filter allows you to change the maximum number of tokens a user can have.
		 *
		 * Default is 5 tokens.
		 *
		 * If the user has reached the maximum number of tokens, the oldest token will be removed.
		 * This is to prevent the user from having too many tokens stored.
		 * It does not affect active tokens, only the stored tokens.
		 * You can set this to -1 for unlimited tokens, but it is not recommended.
		 *
		 * Note: This does not affect refresh tokens which are handled separately.
		 *
		 * @since 3.0.0 Introduced.
		 */
		if ( $count_tokens >= apply_filters( 'cocart_jwt_auth_max_user_tokens', 5, $user ) ) {
			// Remove the oldest token.
			array_shift( $user_tokens );
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
		$header = $this->to_base_64_url( $this->generate_header() );
		$pat    = $this->generate_token_id( $user_tokens );

		// Prepare the payload.
		$payload = array(
			'iss'  => $this->get_iss(),
			'iat'  => $issued_at,
			'nbf'  => $not_before,
			'exp'  => $expire,
			'data' => array(
				'user' => array(
					'id'       => $user->ID,
					'username' => $user->user_login,
					'ip'       => \CoCart_Authentication::get_ip_address(),
					'device'   => ! empty( $_SERVER[ $this->get_user_agent_header() ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $this->get_user_agent_header() ] ) ) : '',
					'pat'      => $pat,
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
		$payload = $this->to_base_64_url( wp_json_encode( $payload ) );

		$algorithm = $this->get_algorithm();

		if ( false === $algorithm ) {
			// See https://datatracker.ietf.org/doc/html/rfc7518#section-3
			return new \WP_Error(
				'cocart_authentication_error',
				__( 'Algorithm not supported', 'cocart-jwt-authentication' ),
				array( 'status' => 403 )
			);
		}

		// The token is signed, now generate the signature to the response.
		$signature = $this->to_base_64_url( $this->generate_signature( $header . '.' . $payload, $secret_key ) );

		$token = $this->get_token_prefix() . $header . '.' . $payload . '.' . $signature;

		// Append the token to the users tokens.
		$user_tokens[ $pat ] = $token;

		// Save user token.
		$pat_data = array(
			$pat => array(
				'token'     => $token,
				'created'   => $issued_at,
				'last-used' => '',
			),
		);
		add_user_meta( $user->ID, '_cocart_jwt_token_pat', $pat_data ); // Store the token PAT individually for reference.
		update_user_meta( $user->ID, '_cocart_jwt_tokens', $user_tokens ); // Store all tokens to validate with.

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
	 * Generate a unique token ID.
	 *
	 * It generates a random 24-character hexadecimal string.
	 *
	 * @access protected
	 *
	 * @param array $user_tokens Existing user tokens to ensure uniqueness.
	 *
	 * @return string The generated token ID.
	 */
	protected function generate_token_id( $user_tokens ) {
		$token_id = bin2hex( random_bytes( 12 ) );

		// Ensure unique token ID.
		if ( in_array( $token_id, array_keys( $user_tokens ), true ) ) {
			$token_id = $this->generate_token_id();
		}

		return 'pat_' . $token_id;
	} // END generate_token_id()

	/**
	 * Get the token prefix.
	 *
	 * This prefix is added to the token when it is generated.
	 * It is not required to use a prefix, but it can help to distinguish
	 * tokens from different sources or implementations.
	 *
	 * @access private
	 *
	 * @since 2.5.0 Introduced.
	 *
	 * @return string The token prefix.
	 */
	private function get_token_prefix() {
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
	 * @since 2.5.0 Introduced.
	 *
	 * @return bool True if the prefix is enabled, false otherwise.
	 */
	private function is_prefix_enabled() {
		return ! empty( $this->get_token_prefix() );
	} // END is_prefix_enabled()

	/**
	 * Get the algorithm used to sign the token and validate that
	 * the algorithm is in the supported list.
	 *
	 * @access private
	 *
	 * @return false|mixed|null
	 */
	protected function get_algorithm() {
		/**
		 * Filter the algorithm to one of the supported.
		 *
		 * @since 2.0.0 Introduced.
		 */
		$algorithm = apply_filters( 'cocart_jwt_auth_algorithm', $this->algorithm );

		if ( ! in_array( $algorithm, $this->supported_algorithms ) ) {
			return false;
		}

		return $algorithm;
	} // END get_algorithm()

	/**
	 * Get the secret private key for token signing.
	 *
	 * @access private
	 *
	 * @since 2.3.0 Introduced.
	 *
	 * @return string|null The public key
	 */
	protected function get_secret_private_key() {
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
	 * @since 2.3.0 Introduced.
	 *
	 * @return string|null The public key
	 */
	protected function get_secret_public_key() {
		/**
		 * Allows you to set the public key for token validation.
		 *
		 * @since 2.3.0 Introduced.
		 */
		return apply_filters( 'cocart_jwt_auth_secret_public_key', defined( 'COCART_JWT_AUTH_SECRET_KEY' ) ? COCART_JWT_AUTH_SECRET_KEY : null );
	} // END get_secret_public_key()

	/**
	 * Get the PAT ID from a token.
	 *
	 * @access protected
	 *
	 * @param string $token The JWT token.
	 *
	 * @return string|null The PAT ID if found, null otherwise.
	 */
	protected function get_pat_from_token( $token ) {
		try {
			$decoded_token = $this->decode_token( $token );

			if ( ! is_wp_error( $decoded_token ) && property_exists( $decoded_token->payload->data->user, 'pat' ) ) {
				return $decoded_token->payload->data->user->pat;
			}

			return null;
		} catch ( Exception $e ) {
			return null;
		}
	} // END get_pat_from_token()

	/**
	 * Find refresh token by PAT ID.
	 *
	 * @access protected
	 *
	 * @param int    $user_id The user ID.
	 * @param string $pat_id  The PAT ID.
	 *
	 * @return string|null The refresh token if found, null otherwise.
	 */
	protected function get_refresh_token_by_pat( $user_id, $pat_id ) {
		$pat_data = get_user_meta( $user_id, '_cocart_jwt_token_pat' );

		foreach ( $pat_data as $pat_entry ) {
			if ( array_key_exists( $pat_id, $pat_entry ) && isset( $pat_entry[ $pat_id ]['refresh_token'] ) ) {
				return $pat_entry[ $pat_id ]['refresh_token'];
			}
		}

		return null;
	} // END get_refresh_token_by_pat()

	/**
	 * Find PAT ID by refresh token.
	 *
	 * @access protected
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param int    $user_id       The user ID.
	 * @param string $refresh_token The refresh token.
	 *
	 * @return string|null The PAT ID if found, null otherwise.
	 */
	protected function get_pat_by_refresh_token( $user_id, $refresh_token ) {
		$pat_data = get_user_meta( $user_id, '_cocart_jwt_token_pat' );

		foreach ( $pat_data as $pat_entry ) {
			foreach ( $pat_entry as $pat_id => $pat_info ) {
				if ( isset( $pat_info['refresh_token'] ) && $pat_info['refresh_token'] === $refresh_token ) {
					return $pat_id;
				}
			}
		}

		return null;
	} // END get_pat_by_refresh_token()

	/**
	 * Store refresh token reference in PAT data.
	 *
	 * @access protected
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param int    $user_id       The user ID.
	 * @param string $token         The JWT token to extract PAT ID from.
	 * @param string $refresh_token The refresh token to store.
	 *
	 * @return bool True if stored successfully, false otherwise.
	 */
	protected function store_refresh_token_in_pat( $user_id, $token, $refresh_token ) {
		$pat_id = $this->get_pat_from_token( $token );

		if ( is_null( $pat_id ) ) {
			return false;
		}

		$pat_data = get_user_meta( $user_id, '_cocart_jwt_token_pat' );

		foreach ( $pat_data as $key => $pat_entry ) {
			if ( array_key_exists( $pat_id, $pat_entry ) ) {
				$pat_entry[ $pat_id ]['refresh_token'] = $refresh_token;
				update_user_meta( $user_id, '_cocart_jwt_token_pat', $pat_entry, $pat_data[ $key ] );
				return true;
			}
		}

		return false;
	} // END store_refresh_token_in_pat()

	/**
	 * Destroys a specified PAT session and all associated data.
	 *
	 * @access protected
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $pat_id  The PAT ID to destroy.
	 *
	 * @return bool True if data was destroyed, false otherwise.
	 */
	protected function destroy_pat_session( $user_id, $pat_id ) {
		if ( empty( $pat_id ) ) {
			return false;
		}

		$destroyed = false;

		// Remove from main tokens collection.
		$user_tokens = get_user_meta( $user_id, '_cocart_jwt_tokens', true );

		if ( is_array( $user_tokens ) && isset( $user_tokens[ $pat_id ] ) ) {
			unset( $user_tokens[ $pat_id ] );
			update_user_meta( $user_id, '_cocart_jwt_tokens', $user_tokens );
			$destroyed = true;
		}

		// Remove PAT entry and get refresh token for cleanup.
		$pat_data      = get_user_meta( $user_id, '_cocart_jwt_token_pat' );
		$refresh_token = null;

		foreach ( $pat_data as $pat_entry ) {
			// Each $pat_entry is like: ['pat_123' => ['token' => '...', 'refresh_token' => '...']].
			if ( array_key_exists( $pat_id, $pat_entry ) ) {
				// Store refresh token for cleanup.
				if ( isset( $pat_entry[ $pat_id ]['refresh_token'] ) ) {
					$refresh_token = $pat_entry[ $pat_id ]['refresh_token'];
				}

				// Remove this specific meta entry that contains our PAT.
				delete_user_meta( $user_id, '_cocart_jwt_token_pat', $pat_entry );
				$destroyed = true;
				break;
			}
		}

		// Clean up associated refresh token if found.
		if ( ! is_null( $refresh_token ) ) {
			// Remove from individual refresh token storage.
			delete_user_meta( $user_id, '_cocart_jwt_refresh_token', $refresh_token );

			// Remove from refresh tokens collection.
			$refresh_tokens = get_user_meta( $user_id, '_cocart_jwt_refresh_tokens', true );

			if ( is_array( $refresh_tokens ) && isset( $refresh_tokens[ $refresh_token ] ) ) {
				unset( $refresh_tokens[ $refresh_token ] );
				update_user_meta( $user_id, '_cocart_jwt_refresh_tokens', $refresh_tokens );
			}
		}

		return $destroyed;
	} // END destroy_pat_session()

	/**
	 * Generate refresh token.
	 *
	 * By default a random 64-byte string is generated.
	 *
	 * @access public
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string The generated refresh token.
	 */
	public function generate_refresh_token( $user_id ) {
		$refresh_tokens = get_user_meta( $user_id, '_cocart_jwt_refresh_tokens', true );

		if ( empty( $refresh_tokens ) || ! is_array( $refresh_tokens ) ) {
			$refresh_tokens = array();
		}

		/**
		 * Filter allows you to change how refresh tokens are generated.
		 *
		 * @since 2.0.0 Introduced.
		 */
		$refresh_token = apply_filters( 'cocart_jwt_auth_refresh_token_generation', bin2hex( random_bytes( 64 ) ) );

		/**
		 * Filter allows you to customize refresh token lifetime based on roles or conditions.
		 *
		 * Default is 30 days.
		 *
		 * @since 2.0.0 Introduced.
		 */
		$expiration = time() + apply_filters( 'cocart_jwt_auth_refresh_token_expiration', DAY_IN_SECONDS * 30 );

		$refresh_tokens[ $refresh_token ] = $expiration;

		add_user_meta( $user_id, '_cocart_jwt_refresh_token', $refresh_token ); // Store the fresh token individually for easy access.
		update_user_meta( $user_id, '_cocart_jwt_refresh_tokens', $refresh_tokens ); // Store all refresh tokens with expiration to validate with.

		return $refresh_token;
	} // END generate_refresh_token()

	/**
	 * Validate the refresh token.
	 *
	 * @access private
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @param string $refresh_token The refresh token.
	 *
	 * @return int|WP_Error User ID if valid, WP_Error otherwise.
	 */
	protected function validate_refresh_token( string $refresh_token ) {
		$user_query = new \WP_User_Query( array(
			'meta_key'   => '_cocart_jwt_refresh_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $refresh_token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		) );

		$users = $user_query->get_results();

		if ( empty( $users ) ) {
			return new \WP_Error( 'cocart_authentication_error', __( 'Invalid refresh token.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) );
		}

		$user_id     = $users[0]->ID;
		$user_tokens = get_user_meta( $user_id, '_cocart_jwt_refresh_tokens', true );

		$expiration = isset( $user_tokens[ $refresh_token ] ) ? $user_tokens[ $refresh_token ] : 0;

		if ( time() > $expiration ) {
			return new \WP_Error( 'cocart_authentication_error', __( 'Refresh token expired.', 'cocart-jwt-authentication' ), array( 'status' => 403 ) );
		}

		return $user_id;
	} // END validate_refresh_token()

	/**
	 * Check if the token is expired.
	 *
	 * @access protected
	 *
	 * @param string $token The JWT token.
	 *
	 * @return bool True if expired, false otherwise.
	 */
	protected function is_token_expired( $token ) {
		try {
			$parts = $this->decode_token( $token );

			if ( ! property_exists( $parts->payload, 'exp' ) || (int) $parts->payload->exp < time() ) {
				return true;
			}

			return false;
		} catch ( Exception $e ) {
			return true;
		}
	} // END is_token_expired()

	/**
	 * Get the token creation time.
	 *
	 * @access protected
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param string $token The JWT token.
	 *
	 * @return false|string The token creation time in 'Y-m-d H:i:s' format, false if not found.
	 */
	protected function get_token_creation_time( $token ) {
		try {
			// Decode token provided.
			$decoded_token = $this->decode_token( $token );

			if ( ! property_exists( $decoded_token->payload, 'iat' ) ) {
				return false;
			}

			$iat = $decoded_token->payload->iat;

			return date( 'Y-m-d H:i:s', $iat );
		} catch ( Exception $e ) {
			return true;
		}
	} // END get_token_creation_time()

	/**
	 * Returns the decoded/encoded header, payload and signature from a token string.
	 *
	 * @throws \Exception If the token is invalid.
	 *
	 * @access public
	 *
	 * @param string $token Full token string.
	 *
	 * @return object
	 */
	public function decode_token( string $token ) {
		// Only validate prefix if enabled.
		if ( $this->is_prefix_enabled() ) {
			if ( strpos( $token, $this->get_token_prefix() ) !== 0 ) {
				\CoCart_Logger::log( esc_html__( 'Token does not have a matching prefix.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
				throw new \Exception( 'Invalid token' );
			}

			// Remove prefix safely.
			$token = substr( $token, strlen( $this->get_token_prefix() ) );
		}

		// Validate token structure.
		$parts = explode( '.', $token );

		if ( count( $parts ) !== 3 ) {
			\CoCart_Logger::log( esc_html__( 'Token was incomplete.', 'cocart-jwt-authentication' ), 'error', 'cocart-jwt-authentication' );
			throw new \Exception( 'Invalid token format' );
		}

		return (object) array(
			'header'            => json_decode( $this->from_base_64_url( $parts[0] ) ),
			'header_encoded'    => $parts[0],
			'payload'           => json_decode( $this->from_base_64_url( $parts[1] ) ),
			'payload_encoded'   => $parts[1],
			'signature'         => $this->from_base_64_url( $parts[2] ),
			'signature_encoded' => $parts[2],
		);
	} // END decode_token()

	/**
	 * Generates the json formatted header for our HS256 JWT token.
	 *
	 * @access private
	 *
	 * @return string|bool
	 */
	private function generate_header() {
		return wp_json_encode(
			array(
				'alg' => $this->get_algorithm(),
				'typ' => 'JWT',
			)
		);
	} // END generate_header()

	/**
	 * Generates a sha256 signature for the provided string using the provided secret.
	 *
	 * @access private
	 *
	 * @since 2.3.0 Added support for more advanced RSA-based configuration.
	 *
	 * @param string $header_encoded Header + Payload token substring.
	 * @param string $secret_key     The secret key used to generate the signature.
	 *
	 * @return false|string
	 */
	protected function generate_signature( string $header_encoded, string $secret_key ) {
		$algorithm = $this->get_algorithm();

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
	 * @param string $encode_string The string to be encoded.
	 *
	 * @return string
	 */
	protected function to_base_64_url( string $encode_string ) {
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
	 * @param string $encoded_string the string to be decoded.
	 *
	 * @return string
	 */
	protected function from_base_64_url( string $encoded_string ) {
		/**
		 * Add padding to base64 strings which require it. Some base64 URL strings
		 * which are decoded will have missing padding which is represented by the
		 * equals sign.
		 */
		if ( strlen( $encoded_string ) % 4 !== 0 ) {
			return $this->from_base_64_url( $encoded_string . '=' );
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
	 * @since 2.0.0 Introduced.
	 *
	 * @return array List of HTTP headers.
	 */
	public function get_user_agent_headers() {
		return is_array( $this->user_agent_headers ) ? $this->user_agent_headers : array();
	} // END get_user_agent_headers()

	/**
	 * Get the found User-Agent header.
	 *
	 * @access public
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @return string
	 */
	public function get_user_agent_header() {
		$user_agent_header = '';

		foreach ( $this->get_user_agent_headers() as $ua_header ) {
			if ( ! empty( $_SERVER[ $ua_header ] ) ) {
				$user_agent_header = $ua_header;
			}
		}

		return $user_agent_header;
	} // END get_user_agent_header()
} // END class

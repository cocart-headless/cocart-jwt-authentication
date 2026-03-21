<?php
/**
 * Test CoCart JWT Authentication
 *
 * Tests for the JWT authentication flow via the /cocart/jwt/validate-token endpoint,
 * which exercises perform_jwt_authentication() and is_token_valid() end-to-end.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Authentication Class
 *
 * Tests the full JWT authentication flow: valid tokens authenticate, invalid
 * tokens fail, and edge cases like deleted users and revoked tokens are handled.
 *
 * IP and User-Agent are set in setUp() before generate_token() is called so that
 * the values baked into the payload match when validation runs in the same test.
 *
 * The $request_made flag on the REST object resets on each new REST() instantiation
 * (bootstrap re-instantiates on every rest_api_init), so each test gets a clean slate.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Authentication extends CoCart_JWT_Test_Case {

	/**
	 * @var WP_User Test user.
	 */
	private WP_User $user;

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->create_test_user();
	}

	/**
	 * Test that a valid JWT token authenticates the user (returns 200).
	 *
	 * @return void
	 */
	public function test_valid_token_authenticates_user() {
		$token    = $this->get_jwt_token_for_user( $this->user->ID );
		$response = $this->jwt_request( 'POST', '/cocart/jwt/validate-token', array(), $token );

		$this->assert_rest_response_status( 200, $response );
	}

	/**
	 * Test that an invalid (garbage) token returns 403.
	 *
	 * @return void
	 */
	public function test_invalid_token_returns_403() {
		$response = $this->jwt_request( 'POST', '/cocart/jwt/validate-token', array(), 'not.a.valid.jwt.token' );

		$this->assert_rest_response_status( 403, $response );
	}

	/**
	 * Test that a request without an Authorization header returns 401.
	 *
	 * @return void
	 */
	public function test_missing_token_returns_401() {
		$response = $this->rest_request( 'POST', '/cocart/jwt/validate-token' );

		$this->assert_rest_response_status( 401, $response );
	}

	/**
	 * Test that a token signed with the wrong secret key returns 403.
	 *
	 * @return void
	 */
	public function test_wrong_secret_key_returns_403() {
		// Generate token with the wrong key via filter.
		add_filter( 'cocart_jwt_auth_secret_private_key', function () {
			return 'completely-different-secret-key';
		}, 99 );
		add_filter( 'cocart_jwt_auth_secret_public_key', function () {
			return 'completely-different-secret-key';
		}, 99 );

		$rest  = new \CoCart\JWTAuthentication\REST();
		$token = $rest->generate_token( $this->user->ID );

		remove_all_filters( 'cocart_jwt_auth_secret_private_key', 99 );
		remove_all_filters( 'cocart_jwt_auth_secret_public_key', 99 );

		// Validate with the real key — signature won't match.
		$response = $this->jwt_request( 'POST', '/cocart/jwt/validate-token', array(), $token );

		$this->assert_rest_response_status( 403, $response );
	}

	/**
	 * Test that a token for a user that no longer exists returns 403.
	 *
	 * @return void
	 */
	public function test_token_for_deleted_user_returns_403() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );

		// Delete the user.
		wp_delete_user( $this->user->ID );

		$response = $this->jwt_request( 'POST', '/cocart/jwt/validate-token', array(), $token );

		$this->assert_rest_response_status( 403, $response );
	}

	/**
	 * Test that a token whose _cocart_jwt_tokens meta has been deleted returns 403.
	 *
	 * Simulates manual revocation of all tokens for a user.
	 *
	 * @return void
	 */
	public function test_revoked_token_returns_403() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );

		// Remove all tokens from user meta (simulates revocation).
		delete_user_meta( $this->user->ID, '_cocart_jwt_tokens' );

		$response = $this->jwt_request( 'POST', '/cocart/jwt/validate-token', array(), $token );

		$this->assert_rest_response_status( 403, $response );
	}

	/**
	 * Test that a token whose PAT meta has been deleted returns 403.
	 *
	 * Simulates per-session revocation where the PAT entry is removed.
	 *
	 * @return void
	 */
	public function test_revoked_pat_returns_403() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );

		// Remove all PAT entries from user meta.
		delete_user_meta( $this->user->ID, '_cocart_jwt_token_pat' );

		$response = $this->jwt_request( 'POST', '/cocart/jwt/validate-token', array(), $token );

		$this->assert_rest_response_status( 403, $response );
	}
}

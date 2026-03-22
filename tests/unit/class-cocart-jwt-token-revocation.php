<?php
/**
 * Test CoCart JWT Token Revocation
 *
 * Tests for the DestroyTokens class which revokes all JWT tokens on logout,
 * password reset, and user deletion, and for direct PAT session destruction.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Token Revocation Class
 *
 * Tests that tokens are properly revoked by the DestroyTokens hooks and
 * that a revoked token cannot subsequently authenticate.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Token_Revocation extends CoCart_JWT_Test_Case {

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
	 * Call the protected destroy_pat_session() method via reflection.
	 *
	 * @param int    $user_id User ID.
	 * @param string $pat_id  PAT ID.
	 *
	 * @return void
	 */
	private function destroy_pat_session( int $user_id, string $pat_id ): void {
		$rest   = new \CoCart\JWTAuthentication\REST();
		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'destroy_pat_session' );
		$method->setAccessible( true );
		$method->invoke( $rest, $user_id, $pat_id );
	}

	/**
	 * Test that destroy_pat_session() removes the token from _cocart_jwt_tokens.
	 *
	 * @return void
	 */
	public function test_destroy_pat_session_removes_token() {
		$tokens = $this->get_jwt_tokens_for_user( $this->user->ID );

		// Get the PAT ID from the token.
		$rest    = new \CoCart\JWTAuthentication\REST();
		$decoded = $rest->decode_token( $tokens['token'] );
		$pat_id  = $decoded->payload->data->user->pat;

		$this->destroy_pat_session( $this->user->ID, $pat_id );

		$user_tokens = get_user_meta( $this->user->ID, '_cocart_jwt_tokens', true );

		if ( is_array( $user_tokens ) ) {
			$this->assertArrayNotHasKey( $pat_id, $user_tokens, 'PAT must be removed from _cocart_jwt_tokens after destruction.' );
		} else {
			// Empty/non-array means all tokens gone — also acceptable.
			$this->assertTrue( true );
		}
	}

	/**
	 * Test that destroy_pat_session() removes the refresh token from user meta.
	 *
	 * @return void
	 */
	public function test_destroy_pat_session_removes_refresh_token() {
		$tokens = $this->get_jwt_tokens_for_user( $this->user->ID );

		$rest    = new \CoCart\JWTAuthentication\REST();
		$decoded = $rest->decode_token( $tokens['token'] );
		$pat_id  = $decoded->payload->data->user->pat;

		// Confirm refresh token exists before destroy.
		$before = get_user_meta( $this->user->ID, '_cocart_jwt_refresh_tokens', true );
		$this->assertArrayHasKey( $tokens['refresh_token'], $before, 'Refresh token must exist before destroy.' );

		$this->destroy_pat_session( $this->user->ID, $pat_id );

		$after = get_user_meta( $this->user->ID, '_cocart_jwt_refresh_tokens', true );

		if ( is_array( $after ) ) {
			$this->assertArrayNotHasKey( $tokens['refresh_token'], $after, 'Refresh token must be removed after PAT destroy.' );
		} else {
			$this->assertTrue( true );
		}
	}

	/**
	 * Test that wp_logout() clears all JWT meta for the user.
	 *
	 * DestroyTokens hooks into wp_logout. WP_UnitTestCase::setUp() has added
	 * the send_auth_cookies=false filter (from CoCart_API_V2_Test_Case::setUp()),
	 * so wp_logout() won't try to clear cookies.
	 *
	 * @return void
	 */
	public function test_logout_clears_all_jwt_meta() {
		$this->get_jwt_tokens_for_user( $this->user->ID );

		// Confirm meta exists before logout.
		$this->assertNotEmpty( get_user_meta( $this->user->ID, '_cocart_jwt_tokens', true ) );

		// Trigger logout as this user.
		wp_set_current_user( $this->user->ID );
		wp_logout();

		$this->assertEmpty( get_user_meta( $this->user->ID, '_cocart_jwt_tokens', true ) );
		$this->assertEmpty( get_user_meta( $this->user->ID, '_cocart_jwt_token_pat' ) );
		$this->assertEmpty( get_user_meta( $this->user->ID, '_cocart_jwt_refresh_tokens', true ) );
	}

	/**
	 * Test that after_password_reset clears all JWT meta.
	 *
	 * @return void
	 */
	public function test_password_reset_clears_all_jwt_meta() {
		$this->get_jwt_tokens_for_user( $this->user->ID );

		// Confirm meta exists.
		$this->assertNotEmpty( get_user_meta( $this->user->ID, '_cocart_jwt_tokens', true ) );

		// Fire the hook that DestroyTokens listens to.
		// WP core passes a WP_User object as the first argument to after_password_reset.
		do_action( 'after_password_reset', $this->user, '' );

		$this->assertEmpty( get_user_meta( $this->user->ID, '_cocart_jwt_tokens', true ) );
		$this->assertEmpty( get_user_meta( $this->user->ID, '_cocart_jwt_token_pat' ) );
		$this->assertEmpty( get_user_meta( $this->user->ID, '_cocart_jwt_refresh_tokens', true ) );
	}

	/**
	 * Test that a token can no longer authenticate after its PAT is destroyed.
	 *
	 * @return void
	 */
	public function test_revoked_token_cannot_authenticate() {
		$tokens = $this->get_jwt_tokens_for_user( $this->user->ID );

		// Destroy the PAT session.
		$rest    = new \CoCart\JWTAuthentication\REST();
		$decoded = $rest->decode_token( $tokens['token'] );
		$pat_id  = $decoded->payload->data->user->pat;
		$this->destroy_pat_session( $this->user->ID, $pat_id );

		// Now try to use the revoked token.
		$response = $this->jwt_request( 'POST', '/cocart/jwt/validate-token', array(), $tokens['token'] );

		$status = $response->get_status();
		$this->assertContains( $status, array( 401, 403 ), 'Revoked token must not authenticate.' );
	}
}

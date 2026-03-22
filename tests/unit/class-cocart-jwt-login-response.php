<?php
/**
 * Test CoCart JWT Login Response
 *
 * Tests that the JWT plugin adds jwt_token and jwt_refresh to the CoCart
 * login response via the cocart_login_extras filter (send_tokens() method).
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Login Response Class
 *
 * Tests send_tokens() directly via the cocart_login_extras filter since
 * WP REST unit tests do not re-run determine_current_user on dispatch,
 * making it impossible to drive login auth via HTTP headers in unit tests.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Login_Response extends CoCart_JWT_Test_Case {

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
	 * Apply the cocart_login_extras filter as send_tokens() would during login.
	 *
	 * @return array The extras array after JWT keys are added.
	 */
	private function get_login_extras(): array {
		return apply_filters( 'cocart_login_extras', array(), $this->user );
	}

	/**
	 * Test that the login extras contain the jwt_token key.
	 *
	 * @return void
	 */
	public function test_login_response_contains_jwt_token() {
		$extras = $this->get_login_extras();
		$this->assertArrayHasKey( 'jwt_token', $extras, 'Login extras must contain jwt_token.' );
	}

	/**
	 * Test that the login extras contain the jwt_refresh key.
	 *
	 * @return void
	 */
	public function test_login_response_contains_refresh_token() {
		$extras = $this->get_login_extras();
		$this->assertArrayHasKey( 'jwt_refresh', $extras, 'Login extras must contain jwt_refresh.' );
	}

	/**
	 * Test that the jwt_token in the login extras is a valid JWT format.
	 *
	 * @return void
	 */
	public function test_jwt_token_is_valid_format() {
		$extras = $this->get_login_extras();
		$parts  = explode( '.', $extras['jwt_token'] );
		$this->assertCount( 3, $parts, 'jwt_token must be in header.payload.signature format.' );
	}

	/**
	 * Test that the jwt_refresh token is a 128-char hex string.
	 *
	 * @return void
	 */
	public function test_refresh_token_is_hex_string() {
		$extras        = $this->get_login_extras();
		$refresh_token = $extras['jwt_refresh'];
		$this->assertEquals( 128, strlen( $refresh_token ), 'Refresh token must be 128 hex chars.' );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]+$/', $refresh_token, 'Refresh token must be a hex string.' );
	}

	/**
	 * Test that calling send_tokens() with an existing valid Bearer token reuses it.
	 *
	 * When send_tokens() detects a valid Bearer token in $_SERVER, it reuses that
	 * token rather than generating a new one.
	 *
	 * @return void
	 */
	public function test_existing_bearer_token_is_reused() {
		$existing_token = $this->get_jwt_token_for_user( $this->user->ID );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $existing_token;

		$extras = $this->get_login_extras();

		unset( $_SERVER['HTTP_AUTHORIZATION'] );

		$this->assertEquals( $existing_token, $extras['jwt_token'], 'Login with existing Bearer token must reuse the token.' );
	}
}

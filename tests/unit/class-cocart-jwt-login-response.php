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
 * Tests the login response extras added by REST::send_tokens() which hooks
 * into cocart_login_extras at priority 0.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Login_Response extends CoCart_JWT_Test_Case {

	/**
	 * @var WP_User Test user.
	 */
	private WP_User $user;

	/**
	 * @var string Test user login.
	 */
	private string $user_login;

	/**
	 * @var string Test user password.
	 */
	private string $user_password;

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user_login    = 'jwt_login_test_' . wp_rand( 1000, 9999 );
		$this->user_password = 'test_password_123';

		$this->user = $this->create_test_user( array(
			'user_login' => $this->user_login,
			'user_pass'  => $this->user_password,
		) );
	}

	/**
	 * Test that the login response contains the jwt_token key.
	 *
	 * @return void
	 */
	public function test_login_response_contains_jwt_token() {
		$response = $this->login( array(
			'username' => $this->user_login,
			'password' => $this->user_password,
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'jwt_token', $data, 'Login response must contain jwt_token.' );
	}

	/**
	 * Test that the login response contains the jwt_refresh key.
	 *
	 * @return void
	 */
	public function test_login_response_contains_refresh_token() {
		$response = $this->login( array(
			'username' => $this->user_login,
			'password' => $this->user_password,
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'jwt_refresh', $data, 'Login response must contain jwt_refresh.' );
	}

	/**
	 * Test that the jwt_token in the login response is a valid JWT format.
	 *
	 * @return void
	 */
	public function test_jwt_token_is_valid_format() {
		$response = $this->login( array(
			'username' => $this->user_login,
			'password' => $this->user_password,
		) );

		$this->assert_rest_response_status( 200, $response );

		$data  = $response->get_data();
		$token = $data['jwt_token'];

		$parts = explode( '.', $token );
		$this->assertCount( 3, $parts, 'jwt_token must be in header.payload.signature format.' );
	}

	/**
	 * Test that the jwt_refresh token is a hex string (128 chars).
	 *
	 * @return void
	 */
	public function test_refresh_token_is_hex_string() {
		$response = $this->login( array(
			'username' => $this->user_login,
			'password' => $this->user_password,
		) );

		$this->assert_rest_response_status( 200, $response );

		$data          = $response->get_data();
		$refresh_token = $data['jwt_refresh'];

		$this->assertEquals( 128, strlen( $refresh_token ), 'Refresh token must be 128 hex chars.' );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]+$/', $refresh_token, 'Refresh token must be a hex string.' );
	}

	/**
	 * Test that logging in with an existing valid Bearer token reuses it in the response.
	 *
	 * When send_tokens() detects a valid Bearer token in the request, it reuses that
	 * token rather than generating a new one.
	 *
	 * @return void
	 */
	public function test_login_with_existing_bearer_reuses_token() {
		// First login to get a token.
		$first_response = $this->login( array(
			'username' => $this->user_login,
			'password' => $this->user_password,
		) );

		$this->assert_rest_response_status( 200, $first_response );
		$first_data  = $first_response->get_data();
		$first_token = $first_data['jwt_token'];

		// Login again with the existing token in the Authorization header.
		$second_response = $this->login(
			array(
				'username' => $this->user_login,
				'password' => $this->user_password,
			),
			array( 'Authorization' => 'Bearer ' . $first_token )
		);

		$this->assert_rest_response_status( 200, $second_response );
		$second_data  = $second_response->get_data();
		$second_token = $second_data['jwt_token'];

		// The token should be reused, not regenerated.
		$this->assertEquals( $first_token, $second_token, 'Login with existing Bearer token must reuse the token.' );
	}
}

<?php
/**
 * Test CoCart JWT Authentication
 *
 * Tests for the JWT authentication flow: perform_jwt_authentication() is tested
 * directly (unit-style) since WP REST unit tests do not re-run determine_current_user
 * on dispatch. Endpoint permission is verified via wp_set_current_user simulation.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Authentication Class
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
	 * Test that perform_jwt_authentication() returns the user ID for a valid token.
	 *
	 * @return void
	 */
	public function test_valid_token_authenticates_user() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );
		$rest  = new \CoCart\JWTAuthentication\REST();

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$_SERVER['REQUEST_URI']        = '/wp-json/cocart/jwt/validate-token';

		$auth   = new CoCart_Authentication();
		$result = $rest->perform_jwt_authentication( 0, false, $auth );

		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REQUEST_URI'] );

		$this->assertEquals( $this->user->ID, $result, 'Valid token must return the user ID.' );
	}

	/**
	 * Test that perform_jwt_authentication() returns 0 for a garbage token.
	 *
	 * @return void
	 */
	public function test_invalid_token_does_not_authenticate() {
		$rest = new \CoCart\JWTAuthentication\REST();

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer not.a.valid.jwt';
		$_SERVER['REQUEST_URI']        = '/wp-json/cocart/jwt/validate-token';

		$auth   = new CoCart_Authentication();
		$result = $rest->perform_jwt_authentication( 0, false, $auth );

		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REQUEST_URI'] );

		$this->assertEquals( 0, $result, 'Invalid token must not authenticate.' );
	}

	/**
	 * Test that validate-token endpoint returns 401 when no token is provided.
	 *
	 * @return void
	 */
	public function test_missing_token_returns_401() {
		$response = $this->rest_request( 'POST', '/cocart/jwt/validate-token' );
		$this->assert_rest_response_status( 401, $response );
	}

	/**
	 * Test that perform_jwt_authentication() returns 0 for a wrong-secret token.
	 *
	 * @return void
	 */
	public function test_wrong_secret_key_does_not_authenticate() {
		add_filter( 'cocart_jwt_auth_secret_private_key', function () {
			return 'completely-different-secret-key';
		}, 99 );
		add_filter( 'cocart_jwt_auth_secret_public_key', function () {
			return 'completely-different-secret-key';
		}, 99 );

		$bad_rest = new \CoCart\JWTAuthentication\REST();
		$token    = $bad_rest->generate_token( $this->user->ID );

		remove_all_filters( 'cocart_jwt_auth_secret_private_key', 99 );
		remove_all_filters( 'cocart_jwt_auth_secret_public_key', 99 );

		$rest = new \CoCart\JWTAuthentication\REST();

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$_SERVER['REQUEST_URI']        = '/wp-json/cocart/jwt/validate-token';

		$auth   = new CoCart_Authentication();
		$result = $rest->perform_jwt_authentication( 0, false, $auth );

		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REQUEST_URI'] );

		$this->assertEquals( 0, $result, 'Token signed with wrong key must not authenticate.' );
	}

	/**
	 * Test that perform_jwt_authentication() returns 0 for a deleted user's token.
	 *
	 * @return void
	 */
	public function test_token_for_deleted_user_does_not_authenticate() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );
		wp_delete_user( $this->user->ID );

		$rest = new \CoCart\JWTAuthentication\REST();

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$_SERVER['REQUEST_URI']        = '/wp-json/cocart/jwt/validate-token';

		$auth   = new CoCart_Authentication();
		$result = $rest->perform_jwt_authentication( 0, false, $auth );

		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REQUEST_URI'] );

		$this->assertEquals( 0, $result, 'Token for deleted user must not authenticate.' );
	}

	/**
	 * Test that perform_jwt_authentication() returns 0 after _cocart_jwt_tokens meta is deleted.
	 *
	 * @return void
	 */
	public function test_revoked_token_does_not_authenticate() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );
		delete_user_meta( $this->user->ID, '_cocart_jwt_tokens' );

		$rest = new \CoCart\JWTAuthentication\REST();

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$_SERVER['REQUEST_URI']        = '/wp-json/cocart/jwt/validate-token';

		$auth   = new CoCart_Authentication();
		$result = $rest->perform_jwt_authentication( 0, false, $auth );

		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REQUEST_URI'] );

		$this->assertEquals( 0, $result, 'Revoked token must not authenticate.' );
	}

	/**
	 * Test that perform_jwt_authentication() returns 0 after PAT meta is deleted.
	 *
	 * @return void
	 */
	public function test_revoked_pat_does_not_authenticate() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );
		delete_user_meta( $this->user->ID, '_cocart_jwt_token_pat' );

		$rest = new \CoCart\JWTAuthentication\REST();

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$_SERVER['REQUEST_URI']        = '/wp-json/cocart/jwt/validate-token';

		$auth   = new CoCart_Authentication();
		$result = $rest->perform_jwt_authentication( 0, false, $auth );

		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REQUEST_URI'] );

		$this->assertEquals( 0, $result, 'Token with revoked PAT must not authenticate.' );
	}
}

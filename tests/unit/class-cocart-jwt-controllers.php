<?php
/**
 * Test CoCart JWT Controllers
 *
 * Tests for the CoCart v5 controller classes introduced to replace the
 * inline route registration in class-rest.php.
 *
 * These tests are skipped automatically when CoCart_REST_Controller is not
 * available (i.e. CoCart < 5.0.0 is installed).
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Controllers Class
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Controllers extends CoCart_JWT_Test_Case {

	/**
	 * Skip all tests in this class when CoCart_REST_Controller is unavailable.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'CoCart_REST_Controller' ) ) {
			$this->markTestSkipped( 'CoCart_REST_Controller not available (requires CoCart 5.0+).' );
		}
	}

	// -------------------------------------------------------------------------
	// Refresh Token Controller
	// -------------------------------------------------------------------------

	/**
	 * Test that the refresh token controller returns the correct path regex.
	 *
	 * @return void
	 */
	public function test_refresh_token_controller_path_regex() {
		$controller = new CoCart_REST_JWT_Refresh_Token_Controller();
		$this->assertSame( '/refresh-token', $controller->get_path_regex() );
	}

	/**
	 * Test that the refresh token controller args declare a POST method.
	 *
	 * @return void
	 */
	public function test_refresh_token_controller_args_has_post_method() {
		$controller = new CoCart_REST_JWT_Refresh_Token_Controller();
		$args       = $controller->get_args();
		$this->assertSame( WP_REST_Server::CREATABLE, $args[0]['methods'] );
	}

	/**
	 * Test that the refresh token controller allows unauthenticated requests.
	 *
	 * The permission callback must be __return_true so anyone can call the
	 * refresh endpoint (authentication is done via the refresh token itself).
	 *
	 * @return void
	 */
	public function test_refresh_token_controller_permission_callback_allows_anyone() {
		$controller          = new CoCart_REST_JWT_Refresh_Token_Controller();
		$args                = $controller->get_args();
		$permission_callback = $args[0]['permission_callback'];
		$this->assertSame( '__return_true', $permission_callback );
	}

	/**
	 * Test that the refresh token controller reports the 'jwt' version.
	 *
	 * @return void
	 */
	public function test_refresh_token_controller_version_is_jwt() {
		$controller = new CoCart_REST_JWT_Refresh_Token_Controller();
		$this->assertSame( 'jwt', $controller->get_version() );
	}

	/**
	 * Test that the refresh token controller has a callable handle_request method.
	 *
	 * @return void
	 */
	public function test_refresh_token_controller_has_callable_handle_request() {
		$controller = new CoCart_REST_JWT_Refresh_Token_Controller();
		$this->assertTrue( is_callable( array( $controller, 'handle_request' ) ) );
	}

	// -------------------------------------------------------------------------
	// Validate Token Controller
	// -------------------------------------------------------------------------

	/**
	 * Test that the validate token controller returns the correct path regex.
	 *
	 * @return void
	 */
	public function test_validate_token_controller_path_regex() {
		$controller = new CoCart_REST_JWT_Validate_Token_Controller();
		$this->assertSame( '/validate-token', $controller->get_path_regex() );
	}

	/**
	 * Test that the validate token controller args declare a POST method.
	 *
	 * @return void
	 */
	public function test_validate_token_controller_args_has_post_method() {
		$controller = new CoCart_REST_JWT_Validate_Token_Controller();
		$args       = $controller->get_args();
		$this->assertSame( WP_REST_Server::CREATABLE, $args[0]['methods'] );
	}

	/**
	 * Test that the validate token controller rejects unauthenticated users.
	 *
	 * @return void
	 */
	public function test_validate_token_controller_rejects_unauthenticated_user() {
		wp_set_current_user( 0 );

		$controller          = new CoCart_REST_JWT_Validate_Token_Controller();
		$args                = $controller->get_args();
		$permission_callback = $args[0]['permission_callback'];

		$result = call_user_func( $permission_callback, new WP_REST_Request() );
		$this->assertFalse( (bool) $result, 'Validate-token must reject unauthenticated requests.' );
	}

	/**
	 * Test that the validate token controller allows authenticated users.
	 *
	 * @return void
	 */
	public function test_validate_token_controller_allows_authenticated_user() {
		$user = $this->create_test_user();
		wp_set_current_user( $user->ID );

		$controller          = new CoCart_REST_JWT_Validate_Token_Controller();
		$args                = $controller->get_args();
		$permission_callback = $args[0]['permission_callback'];

		$result = call_user_func( $permission_callback, new WP_REST_Request() );
		$this->assertTrue( (bool) $result, 'Validate-token must allow authenticated requests.' );
	}

	/**
	 * Test that the validate token controller handle_request returns a message key.
	 *
	 * @return void
	 */
	public function test_validate_token_controller_handle_request_has_message_key() {
		$controller = new CoCart_REST_JWT_Validate_Token_Controller();
		$response   = $controller->handle_request( new WP_REST_Request() );
		$data       = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
	}

	/**
	 * Test that the validate token controller handle_request returns the correct message.
	 *
	 * @return void
	 */
	public function test_validate_token_controller_handle_request_returns_valid_message() {
		$controller = new CoCart_REST_JWT_Validate_Token_Controller();
		$response   = $controller->handle_request( new WP_REST_Request() );
		$data       = $response->get_data();
		$this->assertSame( 'Token is valid.', $data['message'] );
	}

	/**
	 * Test that the validate token controller reports the 'jwt' version.
	 *
	 * @return void
	 */
	public function test_validate_token_controller_version_is_jwt() {
		$controller = new CoCart_REST_JWT_Validate_Token_Controller();
		$this->assertSame( 'jwt', $controller->get_version() );
	}
}

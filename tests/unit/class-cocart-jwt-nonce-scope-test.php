<?php
/**
 * Test CoCart JWT Authentication Nonce Scoping Interaction
 *
 * Regression tests confirming CoCart JWT Authentication does not reopen the
 * REST auth error scoping vulnerability fixed in CoCart Starter/Community
 * (reported by Naoki Kawahigashi): CoCart_Authentication::
 * check_authentication_error() used to answer `true` for any request that
 * merely *looked* like a CoCart request — including one whose URL smuggled
 * the route pattern into an unrelated query value — which short-circuited
 * WordPress core's REST nonce/CSRF check and let a logged-in
 * administrator's session cookie alone create a new administrator account.
 *
 * The fix replaced that URL-based check with `$authenticated_by_cocart`,
 * set only when `CoCart_Authentication::authenticate()` actually
 * establishes a user — via Basic Auth, or a third party hooking
 * `cocart_authenticate`, which is exactly how this plugin's
 * `perform_jwt_authentication()` integrates. This suite confirms that
 * integration point cannot itself be used to satisfy
 * `$authenticated_by_cocart` without a genuinely valid JWT: neither a
 * missing Authorization header nor an invalid/garbage bearer token sets it,
 * while a real, valid token still does (and still correctly bypasses the
 * nonce requirement, since JWT is one of this plugin's supported auth
 * methods and does not rely on ambient cookies).
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Authentication Nonce Scoping Interaction Class.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Nonce_Scope extends CoCart_JWT_Test_Case {

	/**
	 * Original superglobal/global state, restored in tearDown().
	 *
	 * @var array
	 */
	private $original_state = array();

	/**
	 * Snapshot request-related state before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_state = array(
			'REQUEST_URI'         => $_SERVER['REQUEST_URI'] ?? null,
			'REQUEST_METHOD'      => $_SERVER['REQUEST_METHOD'] ?? null,
			'HTTP_X_WP_NONCE'     => $_SERVER['HTTP_X_WP_NONCE'] ?? null,
			'HTTP_AUTHORIZATION'  => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
			'GET'                 => $_GET,
			'REQUEST'             => $_REQUEST,
			'wp_rest_auth_cookie' => $GLOBALS['wp_rest_auth_cookie'] ?? null,
		);
	}

	/**
	 * Restore superglobal/global state after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( array( 'REQUEST_URI', 'REQUEST_METHOD', 'HTTP_X_WP_NONCE', 'HTTP_AUTHORIZATION' ) as $key ) {
			if ( null === $this->original_state[ $key ] ) {
				unset( $_SERVER[ $key ] );
			} else {
				$_SERVER[ $key ] = $this->original_state[ $key ];
			}
		}

		$_GET     = $this->original_state['GET'];
		$_REQUEST = $this->original_state['REQUEST'];

		if ( null === $this->original_state['wp_rest_auth_cookie'] ) {
			unset( $GLOBALS['wp_rest_auth_cookie'] );
		} else {
			$GLOBALS['wp_rest_auth_cookie'] = $this->original_state['wp_rest_auth_cookie'];
		}

		$this->reset_authentication_state();

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Locate the single CoCart_Authentication instance registered on
	 * WordPress's own filters at plugin load time (there is exactly one for
	 * the life of the process — the same instance this plugin's
	 * perform_jwt_authentication() is hooked to authenticate through).
	 *
	 * @return CoCart_Authentication|null
	 */
	private function get_authentication_instance() {
		global $wp_filter;

		if ( empty( $wp_filter['rest_authentication_errors'] ) ) {
			return null;
		}

		foreach ( $wp_filter['rest_authentication_errors']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];

				if ( is_array( $function ) && $function[0] instanceof CoCart_Authentication ) {
					return $function[0];
				}
			}
		}

		return null;
	}

	/**
	 * Force a protected property on the shared CoCart_Authentication
	 * instance. Used to reset state between tests, since a real
	 * authentication attempt (this suite makes several) mutates it on the
	 * one shared instance for the life of the process.
	 *
	 * @param string $property Property name.
	 * @param mixed  $value    Value to set.
	 *
	 * @return void
	 */
	private function set_authentication_property( $property, $value ) {
		$instance = $this->get_authentication_instance();

		if ( ! $instance || ! property_exists( $instance, $property ) ) {
			return;
		}

		$ref = new ReflectionProperty( CoCart_Authentication::class, $property );
		$ref->setAccessible( true );
		$ref->setValue( $instance, $value );
	}

	/**
	 * Read a protected property off the shared CoCart_Authentication
	 * instance.
	 *
	 * @param string $property Property name.
	 *
	 * @return mixed
	 */
	private function get_authentication_property( $property ) {
		$instance = $this->get_authentication_instance();

		if ( ! $instance || ! property_exists( $instance, $property ) ) {
			return null;
		}

		$ref = new ReflectionProperty( CoCart_Authentication::class, $property );
		$ref->setAccessible( true );

		return $ref->getValue( $instance );
	}

	/**
	 * Reset every property check_authentication_error()/authenticate() can
	 * mutate, so a real authentication attempt in one test can't leak into
	 * the next.
	 *
	 * @return void
	 */
	private function reset_authentication_state() {
		$this->set_authentication_property( 'authenticated_by_cocart', false );
		$this->set_authentication_property( 'error', null );
		$this->set_authentication_property( 'user', null );
		$this->set_authentication_property( 'auth_method', '' );
	}

	/**
	 * Simulate a logged-in administrator whose only credential is a valid
	 * WordPress auth cookie, with no REST nonce and no JWT/Basic Auth
	 * credentials of any kind.
	 *
	 * @return int Administrator user ID.
	 */
	private function simulate_cookie_authenticated_admin_with_no_nonce() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $admin_id );

		// Mirrors what core's rest_cookie_collect_status() sets on a real
		// auth_cookie_valid event — this request is using cookie auth.
		$GLOBALS['wp_rest_auth_cookie'] = true;

		unset( $_SERVER['HTTP_X_WP_NONCE'], $_REQUEST['_wpnonce'], $_GET['_wpnonce'], $_SERVER['HTTP_AUTHORIZATION'] );

		return $admin_id;
	}

	/**
	 * Replicates the auth-then-dispatch sequence from
	 * WP_REST_Server::serve_request() without its header-sending/output
	 * side effects, so a test can assert on the resulting WP_REST_Response
	 * exactly as a real HTTP client would receive it — status code included.
	 *
	 * @param WP_REST_Request $request Request to authenticate and dispatch.
	 *
	 * @return WP_REST_Response
	 */
	private function dispatch_with_full_authentication( WP_REST_Request $request ) {
		$auth_result = apply_filters( 'rest_authentication_errors', null );

		if ( is_wp_error( $auth_result ) ) {
			return rest_ensure_response( $auth_result );
		}

		return rest_ensure_response( $this->server->dispatch( $request ) );
	}

	/**
	 * Runs the real, globally-registered authenticate() (via
	 * determine_current_user) on the shared CoCart_Authentication instance —
	 * which is what invokes this plugin's perform_jwt_authentication()
	 * through the cocart_authenticate filter, exactly as production does.
	 *
	 * @return int|false Whatever authenticate() returns.
	 */
	private function run_real_authenticate() {
		return apply_filters( 'determine_current_user', false );
	}

	/**
	 * Lane — smuggled URL, no JWT token at all.
	 *
	 * A request whose URL smuggles CoCart's route pattern into an unrelated
	 * query value satisfies CoCart::is_rest_api_request(), so
	 * authenticate() (and in turn the cocart_authenticate filter, where
	 * this plugin's perform_jwt_authentication() lives) does get invoked.
	 * With no Authorization header present, perform_jwt_authentication()
	 * must not treat that as authenticated.
	 *
	 * @return void
	 */
	public function test_smuggled_route_with_no_jwt_token_does_not_authenticate() {
		$this->simulate_cookie_authenticated_admin_with_no_nonce();

		$_SERVER['REQUEST_URI']    = '/wp-json/wp/v2/users?_method=POST&x=/wp-json/cocart/v2/cart&username=newadmin&email=newadmin%40example.invalid&password=hunter2&roles%5B%5D=administrator';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET['_method']           = 'POST';
		$_GET['x']                 = '/wp-json/cocart/v2/cart';

		$this->run_real_authenticate();

		$this->assertFalse(
			$this->get_authentication_property( 'authenticated_by_cocart' ),
			'With no Authorization header at all, perform_jwt_authentication() must not set authenticated_by_cocart.'
		);

		// run_real_authenticate() above triggers WordPress core's real cookie
		// validation as a side effect (via determine_current_user). Since no
		// real $_COOKIE exists in this test, that fires auth_cookie_malformed,
		// which overwrites wp_rest_auth_cookie via rest_cookie_collect_status().
		// Re-simulate "a genuine, valid session cookie was presented" here,
		// immediately before the check that reads it, matching a real request
		// where cookie validation and this check happen as a single pass.
		$GLOBALS['wp_rest_auth_cookie'] = true;

		$result = apply_filters( 'rest_authentication_errors', null );

		$this->assertTrue( $result, 'Core cookie check should still run and report success (as anonymous).' );
		$this->assertSame(
			0,
			get_current_user_id(),
			'Without a nonce or a valid JWT token, a smuggled-route request must still be treated as anonymous.'
		);
	}

	/**
	 * Lane — smuggled URL with an invalid/garbage bearer token attached.
	 *
	 * Confirms an invalid token is rejected by JWT validation and does not
	 * accidentally satisfy authenticated_by_cocart, even though it does
	 * cause perform_jwt_authentication() to actually run its validation
	 * logic (unlike the no-token case above).
	 *
	 * @return void
	 */
	public function test_smuggled_route_with_invalid_jwt_token_does_not_authenticate() {
		$this->simulate_cookie_authenticated_admin_with_no_nonce();

		$_SERVER['REQUEST_URI']        = '/wp-json/wp/v2/users?_method=POST&x=/wp-json/cocart/v2/cart&username=newadmin&email=newadmin%40example.invalid&password=hunter2&roles%5B%5D=administrator';
		$_SERVER['REQUEST_METHOD']     = 'GET';
		$_GET['_method']               = 'POST';
		$_GET['x']                     = '/wp-json/cocart/v2/cart';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer not.a.valid.jwt.token';

		$this->run_real_authenticate();

		$this->assertFalse(
			$this->get_authentication_property( 'authenticated_by_cocart' ),
			'An invalid bearer token must not set authenticated_by_cocart.'
		);

		// See the identical comment in test_smuggled_route_with_no_jwt_token_does_not_authenticate().
		$GLOBALS['wp_rest_auth_cookie'] = true;

		$result = apply_filters( 'rest_authentication_errors', null );

		$this->assertTrue( $result, 'Core cookie check should still run and report success (as anonymous).' );
		$this->assertSame(
			0,
			get_current_user_id(),
			'An invalid JWT token must not preserve the administrator identity without a nonce.'
		);
	}

	/**
	 * End-to-end — the smuggled-URL exploit, with no valid JWT credentials,
	 * routed all the way through to the real /wp/v2/users endpoint. Must be
	 * rejected and must not create an account, confirming this plugin's
	 * presence does not reopen the vulnerability at the HTTP-response level,
	 * not just the auth-layer level.
	 *
	 * @return void
	 */
	public function test_smuggled_route_end_to_end_does_not_create_admin_without_nonce() {
		$this->simulate_cookie_authenticated_admin_with_no_nonce();
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer not.a.valid.jwt.token';

		$_SERVER['REQUEST_URI']    = '/wp-json/wp/v2/users?x=/wp-json/cocart/v2/cart';
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$request = new WP_REST_Request( 'POST', '/wp/v2/users' );
		$request->set_param( 'username', 'jwt_nonce_bypass_test' );
		$request->set_param( 'email', 'jwt-nonce-bypass-test@example.invalid' );
		$request->set_param( 'password', 'Sup3r-Secret-Password!5' );
		$request->set_param( 'roles', array( 'administrator' ) );

		$response = $this->dispatch_with_full_authentication( $request );

		$this->assertSame( 401, $response->get_status(), 'The smuggled route pattern combined with an invalid JWT token must not create an account.' );
		$this->assertFalse( username_exists( 'jwt_nonce_bypass_test' ), 'No account should have been created.' );
	}

	/**
	 * End-to-end — the batch-endpoint variant of the exploit, with no valid
	 * JWT credentials, routed all the way through core's real batch
	 * dispatcher (WordPress core registers /wp/v2/users with
	 * allow_batch => ['v1' => true]). The outer /wp-json/batch/v1 request is
	 * authenticated once; if it were wrongly treated as CoCart-authenticated,
	 * every batched sub-request would inherit that identity. Must be
	 * rejected and must not create an account.
	 *
	 * @return void
	 */
	public function test_batch_endpoint_end_to_end_does_not_create_admin_without_nonce() {
		$this->simulate_cookie_authenticated_admin_with_no_nonce();
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer not.a.valid.jwt.token';

		$_SERVER['REQUEST_URI']    = '/wp-json/batch/v1';
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$request = new WP_REST_Request( 'POST', '/batch/v1' );
		$request->set_body_params(
			array(
				'validation' => 'normal',
				'requests'   => array(
					array(
						'method' => 'POST',
						'path'   => '/wp/v2/users',
						'body'   => array(
							'username' => 'jwt_nonce_bypass_batch',
							'email'    => 'jwt-nonce-bypass-batch@example.invalid',
							'password' => 'Sup3r-Secret-Password!6',
							'roles'    => array( 'administrator' ),
						),
					),
				),
			)
		);

		$response = $this->dispatch_with_full_authentication( $request );
		$data     = $response->get_data();

		$this->assertFalse( username_exists( 'jwt_nonce_bypass_batch' ), 'No account should have been created via the batched sub-request.' );

		if ( isset( $data['responses'][0]['status'] ) ) {
			$this->assertSame( 401, $data['responses'][0]['status'], 'The batched sub-request must be rejected as unauthenticated, not used to create an account.' );
		} else {
			$this->assertSame( 401, $response->get_status(), 'If the batch request is not routed to sub-requests, the outer call must itself be rejected as unauthenticated.' );
		}
	}

	/**
	 * Positive control — a genuinely valid JWT token must still authenticate
	 * normally and correctly bypass the nonce requirement on a real CoCart
	 * route. This confirms the fix did not collaterally break legitimate
	 * JWT authentication while closing the cookie-based bypass.
	 *
	 * @return void
	 */
	public function test_valid_jwt_token_still_authenticates_and_bypasses_nonce_check() {
		$customer = $this->create_test_user( array( 'role' => 'customer' ) );
		$token    = $this->get_jwt_token_for_user( $customer->ID );

		$_SERVER['REQUEST_URI']        = '/wp-json/cocart/v2/cart';
		$_SERVER['REQUEST_METHOD']     = 'GET';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		unset( $_SERVER['HTTP_X_WP_NONCE'], $_REQUEST['_wpnonce'], $_GET['_wpnonce'] );

		$authenticated_user_id = $this->run_real_authenticate();

		$this->assertSame( $customer->ID, $authenticated_user_id, 'A valid JWT token must still authenticate the correct user.' );
		$this->assertTrue(
			$this->get_authentication_property( 'authenticated_by_cocart' ),
			'A valid JWT token must set authenticated_by_cocart so the nonce requirement is correctly bypassed.'
		);

		wp_set_current_user( $authenticated_user_id );

		$result = apply_filters( 'rest_authentication_errors', null );

		$this->assertTrue( $result, 'A genuinely JWT-authenticated request must not require a nonce.' );
		$this->assertSame( $customer->ID, get_current_user_id(), 'The JWT-authenticated user must remain the current user.' );
	}
}

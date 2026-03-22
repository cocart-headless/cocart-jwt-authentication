<?php
/**
 * CoCart JWT Authentication Test Case
 *
 * Provides JWT-specific testing functionality extending the CoCart API v2 base.
 *
 * @package CoCart JWT Authentication\Tests\Framework
 */

/**
 * CoCart JWT Test Case Class
 *
 * Base test case for JWT Authentication tests. Provides helpers for token
 * generation, JWT-authenticated requests, and consistent server environment
 * setup so that IP/User-Agent baked into tokens match at validation time.
 *
 * @package CoCart JWT Authentication\Tests\Framework
 */
abstract class CoCart_JWT_Test_Case extends CoCart_API_V2_Test_Case {

	/**
	 * Set up test environment.
	 *
	 * Sets consistent $_SERVER values before every test so that tokens generated
	 * during a test have the same IP/UA as when they are validated in the same test.
	 *
	 * Also disables rate limiting to allow repeated calls to JWT endpoints.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Set a consistent User-Agent. This value is baked into the JWT payload
		// at generation time (payload.data.user.device) and checked at validation.
		// Must be set BEFORE any call to generate_token().
		$_SERVER['HTTP_USER_AGENT'] = 'CoCart-JWT-PHPUnit-Test-Runner/1.0';

		// Ensure REMOTE_ADDR is set so CoCart_Authentication::get_ip_address()
		// returns a predictable value that matches the token payload.
		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		}

		// Disable rate limiting so repeated endpoint calls during tests do not
		// get rejected. jwt_rate_limits() reads $GLOBALS['wp']->query_vars['rest_route']
		// which is not populated in unit tests, so rate limiting is already a no-op,
		// but this filter provides an explicit safety net.
		add_filter(
			'cocart_api_rate_limit_options',
			function ( $opts ) {
				$opts['enabled'] = false;
				return $opts;
			}
		);

		// Ensure JWT is properly configured.
		if ( ! defined( 'COCART_JWT_AUTH_SECRET_KEY' ) || ! class_exists( 'CoCart\JWTAuthentication\REST' ) ) {
			$this->markTestSkipped( 'CoCart JWT Authentication is not available.' );
		}
	}

	/**
	 * Tear down test environment.
	 *
	 * Removes the HTTP_USER_AGENT server var to restore a clean environment.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_USER_AGENT'] );
		parent::tearDown();
	}

	/**
	 * Generate a JWT token for a user.
	 *
	 * Calls generate_token() directly via a REST instance. Must be called after
	 * setUp() has set $_SERVER['HTTP_USER_AGENT'] so the UA is baked correctly.
	 *
	 * @param int $user_id User ID to generate a token for.
	 *
	 * @return string The generated JWT token.
	 */
	protected function get_jwt_token_for_user( int $user_id ): string {
		$rest  = new \CoCart\JWTAuthentication\REST();
		$token = $rest->generate_token( $user_id );
		$this->assertNotWPError( $token, 'Token generation failed: ' . ( is_wp_error( $token ) ? $token->get_error_message() : '' ) );
		return $token;
	}

	/**
	 * Generate a JWT token and refresh token pair for a user.
	 *
	 * Both tokens are stored in user meta, ready for use in endpoint tests.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array {
	 *     @type string $token         The JWT access token.
	 *     @type string $refresh_token The refresh token.
	 * }
	 */
	protected function get_jwt_tokens_for_user( int $user_id ): array {
		$rest          = new \CoCart\JWTAuthentication\REST();
		$token         = $rest->generate_token( $user_id );
		$refresh_token = $rest->generate_refresh_token( $user_id );

		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'store_refresh_token_in_pat' );
		$method->setAccessible( true );
		$method->invoke( $rest, $user_id, $token, $refresh_token );

		$this->assertNotWPError( $token, 'Token generation failed.' );
		$this->assertIsString( $refresh_token, 'Refresh token generation failed.' );

		return compact( 'token', 'refresh_token' );
	}

	/**
	 * Make a REST request with a JWT Bearer token.
	 *
	 * @param string $method HTTP method (GET, POST, etc.).
	 * @param string $route  REST route (full path, e.g. '/cocart/jwt/validate-token').
	 * @param array  $params Request parameters.
	 * @param string $token  JWT token to send in the Authorization header.
	 *
	 * @return WP_REST_Response
	 */
	protected function jwt_request( string $method, string $route, array $params = array(), string $token = '' ): WP_REST_Response {
		$headers = $token ? array( 'Authorization' => 'Bearer ' . $token ) : array();

		// Preserve originals so we can restore them after dispatch.
		$prev_request_uri    = $_SERVER['REQUEST_URI'] ?? '';
		$prev_authorization  = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

		// CoCart::is_rest_api_request() checks $_SERVER['REQUEST_URI'] for the
		// CoCart namespace. Without it, authenticate() returns early and JWT auth
		// never fires. Set a fake URI that satisfies the check.
		$_SERVER['REQUEST_URI'] = '/wp-json/cocart/jwt/validate-token';

		// CoCart_Authentication::get_auth_header() reads $_SERVER directly,
		// not from the WP_REST_Request object. Set it so JWT auth fires.
		if ( $token ) {
			$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		}

		$response = $this->rest_request( $method, $route, $params, $headers );

		// Restore originals rather than unsetting — WP cron reads REQUEST_URI
		// after dispatch and throws a deprecation notice if it is null.
		$_SERVER['REQUEST_URI'] = $prev_request_uri;
		if ( is_null( $prev_authorization ) ) {
			unset( $_SERVER['HTTP_AUTHORIZATION'] );
		} else {
			$_SERVER['HTTP_AUTHORIZATION'] = $prev_authorization;
		}

		return $response;
	}

	/**
	 * Call perform_jwt_authentication() with $_SERVER vars set and safely restored.
	 *
	 * Restores REQUEST_URI to its previous value after the call so WP cron code
	 * reading $_SERVER['REQUEST_URI'] after tearDown does not receive null.
	 *
	 * @param string $token JWT bearer token to test with.
	 *
	 * @return int The user ID returned by perform_jwt_authentication(), or 0.
	 */
	protected function perform_jwt_auth( string $token ): int {
		$prev_request_uri   = $_SERVER['REQUEST_URI'] ?? '';
		$prev_authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

		$_SERVER['REQUEST_URI']        = '/wp-json/cocart/jwt/validate-token';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

		$rest   = new \CoCart\JWTAuthentication\REST();
		$auth   = new CoCart_Authentication();
		$result = $rest->perform_jwt_authentication( 0, false, $auth );

		$_SERVER['REQUEST_URI'] = $prev_request_uri;
		if ( is_null( $prev_authorization ) ) {
			unset( $_SERVER['HTTP_AUTHORIZATION'] );
		} else {
			$_SERVER['HTTP_AUTHORIZATION'] = $prev_authorization;
		}

		return $result;
	}

	/**
	 * Create a test WordPress user.
	 *
	 * @param array $args Optional overrides for wp_insert_user().
	 *
	 * @return WP_User
	 */
	protected function create_test_user( array $args = array() ): WP_User {
		$defaults = array(
			'user_login' => 'jwt_test_user_' . wp_rand( 1000, 9999 ),
			'user_pass'  => 'password123',
			'user_email' => 'jwt_test_' . wp_rand( 1000, 9999 ) . '@example.com',
			'role'       => 'customer',
		);

		$args    = array_merge( $defaults, $args );
		$user_id = wp_insert_user( $args );

		$this->assertNotWPError( $user_id, 'Failed to create test user.' );

		return get_user_by( 'id', $user_id );
	}
}

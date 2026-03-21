<?php
/**
 * Test CoCart JWT Decode Token
 *
 * Low-level unit tests for token parsing utilities in the Tokens abstract class,
 * accessed via the concrete REST class. These tests do not make any HTTP requests.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Decode Token Class
 *
 * Tests decode_token(), is_bearer_auth(), extract_bearer_token(), and
 * is_token_expired() methods exposed as protected/public on the REST class.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Decode_Token extends CoCart_JWT_Test_Case {

	/**
	 * @var WP_User Test user.
	 */
	private WP_User $user;

	/**
	 * @var \CoCart\JWTAuthentication\REST REST instance for direct method calls.
	 */
	private \CoCart\JWTAuthentication\REST $rest;

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->create_test_user();
		$this->rest = new \CoCart\JWTAuthentication\REST();
	}

	/**
	 * Test that decode_token() returns an object with the expected properties.
	 *
	 * @return void
	 */
	public function test_decode_token_returns_expected_properties() {
		$token   = $this->get_jwt_token_for_user( $this->user->ID );
		$decoded = $this->rest->decode_token( $token );

		$this->assertIsObject( $decoded );
		$this->assertTrue( property_exists( $decoded, 'header' ) );
		$this->assertTrue( property_exists( $decoded, 'payload' ) );
		$this->assertTrue( property_exists( $decoded, 'signature' ) );
	}

	/**
	 * Test that the decoded token header has alg set to HS256.
	 *
	 * @return void
	 */
	public function test_decode_token_header_alg_is_hs256() {
		$token   = $this->get_jwt_token_for_user( $this->user->ID );
		$decoded = $this->rest->decode_token( $token );

		$this->assertEquals( 'HS256', $decoded->header->alg );
	}

	/**
	 * Test that the decoded token header has typ set to JWT.
	 *
	 * @return void
	 */
	public function test_decode_token_header_typ_is_jwt() {
		$token   = $this->get_jwt_token_for_user( $this->user->ID );
		$decoded = $this->rest->decode_token( $token );

		$this->assertEquals( 'JWT', $decoded->header->typ );
	}

	/**
	 * Test that decode_token() throws an Exception for a garbage string.
	 *
	 * @return void
	 */
	public function test_decode_token_invalid_format_throws_exception() {
		$this->expectException( Exception::class );
		$this->rest->decode_token( 'this-is-not-a-jwt' );
	}

	/**
	 * Test that decode_token() throws an Exception for a two-part token.
	 *
	 * @return void
	 */
	public function test_decode_token_two_parts_throws_exception() {
		$this->expectException( Exception::class );
		$this->rest->decode_token( 'header.payload' );
	}

	/**
	 * Test that is_bearer_auth() returns true for a valid Bearer header.
	 *
	 * @return void
	 */
	public function test_is_bearer_auth_true_for_bearer_header() {
		// is_bearer_auth() is protected, but accessible via REST which extends Tokens.
		// Use a real generated token so the pattern matches.
		$token = $this->get_jwt_token_for_user( $this->user->ID );

		// Reflect to call the protected method.
		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'is_bearer_auth' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->rest, 'Bearer ' . $token );
		$this->assertTrue( (bool) $result );
	}

	/**
	 * Test that is_bearer_auth() returns false for a Basic auth header.
	 *
	 * @return void
	 */
	public function test_is_bearer_auth_false_for_basic() {
		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'is_bearer_auth' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->rest, 'Basic dXNlcjpwYXNz' );
		$this->assertFalse( (bool) $result );
	}

	/**
	 * Test that is_bearer_auth() returns false for an empty string.
	 *
	 * @return void
	 */
	public function test_is_bearer_auth_false_for_empty_string() {
		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'is_bearer_auth' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->rest, '' );
		$this->assertFalse( (bool) $result );
	}

	/**
	 * Test that extract_bearer_token() returns the token string.
	 *
	 * @return void
	 */
	public function test_extract_bearer_token_returns_token_string() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );

		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'extract_bearer_token' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->rest, 'Bearer ' . $token );
		$this->assertEquals( $token, $result );
	}

	/**
	 * Test that extract_bearer_token() returns null for a non-Bearer header.
	 *
	 * @return void
	 */
	public function test_extract_bearer_token_returns_null_for_non_bearer() {
		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'extract_bearer_token' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->rest, 'Basic dXNlcjpwYXNz' );
		$this->assertNull( $result );
	}

	/**
	 * Test that is_token_expired() returns false for a freshly generated token.
	 *
	 * @return void
	 */
	public function test_is_token_expired_false_for_fresh_token() {
		$token = $this->get_jwt_token_for_user( $this->user->ID );

		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'is_token_expired' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->rest, $token );
		$this->assertFalse( $result );
	}

	/**
	 * Test that is_token_expired() returns true for a token with a past expiry.
	 *
	 * @return void
	 */
	public function test_is_token_expired_true_for_expired_token() {
		// Generate token with immediate expiry.
		add_filter( 'cocart_jwt_auth_expire', function () {
			return -3600; // Expired 1 hour ago.
		} );

		$rest  = new \CoCart\JWTAuthentication\REST();
		$token = $rest->generate_token( $this->user->ID );

		remove_all_filters( 'cocart_jwt_auth_expire' );

		$method = new ReflectionMethod( \CoCart\JWTAuthentication\REST::class, 'is_token_expired' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->rest, $token );
		$this->assertTrue( $result );
	}
}

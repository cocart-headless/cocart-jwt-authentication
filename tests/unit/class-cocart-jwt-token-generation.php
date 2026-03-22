<?php
/**
 * Test CoCart JWT Token Generation
 *
 * Tests for JWT token and refresh token generation, storage in user meta,
 * and payload structure validation.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Token Generation Class
 *
 * Tests generate_token() and generate_refresh_token() methods of the Tokens
 * abstract class via the concrete REST class.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Token_Generation extends CoCart_JWT_Test_Case {

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
	 * Test that generate_token() returns a string.
	 *
	 * @return void
	 */
	public function test_generate_token_returns_string() {
		$rest  = new \CoCart\JWTAuthentication\REST();
		$token = $rest->generate_token( $this->user->ID );

		$this->assertIsString( $token );
	}

	/**
	 * Test that the generated token has three dot-separated parts (valid JWT format).
	 *
	 * @return void
	 */
	public function test_generate_token_is_valid_jwt_format() {
		$rest  = new \CoCart\JWTAuthentication\REST();
		$token = $rest->generate_token( $this->user->ID );

		$this->assertIsString( $token );
		$parts = explode( '.', $token );
		$this->assertCount( 3, $parts, 'JWT must have exactly 3 dot-separated parts (header.payload.signature).' );
	}

	/**
	 * Test that generate_token() stores the token in _cocart_jwt_tokens user meta.
	 *
	 * @return void
	 */
	public function test_generate_token_stores_in_user_meta() {
		$rest  = new \CoCart\JWTAuthentication\REST();
		$token = $rest->generate_token( $this->user->ID );

		$user_tokens = get_user_meta( $this->user->ID, '_cocart_jwt_tokens', true );

		$this->assertIsArray( $user_tokens );
		$this->assertContains( $token, $user_tokens );
	}

	/**
	 * Test that generate_token() stores PAT data in _cocart_jwt_token_pat user meta.
	 *
	 * @return void
	 */
	public function test_generate_token_pat_stored_in_user_meta() {
		$rest = new \CoCart\JWTAuthentication\REST();
		$rest->generate_token( $this->user->ID );

		$pat_data = get_user_meta( $this->user->ID, '_cocart_jwt_token_pat' );

		$this->assertNotEmpty( $pat_data );
	}

	/**
	 * Test that the PAT key has the correct 'pat_' prefix.
	 *
	 * @return void
	 */
	public function test_generate_token_pat_has_correct_prefix() {
		$rest  = new \CoCart\JWTAuthentication\REST();
		$token = $rest->generate_token( $this->user->ID );

		$user_tokens = get_user_meta( $this->user->ID, '_cocart_jwt_tokens', true );

		$this->assertIsArray( $user_tokens );

		foreach ( array_keys( $user_tokens ) as $pat_key ) {
			$this->assertStringStartsWith( 'pat_', $pat_key, 'PAT key must start with "pat_".' );
		}
	}

	/**
	 * Test that the token payload contains the correct user ID.
	 *
	 * @return void
	 */
	public function test_generate_token_payload_contains_user_id() {
		$rest    = new \CoCart\JWTAuthentication\REST();
		$token   = $rest->generate_token( $this->user->ID );
		$decoded = $rest->decode_token( $token );

		$this->assertTrue( property_exists( $decoded->payload->data->user, 'id' ) );
		$this->assertEquals( $this->user->ID, $decoded->payload->data->user->id );
	}

	/**
	 * Test that the token payload contains the IP address.
	 *
	 * @return void
	 */
	public function test_generate_token_payload_contains_ip() {
		$rest    = new \CoCart\JWTAuthentication\REST();
		$token   = $rest->generate_token( $this->user->ID );
		$decoded = $rest->decode_token( $token );

		$this->assertTrue( property_exists( $decoded->payload->data->user, 'ip' ) );
		$this->assertNotEmpty( $decoded->payload->data->user->ip );
	}

	/**
	 * Test that the token payload contains the device (User-Agent).
	 *
	 * @return void
	 */
	public function test_generate_token_payload_contains_device() {
		$rest    = new \CoCart\JWTAuthentication\REST();
		$token   = $rest->generate_token( $this->user->ID );
		$decoded = $rest->decode_token( $token );

		$this->assertTrue( property_exists( $decoded->payload->data->user, 'device' ) );
		$this->assertEquals( 'CoCart-JWT-PHPUnit-Test-Runner/1.0', $decoded->payload->data->user->device );
	}

	/**
	 * Test that the token payload iss matches the site URL.
	 *
	 * @return void
	 */
	public function test_generate_token_payload_iss_matches_site() {
		$rest    = new \CoCart\JWTAuthentication\REST();
		$token   = $rest->generate_token( $this->user->ID );
		$decoded = $rest->decode_token( $token );

		$this->assertEquals( get_bloginfo( 'url' ), $decoded->payload->iss );
	}

	/**
	 * Test that generate_token() returns WP_Error when secret key is absent.
	 *
	 * @return void
	 */
	public function test_generate_token_without_secret_returns_error() {
		// Override the secret key filter to return null.
		add_filter( 'cocart_jwt_auth_secret_private_key', '__return_null' );

		$rest   = new \CoCart\JWTAuthentication\REST();
		$result = $rest->generate_token( $this->user->ID );

		remove_filter( 'cocart_jwt_auth_secret_private_key', '__return_null' );

		$this->assertWPError( $result );
	}

	/**
	 * Test that generate_refresh_token() returns a string.
	 *
	 * @return void
	 */
	public function test_generate_refresh_token_returns_string() {
		$rest          = new \CoCart\JWTAuthentication\REST();
		$refresh_token = $rest->generate_refresh_token( $this->user->ID );

		$this->assertIsString( $refresh_token );
		$this->assertNotEmpty( $refresh_token );
	}

	/**
	 * Test that the refresh token is a hex string (128 chars = 64 bytes in hex).
	 *
	 * @return void
	 */
	public function test_generate_refresh_token_is_hex_string() {
		$rest          = new \CoCart\JWTAuthentication\REST();
		$refresh_token = $rest->generate_refresh_token( $this->user->ID );

		// bin2hex(random_bytes(64)) produces 128 hex chars.
		$this->assertEquals( 128, strlen( $refresh_token ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]+$/', $refresh_token );
	}

	/**
	 * Test that generate_refresh_token() stores the token in user meta.
	 *
	 * @return void
	 */
	public function test_generate_refresh_token_stored_in_user_meta() {
		$rest          = new \CoCart\JWTAuthentication\REST();
		$refresh_token = $rest->generate_refresh_token( $this->user->ID );

		$stored = get_user_meta( $this->user->ID, '_cocart_jwt_refresh_tokens', true );

		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( $refresh_token, $stored );
	}

	/**
	 * Test that two separate generate_token() calls both end up in user meta.
	 *
	 * @return void
	 */
	public function test_multiple_tokens_for_same_user() {
		$rest   = new \CoCart\JWTAuthentication\REST();
		$token1 = $rest->generate_token( $this->user->ID );
		$token2 = $rest->generate_token( $this->user->ID );

		$user_tokens = get_user_meta( $this->user->ID, '_cocart_jwt_tokens', true );

		$this->assertIsArray( $user_tokens );
		$this->assertContains( $token1, $user_tokens );
		$this->assertContains( $token2, $user_tokens );
		$this->assertCount( 2, $user_tokens );
	}
}

<?php
/**
 * Test CoCart JWT Refresh Token
 *
 * Tests for POST /cocart/jwt/refresh-token endpoint which validates a refresh
 * token and issues a new JWT access token + refresh token pair.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */

/**
 * Test CoCart JWT Refresh Token Class
 *
 * Tests the refresh_token() method exposed at POST /cocart/jwt/refresh-token.
 *
 * @package CoCart JWT Authentication\Tests\Unit
 */
class Test_CoCart_JWT_Refresh_Token extends CoCart_JWT_Test_Case {

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
	 * Test that a valid refresh token returns 200.
	 *
	 * @return void
	 */
	public function test_refresh_token_returns_200() {
		$tokens   = $this->get_jwt_tokens_for_user( $this->user->ID );
		$response = $this->rest_post( '/cocart/jwt/refresh-token', array(
			'refresh_token' => $tokens['refresh_token'],
		) );

		$this->assert_rest_response_status( 200, $response );
	}

	/**
	 * Test that the refresh response contains a token key.
	 *
	 * @return void
	 */
	public function test_refresh_response_has_token_key() {
		$tokens   = $this->get_jwt_tokens_for_user( $this->user->ID );
		$response = $this->rest_post( '/cocart/jwt/refresh-token', array(
			'refresh_token' => $tokens['refresh_token'],
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'token', $data );
	}

	/**
	 * Test that the refresh response contains a refresh_token key.
	 *
	 * @return void
	 */
	public function test_refresh_response_has_refresh_token_key() {
		$tokens   = $this->get_jwt_tokens_for_user( $this->user->ID );
		$response = $this->rest_post( '/cocart/jwt/refresh-token', array(
			'refresh_token' => $tokens['refresh_token'],
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'refresh_token', $data );
	}

	/**
	 * Test that the new token from the refresh response is a valid JWT format.
	 *
	 * @return void
	 */
	public function test_refresh_response_token_is_valid_format() {
		$tokens   = $this->get_jwt_tokens_for_user( $this->user->ID );
		$response = $this->rest_post( '/cocart/jwt/refresh-token', array(
			'refresh_token' => $tokens['refresh_token'],
		) );

		$this->assert_rest_response_status( 200, $response );

		$data  = $response->get_data();
		$parts = explode( '.', $data['token'] );
		$this->assertCount( 3, $parts, 'New token must be in header.payload.signature format.' );
	}

	/**
	 * Test that refreshing destroys the old PAT session.
	 *
	 * After a successful refresh, the old PAT entry should be gone from
	 * _cocart_jwt_token_pat and a new one should exist.
	 *
	 * @return void
	 */
	public function test_refresh_destroys_old_pat() {
		$tokens = $this->get_jwt_tokens_for_user( $this->user->ID );

		// Get the current PAT ID from the token before refreshing.
		$rest           = new \CoCart\JWTAuthentication\REST();
		$old_decoded    = $rest->decode_token( $tokens['token'] );
		$old_pat_id     = $old_decoded->payload->data->user->pat;

		// Perform the refresh.
		$response = $this->rest_post( '/cocart/jwt/refresh-token', array(
			'refresh_token' => $tokens['refresh_token'],
		) );

		$this->assert_rest_response_status( 200, $response );

		// The old PAT should no longer be in _cocart_jwt_token_pat.
		$pat_data = get_user_meta( $this->user->ID, '_cocart_jwt_token_pat' );
		$old_pat_found = false;

		foreach ( $pat_data as $pat_entry ) {
			if ( array_key_exists( $old_pat_id, $pat_entry ) ) {
				$old_pat_found = true;
				break;
			}
		}

		$this->assertFalse( $old_pat_found, 'Old PAT should be destroyed after token refresh.' );
	}

	/**
	 * Test that an invalid refresh token returns 403.
	 *
	 * @return void
	 */
	public function test_invalid_refresh_token_returns_403() {
		$response = $this->rest_post( '/cocart/jwt/refresh-token', array(
			'refresh_token' => 'this-is-not-a-valid-refresh-token',
		) );

		$this->assert_rest_response_status( 403, $response );
	}

	/**
	 * Test that an expired refresh token returns 403.
	 *
	 * @return void
	 */
	public function test_expired_refresh_token_returns_403() {
		$rest          = new \CoCart\JWTAuthentication\REST();
		$refresh_token = $rest->generate_refresh_token( $this->user->ID );

		// Manually set the expiration to the past.
		$refresh_tokens                  = get_user_meta( $this->user->ID, '_cocart_jwt_refresh_tokens', true );
		$refresh_tokens[ $refresh_token ] = time() - 3600; // Expired 1 hour ago.
		update_user_meta( $this->user->ID, '_cocart_jwt_refresh_tokens', $refresh_tokens );

		$response = $this->rest_post( '/cocart/jwt/refresh-token', array(
			'refresh_token' => $refresh_token,
		) );

		$this->assert_rest_response_status( 403, $response );
	}

	/**
	 * Test that missing the refresh_token parameter returns an error (400 or 403).
	 *
	 * @return void
	 */
	public function test_missing_refresh_token_param_returns_error() {
		$response = $this->rest_post( '/cocart/jwt/refresh-token', array() );

		$status = $response->get_status();
		$this->assertContains( $status, array( 400, 403 ), 'Missing refresh_token param must return 400 or 403.' );
	}
}

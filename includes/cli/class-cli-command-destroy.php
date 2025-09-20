<?php
/**
 * CoCart JWT Authentication - CLI Command Destroy.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @since   3.0.0
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication;

use CoCart\JWTAuthentication\Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Destroy tokens for a specific user.
 *
 * @class CoCart\JWTAuthentication\CLI_Command_Destroy
 */
class CLI_Command_Destroy extends Tokens {

	/**
	 * Destroy tokens for a specific user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, email, or login to destroy tokens for.
	 *
	 * [--pat=<pat_id>]
	 * : Specific PAT ID to destroy (optional - destroys specific token).
	 *
	 * [--force]
	 * : Force destroying of all tokens by user without confirmation.
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt destroy 1
	 * wp cocart jwt destroy admin@example.com --force
	 * wp cocart jwt destroy username --pat=pat_abc123
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 *
	 * @return void
	 */
	public function destroy( $args, $assoc_args ) {
		// Validate required arguments.
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( 'Please provide a user ID, email, or login.' );
		}

		$user_identifier = $args[0];
		$force           = isset( $assoc_args['force'] ) ? (bool) $assoc_args['force'] : false;
		$specific_pat    = isset( $assoc_args['pat'] ) ? sanitize_text_field( $assoc_args['pat'] ) : null;

		// Validate user exists.
		$user = $this->is_user_valid( $user_identifier );
		if ( is_wp_error( $user ) || ! $user ) {
			\WP_CLI::error( sprintf( 'User "%s" not found.', $user_identifier ) );
		}

		// Get user tokens.
		$user_tokens = get_user_meta( $user->ID, '_cocart_jwt_tokens', true );
		$pat_data    = get_user_meta( $user->ID, '_cocart_jwt_token_pat' );

		if ( empty( $user_tokens ) && empty( $pat_data ) ) {
			\WP_CLI::success( sprintf( 'User "%s" has no JWT tokens to destroy.', $user->user_login ) );
			return;
		}

		// Handle specific PAT destruction.
		if ( ! is_null( $specific_pat ) ) {
			$this->destroy_specific_pat( $user, $specific_pat );
			return;
		}

		// Handle all tokens destruction.
		$this->destroy_all_user_tokens( $user, $force );
	} // END destroy()

	/**
	 * Destroy a specific PAT token for a user.
	 *
	 * @access private
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param \WP_User $user   User object.
	 * @param string   $pat_id PAT ID to destroy.
	 *
	 * @return void
	 */
	private function destroy_specific_pat( $user, $pat_id ) {
		// Check if PAT exists.
		$user_tokens = get_user_meta( $user->ID, '_cocart_jwt_tokens', true );

		if ( ! is_array( $user_tokens ) || ! isset( $user_tokens[ $pat_id ] ) ) {
			\WP_CLI::error( sprintf( 'PAT "%s" not found for user "%s".', $pat_id, $user->user_login ) );
		}

		// Use the existing destroy_pat_session method from parent Tokens class.
		$destroyed = $this->destroy_pat_session( $user->ID, $pat_id );

		if ( $destroyed ) {
			\WP_CLI::success( sprintf( 'Successfully destroyed PAT "%s" for user "%s".', $pat_id, $user->user_login ) );
		} else {
			\WP_CLI::error( sprintf( 'Failed to destroy PAT "%s" for user "%s".', $pat_id, $user->user_login ) );
		}
	} // END destroy_specific_pat()

	/**
	 * Destroy all tokens for a user.
	 *
	 * @access private
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param \WP_User $user  User object.
	 * @param bool     $force Whether to skip confirmation.
	 *
	 * @return void
	 */
	private function destroy_all_user_tokens( $user, $force ) {
		// Count tokens to destroy.
		$user_tokens    = get_user_meta( $user->ID, '_cocart_jwt_tokens', true );
		$refresh_tokens = get_user_meta( $user->ID, '_cocart_jwt_refresh_tokens', true );

		$token_count         = is_array( $user_tokens ) ? count( $user_tokens ) : 0;
		$refresh_token_count = is_array( $refresh_tokens ) ? count( $refresh_tokens ) : 0;
		$total_count         = $token_count + $refresh_token_count;

		if ( 0 === $total_count ) {
			\WP_CLI::success( sprintf( 'User "%s" has no tokens to destroy.', $user->user_login ) );
			return;
		}

		// Confirmation prompt unless forced.
		if ( ! $force ) {
			\WP_CLI::confirm( sprintf( 'Are you sure you want to destroy ALL %d tokens for user "%s"? This action cannot be undone.', $total_count, $user->user_login ) );
		}

		// Destroy all tokens using the existing method.
		$this->destroy_all_tokens( $user->ID );

		\WP_CLI::success( sprintf( 'Successfully destroyed all tokens (%d JWT tokens, %d refresh tokens) for user "%s".', $token_count, $refresh_token_count, $user->user_login ) );
	} // END destroy_all_user_tokens()

	/**
	 * Destroy all tokens for a user (wrapper for existing functionality).
	 *
	 * @access private
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	private function destroy_all_tokens( $user_id ) {
		delete_user_meta( $user_id, '_cocart_jwt_token' );
		delete_user_meta( $user_id, '_cocart_jwt_tokens' );
		delete_user_meta( $user_id, '_cocart_jwt_token_pat' );
		delete_user_meta( $user_id, '_cocart_jwt_refresh_tokens' );
		delete_user_meta( $user_id, '_cocart_jwt_refresh_token' );
	} // END destroy_all_tokens()
} // END class

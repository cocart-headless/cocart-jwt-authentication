<?php
/**
 * CoCart JWT Authentication - CLI Command Cleanup.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication;

use CoCart\JWTAuthentication\Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clean up expired tokens or force all tokens to be cleared.
 *
 * @class CoCart\JWTAuthentication\CLI_Command_Cleanup
 */
class CLI_Command_Cleanup extends Tokens {

	/**
	 * Clean up expired tokens or force all tokens to be cleared.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<number>]
	 * : Number of users to process per batch.
	 * ---
	 * default: 100
	 *
	 * [--force]
	 * : Force cleanup of all tokens.
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt cleanup --batch-size=50
	 * wp cocart jwt cleanup --force
	 *
	 * @access public
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function cleanup( $args, $assoc_args ) {
		$batch_size = isset( $assoc_args['batch-size'] ) ? intval( $assoc_args['batch-size'] ) : 100;
		$force      = isset( $assoc_args['force'] ) ? (bool) $assoc_args['force'] : false;

		$total_users = count( get_users( array(
			'meta_key'     => '_cocart_jwt_tokens', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
			'fields'       => 'ID',
		) ) );

		$progress = \WP_CLI\Utils\make_progress_bar( __( 'Cleaning up tokens', 'cocart-jwt-authentication' ), $total_users );

		$offset = 0;

		do {
			$users = get_users( array(
				'meta_key'     => '_cocart_jwt_tokens', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
				'number'       => $batch_size,
				'offset'       => $offset,
			) );

			$users_count = count( $users );

			foreach ( $users as $user ) {
				if ( $force ) {
					delete_user_meta( $user->ID, '_cocart_jwt_tokens' );
				} else {
					$user_tokens = get_user_meta( $user->ID, '_cocart_jwt_tokens', true );

					foreach ( $user_tokens as $id => $token ) {
						if ( $this->is_token_expired( $token ) ) {
							unset( $user_tokens[ $id ] );
						}
					}

					update_user_meta( $user->ID, '_cocart_jwt_tokens', $user_tokens );
				}

				$progress->tick();
			}

			$offset += $batch_size;
		} while ( $users_count === $batch_size );

		$progress->finish();
	} // END cleanup()
} // END class

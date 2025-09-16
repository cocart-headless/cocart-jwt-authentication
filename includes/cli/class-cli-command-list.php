<?php
/**
 * CoCart JWT Authentication - CLI Command List.
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
 * List all active JWT tokens.
 *
 * @class CoCart\JWTAuthentication\CLI_Command_List
 */
class CLI_Command_List extends Tokens {

	/**
	 * List all active JWT tokens.
	 *
	 * ## OPTIONS
	 *
	 * [--page=<number>]
	 * : Page number to display.
	 * ---
	 * default: 1
	 *
	 * [--per-page=<number>]
	 * : Number of tokens to display per page.
	 * ---
	 * default: 20
	 *
	 * [--field=<field>]
	 * : Display only a specific field in copy-friendly format.
	 * ---
	 * options:
	 *   - pat
	 *   - token
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt list --page=2 --per-page=10
	 * wp cocart jwt list --field=token
	 * wp cocart jwt list --field=pat
	 *
	 * @access public
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function list( $args, $assoc_args ) {
		$page     = isset( $assoc_args['page'] ) ? intval( $assoc_args['page'] ) : 1;
		$per_page = isset( $assoc_args['per-page'] ) ? intval( $assoc_args['per-page'] ) : 20;
		$field    = isset( $assoc_args['field'] ) ? $assoc_args['field'] : null;

		// Validate field parameter.
		if ( $field && ! in_array( $field, array( 'pat', 'token' ) ) ) {
			\WP_CLI::error(
				sprintf(
					/* translators: 1: Invalid field. 2: Allowed fields. */
					__( 'Invalid field %1$s. Allowed fields: %2$s', 'cocart-jwt-authentication' ),
					$field,
					'pat, token'
				)
			);
		}

		// Get all users with tokens.
		$users = get_users( array(
			'meta_key'     => '_cocart_jwt_tokens', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
		) );

		$tokens = array();

		foreach ( $users as $user ) {
			$user_tokens = get_user_meta( $user->ID, '_cocart_jwt_tokens', true );

			if ( ! is_array( $user_tokens ) || empty( $user_tokens ) ) {
				continue;
			}

			// Get PAT data for last-used timestamps.
			$pat_data = get_user_meta( $user->ID, '_cocart_jwt_token_pat' );

			foreach ( $user_tokens as $id => $token ) {
				// Check the token is a string and not expired.
				if ( is_string( $token ) && ! $this->is_token_expired( $token ) ) {
					// Find last-used timestamp from PAT data.
					$last_used = '';

					foreach ( $pat_data as $pat_entry ) {
						if ( array_key_exists( $id, $pat_entry ) && ! empty( $pat_entry[ $id ]['last-used'] ) ) {
							$last_used = date_i18n( 'Y-m-d H:i:s', $pat_entry[ $id ]['last-used'] );
							break;
						}
					}

					$tokens[] = array(
						'user_id'   => $user->ID,
						'pat'       => $id,
						'token'     => $token,
						'created'   => $this->get_token_creation_time( $token ),
						'last-used' => $last_used ? $last_used : __( 'Never', 'cocart-jwt-authentication' ),
					);
				}
			}
		}

		if ( empty( $tokens ) ) {
			\WP_CLI::log( __( 'No tokens found.', 'cocart-jwt-authentication' ) );
			return;
		}

		// Apply pagination to tokens (not users).
		$total_tokens = count( $tokens );
		$offset       = ( $page - 1 ) * $per_page;
		$tokens       = array_slice( $tokens, $offset, $per_page );

		// Show pagination info.
		$showing_count = count( $tokens );
		$start_num     = $offset + 1;
		$end_num       = $offset + $showing_count;

		\WP_CLI::log( ' ' );
		\WP_CLI::log( '####################################################' );

		// Handle field-specific output.
		if ( $field ) {
			\WP_CLI::log(
				sprintf(
					/* translators: 1: Showing count. 2: Total tokens. 3: Field name. 4: Start number. 5: End number. */
					__( 'Showing %1$s of %2$s field: %3$s (items %4$s-%5$s) - (copy-friendly format)', 'cocart-jwt-authentication' ),
					$showing_count,
					$total_tokens,
					$field,
					$start_num,
					$end_num
				)
			);
			\WP_CLI::log( '####################################################' );

			foreach ( $tokens as $token ) {
				if ( isset( $token[ $field ] ) ) {
					\WP_CLI::line( $token[ $field ] );
					\WP_CLI::line( '' );
				}
			}
		} else {
			// Default table output.
			\WP_CLI::log(
				sprintf(
					/* translators: 1: Showing count. 2: Total tokens. 3: Start number. 4: End number. */
					__( 'Showing %1$s of %2$s tokens (items %3$s-%4$s)', 'cocart-jwt-authentication' ),
					$showing_count,
					$total_tokens,
					$start_num,
					$end_num
				)
			);
			\WP_CLI::log( '####################################################' );

			// Enhanced table with word wrapping and auto terminal width detection.
			Table_Formatter::prettier_table( $tokens, array( 'user_id', 'pat', 'token', 'created', 'last-used' ), 44 );
		}
	} // END list()
} // END class

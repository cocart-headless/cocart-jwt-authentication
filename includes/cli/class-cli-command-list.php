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
	 * ## EXAMPLES
	 *
	 * wp cocart jwt list --page=2 --per-page=10
	 *
	 * @access public
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function list( $args, $assoc_args ) {
		$page     = isset( $assoc_args['page'] ) ? intval( $assoc_args['page'] ) : 1;
		$per_page = isset( $assoc_args['per-page'] ) ? intval( $assoc_args['per-page'] ) : 20;

		$users = get_users( array(
			'meta_key'     => '_cocart_jwt_tokens', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_compare' => 'EXISTS',
			'number'       => $per_page,
			'offset'       => ( $page - 1 ) * $per_page,
		) );

		$tokens = array();

		foreach ( $users as $user ) {
			$user_tokens = get_user_meta( $user->ID, '_cocart_jwt_tokens', true );

			if ( ! is_array( $user_tokens ) || empty( $user_tokens ) ) {
				continue;
			}

			foreach ( $user_tokens as $id => $token ) {
				// Check the token is a string and not expired.
				if ( is_string( $token ) && ! $this->is_token_expired( $token ) ) {
					$tokens[] = array(
						'user_id' => $user->ID,
						'pat'     => $id,
						'token'   => $token,
						'created' => $this->get_token_creation_time( $token ),
					);
				}
			}
		}

		\WP_CLI::log( '####################################################' );

		if ( empty( $tokens ) ) {
			\WP_CLI::log( __( 'No tokens found.', 'cocart-jwt-authentication' ) );
		} else {
			\WP_CLI::log( '# Tokens' );

			$this->pretty_table_wrapped( $tokens, array( 'user_id', 'pat', 'token', 'created' ), 44 );
		}
	} // END list()

	/**
	 * Pretty print a table with word wrapping for long text fields.
	 *
	 * @access private
	 *
	 * @param array $items      Array of associative arrays representing rows.
	 * @param array $fields     Fields to display in the table.
	 * @param int   $wrap_width Maximum width before wrapping text.
	 *
	 * @return void
	 */
	private function pretty_table_wrapped( $items, $fields, $wrap_width = 20 ) {
		if ( empty( $items ) ) {
			\WP_CLI::line( 'No items.' );
			return;
		}

		// Prepare rows: wrap each cell.
		$rows       = array();
		$col_widths = array();

		foreach ( $fields as $field ) {
			$col_widths[ $field ] = strlen( $field );
		}

		foreach ( $items as $item ) {
			$wrapped_row = array();

			foreach ( $fields as $field ) {
				$value = (string) ( $item[ $field ] ?? '' );

				// Wrap value.
				$lines                 = str_split( $value, $wrap_width );
				$wrapped_row[ $field ] = $lines;

				// Update column width.
				foreach ( $lines as $line ) {
					$col_widths[ $field ] = max( $col_widths[ $field ], strlen( $line ) );
				}
			}

			$rows[] = $wrapped_row;
		}

		// Build separator line.
		$sep = '+';
		foreach ( $fields as $field ) {
			$sep .= str_repeat( '-', $col_widths[ $field ] + 2 ) . '+';
		}

		// Print header.
		\WP_CLI::line( $sep );

		$header = '|';
		foreach ( $fields as $field ) {
			$header .= ' ' . str_pad( $field, $col_widths[ $field ] ) . ' |';
		}

		\WP_CLI::line( $header );
		\WP_CLI::line( $sep );

		// Print rows.
		foreach ( $rows as $row ) {
			$max_lines = max( array_map( 'count', $row ) );

			for ( $i = 0; $i < $max_lines; $i++ ) {
				$line = '|';

				foreach ( $fields as $field ) {
					$cell_line = $row[ $field ][ $i ] ?? '';
					$line     .= ' ' . str_pad( $cell_line, $col_widths[ $field ] ) . ' |';
				}

				\WP_CLI::line( $line );
			}

			\WP_CLI::line( $sep );
		}
	} // END pretty_table_wrapped()
} // END class

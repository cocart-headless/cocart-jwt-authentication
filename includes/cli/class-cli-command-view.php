<?php
/**
 * CoCart JWT Authentication - CLI Command View.
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
 * View details of a JWT token.
 *
 * @class CoCart\JWTAuthentication\CLI_Command_View
 */
class CLI_Command_View extends Tokens {

	/**
	 * View details of a JWT token.
	 *
	 * ## OPTIONS
	 *
	 * <token>
	 * : The JWT token to view.
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt view <token>
	 *
	 * @access public
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function view( $args, $assoc_args ) {
		list( $token ) = $args;

		if ( empty( $token ) ) {
			\WP_CLI::error( __( 'Token is required.', 'cocart-jwt-authentication' ) );
		}

		try {
			$decoded_token = $this->decode_token( $token );
			\WP_CLI::log( '####################################################' );
			\WP_CLI::log( __( 'Header Encoded', 'cocart-jwt-authentication' ) . ': ' . $decoded_token->header_encoded );
			\WP_CLI::log( '####################################################' );
			\WP_CLI::log( '# ' . __( 'Token details', 'cocart-jwt-authentication' ) );

			$table = array();

			// Add header data.
			foreach ( $decoded_token->header as $key => $value ) {
				$table[] = array(
					'Key'   => $key,
					'Value' => $value,
				);
			}

			// Add payload data.
			foreach ( $decoded_token->payload as $key => $value ) {
				if ( 'data' === $key && isset( $value->user ) ) {
					// Handle user data separately.
					foreach ( get_object_vars( $value->user ) as $user_key => $user_value ) {
						$table[] = array(
							__( 'Key', 'cocart-jwt-authentication' )   => "user.$user_key",
							__( 'Value', 'cocart-jwt-authentication' ) => $user_value,
						);
					}
				} else {
					$table[] = array(
						'Key'   => $key,
						'Value' => is_scalar( $value ) ? $value : wp_json_encode( $value ),
					);
				}
			}

			\WP_CLI\Utils\format_items( 'table', $table, array( __( 'Key', 'cocart-jwt-authentication' ), __( 'Value', 'cocart-jwt-authentication' ) ) );
		} catch ( Exception $e ) {
			\WP_CLI::error( __( 'Invalid token.', 'cocart-jwt-authentication' ) );
		}
	} // END view()
} // END class

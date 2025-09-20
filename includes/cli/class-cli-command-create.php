<?php
/**
 * CoCart JWT Authentication - CLI Command Create.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @since   2.2.0
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication;

use CoCart\JWTAuthentication\Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate a new JWT token for a user.
 *
 * @class CoCart\JWTAuthentication\CLI_Command_Create
 */
class CLI_Command_Create extends Tokens {

	/**
	 * Generate a new JWT token for a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user ID, email, or login.
	 *
	 * [--user-agent=<user-agent>]
	 * : The User Agent to override the server User Agent.
	 *
	 * ## EXAMPLES
	 *
	 * wp cocart jwt create 123 --user-agent=<user-agent>
	 * wp cocart jwt create email@domain.com --user-agent=<user-agent>
	 * wp cocart jwt create johnny --user-agent=<user-agent>
	 *
	 * @access public
	 *
	 * @param array $args       WP-CLI positional arguments.
	 * @param array $assoc_args WP-CLI associative arguments.
	 */
	public function create( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( __( 'A user ID, email or login is required.', 'cocart-jwt-authentication' ) );
		}

		$user = $args[0];

		$user_agent = isset( $assoc_args['user-agent'] ) ? $assoc_args['user-agent'] : '';

		if ( is_numeric( $user ) ) {
			$get_user_by = 'id';
		} elseif ( is_email( $user ) ) {
			$get_user_by = 'email';
		} else {
			$get_user_by = 'login';
		}

		$user_object = get_user_by( $get_user_by, $user );

		// Not found user.
		if ( ! $user_object ) {
			\WP_CLI::error( __( 'Invalid user ID, email or login.', 'cocart-jwt-authentication' ) );
		}

		// Set User agent.
		if ( ! empty( $user_agent ) ) {
			$_SERVER['HTTP_USER_AGENT'] = $user_agent;
		}

		$token = $this->generate_token( $user_object->ID );

		if ( is_wp_error( $token ) ) {
			\WP_CLI::error( $token->get_error_message() );
		}

		\WP_CLI::success( __( 'Token generated.', 'cocart-jwt-authentication' ) );
		\WP_CLI::log( __( 'Copy', 'cocart-jwt-authentication' ) . ': ' . $token );
	} // END create()
} // END class

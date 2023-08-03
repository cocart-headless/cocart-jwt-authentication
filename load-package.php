<?php
/**
 * This file is designed to be used to load as package NOT a WP plugin!
 *
 * @version 1.0.1
 * @package CoCart JWT Authentication
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '7.3', '<' ) ) {
	return;
}

if ( ! defined( 'COCART_JWT_AUTHENTICATION_FILE' ) ) {
	define( 'COCART_JWT_AUTHENTICATION_FILE', __FILE__ );
}

// Include the main CoCart JWT Authentication class.
if ( ! class_exists( 'CoCart\JWTAuthentication\Plugin', false ) ) {
	include_once untrailingslashit( plugin_dir_path( COCART_JWT_AUTHENTICATION_FILE ) ) . '/includes/class-cocart-jwt-authentication.php';
}

/**
 * Returns the main instance of cocart_jwt_authentication and only runs if it does not already exists.
 *
 * @return cocart_jwt_authentication
 */
if ( ! function_exists( 'cocart_jwt_authentication' ) ) {
	function cocart_jwt_authentication() {
		return \CoCart\JWTAuthentication\Plugin::init();
	}

	cocart_jwt_authentication();
}

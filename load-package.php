<?php
/**
 * This file is designed to be used to load as package NOT a WP plugin!
 *
 * @version 3.0.0
 * @package CoCart JWT Authentication
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	return;
}

if ( ! defined( 'COCART_JWT_AUTHENTICATION_FILE' ) ) {
	define( 'COCART_JWT_AUTHENTICATION_FILE', __FILE__ );
}

// Include the main CoCart JWT Authentication class.
if ( ! class_exists( 'CoCart\JWTAuthentication\Plugin', false ) ) {
	include_once untrailingslashit( __DIR__ ) . '/includes/class-cocart-jwt-authentication.php';
}

/**
 * Returns the main instance of cocart_jwt_authentication and only runs if it does not already exists.
 *
 * @return \CoCart\JWTAuthentication\Plugin
 */
if ( ! function_exists( 'cocart_jwt_authentication' ) ) {
	/**
	 * Initialize CoCart JWT Authentication.
	 *
	 * @return \CoCart\JWTAuthentication\Plugin
	 */
	function cocart_jwt_authentication() {
		return \CoCart\JWTAuthentication\Plugin::instance();
	}

	cocart_jwt_authentication();
}

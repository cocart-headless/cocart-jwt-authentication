<?php
/**
 * Plugin Name: CoCart JWT Authentication
 * Plugin URI:  https://cocartapi.com
 * Description: JWT Authentication for CoCart.
 * Author:      CoCart Headless, LLC
 * Author URI:  https://cocartapi.com
 * Version:     1.0.3
 * Text Domain: cocart-jwt-authentication
 * Domain Path: /languages/
 * Requires at least: 5.6
 * Requires PHP: 7.4
 *
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
	include_once untrailingslashit( plugin_dir_path( COCART_JWT_AUTHENTICATION_FILE ) ) . '/includes/class-cocart-jwt-authentication.php';
}

/**
 * Returns the main instance of cocart_jwt_authentication and only runs if it does not already exists.
 *
 * @return cocart_jwt_authentication
 */
if ( ! function_exists( 'cocart_jwt_authentication' ) ) {
	/**
	 * Initialize CoCart JWT Authentication.
	 */
	function cocart_jwt_authentication() {
		return \CoCart\JWTAuthentication\Plugin::init();
	}

	cocart_jwt_authentication();
}

<?php
/**
 * CoCart JWT Authentication - WooCommerce System Status.
 *
 * Adds additional related information to the WooCommerce System Status.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication\Admin\WooCommerce System Status
 * @since   2.4.0 Introduced.
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication\Admin;

use CoCart\JWTAuthentication\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SystemStatus {

	/**
	 * Constructor.
	 *
	 * @access public
	 *
	 * @since 2.4.0 Introduced.
	 */
	public function __construct() {
		// Adds CoCart fields to WooCommerce System Status response.
		add_filter( 'woocommerce_rest_prepare_system_status', array( $this, 'add_cocart_fields_to_response' ) );

		// Add additional system status data.
		add_filter( 'cocart_system_status_data', array( $this, 'add_system_status_data' ) );

		// Add clear expired tokens button to System Status.
		add_filter( 'woocommerce_debug_tools', array( $this, 'add_clear_expired_tokens_button' ) );
	} // END __construct()

	/**
	 * Adds CoCart JWT fields to WooCommerce System Status response.
	 *
	 * @access public
	 *
	 * @since 2.4.0 Introduced.
	 *
	 * @param WP_REST_Response $response The base system status response.
	 *
	 * @return WP_REST_Response
	 */
	public function add_cocart_fields_to_response( $response ) {
		$response->data['cocart_jwt'] = array(
			'cocart_jwt_version' => Plugin::get_version(),
		);

		return $response;
	} // END add_cocart_fields_to_response()

	/**
	 * Adds additional system status data to return.
	 *
	 * @access public
	 *
	 * @since 2.4.0 Introduced.
	 *
	 * @param array $data Current system status data.
	 *
	 * @return array $data System status data.
	 */
	public function add_system_status_data( $data ) {
		$data['cocart_jwt_version'] = array(
			'name'      => _x( 'JWT Authentication Version', 'label that indicates the version of the plugin', 'cocart-jwt-authentication' ),
			'label'     => esc_html__( 'JWT Authentication Version', 'cocart-jwt-authentication' ),
			'note'      => Plugin::get_version(),
			'mark'      => '',
			'mark_icon' => '',
		);

		return $data;
	} // END add_system_status_data()

	/**
	 * Adds a button under the tools section of WooCommerce System Status to clear expired tokens.
	 *
	 * @access public
	 *
	 * @since 2.4.0 Introduced.
	 *
	 * @param array $tools Current tools.
	 *
	 * @return array $tools Updated tools.
	 */
	public function add_clear_expired_tokens_button( $tools ) {
		$tools['cocart_jwt_clear_expired_tokens'] = array(
			'name'     => esc_html__( 'Clear expired JWT tokens', 'cocart-jwt-authentication' ),
			'button'   => esc_html__( 'Clear', 'cocart-jwt-authentication' ),
			'desc'     => sprintf(
				'<strong class="red">%1$s</strong> %2$s',
				esc_html__( 'Note:', 'cocart-jwt-authentication' ),
				sprintf(
					/* translators: %s = Plugin name */
					esc_html__( 'This tool will clear all expired JWT tokens generated with %s.', 'cocart-jwt-authentication' ),
					'CoCart JWT Authentication'
				)
			),
			'callback' => array( $this, 'clear_expired_tokens' ),
		);

		return $tools;
	} // END add_clear_expired_tokens_button()

	/**
	 * Runs the callback for clearing expired tokens.
	 *
	 * @access public
	 *
	 * @since 2.4.0 Introduced.
	 *
	 * @return string
	 */
	public function clear_expired_tokens() {
		Plugin::cleanup_expired_tokens();

		return esc_html__( 'All expired tokens have now been cleared.', 'cocart-jwt-authentication' );
	} // END clear_expired_tokens()
} // END class

return new SystemStatus();

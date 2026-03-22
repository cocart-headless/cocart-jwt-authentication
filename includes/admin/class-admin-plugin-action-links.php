<?php
/**
 * CoCart JWT Authentication - Plugin Action Links
 *
 * Adds action links for the plugins page.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication\Admin\Plugin_Action_Links
 * @since   3.0.1 Introduced.
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin_Action_Links {

	/**
	 * Stores the campaign arguments.
	 *
	 * @access public
	 *
	 * @var array
	 */
	public $campaign_args = array();

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		$this->campaign_args['utm_source']  = 'CoCartJWTAuth';
		$this->campaign_args['utm_medium']  = 'plugin-admin';
		$this->campaign_args['utm_content'] = 'action-links';

		add_filter( 'plugin_action_links_' . plugin_basename( COCART_JWT_AUTHENTICATION_FILE ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	} // END __construct()

	/**
	 * Plugin action links.
	 *
	 * @access public
	 *
	 * @since 3.0.1 Introduced.
	 *
	 * @param array $links An array of plugin links.
	 *
	 * @return array $links
	 */
	public function plugin_action_links( $links ) {
		$page = admin_url( 'admin.php' );

		if ( current_user_can( 'manage_options' ) ) {
			$action_links['setup-jwt'] = '<a href="' . add_query_arg(
				array(
					'page' => 'cocart-jwt-setup',
				),
				$page
			) . '" title="' . esc_attr__( 'Setup JWT', 'cocart-jwt-authentication' ) . '">' . esc_attr__( 'Setup JWT', 'cocart-jwt-authentication' ) . '</a>';
		}

		$links = array_merge( $links, $action_links );

		return $links;
	} // END plugin_action_links()

	/**
	 * Plugin row meta links
	 *
	 * @access public
	 *
	 * @since 3.0.1 Introduced.
	 *
	 * @param array  $metadata An array of the plugin's metadata.
	 * @param string $file     Path to the plugin file.
	 *
	 * @return array $metadata An array of the plugin's metadata.
	 */
	public function plugin_row_meta( $metadata, $file ) {
		if ( plugin_basename( COCART_JWT_AUTHENTICATION_FILE ) === $file ) {
			$row_meta = array(
				'docs'      => '<a href="' . esc_url( 'https://docs.cocartapi.com/getting-started/jwt/quick-start' ) . '" title="' . sprintf(
					/* translators: %s: CoCart */
					esc_attr__( 'View %s Documentation', 'cocart-jwt-authentication' ),
					'CoCart'
				) . '" target="_blank" rel="noopener noreferrer">' . esc_attr__( 'Documentation', 'cocart-jwt-authentication' ) . '</a>',
				'translate' => '<a href="' . \CoCart_Helpers::build_shortlink( add_query_arg( $this->campaign_args, esc_url( 'https://translate.cocartapi.com/projects/cocart-jwt-authentication/' ) ) ) . '" title="' . sprintf(
					/* translators: %s: CoCart */
					esc_attr__( 'Translate %s', 'cocart-jwt-authentication' ),
					'CoCart'
				) . '" target="_blank" rel="noopener noreferrer">' . esc_attr__( 'Translate', 'cocart-jwt-authentication' ) . '</a>',
				'review'    => '<a href="' . esc_url( COCART_REVIEW_URL ) . '" title="' . sprintf(
					/* translators: %s: CoCart */
					esc_attr__( 'Submit a review for %s', 'cocart-jwt-authentication' ),
					'CoCart'
				) . '" target="_blank" rel="noopener noreferrer">' . esc_attr__( 'Leave a Review', 'cocart-jwt-authentication' ) . '</a>',
			);

			$metadata = array_merge( $metadata, $row_meta );
		}

		return $metadata;
	} // END plugin_row_meta()
} // END class

return new Plugin_Action_Links();

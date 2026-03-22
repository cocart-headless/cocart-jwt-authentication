<?php
/**
 * CoCart JWT Authentication - Admin Notices.
 *
 * Handles admin notices for dependency checks.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication\Admin\Notices
 * @since   3.0.0 Introduced.
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notices {

	/**
	 * Constructor.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_dependency_notices' ) );
	}

	/**
	 * Display dependency notices for missing CoCart.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 */
	public function display_dependency_notices() {
		// Check if CoCart is installed first.
		if ( ! defined( 'COCART_FILE' ) ) {
			$this->display_cocart_missing_notice();
		}
	} // END display_dependency_notices()

	/**
	 * Display notice when CoCart is missing.
	 *
	 * @access private
	 *
	 * @since 3.0.0 Introduced.
	 */
	private function display_cocart_missing_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'CoCart JWT Authentication', 'cocart-jwt-authentication' ); ?></strong>
				<?php esc_html_e( 'requires CoCart to be installed and activated.', 'cocart-jwt-authentication' ); ?>
				<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=cart-rest-api-for-woocommerce&TB_iframe=true&width=772&height=935' ) ); ?>" class="thickbox open-plugin-details-modal">
					<?php esc_html_e( 'Install CoCart now', 'cocart-jwt-authentication' ); ?>
				</a>
			</p>
		</div>
		<?php
	} // END display_cocart_missing_notice()
}

return new Notices();

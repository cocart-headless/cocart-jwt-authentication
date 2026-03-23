<?php
/**
 * CoCart JWT Authentication - Refresh Token Controller.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @license GPL-3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller for refreshing JWT tokens via the REST API.
 *
 * Handles requests to POST /cocart/jwt/refresh-token.
 * Business logic is delegated to the REST singleton which holds
 * all token management methods inherited from Tokens.
 *
 * @since 4.0.0 Introduced.
 * @extends CoCart_REST_Controller
 */
class CoCart_REST_JWT_Refresh_Token_Controller extends CoCart_REST_Controller {

	/**
	 * The version of this controller's route.
	 *
	 * @var string
	 */
	protected $version = 'jwt';

	/**
	 * Get the path regex for this REST route.
	 *
	 * @access public
	 *
	 * @since 4.0.0 Introduced.
	 *
	 * @return string
	 */
	public function get_path_regex(): string {
		return '/refresh-token';
	} // END get_path_regex()

	/**
	 * Get method arguments for this REST route.
	 *
	 * @access public
	 *
	 * @since 4.0.0 Introduced.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args(): array {
		return array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => '__return_true',
			),
		);
	} // END get_args()

	/**
	 * Handle the refresh token request.
	 *
	 * Delegates to the REST singleton's refresh_token() method which
	 * validates the supplied refresh token and issues a new token pair.
	 *
	 * @access public
	 *
	 * @since 4.0.0 Introduced.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_request( \WP_REST_Request $request ) {
		return \CoCart\JWTAuthentication\REST::instance()->refresh_token( $request );
	} // END handle_request()
} // END class

<?php
/**
 * CoCart JWT Authentication - Validate Token Controller.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @license GPL-3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller for validating JWT tokens via the REST API.
 *
 * Handles requests to POST /cocart/jwt/validate-token.
 * The permission callback ensures only authenticated users (i.e. those who
 * already passed JWT authentication via the cocart_authenticate filter)
 * can reach the handler. A 200 response confirms the token is valid.
 *
 * @since 4.0.0 Introduced.
 * @extends CoCart_REST_Controller
 */
class CoCart_REST_JWT_Validate_Token_Controller extends CoCart_REST_Controller {

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
		return '/validate-token';
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
				'permission_callback' => array( $this, 'check_permission' ),
			),
		);
	} // END get_args()

	/**
	 * Check whether the request has an authenticated user.
	 *
	 * The cocart_authenticate filter runs before WordPress calls
	 * the permission callback, so a valid JWT token will have already
	 * resolved the current user by the time this check runs.
	 *
	 * @access public
	 *
	 * @since 4.0.0 Introduced.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if a user is authenticated, false otherwise.
	 */
	public function check_permission( \WP_REST_Request $request ): bool {
		return get_current_user_id() > 0;
	} // END check_permission()

	/**
	 * Handle the validate token request.
	 *
	 * Reaching this handler means the permission callback already confirmed
	 * a valid authenticated user, so the token is considered valid.
	 *
	 * @access public
	 *
	 * @since 4.0.0 Introduced.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response
	 */
	public function handle_request( \WP_REST_Request $request ): \WP_REST_Response {
		return rest_ensure_response( array( 'message' => __( 'Token is valid.', 'cocart-jwt-authentication' ) ) );
	} // END handle_request()
} // END class

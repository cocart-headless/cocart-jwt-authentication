<?php
/**
 * CoCart JWT Authentication - Destroy Tokens.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Destroy Tokens class.
 *
 * @class CoCart\JWTAuthentication\DestroyTokens
 */
class DestroyTokens {

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		// Delete tokens when user logs out.
		add_action( 'wp_logout', array( $this, 'destroy_tokens' ) );

		// Delete tokens when user changes password/email or user is deleted.
		add_action( 'after_password_reset', array( $this, 'destroy_tokens' ) );
		add_action( 'profile_update', array( $this, 'maybe_destroy_tokens' ), 10, 2 );
		add_action( 'delete_user', array( $this, 'destroy_tokens' ), 10, 1 );
	} // END init()

	/**
	 * Destroys both the token and refresh token when the user changes.
	 *
	 * @access public
	 *
	 * @since 2.0.0 Introduced.
	 * @since 2.3.0 Added filter to allow control over token revocation.
	 *
	 * @hooked: wp_logout
	 *
	 * @param int $user_id User ID.
	 */
	public function destroy_tokens( $user_id ) {
		// Get current action hook.
		$current_action = current_action();

		/**
		 * Filter allows you to control token revocation based on the action triggered.
		 *
		 * @since 2.3.0 Introduced.
		 *
		 * @param bool         True if allowed to destroy tokens.
		 * @param int $user_id User ID.
		 */
		$allow_action = apply_filters( 'cocart_jwt_auth_revoke_tokens_on_' . $current_action, true, $user_id );

		if ( ! $allow_action ) {
			return;
		}

		delete_user_meta( $user_id, '_cocart_jwt_token' );
		delete_user_meta( $user_id, '_cocart_jwt_tokens' );
		delete_user_meta( $user_id, '_cocart_jwt_refresh_tokens' );

		/**
		 * Fires when a token is deleted.
		 *
		 * @since 2.1.0 Introduced.
		 *
		 * @param int $user_id User ID.
		 */
		do_action( 'cocart_jwt_auth_token_deleted', $user_id );
	} // END destroy_tokens()

	/**
	 * Maybe destroys tokens when user changes password/email.
	 *
	 * @access public
	 *
	 * @since 2.0.0 Introduced.
	 * @since 2.3.0 Added filter to allow control over token revocation.
	 *
	 * @hooked: profile_update
	 *
	 * @param int    $user_id       User ID.
	 * @param object $old_user_data User data.
	 */
	public function maybe_destroy_tokens( $user_id, $old_user_data ) {
		$new_user_data = get_userdata( $user_id );

		// Check if the email was changed.
		if ( $new_user_data->user_email !== $old_user_data->user_email ) {
			/**
			 * Filter allows you to control token revocation on email changes.
			 *
			 * @since 2.3.0 Introduced.
			 *
			 * @param bool         True if allowed to destroy tokens.
			 * @param int $user_id User ID.
			 */
			$allow_email_change = apply_filters( 'cocart_jwt_auth_revoke_tokens_on_email_change', true, $user_id );

			if ( ! $allow_email_change ) {
				return;
			}

			$this->destroy_tokens( $user_id );
		}

		// Check if the password was changed.
		if ( $new_user_data->user_pass !== $old_user_data->user_pass ) {
			/**
			 * Filter allows you to control token revocation on password changes for security policies.
			 *
			 * @since 2.3.0 Introduced.
			 *
			 * @param bool         True if allowed to destroy tokens.
			 * @param int $user_id User ID.
			 */
			$allow_password_change = apply_filters( 'cocart_jwt_auth_revoke_tokens_on_password_change', true, $user_id );

			if ( ! $allow_password_change ) {
				return;
			}

			$this->destroy_tokens( $user_id );
		}
	} // END maybe_destroy_tokens()
} // END class

return new DestroyTokens();

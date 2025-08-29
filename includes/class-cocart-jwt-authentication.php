<?php
/**
 * CoCart JWT Authentication core setup.
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
 * Main CoCart JWT Authentication class.
 *
 * @class CoCart\JWTAuthentication\Plugin
 */
final class Plugin {

	/**
	 * Plugin Version
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @var string
	 */
	public static $version = '3.0.0-beta.1';

	/**
	 * Single instance of the CoCart class
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @var CoCart
	 */
	private static $instance = null;

	/**
	 * Cloning is forbidden.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning this object is forbidden.', 'cocart-jwt-authentication' ), '3.0.0' );
	} // END __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'cocart-jwt-authentication' ), '3.0.0' );
	} // END __wakeup()

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of CoCart JWT Authentication is loaded or can be loaded.
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @return CoCart JWT Authentication - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * CoCart JWT Authentication Constructor.
	 *
	 * @access private
	 *
	 * @since 3.0.0 Introduced.
	 */
	private function __construct() {
		include_once __DIR__ . '/admin/class-cocart-jwt-wc-admin-system-status.php';
		include_once __DIR__ . '/class-tokens.php';
		include_once __DIR__ . '/class-destroy-tokens.php';
		include_once __DIR__ . '/class-rest.php';

		$this->init_hooks();
	}

	/**
	 * Initiate CoCart JWT Authentication.
	 *
	 * @access public
	 *
	 * @static
	 */
	public function init_hooks() {
		// Load translation files.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );

		// Register the CLI commands.
		add_action( 'plugins_loaded', array( $this, 'register_cli' ) );

		// Schedule cron job for cleaning up expired tokens.
		add_action( 'cocart_jwt_cleanup_cron', array( __CLASS__, 'cleanup_expired_tokens' ) );
		register_activation_hook( COCART_JWT_AUTHENTICATION_FILE, array( $this, 'schedule_cron_job' ) );
		register_deactivation_hook( COCART_JWT_AUTHENTICATION_FILE, array( $this, 'clear_scheduled_cron_job' ) );
	} // END init()

	/**
	 * Register the CLI commands.
	 *
	 * @access public
	 */
	public function register_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include_once __DIR__ . '/cli/class-cli-command-create.php';
			include_once __DIR__ . '/cli/class-cli-command-list.php';
			include_once __DIR__ . '/cli/class-cli-command-view.php';
			include_once __DIR__ . '/cli/class-cli-command-cleanup.php';

			// Register the command group.
			\WP_CLI::add_command( 'cocart jwt', array( $this, 'cli_jwt_command' ) );
		}
	} // END register_cli()

	/**
	 * Handles subcommands under 'cocart jwt'
	 *
	 * @access public
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 */
	public function cli_jwt_command( $args, $assoc_args ) {
		// Simple router for subcommands.
		$subcommand = $args[0] ?? null;

		if ( ! $subcommand ) {
			\WP_CLI::error( 'Please provide a subcommand, e.g., create, view, list or cleanup.' );
		}

		switch ( $subcommand ) {
			case 'create':
				$class = new CLI_Command_Create();
				$class->create( array_slice( $args, 1 ), $assoc_args );
				break;

			case 'list':
				$class = new CLI_Command_List();
				$class->list( array_slice( $args, 1 ), $assoc_args );
				break;

			case 'view':
				$class = new CLI_Command_View();
				$class->view( array_slice( $args, 1 ), $assoc_args );
				break;

			case 'cleanup':
				$class = new CLI_Command_Cleanup();
				$class->cleanup( array_slice( $args, 1 ), $assoc_args );
				break;

			default:
				\WP_CLI::error( "Unknown subcommand: $subcommand" );
		}
	} // END cli_jwt_command()

	/**
	 * Clean up expired tokens in batches.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 * @since 2.2.0 Improved to work in batches.
	 * @since 3.0.0 Updated to clean up PAT and refresh token data.
	 *
	 * @param int $batch_size Number of users to process per batch.
	 */
	public static function cleanup_expired_tokens( $batch_size = 100 ) {
		$offset = 0;

		do {
			$users = get_users( array(
				'meta_key'     => '_cocart_jwt_tokens', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
				'number'       => $batch_size,
				'offset'       => $offset,
			) );

			$users_count = count( $users );

			foreach ( $users as $user ) {
				self::cleanup_expired_tokens_for_user( $user->ID );
			}

			$offset += $batch_size;
		} while ( $users_count === $batch_size );
	} // END cleanup_expired_tokens()

	/**
	 * Clean up expired tokens for a specific user.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param int $user_id User ID to clean up tokens for.
	 */
	public static function cleanup_expired_tokens_for_user( $user_id ) {
		// Clean up expired JWT tokens and associated PAT data.
		$user_tokens = get_user_meta( $user_id, '_cocart_jwt_tokens', true );
		$pat_data    = get_user_meta( $user_id, '_cocart_jwt_token_pat' );

		if ( is_array( $user_tokens ) ) {
			foreach ( $user_tokens as $pat_id => $token ) {
				if ( self::is_token_expired( $token ) ) {
					// Remove from main tokens collection.
					unset( $user_tokens[ $pat_id ] );

					// Remove associated PAT entry.
					foreach ( $pat_data as $pat_entry ) {
						if ( array_key_exists( $pat_id, $pat_entry ) ) {
							delete_user_meta( $user_id, '_cocart_jwt_token_pat', $pat_entry );

							break;
						}
					}
				}
			}

			update_user_meta( $user_id, '_cocart_jwt_tokens', $user_tokens );
		}

		// Clean up expired refresh tokens.
		$refresh_tokens = get_user_meta( $user_id, '_cocart_jwt_refresh_tokens', true );

		if ( is_array( $refresh_tokens ) ) {
			$current_time = time();

			foreach ( $refresh_tokens as $refresh_token => $expiration ) {
				if ( $current_time > $expiration ) {
					unset( $refresh_tokens[ $refresh_token ] );
					delete_user_meta( $user_id, '_cocart_jwt_refresh_token', $refresh_token );
				}
			}

			update_user_meta( $user_id, '_cocart_jwt_refresh_tokens', $refresh_tokens );
		}
	} // END cleanup_expired_tokens_for_user()

	/**
	 * Schedule cron job for cleaning up expired tokens.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 */
	public static function schedule_cron_job() {
		if ( ! wp_next_scheduled( 'cocart_jwt_cleanup_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'cocart_jwt_cleanup_cron' );
		}
	} // END schedule_cron_job()

	/**
	 * Clear scheduled cron job on plugin deactivation.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 */
	public static function clear_scheduled_cron_job() {
		$timestamp = wp_next_scheduled( 'cocart_jwt_cleanup_cron' );
		wp_unschedule_event( $timestamp, 'cocart_jwt_cleanup_cron' );
	} // END clear_scheduled_cron_job()

	/**
	 * Return the name of the package.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'CoCart JWT Authentication';
	} // END get_name()

	/**
	 * Return the version of the package.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::$version;
	} // END get_version()

	/**
	 * Return the path to the package.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	} // END get_path()

	/**
	 * Load the plugin translations if any ready.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/cocart-jwt-authentication/cocart-jwt-authentication-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/cocart-jwt-authentication-LOCALE.mo
	 *
	 * @access public
	 */
	public function load_plugin_textdomain() {
		/**
		 * Filter to adjust the cocart-jwt-authentication locale to use for translations.
		 */
		$locale                  = apply_filters( 'plugin_locale', determine_locale(), 'cocart-jwt-authentication' );
		$custom_translation_path = WP_LANG_DIR . '/cocart-jwt-authentication/cocart-jwt-authentication-' . $locale . '.mo';
		$plugin_translation_path = WP_LANG_DIR . '/plugins/cocart-jwt-authentication-' . $locale . '.mo';

		// If a custom translation exists (by default it will not, as it is not a standard WordPress convention)
		// we unload the existing translation, then essentially layer the custom translation on top of the canonical
		// translation. Otherwise, we simply step back and let WP manage things.
		unload_textdomain( 'cocart-jwt-authentication' );
		if ( is_readable( $custom_translation_path ) ) {
			load_textdomain( 'cocart-jwt-authentication', $custom_translation_path );
			load_textdomain( 'cocart-jwt-authentication', $plugin_translation_path );
		} else {
			load_textdomain( 'cocart-jwt-authentication', plugin_basename( dirname( COCART_JWT_AUTHENTICATION_FILE ) ) . '/languages/cocart-jwt-authentication-' . $locale . '.mo' );
		}
	} // END load_plugin_textdomain()
} // END class

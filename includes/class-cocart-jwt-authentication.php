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
	public static $version = '3.0.0';

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

		// Load admin classes.
		add_action( 'init', array( $this, 'load_admin' ) );

		// Register the CLI commands.
		add_action( 'plugins_loaded', array( $this, 'register_cli' ) );

		// Schedule cron job for cleaning up expired tokens.
		add_action( 'cocart_jwt_cleanup_cron', array( __CLASS__, 'cleanup_expired_tokens' ) );
		add_action( 'cocart_jwt_cleanup_legacy_cron', array( __CLASS__, 'cleanup_legacy_user_meta' ) );
		register_activation_hook( COCART_JWT_AUTHENTICATION_FILE, array( $this, 'schedule_cron_job' ) );
		register_activation_hook( COCART_JWT_AUTHENTICATION_FILE, array( $this, 'schedule_legacy_cleanup' ) );
		register_activation_hook( COCART_JWT_AUTHENTICATION_FILE, array( $this, 'activation_redirect' ) );
		register_deactivation_hook( COCART_JWT_AUTHENTICATION_FILE, array( $this, 'clear_scheduled_cron_job' ) );
	} // END init()

	/**
	 * Load admin classes.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function load_admin() {
		if ( is_admin() ) {
			include_once __DIR__ . '/admin/class-cocart-jwt-wc-admin-system-status.php';
			include_once __DIR__ . '/admin/class-admin-notices.php';
			include_once __DIR__ . '/admin/class-jwt-setup.php';

			// Handle activation redirect.
			add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
		}
	} // END load_admin()

	/**
	 * Handle the activation redirect to the setup page.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @return void
	 */
	public function handle_activation_redirect() {
		// Check if we should redirect after activation.
		if ( get_transient( 'cocart_jwt_activation_redirect' ) ) {
			// Delete the transient to prevent future redirects.
			delete_transient( 'cocart_jwt_activation_redirect' );

			// Only redirect if user has proper capabilities.
			if ( current_user_can( 'manage_options' ) ) {
				wp_redirect( admin_url( 'admin.php?page=cocart-jwt-setup' ) );
				exit;
			}
		}
	} // END handle_activation_redirect()

	/**
	 * Register the CLI commands.
	 *
	 * @access public
	 */
	public function register_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include_once __DIR__ . '/class-table-formatter.php';
			include_once __DIR__ . '/cli/class-cli-command-create.php';
			include_once __DIR__ . '/cli/class-cli-command-list.php';
			include_once __DIR__ . '/cli/class-cli-command-view.php';
			include_once __DIR__ . '/cli/class-cli-command-cleanup.php';
			include_once __DIR__ . '/cli/class-cli-command-destroy.php';

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
		// Show help if requested or no subcommand provided.
		if ( empty( $args[0] ) || isset( $assoc_args['help'] ) ) {
			$this->show_cli_help();
			return;
		}

		// Simple router for subcommands.
		$subcommand = $args[0];

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

			case 'destroy':
				$class = new CLI_Command_Destroy();
				$class->destroy( array_slice( $args, 1 ), $assoc_args );
				break;

			default:
				\WP_CLI::error( "Unknown subcommand: $subcommand" );
		}
	} // END cli_jwt_command()

	/**
	 * Display CLI help information.
	 *
	 * @access private
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @return void
	 */
	private function show_cli_help() {
		\WP_CLI::line( __( 'NAME', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '' );
		\WP_CLI::line( '  wp cocart jwt - ' . __( 'Manage JWT tokens for CoCart.', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '' );
		\WP_CLI::line( __( 'SYNOPSIS', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '' );
		\WP_CLI::line( '  wp cocart jwt <subcommand>' );
		\WP_CLI::line( '' );
		\WP_CLI::line( __( 'SUBCOMMANDS', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '' );
		\WP_CLI::line( '  create     ' . __( 'Generate a new JWT token for a user', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '    wp cocart jwt create --user=<user> [--user-agent=<user-agent>]' );
		\WP_CLI::line( '' );
		\WP_CLI::line( '  view       ' . __( 'Display details of a JWT token', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '    wp cocart jwt view <token>' );
		\WP_CLI::line( '' );
		\WP_CLI::line( '  list       ' . __( 'List all active JWT tokens', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '    wp cocart jwt list [--page=<number>] [--per-page=<number>]' );
		\WP_CLI::line( '' );
		\WP_CLI::line( '  cleanup    ' . __( 'Clean up expired JWT tokens', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '    wp cocart jwt cleanup [--batch-size=<number>] [--force]' );
		\WP_CLI::line( '' );
		\WP_CLI::line( '  destroy    ' . __( 'Destroy JWT tokens for a specific user', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '    wp cocart jwt destroy <user> [--pat=<pat_id>] [--force]' );
		\WP_CLI::line( '' );
		\WP_CLI::line( __( 'EXAMPLES', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( '' );
		\WP_CLI::line( '  wp cocart jwt create --user=123' );
		\WP_CLI::line( '  wp cocart jwt list --per-page=5' );
		\WP_CLI::line( '  wp cocart jwt destroy admin@example.com --force' );
		\WP_CLI::line( '  wp cocart jwt cleanup --batch-size=50' );
		\WP_CLI::line( '' );
		\WP_CLI::line( __( 'For more details on each subcommand, visit:', 'cocart-jwt-authentication' ) );
		\WP_CLI::line( 'https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/wp-cli.md' );
	} // END show_cli_help()

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
	 * Clean up legacy user meta data from previous versions.
	 *
	 * Removes outdated meta keys that are no longer used in version 3.0.0+.
	 * Uses batching to prevent performance issues on large sites.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param int $batch_size Number of rows to process per batch. Default 500.
	 *
	 * @return void
	 */
	public static function cleanup_legacy_user_meta( $batch_size = 500 ) {
		global $wpdb;

		// Legacy meta keys to be removed (from v2.5.1 and earlier).
		$legacy_meta_keys = array(
			'cocart_jwt_token',                    // Old single token storage (without underscore prefix).
			'cocart_jwt_refresh_token',            // Old refresh token storage (without underscore prefix).
			'cocart_jwt_refresh_token_expiration', // Old refresh token expiration storage.
		);

		$total_deleted = 0;
		$batch_deleted = 0;

		// Process in batches to prevent memory issues.
		do {
			// Create placeholders for the IN clause.
			$placeholders = implode( ', ', array_fill( 0, count( $legacy_meta_keys ), '%s' ) );

			// Prepare and execute batched query.
			$query = $wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ($placeholders) LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( $legacy_meta_keys, array( $batch_size ) )
			);

			$batch_deleted  = $wpdb->query( $query );
			$total_deleted += $batch_deleted;

		} while ( $batch_deleted === $batch_size );

		// Log the cleanup results if CoCart logger is available.
		if ( class_exists( 'CoCart_Logger' ) && $total_deleted > 0 ) {
			\CoCart_Logger::log(
				sprintf( 'Background cleanup completed: removed %d legacy JWT token meta entries (processed in batches of %d).', $total_deleted, $batch_size ),
				'info',
				'cocart-jwt-authentication'
			);
		}
	} // END cleanup_legacy_user_meta()

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
	 * Schedule one-time legacy cleanup job.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @return void
	 */
	public static function schedule_legacy_cleanup() {
		// Only schedule if not already scheduled and there might be legacy data.
		if ( ! wp_next_scheduled( 'cocart_jwt_cleanup_legacy_cron' ) ) {
			// Schedule to run in 30 seconds to allow plugin activation to complete.
			wp_schedule_single_event( time() + 30, 'cocart_jwt_cleanup_legacy_cron' );
		}
	} // END schedule_legacy_cleanup()

	/**
	 * Handle plugin activation redirect to setup page.
	 *
	 * Redirects to the JWT setup page if CoCart is installed and active.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @return void
	 */
	public function activation_redirect() {
		// Check if CoCart is installed and active.
		if ( ! defined( 'COCART_FILE' ) ) {
			return;
		}

		// Don't redirect if we're doing bulk activation or in network admin.
		if ( isset( $_GET['activate-multi'] ) || is_network_admin() ) {
			return;
		}

		// Set a transient to trigger redirect after activation.
		set_transient( 'cocart_jwt_activation_redirect', true, 30 );
	} // END activation_redirect()

	/**
	 * Clear scheduled cron job on plugin deactivation.
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @since 2.0.0 Introduced.
	 *
	 * @return void
	 */
	public static function clear_scheduled_cron_job() {
		// Clear regular cleanup cron.
		$timestamp = wp_next_scheduled( 'cocart_jwt_cleanup_cron' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'cocart_jwt_cleanup_cron' );
		}

		// Clear legacy cleanup cron if still scheduled.
		$legacy_timestamp = wp_next_scheduled( 'cocart_jwt_cleanup_legacy_cron' );
		if ( $legacy_timestamp ) {
			wp_unschedule_event( $legacy_timestamp, 'cocart_jwt_cleanup_legacy_cron' );
		}
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

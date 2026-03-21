<?php
/**
 * Bootstrap class
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication
 */

/**
 * The test suite bootstrap.
 */
class CoCart_JWT_Unit_Tests_Bootstrap {

	/**
	 * The instance.
	 *
	 * @var CoCart_JWT_Unit_Tests_Bootstrap
	 */
	protected static $instance = null;

	/**
	 * The JWT plugin directory.
	 *
	 * @var string
	 */
	private $jwt_plugin_dir;

	/**
	 * The plugin tests directory.
	 *
	 * @var string
	 */
	private $tests_dir;

	/**
	 * The WP tests library directory.
	 *
	 * @var string
	 */
	private $wp_tests_dir;

	/**
	 * The required plugins directory.
	 *
	 * @var string
	 */
	private $wp_plugins_dir;

	/**
	 * Get the single class instance.
	 *
	 * @return CoCart_JWT_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructs the bootstrap class.
	 */
	public function __construct() {
		// Define the JWT secret key before any class is instantiated.
		if ( ! defined( 'COCART_JWT_AUTH_SECRET_KEY' ) ) {
			define( 'COCART_JWT_AUTH_SECRET_KEY', 'cocart-jwt-test-secret-key-for-phpunit-tests-only' );
		}

		$this->tests_dir      = __DIR__;
		$this->jwt_plugin_dir = dirname( __DIR__ );
		$this->wp_tests_dir   = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

		$wp_core_dir          = getenv( 'WP_CORE_DIR' ) ? rtrim( getenv( 'WP_CORE_DIR' ), '/' ) : '/tmp/wordpress';
		$this->wp_plugins_dir = $wp_core_dir . '/wp-content/plugins';

		// Use the polyfills from CoCart core's vendor since SSL prevents downloading them here.
		$cocart_vendor = $this->wp_plugins_dir . '/cart-rest-api-for-woocommerce/vendor';
		define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $cocart_vendor . '/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );
		define( 'WP_PLUGIN_DIR', $this->wp_plugins_dir );

		require_once $this->wp_tests_dir . '/includes/functions.php';

		tests_add_filter( 'plugins_loaded', array( $this, 'load_plugins' ) );

		// Setup CoCart Session Handler.
		tests_add_filter( 'woocommerce_session_handler', array( $this, 'session_handler' ) );

		// Setup WooCommerce.
		tests_add_filter( 'woocommerce_loaded', array( $this, 'woocommerce' ) );

		// Load REST API.
		tests_add_filter( 'rest_api_init', array( $this, 'load_rest_api' ) );

		// Default configurations.
		tests_add_filter( 'woocommerce_admin_disabled', '__return_true' );

		// Prevent WC session handler from calling setcookie().
		tests_add_filter( 'woocommerce_set_cookie_enabled', '__return_false' );

		// Load the WordPress testing environment.
		require $this->wp_tests_dir . '/includes/bootstrap.php';

		$this->includes();
	}

	/**
	 * Loads the required files.
	 */
	private function includes() {
		$cocart_tests_dir = $this->wp_plugins_dir . '/cart-rest-api-for-woocommerce/tests';

		// CoCart core framework (load order: base → rest → api → v1 → v2).
		require_once $cocart_tests_dir . '/framework/class-cocart-unit-test-case.php';
		require_once $cocart_tests_dir . '/framework/class-cocart-rest-test-case.php';
		require_once $cocart_tests_dir . '/framework/class-cocart-api-test-case.php';
		require_once $cocart_tests_dir . '/framework/class-cocart-api-v1-test-case.php';
		require_once $cocart_tests_dir . '/framework/class-cocart-api-v2-test-case.php';

		// JWT test framework.
		require_once $this->tests_dir . '/framework/class-jwt-test-case.php';
	}

	/**
	 * Loads plugins.
	 */
	public function load_plugins() {
		// Load WooCommerce.
		require $this->wp_plugins_dir . '/woocommerce/woocommerce.php';

		// Install WooCommerce database tables after post types are registered (priority 20, after WC's priority 10).
		tests_add_filter( 'init', array( 'WC_Install', 'install' ), 20 );

		// Create CoCart custom tables (cocart_carts) after WooCommerce tables exist.
		tests_add_filter( 'init', array( 'CoCart_Install', 'create_tables' ), 30 );

		// Force Action Scheduler to initialize now so its autoloader is registered before CoCart loads.
		if ( function_exists( 'action_scheduler_initialize_3_dot_9_dot_3' ) ) {
			action_scheduler_initialize_3_dot_9_dot_3();
			ActionScheduler_Versions::initialize_latest_version();
		}

		// Stub Action Scheduler functions not available in test environment.
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			function as_schedule_single_action() { // phpcs:ignore
				return 0;
			}
		}
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			function as_has_scheduled_action() { // phpcs:ignore
				return false;
			}
		}
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			function as_unschedule_all_actions() { // phpcs:ignore
				return;
			}
		}
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			function as_schedule_recurring_action() { // phpcs:ignore
				return 0;
			}
		}
		if ( ! function_exists( 'as_unschedule_action' ) ) {
			function as_unschedule_action() { // phpcs:ignore
				return;
			}
		}
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			function as_next_scheduled_action() { // phpcs:ignore
				return false;
			}
		}

		// Load CoCart core.
		require_once $this->wp_plugins_dir . '/cart-rest-api-for-woocommerce/cocart-core.php';

		if ( ! defined( 'COCART_CART_CACHE_GROUP' ) ) {
			define( 'COCART_CART_CACHE_GROUP', 'cocart_cart_id' );
		}

		// Load CoCart JWT Authentication.
		require_once $this->jwt_plugin_dir . '/cocart-jwt-authentication.php';
	}

	/**
	 * Filters the session handler to replace with our own.
	 *
	 * @access public
	 *
	 * @param string $handler WooCommerce Session Handler.
	 *
	 * @return string $handler CoCart Session Handler.
	 */
	public function session_handler( $handler ) {
		if ( class_exists( 'WC_Session_Handler' ) ) {
			require_once COCART_FILE_PATH . '/includes/classes/class-cocart-session-handler.php';
			$handler = 'CoCart_Session_Handler';
		}

		return $handler;
	} // END session_handler()

	/**
	 * Includes WooCommerce tweaks.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function woocommerce() {
		require_once COCART_FILE_PATH . '/includes/classes/class-cocart-woocommerce.php';
	} // END woocommerce()

	/**
	 * Load REST API.
	 *
	 * @access public
	 */
	public function load_rest_api() {
		require_once COCART_FILE_PATH . '/includes/classes/class-cocart-data-exception.php';
		require_once COCART_FILE_PATH . '/includes/classes/rest-api/class-cocart-cart-callbacks.php';
		require_once COCART_FILE_PATH . '/includes/classes/rest-api/class-cocart-cart-extension.php';
		require_once COCART_FILE_PATH . '/includes/classes/rest-api/class-cocart-response.php';
		require_once COCART_FILE_PATH . '/includes/classes/rest-api/class-cocart-cart-formatting.php';
		require_once COCART_FILE_PATH . '/includes/classes/rest-api/class-cocart-cart-validation.php';
		require_once COCART_FILE_PATH . '/includes/classes/rest-api/class-cocart-product-validation.php';
		require_once COCART_FILE_PATH . '/includes/classes/rest-api/class-cocart-rest-api.php';
		require_once COCART_FILE_PATH . '/includes/classes/rest-api/class-cocart-security.php';

		// Re-instantiate CoCart_Cart_Callbacks on every rest_api_init so that the
		// cocart_register_extension_callback hooks are re-registered in $wp_filter.
		// WP_UnitTestCase::tearDown() restores $wp_filter to its pre-setUp() state,
		// which removes any hooks added during the previous test's rest_api_init.
		new CoCart_Cart_Callbacks();

		// Explicitly instantiate CoCart_REST_API on every rest_api_init so that routes
		// are registered into the fresh WP_REST_Server created by each test's setUp().
		new CoCart_REST_API();

		// Re-instantiate JWT REST on every rest_api_init so that JWT hooks and routes
		// are re-registered after $wp_filter is restored by WP_UnitTestCase::tearDown().
		new \CoCart\JWTAuthentication\REST();

		// Re-instantiate DestroyTokens on every rest_api_init so that revocation hooks
		// (wp_logout, after_password_reset, etc.) survive the $wp_filter reset.
		new \CoCart\JWTAuthentication\DestroyTokens();
	} // END load_rest_api()
}

CoCart_JWT_Unit_Tests_Bootstrap::instance();

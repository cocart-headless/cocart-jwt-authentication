<?php
/**
 * CoCart JWT Authentication - JWT Setup Page.
 *
 * Provides an admin page for generating JWT secret keys.
 *
 * @author  Sébastien Dumont
 * @package CoCart JWT Authentication\Admin\JWT Setup
 * @since   3.0.0 Introduced.
 * @license GPL-3.0
 */

namespace CoCart\JWTAuthentication\Admin;

use CoCart\JWTAuthentication\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Return early if CoCart_Submenu_Page class doesn't exist.
if ( ! class_exists( 'CoCart_Submenu_Page' ) ) {
	return;
}

class JWTSetup extends \CoCart_Submenu_Page {

	/**
	 * Initialize the admin page.
	 *
	 * @access protected
	 *
	 * @since 3.0.0 Introduced.
	 */
	protected function init() {
		// Registers the JWT setup page.
		add_filter( 'cocart_register_submenu_page', array( $this, 'register_submenu_page' ) );

		// Filters what screens the plugin will focus on displaying notices or enqueue scripts/styles.
		add_filter(
			'cocart_admin_screens',
			function ( $screens ) {
				$screens[] = 'cocart_page_cocart-jwt-setup';

				return $screens;
			}
		);

		// Enqueue scripts and styles for the JWT setup page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register the admin submenu page.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param array $submenu_pages Currently registered submenu pages.
	 *
	 * @return array $submenu_pages All registered submenu pages.
	 */
	public function register_submenu_page( $submenu_pages ) {
		if ( ! is_array( $submenu_pages ) ) {
			return $submenu_pages;
		}

		$submenu_pages['jwt-setup'] = array(
			'class_name' => 'CoCart\JWTAuthentication\Admin\JWTSetup',
			'data'       => array(
				'page_title' => __( 'JWT Setup', 'cocart-jwt-authentication' ),
				'menu_title' => __( 'JWT Setup', 'cocart-jwt-authentication' ),
				'capability' => apply_filters( 'cocart_screen_capability', 'manage_options' ),
				'menu_slug'  => 'cocart-jwt-setup',
			),
		);

		return $submenu_pages;
	} // END register_submenu_page()
	/**
	 * Enqueue scripts for the JWT Setup page.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( ! $this->is_current_page( $hook_suffix ) ) {
			return;
		}

		$this->enqueue_page_scripts();
		$this->localize_script();
	}

	/**
	 * Check if we're on the current page.
	 *
	 * @access protected
	 *
	 * @since 3.0.0 Introduced.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return bool
	 */
	protected function is_current_page( $hook_suffix ) {
		return 'cocart_page_cocart-jwt-setup' === $hook_suffix;
	}

	/**
	 * Enqueue page scripts.
	 *
	 * @access protected
	 *
	 * @since 3.0.0 Introduced.
	 */
	protected function enqueue_page_scripts() {
		wp_enqueue_script(
			'cocart-jwt-setup',
			plugin_dir_url( COCART_JWT_AUTHENTICATION_FILE ) . 'assets/js/jwt-setup.js',
			array(),
			Plugin::get_version(),
			true
		);
	}

	/**
	 * Localize script with translatable strings.
	 *
	 * @access protected
	 *
	 * @since 3.0.0 Introduced.
	 */
	protected function localize_script() {
		wp_localize_script(
			'cocart-jwt-setup',
			'cocart_jwt_setup',
			array(
				'copy_failed' => esc_html__( 'Failed to copy to clipboard. Please copy manually.', 'cocart-jwt-authentication' ),
			)
		);

		// Add inline script for code tab functionality.
		wp_add_inline_script(
			'cocart-jwt-setup',
			"
			document.addEventListener('DOMContentLoaded', function() {
				// Handle code tab switching - switch all authentication steps to the same language
				document.querySelectorAll('.code-tab').forEach(function(tab) {
					tab.addEventListener('click', function() {
						var lang = this.getAttribute('data-lang');

						// Switch all authentication steps to the selected language
						document.querySelectorAll('.jwt-auth-flow-section .code-examples').forEach(function(container) {
							// Remove active class from all tabs in all containers
							container.querySelectorAll('.code-tab').forEach(function(t) {
								t.classList.remove('active');
							});

							// Add active class to the corresponding tab in each container
							var targetTab = container.querySelector('.code-tab[data-lang=\"' + lang + '\"]');
							if (targetTab) {
								targetTab.classList.add('active');
							}

							// Hide all code content in all containers
							container.querySelectorAll('.code-content').forEach(function(content) {
								content.classList.remove('active');
							});

							// Show the selected code content in each container
							var targetContent = container.querySelector('.code-content[data-lang=\"' + lang + '\"]');
							if (targetContent) {
								targetContent.classList.add('active');
							}
						});
					});
				});
			});
			"
		);
	} // END localize_script()

	/**
	 * Output the JWT Setup page content.
	 *
	 * @access public
	 *
	 * @since 3.0.0 Introduced.
	 */
	public function output() {
		$is_secret_key_defined = defined( 'COCART_JWT_AUTH_SECRET_KEY' ) && ! empty( COCART_JWT_AUTH_SECRET_KEY );
		?>
		<div class="wrap cocart-wrapped" role="main" style="max-width: 920px;">
			<div class="cocart-content">
				<h2><?php esc_html_e( 'JWT Authentication for CoCart', 'cocart-jwt-authentication' ); ?></h2>
				<p><?php esc_html_e( 'Enabling stateless authentication for CoCart REST API with industry-standard JWT tokens.', 'cocart-jwt-authentication' ); ?></p>

				<?php if ( $is_secret_key_defined ) : ?>
					<div class="jwt-status-section">
						<div class="jwt-status-indicator jwt-status-ready">
							<span class="status-icon">✅</span>
							<strong><?php esc_html_e( 'JWT Authentication is Ready', 'cocart-jwt-authentication' ); ?></strong>
							<p><?php esc_html_e( 'Your secret key is configured and JWT authentication is active.', 'cocart-jwt-authentication' ); ?></p>
						</div>
					</div>

					<div class="jwt-auth-flow-section">
						<h3><?php esc_html_e( 'Authentication Flow', 'cocart-jwt-authentication' ); ?></h3>
						<p><?php esc_html_e( 'Follow these steps to authenticate with your CoCart API:', 'cocart-jwt-authentication' ); ?></p>

						<div class="auth-step">
							<h4><?php esc_html_e( '1. Get a Token', 'cocart-jwt-authentication' ); ?></h4>
							<p><?php esc_html_e( 'Send a POST request to obtain a JWT token:', 'cocart-jwt-authentication' ); ?></p>
							
							<div class="code-examples">
								<div class="code-tabs">
									<button class="code-tab active" data-lang="curl">cURL</button>
									<button class="code-tab" data-lang="php">PHP</button>
									<button class="code-tab" data-lang="javascript">JavaScript</button>
									<button class="code-tab" data-lang="python">Python</button>
								</div>

								<div class="terminal-window">
									<div class="terminal-content">
										<pre class="code-content active" data-lang="curl"><code><span style="color: #61afef;">curl</span> -X POST <?php echo esc_html( home_url( '/wp-json/cocart/v2/login' ) ); ?> \
-H <span class="string">"Content-Type: application/json"</span> \
-d <span class="string">'{"username": "your-username", "password": "your-password"}'</span></code></pre>
										<pre class="code-content" data-lang="php"><code><span class="php-tag">&lt;?php</span>
<span class="variable">$response</span> = <span class="function">wp_remote_post</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/v2/login' ) ); ?>'</span>, [
	<span class="string">'headers'</span> => [
		<span class="string">'Content-Type'</span> => <span class="string">'application/json'</span>
	],
	<span class="string">'body'</span> => <span class="function">json_encode</span>([
		<span class="string">'username'</span> => <span class="string">'your-username'</span>,
		<span class="string">'password'</span> => <span class="string">'your-password'</span>
	])
]);

<span class="variable">$data</span> = <span class="function">json_decode</span>( <span class="function">wp_remote_retrieve_body</span>( <span class="variable">$response</span> ), <span class="boolean">true</span> );</code></pre>
<pre class="code-content" data-lang="javascript"><code><span class="keyword">const</span> <span class="variable">response</span> = <span class="keyword">await</span> <span class="function">fetch</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/v2/login' ) ); ?>'</span>, {
	<span class="property">method</span>: <span class="string">'POST'</span>,
	<span class="property">headers</span>: {
		<span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>
	},
	<span class="property">body</span>: <span class="function">JSON.stringify</span>({
		<span class="property">username</span>: <span class="string">'your-username'</span>,
		<span class="property">password</span>: <span class="string">'your-password'</span>
	})
});

<span class="keyword">const</span> <span class="variable">data</span> = <span class="keyword">await</span> <span class="variable">response</span>.<span class="function">json</span>();</code></pre>
<pre class="code-content" data-lang="python"><code><span class="keyword">import</span> requests
<span class="keyword">import</span> json

<span class="variable">response</span> = requests.<span class="function">post</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/v2/login' ) ); ?>'</span>,
	headers={
		<span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>
	},
	data=json.<span class="function">dumps</span>({
		<span class="string">'username'</span>: <span class="string">'your-username'</span>,
		<span class="string">'password'</span>: <span class="string">'your-password'</span>
	})
)

<span class="variable">data</span> = <span class="variable">response</span>.<span class="function">json</span>()</code></pre>
									</div>
								</div>
							</div>
						</div>

						<div class="auth-step">
							<h4><?php esc_html_e( '2. Use the Token', 'cocart-jwt-authentication' ); ?></h4>
							<p><?php esc_html_e( 'Include the JWT token in your API requests:', 'cocart-jwt-authentication' ); ?></p>
							
							<div class="code-examples">
								<div class="code-tabs">
									<button class="code-tab active" data-lang="curl">cURL</button>
									<button class="code-tab" data-lang="php">PHP</button>
									<button class="code-tab" data-lang="javascript">JavaScript</button>
									<button class="code-tab" data-lang="python">Python</button>
								</div>
								
								<div class="terminal-window">
									<div class="terminal-content">
										<pre class="code-content active" data-lang="curl"><code>curl -X GET <?php echo esc_html( home_url( '/wp-json/cocart/v2/cart' ) ); ?> \
-H <span class="string">"Authorization: Bearer YOUR-JWT-TOKEN"</span></code></pre>
										<pre class="code-content" data-lang="php"><code><span class="php-tag">&lt;?php</span>
<span class="variable">$response</span> = <span class="function">wp_remote_get</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/v2/cart' ) ); ?>'</span>, [
	<span class="string">'headers'</span> => [
		<span class="string">'Authorization'</span> => <span class="string">'Bearer YOUR-JWT-TOKEN'</span>
	]
]);

<span class="variable">$data</span> = <span class="function">json_decode</span>( <span class="function">wp_remote_retrieve_body</span>( <span class="variable">$response</span> ), <span class="boolean">true</span> );</code></pre>

										<pre class="code-content" data-lang="javascript"><code><span class="keyword">const</span> <span class="variable">response</span> = <span class="keyword">await</span> <span class="function">fetch</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/v2/cart' ) ); ?>'</span>, {
	<span class="property">method</span>: <span class="string">'GET'</span>,
	<span class="property">headers</span>: {
		<span class="string">'Authorization'</span>: <span class="string">'Bearer YOUR-JWT-TOKEN'</span>
	}
});

<span class="keyword">const</span> <span class="variable">data</span> = <span class="keyword">await</span> <span class="variable">response</span>.<span class="function">json</span>();</code></pre>

										<pre class="code-content" data-lang="python"><code><span class="keyword">import</span> requests

<span class="variable">response</span> = requests.<span class="function">get</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/v2/cart' ) ); ?>'</span>,
	headers={
		<span class="string">'Authorization'</span>: <span class="string">'Bearer YOUR-JWT-TOKEN'</span>
	}
)

<span class="variable">data</span> = <span class="variable">response</span>.<span class="function">json</span>()</code></pre>
									</div>
								</div>
							</div>
						</div>

						<div class="auth-step">
							<h4><?php esc_html_e( '3. Refresh Token (Optional)', 'cocart-jwt-authentication' ); ?></h4>
							<p><?php esc_html_e( 'Refresh your token when needed:', 'cocart-jwt-authentication' ); ?></p>
							
							<div class="code-examples">
								<div class="code-tabs">
									<button class="code-tab active" data-lang="curl">cURL</button>
									<button class="code-tab" data-lang="php">PHP</button>
									<button class="code-tab" data-lang="javascript">JavaScript</button>
									<button class="code-tab" data-lang="python">Python</button>
								</div>
								
								<div class="terminal-window">
									<div class="terminal-content">
										<pre class="code-content active" data-lang="curl"><code><span class="comment"># cURL</span>
curl -X POST <?php echo esc_html( home_url( '/wp-json/cocart/jwt/refresh-token' ) ); ?> \
	-H <span class="string">"Content-Type: application/json"</span> \
	-d <span class="string">'{"refresh_token": "YOUR-REFRESH-TOKEN"}'</span></code></pre>
										
										<pre class="code-content" data-lang="php"><code><span class="comment">// PHP</span>
<span class="php-tag">&lt;?php</span>
<span class="variable">$response</span> = <span class="function">wp_remote_post</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/jwt/refresh-token' ) ); ?>'</span>, [
	<span class="string">'headers'</span> => [<span class="string">'Content-Type'</span> => <span class="string">'application/json'</span>],
	<span class="string">'body'</span> => <span class="function">json_encode</span>([
		<span class="string">'refresh_token'</span> => <span class="string">'YOUR-REFRESH-TOKEN'</span>
	])
]);
<span class="variable">$data</span> = <span class="function">json_decode</span>(<span class="function">wp_remote_retrieve_body</span>(<span class="variable">$response</span>), <span class="boolean">true</span>);</code></pre>
										
										<pre class="code-content" data-lang="javascript"><code><span class="comment">// JavaScript (Fetch API)</span>
<span class="keyword">const</span> <span class="variable">response</span> = <span class="keyword">await</span> <span class="function">fetch</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/jwt/refresh-token' ) ); ?>'</span>, {
	<span class="property">method</span>: <span class="string">'POST'</span>,
	<span class="property">headers</span>: {
	<span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>
	},
	<span class="property">body</span>: <span class="function">JSON.stringify</span>({
	<span class="property">refresh_token</span>: <span class="string">'YOUR-REFRESH-TOKEN'</span>
	})
});
<span class="keyword">const</span> <span class="variable">data</span> = <span class="keyword">await</span> <span class="variable">response</span>.<span class="function">json</span>();</code></pre>
										
										<pre class="code-content" data-lang="python"><code><span class="comment"># Python</span>
<span class="keyword">import</span> requests
<span class="keyword">import</span> json

<span class="variable">response</span> = requests.<span class="function">post</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/jwt/refresh-token' ) ); ?>'</span>,
	headers={<span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>},
	data=json.<span class="function">dumps</span>({<span class="string">'refresh_token'</span>: <span class="string">'YOUR-REFRESH-TOKEN'</span>})
)
<span class="variable">data</span> = <span class="variable">response</span>.<span class="function">json</span>()</code></pre>
									</div>
								</div>
							</div>
						</div>

						<div class="auth-step">
							<h4><?php esc_html_e( '4. Validate Token', 'cocart-jwt-authentication' ); ?></h4>
							<p><?php esc_html_e( 'Verify if your token is still valid:', 'cocart-jwt-authentication' ); ?></p>
							
							<div class="code-examples">
								<div class="code-tabs">
									<button class="code-tab active" data-lang="curl">cURL</button>
									<button class="code-tab" data-lang="php">PHP</button>
									<button class="code-tab" data-lang="javascript">JavaScript</button>
									<button class="code-tab" data-lang="python">Python</button>
								</div>
								
								<div class="terminal-window">
									<div class="terminal-content">
										<pre class="code-content active" data-lang="curl"><code><span class="comment"># cURL</span>
curl -X POST <?php echo esc_html( home_url( '/wp-json/cocart/jwt/validate-token' ) ); ?> \
	-H <span class="string">"Authorization: Bearer YOUR-JWT-TOKEN"</span></code></pre>
										
										<pre class="code-content" data-lang="php"><code><span class="comment">// PHP</span>
<span class="php-tag">&lt;?php</span>
<span class="variable">$response</span> = <span class="function">wp_remote_post</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/jwt/validate-token' ) ); ?>'</span>, [
	<span class="string">'headers'</span> => [
		<span class="string">'Authorization'</span> => <span class="string">'Bearer YOUR-JWT-TOKEN'</span>
	]
]);
<span class="variable">$data</span> = <span class="function">json_decode</span>(<span class="function">wp_remote_retrieve_body</span>(<span class="variable">$response</span>), <span class="boolean">true</span>);</code></pre>
										
										<pre class="code-content" data-lang="javascript"><code><span class="comment">// JavaScript (Fetch API)</span>
<span class="keyword">const</span> <span class="variable">response</span> = <span class="keyword">await</span> <span class="function">fetch</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/jwt/validate-token' ) ); ?>'</span>, {
	<span class="property">method</span>: <span class="string">'POST'</span>,
	<span class="property">headers</span>: {
	<span class="string">'Authorization'</span>: <span class="string">'Bearer YOUR-JWT-TOKEN'</span>
	}
});
<span class="keyword">const</span> <span class="variable">data</span> = <span class="keyword">await</span> <span class="variable">response</span>.<span class="function">json</span>();</code></pre>
										
										<pre class="code-content" data-lang="python"><code><span class="comment"># Python</span>
<span class="keyword">import</span> requests

<span class="variable">response</span> = requests.<span class="function">post</span>( <span class="string">'<?php echo esc_html( home_url( '/wp-json/cocart/jwt/validate-token' ) ); ?>'</span>,
	headers={<span class="string">'Authorization'</span>: <span class="string">'Bearer YOUR-JWT-TOKEN'</span>}
)
<span class="variable">data</span> = <span class="variable">response</span>.<span class="function">json</span>()</code></pre>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="jwt-status-section">
						<div class="jwt-status-indicator jwt-status-not-ready">
							<span class="status-icon">⚠️</span>
							<strong><?php esc_html_e( 'Not Configured', 'cocart-jwt-authentication' ); ?></strong>
							<p><?php esc_html_e( 'You need to add the secret key to your wp-config.php file to enable JWT authentication.', 'cocart-jwt-authentication' ); ?></p>
						</div>
					</div>
				<?php endif; ?>

				<div class="jwt-key-section">
					<div class="terminal-controls">
						<button type="button" id="copy-config" class="button button-secondary">
							<?php esc_html_e( 'Copy', 'cocart-jwt-authentication' ); ?>
						</button>
						<button type="button" id="generate-new-key" class="button button-secondary">
							<?php esc_html_e( 'Generate New', 'cocart-jwt-authentication' ); ?>
						</button>
					</div>

					<div class="terminal-window">
						<div class="terminal-content">
							<pre id="jwt-config-display"><code></code></pre>
						</div>
					</div>

					<div id="copy-message" class="copy-message" style="display: none;">
						✅ <?php esc_html_e( 'Copied to clipboard!', 'cocart-jwt-authentication' ); ?>
					</div>
				</div>

				<div class="jwt-instructions">
					<h3><?php esc_html_e( 'Setup Instructions', 'cocart-jwt-authentication' ); ?></h3>
					<p><?php esc_html_e( 'Copy this configuration and add it to your wp-config.php file.', 'cocart-jwt-authentication' ); ?></p>
				</div>

				<div class="jwt-security-note">
					<h3><?php esc_html_e( 'Security Note', 'cocart-jwt-authentication' ); ?></h3>
					<p><?php esc_html_e( 'Keep your secret key private and secure. Do not share it publicly or commit it to version control. If you suspect your key has been compromised, generate a new one immediately.', 'cocart-jwt-authentication' ); ?></p>
				</div>
			</div>
		</div>
		<div class="clear"></div>

		<style>
			h2 {
				margin-top: 0;
			}

			/* Status Section */
			.jwt-status-section {
				margin: 20px 0 30px 0;
			}
			
			.jwt-status-indicator {
				align-items: flex-start;
				border-radius: 8px;
				border: 1px solid;
				display: flex;
				line-height: 1.35;
				gap: 12px;
				padding: 15px 20px;
			}
			
			.jwt-status-ready {
				background-color: #d4edda;
				border-color: #c3e6cb;
				color: #155724;
			}
			
			.jwt-status-not-ready {
				background-color: #fff3cd;
				border-color: #ffeeba;
				color: #856404;
			}
			
			.status-icon {
				font-size: 18px;
				flex-shrink: 0;
				margin-top: 0 !important;
			}
			
			.jwt-status-indicator div {
				flex: 1;
			}
			
			.jwt-status-indicator strong {
				display: block;
				font-size: 16px;
				margin-bottom: 5px;
			}
			
			.jwt-status-indicator p {
				margin: 0;
				font-size: 14px;
			}

			/* Authentication Flow Section */
			.jwt-auth-flow-section {
				margin: 30px 0;
				background: #f8f9fa;
				border: 1px solid #e9ecef;
				border-radius: 8px;
				padding: 25px;
			}
			
			.jwt-auth-flow-section h3 {
				margin-top: 0;
				color: #495057;
				border-bottom: 2px solid #007cba;
				padding-bottom: 10px;
			}
			
			.auth-step {
				margin: 25px 0;
				padding: 20px;
				background: white;
				border-radius: 6px;
			}
			
			.auth-step h4 {
				margin-top: 0;
				color: #007cba;
				font-size: 16px;
			}
			
			.auth-step p {
				margin-bottom: 15px;
				color: #495057;
			}
			
			/* Code Examples */
			.code-examples {
				margin: 15px 0;
			}
			
			.code-tabs {
				display: flex;
				gap: 0;
				margin-bottom: 0;
			}
			
			.code-tab {
				background: #44475a;
				color: #f8f8f2;
				border: none;
				padding: 10px 18px;
				cursor: pointer;
				font-size: 12px;
				font-weight: 500;
				transition: background-color 0.2s ease;
				border-radius: 10px 10px 0 0;
				margin-right: 4px;
			}
			
			.code-tab:hover {
				background: #6272a4;
			}
			
			.code-tab.active {
				background: #282a36;
				color: #50fa7b;
			}
			
			.code-examples .terminal-window {
				border-radius: 0 8px 8px 8px;
				margin: 0;
			}

			.code-content {
				display: none;
				white-space: pre;
				word-spacing: normal;
				word-break: normal;
				line-height: 1.5;
				tab-size: 2;
				hyphens: none;
				margin: 0px;
				overflow: auto;
				border-radius: 0.5rem;
				font-size: 0.875rem;
			}

			.code-content.active {
				display: block;
			}

			/* Syntax highlighting - Proper Dracula Theme Colors */
			.code-content .comment {
				color: #6272a4;
				font-style: italic;
			}

			.code-content .string {
				color: #50fa7b;
			}

			.code-content .keyword {
				color: #ff79c6;
				font-weight: 500;
			}

			.code-content .function {
				color: #f1fa8c;
			}

			.code-content .variable {
				color: #8be9fd;
			}

			.code-content .property {
				color: #8be9fd;
			}

			.code-content .php-tag {
				color: #ff79c6;
				font-weight: 500;
			}

			.code-content .boolean {
				color: #bd93f9;
			}

			.code-content .number {
				color: #bd93f9;
			}

			.code-content .constant {
				color: #bd93f9;
			}

			.code-content .operator {
				color: #ff79c6;
			}

			.code-content .punctuation {
				color: #f8f8f2;
			}

			.jwt-key-section {
				margin: 20px 0;
			}

			.terminal-controls {
				display: flex;
				gap: 10px;
				margin-bottom: 15px;
			}

			/* Terminal Window */
			.terminal-window {
				background: #282a36;
				border-radius: 12px;
				overflow: hidden;
				margin: 15px 0;
			}

			.terminal-content {
				padding: 20px;
				background: #282a36;
				position: relative;
			}

			.code-examples .terminal-content {
				padding: 20px;
			}


			#jwt-config-display {
				margin: 0;
				padding: 0;
				background: none;
				border: none;
			}

			#jwt-config-display code,
			.code-content code {
				display: block;
				font-family: 'Fira Code', 'JetBrains Mono', 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Consolas', 'source-code-pro', monospace;
				font-size: 14px;
				color: #f8f8f2;
				padding: 0;
				word-wrap: break-word;
				white-space: pre-wrap;
				font-variant-ligatures: common-ligatures;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
			}

			.code-content span {
				margin: 0 !important;
			}

			.code-content pre {
				margin: 0;
				padding: 0;
				background: none;
				border: none;
				font-size: inherit;
				line-height: inherit;
				color: inherit;
				overflow-x: auto;
			}

			#jwt-config-display code span {
				margin: 0;
			}

			/* Syntax highlighting for the define statement */
			#jwt-config-display code .php-keyword {
				color: #ff79c6;
			}
			
			#jwt-config-display code .php-function {
				color: #50fa7b;
			}
			
			#jwt-config-display code .php-string {
				color: #f1fa8c;
			}
			
			#jwt-config-display code .php-constant {
				color: #8be9fd;
			}
			
			/* Copy Message */
			.copy-message {
				font-size: 14px;
				font-weight: 500;
				margin-top: 15px;
			}
			
			/* Instructions and Security Note */
			.jwt-instructions {
				margin-top: 30px;
			}
			
			.jwt-security-note {
				background: #fff3cd;
				border: 1px solid #ffeeba;
				border-radius: 8px;
				padding: 20px;
				margin-top: 20px;
			}
			
			.jwt-security-note h3 {
				margin-top: 0;
				color: #856404;
			}
			
			.jwt-security-note p {
				margin-bottom: 0;
				color: #856404;
			}
		</style>
		<?php
	}
} // END class

return new JWTSetup();

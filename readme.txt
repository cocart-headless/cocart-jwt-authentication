=== CoCart JWT Authentication === 
Contributors: cocartforwc, sebd86
Tags: woocommerce, rest-api, decoupled, headless, jwt
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 3.0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

JWT Authentication for CoCart API.

== Description ==

This free add-on for [CoCart](https://cocartapi.com/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart) allows you to authenticate the Cart API via JSON Web Tokens as an authentication method.

JSON Web Tokens are an open standard [RFC 7519](https://datatracker.ietf.org/doc/html/rfc7519) for securely transmitting information between parties.

[Read the core concept for more information](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/concepts.md) on what this plugin does and can do.

★★★★★
> An excellent plugin, which makes building a headless WooCommerce experience a breeze. Easy to use, nearly zero setup time. [Harald Schneider](https://wordpress.org/support/topic/excellent-plugin-8062/)

### Key Features

* **Standard JWT Authentication**: Implements the industry-standard RFC 7519 for secure claims representation.
* **Simple Endpoints**: Offers clear endpoints for generating and validating tokens.
* **Configurable Secret Key**: Define your unique secret key via `wp-config.php` for secure token signing.
* **Multiple signing algorithms**: `HS256`, `HS384`, `HS512`, `RS256`, `RS384`, `RS512`, `ES256`, `ES384`, `ES512`, `PS256`, `PS384`, `PS512`
* **Rate Limiting**: Controlled specifically for refreshing and validating tokens. Requires [CoCart Plus](https://cocartapi.com/?utm_medium=website&utm_source=wpplugindirectory&utm_campaign=readme&utm_content=readmelink)
* **Helpful Debugging**: Detailed logs of authentication issues to help figure out exactly what happened and fix it faster.
* **WP-CLI Commands**: Useful [commands to handle tokens](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/wp-cli.md) - whether you need to check, destroy or create new ones, or clean up old ones.
* **Developer Hooks**: Provides [filters](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/filters.md) and [hooks](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/hooks.md) for more configuration to your requirements.

For support, please join the [community on Discord](https://cocartapi.com/community/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart). For priority support, consider upgrading to [CoCart Plus](https://cocartapi.com/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart).

## 📄 Documentation

See documentation on how to [get setup](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/guide.md), [filters](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/filters.md) and [hooks](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/hooks.md) with examples to help configure JWT Authentication to your needs.

Once ready to use, see the [quick start guide](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/quick-start.md). There is also an [advanced configuration](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/docs/advanced-configuration.md) for using RSA Keys.

★★★★★
> Amazing Plugin. I’m using it to create a react-native app with WooCommerce as back-end. This plugin is a life-saver! [Daniel Loureiro](https://wordpress.org/support/topic/amazing-plugin-1562/)

#### 👍 Add-ons to further enhance CoCart

We also have other add-ons that extend CoCart to enhance your headless store development.

* **[CoCart - CORS](https://wordpress.org/plugins/cocart-cors/)** enables support for CORS to allow CoCart to work across multiple domains.
* **[CoCart - Rate Limiting](https://wordpress.org/plugins/cocart-rate-limiting/)** enables the rate limiting feature.
* and more add-ons in development.

These add-ons of course come with support too.

For additional security, consider our [API Security](https://apisecurity.pro/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart) plugin that provides a firewall to block unknown outsiders, rate limit requests and protect data exposure – no configuration required.

### ⌨️ Join our growing community

A Discord community for developers, WordPress agencies and shop owners building the fastest and best headless WooCommerce stores with CoCart.

[Join our community](https://cocartapi.com/community/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart)

### 🐞 Bug reports

Bug reports for CoCart - JWT Authentication are welcomed in the [CoCart - JWT Authentication repository on GitHub](https://github.com/cocart-headless/cocart-jwt-authentication). Please note that GitHub is not a support forum, and that issues that aren’t properly qualified as bugs will be closed.

### More information

* The official [CoCart API plugin](https://cocartapi.com/?utm_medium=website&utm_source=wpplugindirectory&utm_campaign=readme&utm_content=readmelink) website.
* [CoCart for Developers](https://cocart.dev/?utm_medium=website&utm_source=wpplugindirectory&utm_campaign=readme&utm_content=readmelink), an official hub for resources you need to be productive with CoCart and keep track of everything that is happening with the API.
* The CoCart [Documentation](https://cocartapi.com/docs/?utm_medium=website&utm_source=wpplugindirectory&utm_campaign=readme&utm_content=readmelink)
* [Subscribe to updates](http://eepurl.com/dKIYXE)
* Like, Follow and Star on [Facebook](https://www.facebook.com/cocartforwc/), [Twitter](https://twitter.com/cocartapi), [Instagram](https://www.instagram.com/cocartheadless/) and [GitHub](https://github.com/co-cart/co-cart)

#### 💯 Credits

This plugin is developed and maintained by [Sébastien Dumont](https://twitter.com/sebd86).
Founder of [CoCart Headless, LLC](https://twitter.com/cocartheadless).

== Installation ==

= Minimum Requirements =

* WordPress v5.6
* WooCommerce v7.0
* PHP v7.4
* CoCart v4.3

= Recommended Requirements =

* WordPress v6.0 or higher.
* WooCommerce v9.0 or higher.
* PHP v8.0 or higher.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of CoCart JWT Authentication, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "CoCart JWT Authentication" and click Search Plugins. Once you’ve found the plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Upgrading =

It is recommended that anytime you want to update "CoCart JWT Authentication" that you get familiar with what's changed in the release.

CoCart JWT Authentication uses Semver practices. The summary of Semver versioning is as follows:

- *MAJOR* version when you make incompatible API changes.
- *MINOR* version when you add functionality in a backwards compatible manner.
- *PATCH* version when you make backwards compatible bug fixes.

You can read more about the details of Semver at [semver.org](https://semver.org/)

== Frequently Asked Questions ==

= What is CoCart? =

CoCart is developer-first REST API to decouple WooCommerce on the frontend and allow you to build modern storefronts with full control over auth, sessions, cart and product flows.

= Will this work with WooCommerce REST API? =

No! The WooCommerce REST API only use their own API key system to utilize it.

= Can I use this with ordinary REST API endpoints? =

No! This JWT Authentication was specifically designed for the CoCart API ONLY.

= I'm getting fatal error of allowed memory exhausted - a 500 error response. Why? =

It is possible due to a plugin conflict e.g. Login Limit and the token used failed many times and the IP address may have been blacklisted.

== Changelog ==

= v3.0.1 - 3rd December, 2025 =

### What's New?

* Dashboard: Added plugin action links.

### Bug Fix

* Dashboard: WooCommerce System Status was not accessible.

### Compatible

* Tested with WordPress v6.9
* Tested with WooCommerce v10.3

= v3.0.0 - 20th September, 2025 =

📢 This update will invalidate previous tokens as they will no longer be valid.

With this update we have improved tracking of tokens to be dual-secured with a PAT (Personal Access Token) ID. This also makes sure users don't get unnecessary new tokens when already authenticated for proper token life cycle management and prevent token proliferation when users are already authenticated.

### What's New?

* Plugin: Refactored the plugin for better management and performance.
* Plugin: Added background database cleanup for legacy user meta data on plugin activation.
* REST-API: Users can now have multiple active token sessions, each tracked separately for different devices/browsers.
* REST-API: Refresh tokens are now properly linked to their corresponding JWT tokens.
* REST-API: Existing tokens are returned when authenticating with Bearer tokens (prevents token proliferation).
* WP-CLI: Creating a token now accepts the user ID, email or login. See documentation for updated command.
* WP-CLI: Added new `destroy` command to remove tokens for specific users with confirmation prompts.
* Dashboard: Added setup guide with secret key generator.

### Bug Fix

* WP-CLI: Fixed loading of localization too early.

### Improvements

* Plugin: Tokens will now log the last login timestamp. This is also part of the PAT (Personal Access Token).
* Plugin: Meta data is hidden from custom fields.
* REST-API: Authorization will fail if the user has no tokens in session.
* REST-API: Authorization will fail if the token is not found in session.
* REST-API: Token refresh now uses proper session rotation for enhanced security.
* WP-CLI: Listing user tokens will now list each token a user has. See documentation for updated command.
* WP-CLI: Now localized.

### Developers

* Introduced new filter `cocart_jwt_auth_max_user_tokens` that sets the maximum number of tokens stored for a user.
* Introduced new action hook `cocart_jwt_auth_authenticated` that fires when a user is authenticated.

### Compatibility

* Tested with CoCart v4.8

[View the full changelog here](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/CHANGELOG.md).

== Upgrade Notice ==

= 3.0.0 =

This update will invalidate previous tokens as they will no longer be valid.

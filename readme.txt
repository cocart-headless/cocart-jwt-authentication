=== CoCart JWT Authentication === 
Contributors: cocartforwc, sebd86
Tags: woocommerce, rest-api, decoupled, headless, jwt
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 2.4.0
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
* **WP-CLI Commands**: Useful [commands to handle tokens](/docs/wp-cli.md) - whether you need to check them, create new ones, or clean up old ones.
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

== Changelog ==

[View the full changelog here](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/CHANGELOG.md).

== Upgrade Notice ==

= 2.2.0 =

Update CoCart to version 4.3 before updating this plugin.
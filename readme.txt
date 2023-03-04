=== CoCart - JWT Authentication === 
Author URI: https://sebastiendumont.com
Plugin URI: https://cocart.xyz
Contributors: cocartforwc, sebd86
Tags: jwt, jwt-auth, json-web-token, woocommerce, cart, decoupled, headless
Requires at least: 5.6
Requires PHP: 7.4
Tested up to: 6.2
Stable tag: 1.0.0
WC requires at least: 6.4
WC tested up to: 7.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

JWT Authentication for CoCart.

== Description ==

This free add-on for [CoCart](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/) allows you to authenticate via a simple JWT Token.

## Enable PHP HTTP Authorization Header

### Shared Hosts

Most shared hosts have disabled the **HTTP Authorization Header** by default.

To enable this option you'll need to edit your **.htaccess** file by adding the following:

`
RewriteEngine on
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
`

or

`
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
`

### WPEngine

To enable this option you'll need to edit your **.htaccess** file by adding the following (see [this issue](https://github.com/Tmeister/wp-api-jwt-auth/issues/1)):

`
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
`

## Configuration

1. Set a unique secret key in your `wp-config.php` file defined to `COCART_JWT_AUTH_SECRET_KEY`.
2. Install and activate plugin.

### Token Expiration

By default, the token expires after two full days but can be filtered to change to your preference using this hook `cocart_jwt_auth_expire`.

Here is an example changing it to expire after just 2 hours.

`
add_filter( 'cocart_jwt_auth_expire', function() {
  return MINUTE_IN_SECONDS * 120
});
`

## Usage

1. Authenticate via basic method with the login endpoint to get your token.
2. Store the given token under `jwt_token` in your application.
3. Now authenticate any cart route with `Bearer` authentication with the token given.

## Tools and Libraries

* **[CoCart Beta Tester](https://github.com/co-cart/cocart-beta-tester)** allows you to easily update to prerelease versions of CoCart Lite for testing and development purposes.
* **[CoCart VSCode](https://github.com/co-cart/cocart-vscode)** extension for Visual Studio Code adds snippets and autocompletion of functions, classes and hooks.
* **[CoCart Product Support Boilerplate](https://github.com/co-cart/cocart-product-support-boilerplate)** provides a basic boilerplate for supporting a different product types to add to the cart with validation including adding your own parameters.
* **[CoCart Cart Callback Example](https://github.com/co-cart/cocart-cart-callback-example)** provides you an example of registering a callback that can be triggered when updating the cart.
* **[CoCart Tweaks](https://github.com/co-cart/co-cart-tweaks)** provides a starting point for developers to tweak CoCart to their needs.
* **[Official Node.js Library](https://www.npmjs.com/package/@cocart/cocart-rest-api)** provides a JavaScript wrapper supporting CommonJS (CJS) and ECMAScript Modules (ESM).

#### Other Add-ons to further enhance your cart.

We also have other add-ons that extend CoCart to enhance your development and your customers shopping experience.

* **[CoCart - Cart Enhanced](https://wordpress.org/plugins/cocart-get-cart-enhanced/)** enhances the data returned for the cart and the items added to it. – FREE
* **[CoCart - CORS](https://wordpress.org/plugins/cocart-cors/)** enables support for CORS to allow CoCart to work across multiple domains. - **FREE**
* **[CoCart - Rate Limiting](https://wordpress.org/plugins/cocart-rate-limiting/)** enables the rate limiting feature. - **FREE**

They work with the FREE version of CoCart already, and these add-ons of course come with support too.

### Join our growing community

A Slack community for developers, WordPress agencies and shop owners building the fastest and best headless WooCommerce stores with CoCart.

[Join our community](https://cocart.xyz/community/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart)

### Bug reports

Bug reports for CoCart - JWT Authentication are welcomed in the [CoCart - JWT Authentication repository on GitHub](https://github.com/co-cart/cocart-jwt-authentication). Please note that GitHub is not a support forum, and that issues that aren’t properly qualified as bugs will be closed.

### More information

* The [CoCart plugin](https://cocart.xyz/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart) official website.
* The CoCart [Documentation](https://docs.cocart.xyz/)
* [Subscribe to updates](http://eepurl.com/dKIYXE)
* Like, Follow and Star on [Facebook](https://www.facebook.com/cocartforwc/), [Twitter](https://twitter.com/cocartapi), [Instagram](https://www.instagram.com/co_cart/) and [GitHub](https://github.com/co-cart/co-cart)

= Credits =

This plugin is created by [Sébastien Dumont](https://sebastiendumont.com).

== Installation ==

= Minimum Requirements =

You will need CoCart v3.8.1 or above.

* WordPress v5.6
* WooCommerce v4.3
* PHP v7.3

= Recommended Requirements =

* WordPress v5.8 or higher.
* WooCommerce v5.2 or higher.
* PHP v7.4

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of CoCart - Cart Enhanced, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

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

[View the full changelog here](https://github.com/co-cart/cocart-jwt-authentication/blob/master/CHANGELOG.md).
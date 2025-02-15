=== CoCart - JWT Authentication === 
Contributors: cocartforwc, sebd86
Tags: woocommerce, rest-api, decoupled, headless, jwt
Requires at least: 5.6
Requires PHP: 7.4
Tested up to: 6.7
Stable tag: 1.0.3
WC requires at least: 7.0
WC tested up to: 9.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

JWT Authentication for CoCart.

== Description ==

This free add-on for [CoCart](https://wordpress.org/plugins/cart-rest-api-for-woocommerce/) allows you to authenticate the Cart API via a simple JWT Token.

★★★★★
> An excellent plugin, which makes building a headless WooCommerce experience a breeze. Easy to use, nearly zero setup time. [Harald Schneider](https://wordpress.org/support/topic/excellent-plugin-8062/)

## Enable PHP HTTP Authorization Header

### 🖥️ Shared Hosts

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

To enable this option you'll need to edit your **.htaccess** file by adding the following outside of IfModule:

`
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
`

Example of what that looks like.

`
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
</IfModule>

SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
# END WordPress
`

## 🧰 Configuration

1. Set a unique secret key in your `wp-config.php` file defined to `COCART_JWT_AUTH_SECRET_KEY`.
2. Install and activate plugin.

### Token Expiration

By default, the token expires after 10 full days but can be filtered to change to your preference using this hook `cocart_jwt_auth_expire`.

Here is an example changing it to expire after just 2 days.

`
add_filter( 'cocart_jwt_auth_expire', function() {
  return DAYS_IN_SECONDS * 2
});
`

## 📄 Usage

1. Authenticate via basic method with the CoCart login endpoint `cocart/v2/login` to get your token.
2. Store the given token under `jwt_token` in your application.
3. Now authenticate any cart route with `Bearer` authentication with the token given.

## 🧰 Developer Tools

* **[CoCart Beta Tester](https://github.com/cocart-headless/cocart-beta-tester)** allows you to easily update to pre-release versions of CoCart Lite for testing and development purposes.
* **[CoCart VSCode](https://github.com/cocart-headless/cocart-vscode)** extension for Visual Studio Code adds snippets and autocompletion of functions, classes and hooks.
* **[CoCart Product Support Boilerplate](https://github.com/cocart-headless/cocart-product-support-boilerplate)** provides a basic boilerplate for supporting a different product types to add to the cart with validation including adding your own parameters.
* **[CoCart Cart Callback Example](https://github.com/cocart-headless/cocart-cart-callback-example)** provides you an example of registering a callback that can be triggered when updating the cart.

★★★★★
> Amazing Plugin. I’m using it to create a react-native app with WooCommerce as back-end. This plugin is a life-saver! [Daniel Loureiro](https://wordpress.org/support/topic/amazing-plugin-1562/)

#### 👍 Add-ons to further enhance CoCart

We also have other add-ons that extend CoCart to enhance your development and your customers shopping experience.

* **[CoCart - Cart Enhanced](https://wordpress.org/plugins/cocart-get-cart-enhanced/)** enhances the data returned for the cart and the items added to it.
* **[CoCart - CORS](https://wordpress.org/plugins/cocart-cors/)** enables support for CORS to allow CoCart to work across multiple domains.
* **[CoCart - Rate Limiting](https://wordpress.org/plugins/cocart-rate-limiting/)** enables the rate limiting feature.
* and more add-ons in development.

They work with the core of CoCart already, and these add-ons of course come with support too.

### ⌨️ Join our growing community

A Discord community for developers, WordPress agencies and shop owners building the fastest and best headless WooCommerce stores with CoCart.

[Join our community](https://cocartapi.com/community/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart)

### 🐞 Bug reports

Bug reports for CoCart - JWT Authentication are welcomed in the [CoCart - JWT Authentication repository on GitHub](https://github.com/cocart-headless/cocart-jwt-authentication). Please note that GitHub is not a support forum, and that issues that aren’t properly qualified as bugs will be closed.

### More information

* The official [CoCart API plugin](https://cocartapi.com/?utm_medium=website&utm_source=wpplugindirectory&utm_campaign=readme&utm_content=readmelink) website.
* [CoCart for Developers](https://cocart.dev/?utm_medium=website&utm_source=wpplugindirectory&utm_campaign=readme&utm_content=readmelink), an official hub for resources you need to be productive with CoCart and keep track of everything that is happening with the API.
* The CoCart [Documentation](https://docs.cocart.xyz/)
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
* CoCart v4.2

= Recommended Requirements =

* WordPress v6.0 or higher.
* WooCommerce v9.0 or higher.
* PHP v8.0 or higher.

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

[View the full changelog here](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/CHANGELOG.md).

== Upgrade Notice ==

= 2.0.0 =

Update CoCart to version 4.2 before updating this plugin.
<h1 align="center">CoCart - JWT Authentication</h1>

<p align="center">
	<a href="https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/LICENSE.md" target="_blank">
		<img src="https://img.shields.io/badge/license-GPL--3.0%2B-red.svg" alt="Licence">
	</a>
	<a href="https://wordpress.org/plugins/cocart-jwt-authentication/">
		<img src="https://img.shields.io/wordpress/plugin/dt/cocart-jwt-authentication.svg" alt="WordPress Plugin Downloads">
	</a>
</p>

<p align="center">JWT Authentication for CoCart.</p>

## Minimum Requirements

You will need CoCart v3.8.1 or above to use this plugin.

## Enable PHP HTTP Authorization Header

### Shared Hosts

Most shared hosts have disabled the **HTTP Authorization Header** by default.

To enable this option you'll need to edit your **.htaccess** file by adding the following:

```
RewriteEngine on
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
```

or

```
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

### WPEngine

To enable this option you'll need to edit your **.htaccess** file by adding the following outside of IfModule:

```
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

Example of what that looks like.

```
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
```

## Configuration

1. Set a unique secret key in your `wp-config.php` file defined to `COCART_JWT_AUTH_SECRET_KEY`.
2. Install and activate plugin.

### Token Expiration

By default, the token expires after 10 full days but can be filtered to change to your preference using this hook `cocart_jwt_auth_expire`.

Here is an example changing it to expire after just 2 days.

```php
add_filter( 'cocart_jwt_auth_expire', function() {
  return DAYS_IN_SECONDS * 2
});
```

## Usage

1. Authenticate via basic method with the login endpoint to get your token.
2. Store the given token under `jwt_token` in your application.
3. Now authenticate any cart route with `Bearer` authentication with the token given.

## Bugs

If you find an issue, please [report the issue](https://github.com/cocart-headless/cocart-jwt-authentication/issues/new). Thank you.

---

## CoCart Channels

We have different channels at your disposal where you can find information about the CoCart project, discuss it and get involved:

[![Twitter: cocartapi](https://img.shields.io/twitter/follow/cocartapi?style=social)](https://twitter.com/cocartapi) [![CoCart GitHub Stars](https://img.shields.io/github/stars/co-cart/co-cart?style=social)](https://github.com/co-cart/co-cart)

<ul>
  <li>📖 <strong>Docs</strong>: this is the place to learn how to use CoCart API. <a href="https://docs.cocart.xyz/#getting-started">Get started!</a></li>
  <li>🧰 <strong>Resources</strong>: this is the hub of all CoCart resources to help you build a headless store. <a href="https://cocart.dev/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart">Get resources!</a></li>
  <li>👪 <strong>Community</strong>: use our Discord chat room to share any doubts, feedback and meet great people. This is your place too to share <a href="https://cocartapi.com/community/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart">how are you planning to use CoCart!</a></li>
  <li>🐞 <strong>GitHub</strong>: we use GitHub for bugs and pull requests, doubts are solved with the community.</li>
  <li>🐦 <strong>Social media</strong>: a more informal place to interact with CoCart users, reach out to us on <a href="https://twitter.com/cocartapi">Twitter.</a></li>
  <li>💌 <strong>Newsletter</strong>: do you want to receive the latest plugin updates and news? Subscribe <a href="https://twitter.com/cocartapi">here.</a></li>
</ul>

---

## License

[![License](https://img.shields.io/badge/license-GPL--3.0%2B-red.svg)](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/LICENSE.md)

Released under [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl-3.0.html).

## Credits

Website [cocartapi.com](https://cocartapi.com) &nbsp;&middot;&nbsp;
GitHub [@co-cart](https://github.com/co-cart) &nbsp;&middot;&nbsp;
Twitter [@cocartapi](https://twitter.com/cocartapi)

---

CoCart JWT Authentication is developed and maintained by [Sébastien Dumont](https://github.com/seb86).
Founder of [CoCart Headless, LLC](https://github.com/cocart-headless).

Website [sebastiendumont.com](https://sebastiendumont.com) &nbsp;&middot;&nbsp;
GitHub [@seb86](https://github.com/seb86) &nbsp;&middot;&nbsp;
Twitter [@sebd86](https://twitter.com/sebd86)
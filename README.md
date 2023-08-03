<h1 align="center">CoCart - JWT Authentication</h1>

<p align="center"><img src="https://cocart.xyz/wp-content/uploads/2021/11/cocart-home-default.png.webp" alt="CoCart Logo" /></p>

<br>

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

To enable this option you'll need to edit your **.htaccess** file by adding the following (see [this issue](https://github.com/Tmeister/wp-api-jwt-auth/issues/1)):

```
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

## Configuration

1. Set a unique secret key in your `wp-config.php` file defined to `COCART_JWT_AUTH_SECRET_KEY`.
2. Install and activate plugin.

### Token Expiration

By default, the token expires after two full days but can be filtered to change to your preference using this hook `cocart_jwt_auth_expire`.

Here is an example changing it to expire after just 2 hours.

```php
add_filter( 'cocart_jwt_auth_expire', function() {
  return MINUTE_IN_SECONDS * 120
});
```

## Usage

1. Authenticate via basic method with the login endpoint to get your token.
2. Store the given token under `jwt_token` in your application.
3. Now authenticate any cart route with `Bearer` authentication with the token given.

## License

Released under [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl-3.0.html).

---

## CoCart Channels

We have different channels at your disposal where you can find information about the CoCart project, discuss it and get involved:

[![Twitter: cocartapi](https://img.shields.io/twitter/follow/cocartapi?style=social)](https://twitter.com/cocartapi) [![CoCart GitHub Stars](https://img.shields.io/github/stars/co-cart/co-cart?style=social)](https://github.com/co-cart/co-cart)

<ul>
  <li>📖 <strong>Docs</strong>: this is the place to learn how to use CoCart API. <a href="https://docs.cocart.xyz/#getting-started">Get started!</a></li>
  <li>🧰 <strong>Resources</strong>: this is the hub of all CoCart resources to help you build a headless store. <a href="https://cocart.dev/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart">Get resources!</a></li>
  <li>👪 <strong>Community</strong>: use our Discord chat room to share any doubts, feedback and meet great people. This is your place too to share <a href="https://cocart.xyz/community/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart">how are you planning to use CoCart!</a></li>
  <li>🐞 <strong>GitHub</strong>: we use GitHub for bugs and pull requests, doubts are solved with the community.</li>
  <li>🐦 <strong>Social media</strong>: a more informal place to interact with CoCart users, reach out to us on <a href="https://twitter.com/cocartapi">Twitter.</a></li>
  <li>💌 <strong>Newsletter</strong>: do you want to receive the latest plugin updates and news? Subscribe <a href="https://twitter.com/cocartapi">here.</a></li>
</ul>

---

## Credits

CoCart JWT Authentication is developed and maintained by [Sébastien Dumont](https://github.com/seb86).

Founder of CoCart - [Sébastien Dumont](https://github.com/seb86).

---

Website [sebastiendumont.com](https://sebastiendumont.com) &nbsp;&middot;&nbsp;
GitHub [@seb86](https://github.com/seb86) &nbsp;&middot;&nbsp;
Twitter [@sebd86](https://twitter.com/sebd86)

<p align="center">
    <img src="https://raw.githubusercontent.com/seb86/my-open-source-readme-template/master/a-sebastien-dumont-production.png" width="353">
</p>
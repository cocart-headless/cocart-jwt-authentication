<h1 align="center">CoCart - JWT Authentication</h1>

<p align="center">
	<a href="https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/LICENSE.md" target="_blank">
		<img src="https://img.shields.io/badge/license-GPL--3.0%2B-red.svg" alt="Licence">
	</a>
	<a href="https://wordpress.org/plugins/cocart-jwt-authentication/">
		<img src="https://img.shields.io/wordpress/plugin/dt/cocart-jwt-authentication.svg" alt="WordPress Plugin Downloads">
	</a>
</p>

This free add-on for [CoCart](https://cocartapi.com/?utm_medium=wp.org&utm_source=wordpressorg&utm_campaign=readme&utm_content=cocart) allows you to authenticate the Cart API via JSON Web Tokens as an authentication method.

JSON Web Tokens are an open standard [RFC 7519](https://datatracker.ietf.org/doc/html/rfc7519) for securely transmitting information between parties.

[Read the core concept for more information](docs/concepts.md) on what this plugin does and can do.

### Key Features

* **Standard JWT Authentication**: Implements the industry-standard RFC 7519 for secure claims representation.
* **Simple Endpoints**: Offers clear endpoints for generating and validating tokens.
* **Configurable Secret Key**: Define your unique secret key via `wp-config.php` for secure token signing.
* **Multiple signing algorithms**: `HS256`, `HS384`, `HS512`, `RS256`, `RS384`, `RS512`, `ES256`, `ES384`, `ES512`, `PS256`, `PS384`, `PS512`
* **Rate Limiting**: Controlled specifically for refreshing and validating tokens. Requires [CoCart Plus](https://cocartapi.com/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart)
* **Helpful Debugging**: Detailed logs of authentication issues to help figure out exactly what happened and fix it faster.
* **WP-CLI Commands**: Useful [commands to handle tokens](/docs/wp-cli.md) - whether you need to check them, create new ones, or clean up old ones.
* **Developer Hooks**: Provides [filters](docs/filters.md) and [hooks](docs/hooks.md) for more configuration to your requirements.

For support, please join the [community on Discord](https://cocartapi.com/community/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart). For priority support, consider upgrading to [CoCart Plus](https://cocartapi.com/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart).

### For Developers

See documentation on how to [get setup](docs/guide.md), [filters](docs/filters.md) and [hooks](docs/hooks.md) with examples to help configure JWT Authentication to your needs.

Once ready to use, see the [quick start guide](docs/quick-start.md). There is also an [advanced configuration](docs/advanced-configuration.md) for using RSA Keys.

## Bugs and Security

If you find an issue, please [report the issue](https://github.com/cocart-headless/cocart-jwt-authentication/issues/new). If you believe you have found a security issue then [read the security policy](SECURITY.md).

## CoCart Channels

We have different channels at your disposal where you can find information about the CoCart project, discuss it and get involved:

[![Twitter: cocartapi](https://img.shields.io/twitter/follow/cocartapi?style=social)](https://twitter.com/cocartapi) [![CoCart GitHub Stars](https://img.shields.io/github/stars/cocart-headless/cocart-jwt-authentication?style=social)](https://github.com/cocart-headless/cocart-jwt-authentication)

<ul>
  <li>📖 <strong>Documentation</strong>: this is the place to learn how to use CoCart API. <a href="https://cocartapi.com/docs/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart">Get started!</a></li>
  <li>👪 <strong>Community</strong>: use our Discord chat room to share any doubts, feedback and meet great people. This is your place too to share <a href="https://cocartapi.com/community/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart">how are you planning to use CoCart!</a></li>
  <li>🐞 <strong>GitHub</strong>: we use GitHub for bugs and pull requests, doubts are solved with the community.</li>
  <li>🐦 <strong>Social media</strong>: a more informal place to interact with CoCart users, reach out to us on <a href="https://twitter.com/cocartapi">X/Twitter.</a></li>
</ul>

For additional security, consider our [API Security](https://apisecurity.pro/?utm_medium=gh&utm_source=github&utm_campaign=readme&utm_content=cocart) plugin that provides a firewall to block unknown outsiders, rate limit requests and protect data exposure – no configuration required.

## License

[![License](https://img.shields.io/badge/license-GPL--3.0%2B-red.svg)](https://github.com/cocart-headless/cocart-jwt-authentication/blob/master/LICENSE.md)

Released under [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl-3.0.html).

## Credits

Website [cocartapi.com](https://cocartapi.com/?ref=github) &nbsp;&middot;&nbsp;
GitHub [@cocart-headless](https://github.com/cocart-headless) &nbsp;&middot;&nbsp;
X/Twitter [@cocartapi](https://twitter.com/cocartapi) &nbsp;&middot;&nbsp;
[Facebook](https://www.facebook.com/cocartforwc/) &nbsp;&middot;&nbsp;
[Instagram](https://www.instagram.com/cocartheadless/)

---

CoCart JWT Authentication is developed and maintained by [Sébastien Dumont](https://github.com/seb86).
Founder of [CoCart Headless, LLC](https://github.com/cocart-headless).

Website [sebastiendumont.com](https://sebastiendumont.com) &nbsp;&middot;&nbsp;
GitHub [@seb86](https://github.com/seb86) &nbsp;&middot;&nbsp;
Twitter [@sebd86](https://twitter.com/sebd86)
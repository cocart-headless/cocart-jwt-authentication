# Changelog for CoCart JWT Authentication

## v2.1.0 - 3rd March, 2025

## Improvements

* Added support for getting username when authenticating basic via simple headers or URL.
* Added error response should the user to look up suddenly no longer exist in the middle of a request.

### For Developers

Added a set of actions that allow you to hook into various events. [See hooks](docs/hooks.md) with its description and usage example.

### Compatibility

* Tested with CoCart v4.3
* Tested with WooCommerce 9.7

## v2.0.0 - 25th February, 2025

📢 This update will invalidate previous tokens as they will no longer be valid.

### What's New?

* Bind tokens to specific IP addresses or devices to mitigate token misuse.
* Algorithm can be changed to any other supported. See: https://datatracker.ietf.org/doc/html/rfc7518#section-3
* Refresh token with new REST API endpoint `cocart/jwt/refresh-token`.

### Changes

* Token expires after 10 days by default not 2 hours.
* Filter `cocart_jwt_auth_expire` added token issuance timestamp as parameter.

### Improvements

* Authentication errors have been simplified so they are harder to identify.

### For Developers

[See documentation](docs/filters.md) for examples on how to use these filters.

* Introduced new filter `cocart_jwt_auth_issuer` to change the token issuer (iss claim) for multi-site setups or custom API endpoints.
* Introduced new filter `cocart_jwt_auth_issued_at` to change the token issuance timestamp (iat claim) for token timing synchronization.
* Introduced new filter `cocart_jwt_auth_not_before` to change the timestamp.
* Introduced new filter `cocart_jwt_auth_algorithm` to change to any other supported algorithms.
* Introduced new filter `cocart_jwt_auth_refresh_token_generation` to change how refresh tokens are generated.
* Introduced new filter `cocart_jwt_refresh_token_expiration` to customize refresh token lifetime based on roles or conditions.

### Compatibility and Requirements

* Tested with WordPress 6.7
* Tested with WooCommerce 9.6
* Requires CoCart v4.3 minimum.

## v1.0.3 - 5th June, 2024

### What's New?

* Authorization header now detectable with `getallheaders()` function.

## v1.0.2 - 10th May, 2024

### Compatibility

* Tested with WooCommerce v8.8
* Tested with WordPress v6.5

## v1.0.1 - 3rd August, 2023

### What's New?

* Removed WooCommerce plugin headers to prevent incompatibility warning message when using "HPOS" feature.

## v1.0.0 - 9th March, 2023

* Initial version.

# Changelog for CoCart JWT Authentication

## v2.5.1 - 20th June, 2025

### 🔥 Hot Patch

Last release broke support for guest users. [Reported](https://github.com/cocart-headless/cocart-jwt-authentication/issues/24) by @marianobitelo MB. This patch resolves it by validating the auth header correctly. If auth header is present but returns an empty value, it will fail safely instead of an error response.

## v2.5.0 - 19th June, 2025

### Corrections

* Corrected plugin slug to identify plugin for the logger.

### Improvements

* Token value checks against an improved pattern.
* Improved current debug logs.
* Improved token validation.
* Added more debug logs.

### Developers

* Introduced new filter `cocart_jwt_auth_token_prefix` that allows you to set a prefix to the token to help distinguish from other tokens from other sources.

### Compatibility

* Tested with CoCart v4.6
* Tested with WooCommerce 9.9

## v2.4.0 - 9th May, 2025

### What's New?

* Added debug logs for when authentication fails to help developers identify where the token failed.
* Added WooCommerce System Status data and a button to clear expired tokens manually under "Tools".

## v2.3.1 - 7th May, 2025

### Bug Fix

* Fixed uncaught error.

## v2.3.0 - 28th April, 2025

### What's New?

* Added support for more advanced RSA-based configuration.

### Developers

* Introduced new filter `cocart_jwt_auth_token_before_sign` that allows the token data to be altered before the sign.
* Introduced new filter `cocart_jwt_auth_secret_private_key` that allows you to set the secret private key for token signing.
* Introduced new filter `cocart_jwt_auth_secret_public_key` that allows you to set the public key for token validation.
* Introduced new filter `cocart_jwt_auth_revoke_tokens_on_email_change` that allows you to control token revocation on email changes.
* Introduced new filter `cocart_jwt_auth_revoke_tokens_on_password_change` that allows you to control token revocation on password changes for security policies.
* Introduced new filter `cocart_jwt_auth_revoke_tokens_on_after_password_reset` that allows you to control token revocation on.
* Introduced new filter `cocart_jwt_auth_revoke_tokens_on_profile_update` that allows you to control token revocation on profile update.
* Introduced new filter `cocart_jwt_auth_revoke_tokens_on_delete_user` that allows you to control token revocation on user delete.
* Introduced new filter `cocart_jwt_auth_revoke_tokens_on_wp_logout` that allows you to control token revocation when a user logs out.

* Renamed `cocart_jwt_token_generated` action hook to `cocart_jwt_auth_token_generated` to be consistent with other action hooks. (This is considered a typo correction)

## v2.2.0 - 17th March, 2025

### What's New?

* REST-API: Added validation endpoint `cocart/jwt/validate-token`.
* WP-CLI: New commands to help list, view, validate, clear expired (or force all) and create tokens.

### Bug Fix

* Fixed user agent not checking the value of the header.

### Improvements

* Improved creating token and user identification.
* Improved destroying token when user changes password.
* Improved the cleanup of expired tokens to work in batches of 100 for performance.

### For Developers

* Introduced new filter `cocart_jwt_auth_token_user_data` to allow additional user data to be applied to the payload before the token is generated.

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

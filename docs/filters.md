# Filters

CoCart JWT Authentication provides a comprehensive set of filters that allow you to customize its behavior. Each filter is documented below with its description and usage example.

## Authentication filters

`cocart_jwt_auth_issued_at`

> Made available since v2.0.0

Allows you to change the token issuance timestamp (iat claim) for token timing synchronization.

```php
add_filter( 'cocart_jwt_auth_issued_at', function( $timestamp ) {
    // Add a 5-minute buffer
    return $timestamp + (5 * MINUTE_IN_SECONDS);
} );
```

`cocart_jwt_auth_issuer`

> Made available since v2.0.0

Allows you to change the token issuer (iss claim) for multi-site setups or custom API endpoints.

```php
add_filter( 'cocart_jwt_auth_issuer', function( $issuer ) {
    return 'https://api.yoursite.com';
} );
```

`cocart_jwt_auth_not_before`

> Made available since v2.0.0

Allows you to set when the token becomes valid (nbf claim) for token activation control.

```php
add_filter( 'cocart_jwt_auth_not_before', function( $time, $issued_at ) {
    // Token becomes valid 5 minutes after issuance
    return $issued_at + (5 * MINUTE_IN_SECONDS);
}, 10, 2);
```

`cocart_jwt_auth_expire`

> Made available since v2.0.0

Allows you to customize when the token will expire (exp claim) based on roles or conditions.

```php
add_filter( 'cocart_jwt_auth_expire', function( $expiration, $issued_at ) {
    // Set expiration to 2 days
    return 2 * DAY_IN_SECONDS;
}, 10, 2);
```

`cocart_jwt_auth_algorithm`

> Made available since v2.0.0

Allows you to change the algorithm used for token signing.

```php
add_filter( 'cocart_jwt_auth_algorithm', function( $algorithm ) {
    return 'RS256'; // Use RSA SHA-256 instead of default HS256
});
```

`cocart_jwt_auth_token_user_data`

> Made available since v2.0.0

Allows additional user data to be applied to the payload before the token is generated.

```php
add_filter( 'cocart_jwt_auth_token_user_data', function( $data, $user ) {
    return array_merge( $data, array(
        'role'         => $user->roles[0],
        'display_name' => $user->display_name,
        'email'        => $user->user_email
    ) );
}, 10, 2);
```

`cocart_jwt_auth_token_before_sign`

> Made available since v2.3.0

Allows you to modify the complete token payload before signing.

```php
add_filter( 'cocart_jwt_auth_token_before_sign', function( $token, $user ) {
    $token['custom_claim'] = 'custom_value';

    return $token;
}, 10, 2);
```

`cocart_jwt_auth_secret_private_key`

> Made available since v2.3.0

Allows you to set the private key for token signing.

```php
// Set the private key for token signing.
add_filter( 'cocart_jwt_auth_secret_private_key', function( $key ) {
    return file_get_contents( ABSPATH . 'path/to/private.key' );
});
```

`cocart_jwt_auth_secret_public_key`

> Made available since v2.3.0

Allows you to set the public key for token validation.

```php
// Set the public key for token validation.
add_filter( 'cocart_jwt_auth_secret_public_key', function( $key ) {
    return file_get_contents( ABSPATH . 'path/to/public.key' );
});
```

## Refresh Token Filters

`cocart_jwt_auth_refresh_token_generation`

> Made available since v2.0.0

Allows you to change how refresh tokens are generated.

```php
add_filter( 'cocart_jwt_auth_refresh_token_generation', function( $token ) {
    return md5( uniqid() . time() ); // Use MD5 for token generation
});
```

`cocart_jwt_auth_refresh_token_expiration`

> Made available since v2.0.0

Allows you to customize refresh token lifetime based on roles or conditions.

```php
add_filter( 'cocart_jwt_auth_refresh_token_expiration', function( $expiration ) {
    return 60 * DAY_IN_SECONDS; // Set to 60 days
});
```

## Token Management

`cocart_jwt_auth_revoke_tokens_on_email_change`

> Made available since v2.3.0

Allows you to control token revocation on email changes.

```php
add_filter( 'cocart_jwt_auth_revoke_tokens_on_email_change', function( $should_revoke, $user_id ) {
    return true; // Always revoke tokens on email change.
}, 10, 2);
```

`cocart_jwt_auth_revoke_tokens_on_password_change`

> Made available since v2.3.0

Allows you to control token revocation on password changes for security policies.

```php
add_filter( 'cocart_jwt_auth_revoke_tokens_on_password_change', function( $should_revoke, $user_id ) {
    return $user_id !== 1; // Don't revoke tokens for admin user
}, 10, 2);
```

`cocart_jwt_auth_revoke_tokens_on_after_password_reset`

> Made available since v2.3.0

Allows you to control token revocation on password reset for security policies.

```php
add_filter( 'cocart_jwt_auth_revoke_tokens_on_after_password_reset', function( $should_revoke, $user_id ) {
    return true; // Always revoke tokens after password reset.
}, 10, 2);
```

`cocart_jwt_auth_revoke_tokens_on_profile_update`

> Made available since v2.3.0

Allows you to control token revocation on profile update.

```php
add_filter( 'cocart_jwt_auth_revoke_tokens_on_profile_update', function( $should_revoke, $user_id ) {
    return true; // Always revoke tokens on profile change.
}, 10, 2);
```

`cocart_jwt_auth_revoke_tokens_on_delete_user`

> Made available since v2.3.0

Allows you to control token revocation when a user is deleted.

```php
add_filter( 'cocart_jwt_auth_revoke_tokens_on_delete_user', function( $should_revoke, $user_id ) {
    return true; // Always revoke tokens when user is deleted.
}, 10, 2);
```

`cocart_jwt_auth_revoke_tokens_on_wp_logout`

> Made available since v2.3.0

Allows you to control token revocation when a user logs out.

```php
add_filter( 'cocart_jwt_auth_revoke_tokens_on_wp_logout', function( $should_revoke, $user_id ) {
    return true; // Always revoke tokens on logout.
}, 10, 2);
```

> All filters follow WordPress coding standards and can be used with the standard add_filter() function. The examples above show practical implementations for each filter.

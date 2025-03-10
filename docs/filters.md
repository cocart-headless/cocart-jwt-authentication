# Filters

CoCart JWT Authentication provides a comprehensive set of filters that allow you to customize its behavior. Each filter is documented below with its description and usage example.

`cocart_jwt_auth_issued_at`

Allows you to change the token issuance timestamp (iat claim) for token timing synchronization.

```php
add_filter( 'cocart_jwt_auth_issued_at', function( $timestamp ) {
    // Add a 5-minute buffer
    return $timestamp + (5 * MINUTE_IN_SECONDS);
} );
```

`cocart_jwt_auth_issuer`

Allows you to change the token issuer (iss claim) for multi-site setups or custom API endpoints.

```php
add_filter( 'cocart_jwt_auth_issuer', function( $issuer ) {
    return 'https://api.yoursite.com';
} );
```

`cocart_jwt_auth_not_before`

Allows you to set when the token becomes valid (nbf claim) for token activation control.

```php
add_filter( 'cocart_jwt_auth_not_before', function( $time, $issued_at ) {
    // Token becomes valid 5 minutes after issuance
    return $issued_at + (5 * MINUTE_IN_SECONDS);
}, 10, 2);
```

`cocart_jwt_auth_expire`

Allows you to customize when the token will expire (exp claim) based on roles or conditions.

```php
add_filter( 'cocart_jwt_auth_expire', function( $expiration, $issued_at ) {
    // Set expiration to 14 days
    return $issued_at + (14 * DAY_IN_SECONDS);
}, 10, 2);
```

`cocart_jwt_auth_algorithm`

Allows you to change the algorithm used for token signing.

```php
add_filter( 'cocart_jwt_auth_algorithm', function( $algorithm ) {
    return 'RS256'; // Use RSA SHA-256 instead of default HS256
});
```

`cocart_jwt_auth_token_user_data`

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

`cocart_jwt_auth_refresh_token_generation`

Allows you to change how refresh tokens are generated.

```php
add_filter( 'cocart_jwt_auth_refresh_token_generation', function( $token ) {
    return md5( uniqid() . time() ); // Use MD5 for token generation
});
```

`cocart_jwt_auth_refresh_token_expiration`

Allows you to customize refresh token lifetime based on roles or conditions.

```php
add_filter( 'cocart_jwt_auth_refresh_token_expiration', function( $expiration ) {
    return 60 * DAY_IN_SECONDS; // Set to 60 days
});
```

> All filters follow WordPress coding standards and can be used with the standard add_filter() function. The examples above show practical implementations for each filter.

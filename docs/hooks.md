# Actions

CoCart JWT Authentication provides a set of actions that allow you to hook into various events. Each action is documented below with its description and usage example.

`cocart_jwt_token_generated`

Fires when a new JWT token is generated after successful authentication.

```php
add_action( 'cocart_jwt_token_generated', function( $token, $user ) {
    // Log token generation
    error_log("New token generated for user: {$user->ID}");
}, 10, 2 );
```

`cocart_jwt_auth_token_refreshed`

Fires when a token is refreshed using a refresh token.

```php
add_action( 'cocart_jwt_auth_token_refreshed', function( $token, $user ) {
    // Track token refresh events
    error_log("Token refreshed for user: {$user->ID}");
}, 10, 2 );
```

`cocart_jwt_auth_token_validated`

Fires when a token is successfully validated.

```php
add_action( 'cocart_jwt_auth_token_validated', function( $decoded ) {
    // Access validated token data
    $user_id = $decoded->data->user->id;
    error_log("Token validated for user: {$user_id}");
} );
```

`cocart_jwt_auth_token_deleted`

Fires when a token is deleted.

```php
add_action( 'cocart_jwt_auth_token_deleted', function( $user_id ) {
    $user = get_user_by( 'id', $user_id );

    // Cleanup after token deletion
    error_log("Token for {$user->display_name} has been deleted");
}, 10, 2 );
```

> All actions follow WordPress coding standards and can be used with the standard add_action() function. The examples above show practical implementations for each action.
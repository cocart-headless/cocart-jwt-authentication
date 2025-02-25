# Guide for CoCart JWT Authentication

## Prerequisites

Before installing CoCart JWT Authentication, ensure your system meets these requirements:

- WordPress 6.0 or higher.
- PHP 7.4 or higher.
- CoCart v4.2 or higher.
- Valid SSL certificate for production use.

## Secret Key Configuration

Add the following code to your wp-config.php file:

```php
define( 'COCART_JWT_AUTH_SECRET_KEY', 'YOUR-UNIQUE-SECRET-KEY' );
```

> Never share or commit your secret key. Keep it secure and unique for each environment.

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

## How It Works

Here’s how the authentication process works in your WordPress application:

1. Authentication Request

Client authenticates the login endpoint via Authorization header using the basic method to obtain JWT tokens.

1. Token Usage

Use the JWT token to authenticate any REST API requests via Authorization header using bearer method.

3. Token Refresh

Use refresh token to obtain new access tokens without re-authentication via the refresh-token endpoint.

## Security Best Practices

CoCart JWT Authentication comes with built-in security features to protect your WordPress application. Here are the key security measures you should be aware of:

* Automatic token revocation on password/email changes.
* Automatic token revocation on user deletion.
* Automatic token revocation on user logout.
* Configurable token expiration times.
* Secure refresh token rotation.

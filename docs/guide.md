# Guide for CoCart JWT Authentication

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

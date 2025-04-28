# Advanced Configuration
​
## Using RSA Keys (RS256)

By default, CoCart JWT uses HS256 (HMAC SHA-256) for token signing. You can switch to RS256 (RSA SHA-256) for enhanced security, especially in distributed systems.
​
1. Generate RSA Keys

First, generate a private/public key pair:

```bash
# Generate private key
openssl genpkey -algorithm RSA -out private.key -pkeyopt rsa_keygen_bits:2048

# Generate public key
openssl rsa -pubout -in private.key -out public.key
```

2. Configure Keys

Add these filters to a custom must-use plugin:

```php
// Set the algorithm to RS256
add_filter( 'cocart_jwt_auth_algorithm', function( $algorithm ) {
    return 'RS256';
});

// Set the private key for token signing.
add_filter( 'cocart_jwt_auth_secret_private_key', function( $key ) {
    return file_get_contents( ABSPATH . 'path/to/private.key' );
});

// Set the public key for token validation.
add_filter( 'cocart_jwt_auth_secret_public_key', function( $key ) {
    return file_get_contents( ABSPATH . 'path/to/public.key' );
});
```

> !warning Store your keys securely and never commit them to version control. Consider using environment variables or WordPress constants in `wp-config.php` to store the key paths.

1. Key Storage Example

A secure way to configure keys using constants:

```php
// In wp-config.php
define( 'COCART_JWT_AUTH_PRIVATE_KEY_PATH', '/secure/path/private.key' );
define( 'COCART_JWT_AUTH_PUBLIC_KEY_PATH', '/secure/path/public.key' );

// In your code
add_filter( 'cocart_jwt_auth_secret_private_key', function( $key ) {
    return file_get_contents( COCART_JWT_AUTH_PRIVATE_KEY_PATH );
});

add_filter( 'cocart_jwt_auth_secret_public_key', function( $key ) {
    return file_get_contents( COCART_JWT_AUTH_PUBLIC_KEY_PATH );
});
```

4. Using Key Strings Directly

Alternatively, you can use the RSA key strings directly in your code:

```php
add_filter( 'cocart_jwt_auth_algorithm', function( $algorithm ) {
    return 'RS256';
});

add_filter( 'cocart_jwt_auth_secret_private_key', function( $key ) {
    return <<<EOD
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDL+eJwzzKROIYM
CxsEWygFNhRk5rfAhVxleG7MSBzRZR9eJtXbNQYbvckhoggBaYOi77SfmGF1xa4L
BcaaC73X6O5XTCxGf36CVKFt8SGpZ5RQVi6hbiy4QD+7/tsur/J1IX+8ZRHSaEb4
uugvgrP/WYyhWDTw21HG+eObG9pNLysIWLDLlDQGQapq96NoiM6KbnUtYa+T2yS/
M84f0CU9bw3fKijXScJwdp9Jn72GpusB51o4WbhY0x0qY37cWD1+q6lzX6Yitfyb
aNRxvgmbyA9sBUXOyxiCHXnCYqmDhj8j2BBq2uaMJo8uTj2Ihsb3H2B3qYv96n8s
WpQiOLjZAgMBAAECggEACfR3ozbRinnePO870dIbGWoCw8vo0uoPUNp4Wdr5IRUU
21T84fZsBmWUT+JnDp6WMJkPUSywMP3FWT9ean1BNizlmPgcPxKQ246C1GlM0cY+
3E7gdrt4TkPAeI3fJ5+pryLD569tF75EHHaOx2bTHxbBQMybxNeHXbUrcRICbPrW
h3fgiDVqh8+yQcWQ6LEaYsb2qO918ubkBF1clBEEjI0+NckdybpZ5V7idPjujyzh
jTOHa2gyvk6HgV5dBtsWFfGdzFtlCieu8CV+rGwhrm4x/R0BFZmYvNciifjNHCux
Hm+brpyTF7x/BVP2K4S++rlY6Pkw1W3Mg1qQTKMQKwKBgQDrrG++MO7x9USsfz5g
uMlz/5Hd+o7aaqKCT8Ksqe5vqgpAvHap4rUZRElLTiaZtU/Ox/6BYjPn5e1bG1cV
jPcSiyH6rbUjLa1t03q+ZT9+RvVUTcaMuvSkUlMqMHp0HhBWHmmohh6fYcqfDehj
7kWZmgDBBDAe6F9+8CaZcjMAowKBgQDdkZUl+zcG+v/yo1gKXAb7JbTRJ15Y6eyl
N1hnVseHG8Eah4/0EA/9HTpElieUQwnQ3UJ6t68bSJNqGxCKqw2xffJP+EY7mYhe
xaeB5+7J4C2o9wb2BIk7C4Di1JdS+PIQtafdAsTdnHIMO9olCh0e1t7cu/CyjBFj
eC9dZdasUwKBgQC+nmqDWvDpo4g4PXMmqE/JEx3YfaCt6TIoVHsRTSEeEFraoZUZ
M9Vm6mSeFEgEazJx/jFMVTHGj6K73hFBzRLKXN7O81FfcsKj4jmVZi9E4//qgD3n
9g+KGUxLA4sIAIkWHuM2+8QpBd/tZkJhEYgaBQY3GDwTw7/53CRcWJIIIQKBgClk
L/u3cXExZK0cTK8qv/cc9Sl5dEuh755xt2cetAmOasWc+4x5j7MWSbNUZbJxz5yg
KPIp7GFpbniM88sj51v4DlNYKy6pIOurev5uqJI3+e+trjQ5ZrWMEZjOZDKQ5Q+w
D9re0I4h0sBsUfVHbWc8dse/qFiCiCEP67FD/BWXAoGBAKRpS1QgwNceOD1OiH/q
50v6hQFXzBTjQXh4r4yVkGu7XJtxf/2I0hfUOFBXn9L6hMcacqQIOntMkARFDWUo
PbHzuurEVSOXyivhZ5utXIKpH8wnxw4odzPcmIwUKQbcanmaikEgsZufMMVlnG4u
z/mGBpbzVSyJILT3D8oSF/Z0
-----END PRIVATE KEY-----
EOD;
});

add_filter( 'cocart_jwt_auth_secret_public_key', function( $key ) {
    return <<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy/nicM8ykTiGDAsbBFso
BTYUZOa3wIVcZXhuzEgc0WUfXibV2zUGG73JIaIIAWmDou+0n5hhdcWuCwXGmgu9
1+juV0wsRn9+glShbfEhqWeUUFYuoW4suEA/u/7bLq/ydSF/vGUR0mhG+LroL4Kz
/1mMoVg08NtRxvnjmxvaTS8rCFiwy5Q0BkGqavejaIjOim51LWGvk9skvzPOH9Al
PW8N3yoo10nCcHafSZ+9hqbrAedaOFm4WNMdKmN+3Fg9fqupc1+mIrX8m2jUcb4J
m8gPbAVFzssYgh15wmKpg4Y/I9gQatrmjCaPLk49iIbG9x9gd6mL/ep/LFqUIji4
2QIDAQAB
-----END PUBLIC KEY-----
EOD;
});
```

> !warning While using key strings directly in code is possible, it’s recommended to store them in secure environment variables or files for better security and key management.

## Benefits of RS256

- **Asymmetric Encryption**: Different keys for signing and verification.
- **Better Security**: Private key can be kept secret on the authentication server.
- **Scalability**: Public key can be distributed to multiple verification servers.
- **Standard Compliance**: Widely used in enterprise applications.

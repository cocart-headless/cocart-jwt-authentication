# Google Authentication for CoCart JWT Authentication

This documentation provides a complete guide for developers to set up and implement Google OAuth authentication with the CoCart JWT Authentication plugin.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Google Cloud Console Setup](#google-cloud-console-setup)
4. [WordPress Configuration](#wordpress-configuration)
5. [Implementation Guide](#implementation-guide)
6. [API Endpoints](#api-endpoints)
7. [Frontend Integration Examples](#frontend-integration-examples)
8. [Error Handling](#error-handling)
9. [Security Considerations](#security-considerations)
10. [Hooks and Filters](#hooks-and-filters)
11. [Troubleshooting](#troubleshooting)

## Overview

The Google Authentication extension for CoCart JWT Authentication allows users to authenticate using their Google accounts. It provides secure OAuth 2.0 integration with automatic user creation and JWT token generation.

### Key Features

- **Seamless Google OAuth 2.0 integration**
- **Automatic user creation or linking**
- **JWT token generation for API access**
- **Rate limiting for security**
- **Comprehensive logging**
- **Flexible user management**

## Prerequisites

Before implementing Google Authentication, ensure you have:

- CoCart JWT Authentication plugin installed and activated
- A Google Cloud Console account
- SSL certificate installed (required for OAuth)
- Basic understanding of OAuth 2.0 flow
- Frontend application capable of handling Google OAuth

## Google Cloud Console Setup

### 1. Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the **Google+ API** and **Google Identity Toolkit API**

### 2. Configure OAuth Consent Screen

1. Navigate to **APIs & Services > OAuth consent screen**
2. Choose **External** user type (or Internal for G Suite domains)
3. Fill in the required information:
   - Application name
   - User support email
   - Developer contact information
4. Add your domain to **Authorized domains**
5. Save and continue

### 3. Create OAuth 2.0 Credentials

1. Go to **APIs & Services > Credentials**
2. Click **Create Credentials > OAuth 2.0 Client IDs**
3. Select **Web application**
4. Configure:
   - **Name**: Your application name
   - **Authorized JavaScript origins**: `https://yourdomain.com`
   - **Authorized redirect URIs**: Your frontend callback URLs
5. Save the **Client ID** and **Client Secret**

## WordPress Configuration

### 1. Define Constants

Add the following constants to your `wp-config.php` file:

```php
// Google OAuth Configuration
define( 'COCART_GOOGLE_CLIENT_ID', 'your-google-client-id.apps.googleusercontent.com' );

// Optional: Enable Google user creation (default: true)
define( 'COCART_GOOGLE_USER_CREATION_ENABLED', true );
```

### 2. Include the Google Authentication Class

Add this to your theme's `functions.php` or a custom plugin:

```php
// Load Google Authentication class
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'CoCart\JWTAuthentication\Plugin' ) ) {
        include_once WP_PLUGIN_DIR . '/cocart-jwt-authentication/includes/class-google-auth-rest.php';
    }
});
```

## Implementation Guide

### Authentication Flow

1. **Frontend**: User clicks "Sign in with Google"
2. **Frontend**: Initiates Google OAuth flow and receives ID token
3. **Frontend**: Sends ID token to WordPress `/wp-json/cocart/auth/google-auth`
4. **Backend**: Verifies token with Google, creates/finds user
5. **Backend**: Returns JWT tokens for API access
6. **Frontend**: Uses JWT tokens for subsequent API calls

### Step-by-Step Implementation

#### Step 1: Frontend Google OAuth Setup

```javascript
// Load Google API library
<script src="https://apis.google.com/js/api:client.js"></script>

// Initialize Google API
function initializeGoogleAuth() {
    gapi.load('auth2', function() {
        gapi.auth2.init({
            client_id: 'your-google-client-id.apps.googleusercontent.com'
        });
    });
}

// Handle Google Sign-In
function signInWithGoogle() {
    const authInstance = gapi.auth2.getAuthInstance();
    
    authInstance.signIn().then(function(googleUser) {
        const idToken = googleUser.getAuthResponse().id_token;
        authenticateWithWordPress(idToken);
    }).catch(function(error) {
        console.error('Google Sign-In failed:', error);
    });
}
```

#### Step 2: Send ID Token to WordPress

```javascript
async function authenticateWithWordPress(idToken) {
    try {
        const response = await fetch('https://yoursite.com/wp-json/cocart/auth/google-auth', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_token: idToken
            })
        });

        const data = await response.json();
        
        if (response.ok) {
            // Store JWT tokens
            localStorage.setItem('jwt_token', data.token);
            localStorage.setItem('refresh_token', data.refresh_token);
            
            console.log('Authentication successful:', data);
            // Redirect or update UI
        } else {
            console.error('Authentication failed:', data);
        }
    } catch (error) {
        console.error('Network error:', error);
    }
}
```

#### Step 3: Use JWT Tokens for API Calls

```javascript
async function makeAuthenticatedRequest(endpoint) {
    const token = localStorage.getItem('jwt_token');
    
    try {
        const response = await fetch(`https://yoursite.com/wp-json/cocart/v2/${endpoint}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        return response.json();
    } catch (error) {
        console.error('API request failed:', error);
    }
}
```

## API Endpoints

### POST `/wp-json/cocart/auth/google-auth`

Authenticate user with Google ID token.

#### Request Body
```json
{
    "id_token": "google_id_token_here"
}
```

#### Success Response (200)
```json
{
    "token": "jwt_token_here",
    "refresh_token": "refresh_token_here",
    "user": {
        "id": 123,
        "username": "john.doe",
        "email": "john@example.com",
        "display_name": "John Doe",
        "google_id": "google_user_id"
    },
    "message": "Successfully authenticated with Google."
}
```

#### Error Responses
- `400`: Missing or invalid ID token
- `401`: Invalid or expired Google token
- `403`: Registration disabled or authentication failed
- `500`: Server configuration error

### GET `/wp-json/cocart/auth/google-user-info`

Get Google user information for authenticated users.

#### Headers
```
Authorization: Bearer your_jwt_token
```

#### Success Response (200)
```json
{
    "user_id": 123,
    "username": "john.doe",
    "email": "john@example.com",
    "display_name": "John Doe",
    "google_id": "google_user_id",
    "google_email": "john@gmail.com",
    "linked": true
}
```

## Frontend Integration Examples

### React Implementation

```jsx
import React, { useEffect, useState } from 'react';

const GoogleAuth = () => {
    const [isLoaded, setIsLoaded] = useState(false);
    const [user, setUser] = useState(null);

    useEffect(() => {
        // Load Google API
        const script = document.createElement('script');
        script.src = 'https://apis.google.com/js/api:client.js';
        script.onload = initializeGoogleAuth;
        document.body.appendChild(script);
    }, []);

    const initializeGoogleAuth = () => {
        window.gapi.load('auth2', () => {
            window.gapi.auth2.init({
                client_id: 'your-google-client-id.apps.googleusercontent.com'
            }).then(() => {
                setIsLoaded(true);
            });
        });
    };

    const handleGoogleSignIn = async () => {
        const authInstance = window.gapi.auth2.getAuthInstance();
        
        try {
            const googleUser = await authInstance.signIn();
            const idToken = googleUser.getAuthResponse().id_token;
            
            const response = await fetch('/wp-json/cocart/auth/google-auth', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_token: idToken })
            });

            const data = await response.json();
            
            if (response.ok) {
                setUser(data.user);
                localStorage.setItem('jwt_token', data.token);
                localStorage.setItem('refresh_token', data.refresh_token);
            }
        } catch (error) {
            console.error('Authentication failed:', error);
        }
    };

    return (
        <div>
            {!user ? (
                <button 
                    onClick={handleGoogleSignIn}
                    disabled={!isLoaded}
                >
                    Sign in with Google
                </button>
            ) : (
                <div>
                    <h3>Welcome, {user.display_name}!</h3>
                    <p>Email: {user.email}</p>
                </div>
            )}
        </div>
    );
};

export default GoogleAuth;
```

### Vue.js Implementation

```vue
<template>
  <div>
    <button 
      v-if="!user"
      @click="signInWithGoogle"
      :disabled="!isGoogleLoaded"
    >
      Sign in with Google
    </button>
    
    <div v-else>
      <h3>Welcome, {{ user.display_name }}!</h3>
      <p>Email: {{ user.email }}</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isGoogleLoaded: false,
      user: null
    };
  },
  
  mounted() {
    this.loadGoogleAPI();
  },
  
  methods: {
    loadGoogleAPI() {
      const script = document.createElement('script');
      script.src = 'https://apis.google.com/js/api:client.js';
      script.onload = this.initializeGoogleAuth;
      document.head.appendChild(script);
    },
    
    initializeGoogleAuth() {
      window.gapi.load('auth2', () => {
        window.gapi.auth2.init({
          client_id: 'your-google-client-id.apps.googleusercontent.com'
        }).then(() => {
          this.isGoogleLoaded = true;
        });
      });
    },
    
    async signInWithGoogle() {
      const authInstance = window.gapi.auth2.getAuthInstance();
      
      try {
        const googleUser = await authInstance.signIn();
        const idToken = googleUser.getAuthResponse().id_token;
        
        const response = await fetch('/wp-json/cocart/auth/google-auth', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id_token: idToken })
        });

        const data = await response.json();
        
        if (response.ok) {
          this.user = data.user;
          localStorage.setItem('jwt_token', data.token);
          localStorage.setItem('refresh_token', data.refresh_token);
        }
      } catch (error) {
        console.error('Authentication failed:', error);
      }
    }
  }
};
</script>
```

## Error Handling

### Common Error Codes

| Code | Status | Description | Solution |
|------|--------|-------------|----------|
| `cocart_google_auth_missing_token` | 400 | ID token not provided | Ensure `id_token` is included in request |
| `cocart_google_auth_not_configured` | 500 | Google Client ID not set | Define `COCART_GOOGLE_CLIENT_ID` constant |
| `cocart_google_auth_verification_failed` | 401 | Token verification failed | Check network connectivity and token validity |
| `cocart_google_auth_invalid_token` | 401 | Invalid or malformed token | Generate a new ID token |
| `cocart_google_auth_audience_mismatch` | 401 | Token not for this application | Verify Google Client ID configuration |
| `cocart_google_auth_token_expired` | 401 | Google token expired | Request a new token from Google |
| `cocart_google_auth_registration_disabled` | 403 | User creation disabled | Enable user creation or link existing account |
| `cocart_google_auth_user_creation_failed` | 500 | Failed to create user | Check WordPress user creation permissions |

### Error Response Format

```json
{
    "code": "error_code",
    "message": "Human readable error message",
    "data": {
        "status": 400
    }
}
```

## Security Considerations

### Best Practices

1. **Always use HTTPS** in production
2. **Validate all tokens** server-side
3. **Implement rate limiting** (built-in)
4. **Log authentication attempts** (built-in)
5. **Use short-lived tokens** when possible
6. **Implement token refresh** mechanism
7. **Validate redirect URIs** in Google Console

### Token Security

```javascript
// Secure token storage
const tokenStorage = {
    set: (key, value) => {
        // Consider using secure storage libraries
        localStorage.setItem(key, value);
    },
    
    get: (key) => {
        return localStorage.getItem(key);
    },
    
    remove: (key) => {
        localStorage.removeItem(key);
    }
};

// Auto-refresh tokens before expiry
const refreshTokenIfNeeded = async () => {
    const token = tokenStorage.get('jwt_token');
    const refreshToken = tokenStorage.get('refresh_token');
    
    // Implement token refresh logic
    // Check token expiry and refresh if needed
};
```

## Hooks and Filters

### Available Hooks

#### Actions

```php
// Fired when user authenticates via Google
do_action( 'cocart_jwt_auth_google_authenticated', $token, $user, $google_user_data );

// Fired when existing user is linked to Google account
do_action( 'cocart_jwt_auth_google_user_linked', $user, $google_user_data );

// Fired when new user is created via Google auth
do_action( 'cocart_jwt_auth_google_user_created', $user, $google_user_data );
```

#### Filters

```php
// Customize Google Client ID
add_filter( 'cocart_jwt_auth_google_client_id', function( $client_id ) {
    return 'your-custom-client-id';
});

// Control user creation
add_filter( 'cocart_jwt_auth_google_user_creation_allowed', function( $allowed ) {
    return current_user_can( 'create_users' );
});

// Customize generated username
add_filter( 'cocart_jwt_auth_google_generated_username', function( $username, $google_data ) {
    return 'google_' . $username;
}, 10, 2 );
```

### Implementation Examples

#### Custom User Creation Logic

```php
add_action( 'cocart_jwt_auth_google_user_created', function( $user, $google_data ) {
    // Set default role
    $user->set_role( 'customer' );
    
    // Add custom meta data
    update_user_meta( $user->ID, 'registration_source', 'google' );
    update_user_meta( $user->ID, 'google_picture', $google_data['picture'] ?? '' );
    
    // Send welcome email
    wp_mail( $user->user_email, 'Welcome!', 'Welcome to our site!' );
}, 10, 2 );
```

#### Restrict User Creation by Domain

```php
add_filter( 'cocart_jwt_auth_google_user_creation_allowed', function( $allowed, $google_data ) {
    $email = $google_data['email'] ?? '';
    $allowed_domains = [ 'company.com', 'partner.com' ];
    
    $domain = substr( strrchr( $email, '@' ), 1 );
    
    return in_array( $domain, $allowed_domains );
}, 10, 2 );
```

## Troubleshooting

### Common Issues

#### 1. "Google authentication is not properly configured"

**Cause**: `COCART_GOOGLE_CLIENT_ID` constant not defined.

**Solution**:
```php
// Add to wp-config.php
define( 'COCART_GOOGLE_CLIENT_ID', 'your-client-id.apps.googleusercontent.com' );
```

#### 2. "Token audience mismatch"

**Cause**: Client ID in token doesn't match configured ID.

**Solution**: Verify the Client ID in Google Console matches the one in `wp-config.php`.

#### 3. CORS Issues

**Cause**: Frontend domain not allowed by CORS policy.

**Solution**:
```php
add_action( 'init', function() {
    if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
        header( "Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}" );
        header( 'Access-Control-Allow-Credentials: true' );
        header( 'Access-Control-Max-Age: 86400' );
    }
});
```

#### 4. Rate Limiting Issues

**Cause**: Too many authentication attempts.

**Solution**: Implement exponential backoff on the frontend or increase rate limits:

```php
add_filter( 'cocart_api_rate_limit_options', function( $options ) {
    if ( strpos( $_SERVER['REQUEST_URI'], 'google-auth' ) !== false ) {
        $options['limit'] = 10; // Increase from default 5
    }
    return $options;
});
```

### Debug Mode

Enable debug logging:

```php
// Add to wp-config.php
define( 'COCART_JWT_DEBUG', true );

// Monitor logs in wp-content/debug.log
```

### Testing Endpoints

Use tools like Postman or curl to test endpoints:

```bash
# Test Google authentication
curl -X POST https://yoursite.com/wp-json/cocart/auth/google-auth \
  -H "Content-Type: application/json" \
  -d '{"id_token":"your_google_id_token"}'

# Test user info endpoint
curl -X GET https://yoursite.com/wp-json/cocart/auth/google-user-info \
  -H "Authorization: Bearer your_jwt_token"
```

## Support

For additional support:

1. Check the [CoCart documentation](https://docs.cocartapi.com/)
2. Review WordPress debug logs
3. Verify Google Cloud Console configuration
4. Test with different browsers and devices
5. Contact the CoCart support team

---

**Note**: This implementation requires CoCart JWT Authentication vx.x.x or higher. Always test thoroughly in a staging environment before deploying to production.
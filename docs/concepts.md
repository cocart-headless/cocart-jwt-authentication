# Core Concepts

JSON Web Tokens are an open standard (RFC 7519) for securely transmitting information between parties as a JSON object. In WordPress REST API authentication:

## JSON Web Tokens (JWT)

**Structure**

JWTs consist of three parts: Header, Payload, and Signature, each base64-encoded and separated by dots

**Stateless**

Tokens carry all necessary information, reducing database queries and improving performance.

**Secure**

Digital signatures ensure token integrity and authenticity using cryptographic algorithms.

**Flexible**

Can include custom claims for additional user data or permissions.
​
## Token Types

CoCart JWT Authentication implements a dual-token system for enhanced security. The system uses two distinct types of tokens:
​
### Access Tokens

Short-lived tokens used for API authentication. They:

* Carry user identity and permissions
* Are included in the Authorization header for API requests
* Have configurable expiration times
* Are validated on each request
​
### Refresh Tokens

Long-lived tokens used to maintain user sessions. They:

* Are used to obtain new access tokens
* Are stored securely in the database
* Implement secure token rotation
* Help maintain persistent authentication

## Token Lifecycle

Understanding how tokens are managed throughout their lifetime:

1. Creation

Tokens are generated upon successful authentication with user credentials.

2. Validation

Each API request validates the token’s signature, expiration, and claims.

3. Refresh

Access tokens are renewed using refresh tokens before expiration.

4. Revocation

Tokens can be invalidated for security events or user actions.

## Rate Limiting

Rate limiting is a security feature that helps protect your API from abuse by limiting the number of requests.

For JWT, we force enable this feature when refreshing a client's token and validating within a specific time window.

> Rate limiting is only supported if you have [CoCart Plus](https://cocartapi.com) installed.​

### How It Works

1. Request Tracking

Each request is tracked based on the client’s IP address.

2. Window Management

Requests are counted within a configurable time window (default: 1 minute)

3. Limit Enforcement

For refresh token, limits are exceeded (default: 10 requests per minute per IP)
For validating token, limits are exceeded (default: 2 requests per minute per IP)

Then requests are blocked with a 429 (Too Many Requests) response.

4. Reset Period

After the time window expires, the request count resets automatically.

### Rate Limit Headers

CoCart Plus includes standard rate limit headers in API responses:
​
`X-RateLimit-Limit` integer

Maximum number of requests allowed in the current time window.
​
`X-RateLimit-Remaining` integer

Number of requests remaining in the current time window.
​
`X-RateLimit-Reset` timestamp

Unix timestamp when the current time window expires.
​
`Retry-After` integer

Seconds to wait before making another request. (only present when rate limited)

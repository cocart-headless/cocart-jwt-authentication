# Quick Start

This guide assumes you have already installed and configured CoCart JWT Authentication. If you haven’t, please follow the [Installation Guide first](guide.md).
​
## Authentication Flow
​
**1. Get a Token**

To authenticate a user and get a JWT token:

```
curl -X POST \
  https://your-site.com/wp-json/cocart/v2/login \
  -H "Content-Type: application/json" \
  -d '{"username": "your-username", "password": "your-password"}'
```

```json
{
    "user_id": "123",
    "first_name": "John",
    "last_name": "Smith",
    "display_name": "john",
    "role": "Customer",
    "avatar_urls": {},
    "email": "users@emailaddress.xyz",
    "extras": {
        "jwt_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwOlwvXC9jb2NhcnRhcGkubG9jYWwiLCJpYXQiOjE3Mzk3NTEzNzIsIm5iZiI6MTczOTc1MTM3MiwiZXhwIjoxNzQwNjE1MzcyLCJkYXRhIjp7InVzZXIiOnsiaWQiOjEsInVzZXJuYW1lIjoic2ViYXN0aWVuIiwiaXAiOiIxMjcuMC4wLjEiLCJkZXZpY2UiOiJIVFRQX1hfVUNCUk9XU0VSX0RFVklDRV9VQSJ9LCJzZWNyZXRfa2V5IjoiYmFuYW5hIn19.aBuyRwAtvGb6SI4BB_MN4NYN01jqVZN4PPnd1jfW2UA",
        "jwt_refresh": "90efc95f1d85e465951d10c309897629524b7fc1b40dfab75ed68f7c8540468a05b8b26995685821f52cf736edb566f3317432288af4c6e4edc281f6ab7af371"
    },
    "dev_note": "Don't forget to store the users login information in order to authenticate all other routes with CoCart."
}
```

**2. Use the Token**

Make authenticated requests using the token. Here’s an example using Cart endpoint to get the current user’s cart:

```
curl -X GET \
  https://your-site.com/wp-json/cocart/v2/cart \
  -H "Authorization: Bearer YOUR-JWT-TOKEN"
```

**3. Refresh Token**

When the access token expires, use the refresh token to get a new one:

```
curl -X POST \
  https://your-site.com/wp-json/cocart/jwt/refresh-token \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "YOUR-REFRESH-TOKEN"}'
```

```json
{
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwOlwvXC9jb2NhcnRhcGkubG9jYWwiLCJpYXQiOjE3NDA1MTE5NDgsIm5iZiI6MTc0MDUxMTk0OCwiZXhwIjoxNzQxMzc1OTQ4LCJkYXRhIjp7InVzZXIiOnsiaWQiOjEsInVzZXJuYW1lIjoic2ViYXN0aWVuIiwiaXAiOiIxMjcuMC4wLjEiLCJkZXZpY2UiOiJIVFRQX1hfVUNCUk9XU0VSX0RFVklDRV9VQSJ9LCJzZWNyZXRfa2V5IjoiYmFuYW5hIn19.zHEHjVLE0Rrr7yY4z51bjhnm5ndkbR6J1nDzJNOZTK0",
    "refresh_token": "7dfc00d346277468b975a22768f861702b056e20f7cd84675b4dd4c0eb1148f034ae2610c548458a55213d62ea6034006466919166841e5f6797caeac5bd5e27"
}
```

> Remember to never expose your JWT secret key or store tokens in plain text. Always use secure storage methods appropriate for your platform.

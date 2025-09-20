# WP CLI Commands

The following WP CLI commands are available for CoCart JWT Authentication.

> Made available since v2.2.0

## Clean up tokens

Cleans up expired JWT tokens.

```bash
wp cocart jwt cleanup [--batch-size=<number>] [--force]
```

**Arguments:**

* `--batch-size=<number>` - Number of users to process per batch (default: 100) (Optional)
* `--force` - Force cleanup of all tokens (Optional)

**Examples:**

```bash
wp cocart jwt cleanup --batch-size=50
wp cocart jwt cleanup --force
```

## View token details

Displays details of a JWT token in a table.

```bash
wp cocart jwt view <token>
```

**Arguments:**

* `<token>` - The JWT token to view

**Examples:**

```bash
wp cocart jwt view <token>
```

## Lists tokens

Lists all active JWT tokens.

```bash
wp cocart jwt list [--page=<number>] [--per-page=<number>]
```

**Arguments:**

* `--page=<number>` - Page number to display (default: 1)
* `--per-page=<number>` - Number of tokens to display per page (default: 20)

**Examples:**

```bash
wp cocart jwt list --page=2 --per-page=10
```

## Create token

Generates a new JWT token for a user.

```bash
wp cocart jwt create --user=<user> [--user-agent=<user-agent>]
```

**Arguments:**

* `--user=<user>` - The user ID, email, or login to generate the token for
* `--user-agent=<user-agent>` - The User Agent to override the server User Agent (optional)

**Examples:**

```bash
wp cocart jwt create --user=123
wp cocart jwt create --user=admin@example.com --user-agent="Custom User Agent"
```

## Destroy tokens

Destroys JWT tokens for a specific user.

> Made available since v3.0.0

```bash
wp cocart jwt destroy <user> [--pat=<pat_id>] [--force]
```

**Arguments:**

* `<user>` - User ID, email, or login to destroy tokens for
* `--pat=<pat_id>` - Specific PAT ID to destroy (optional - destroys specific token)
* `--force` - Force destroying of all tokens by user without confirmation (optional)

**Examples:**

```bash
# Destroy all tokens for user (with confirmation prompt)
wp cocart jwt destroy 1

# Destroy all tokens for user by email (skip confirmation)
wp cocart jwt destroy admin@example.com --force

# Destroy specific PAT token
wp cocart jwt destroy username --pat=pat_abc123
```

# WP CLI Commands

The following WP CLI commands are available for CoCart JWT Authentication.

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
wp cocart jwt create --user_id=<id> [--user-agent=<user-agent>]
```

**Arguments:**

* `--user_id=<id>` - The user ID to generate the token for
* `--user-agent=<user-agent>` - The User Agent to override the server User Agent (optional)

**Examples:**

```bash
wp cocart jwt create --user_id=123
wp cocart jwt create --user_id=123 --user-agent="Custom User Agent"
```

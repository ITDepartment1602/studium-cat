# Studium-CAT Refactoring Guide

## Overview

This guide helps you migrate from the old messy codebase to the new clean architecture.

## The Problem with Old Code

### ❌ BAD PRACTICES (Old Code):
```php
<?php
// Multiple config includes
include '../../config.php';
include '../../config.php';  // Included again!

// Direct $_GET usage (SQL INJECTION RISK!)
$id = $_GET['id'];
$sql = "DELETE FROM login WHERE id = $id";  // DANGEROUS!
$con->query($sql);

// Hardcoded credentials scattered everywhere
$servername = "127.0.0.1";
$username = "u436962267_studium";
$password = "Nclexamplified2023";
```

### ✅ GOOD PRACTICES (New Code):
```php
<?php
// Single include at the top
require_once __DIR__ . '/../../config.php';

// Safe input handling
$id = getInt('id');  // Automatically sanitized
if (!$id) {
    redirect('index.php', 'Invalid ID', 'error');
}

// Secure database query with prepared statement
db()->execute("DELETE FROM login WHERE id = ?", [$id]);
```

## Quick Reference

### Including the Config (DO THIS ONCE AT TOP)

```php
<?php
require_once __DIR__ . '/../config.php';  // Adjust path as needed

// Now you have access to:
// - db() - Database instance
// - $con - MySQLi connection (legacy support)
// - get(), getInt(), post(), postInt() - Safe input functions
// - redirect() - Redirect helper
// - All security functions
?>
```

### Database Queries

#### OLD WAY (DANGEROUS):
```php
$user_id = $_GET['user_id'];  // SQL INJECTION!
$query = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($con, $query);
```

#### NEW WAY (SECURE):
```php
$user_id = getInt('user_id');  // Sanitized
$result = db()->fetchAll("SELECT * FROM users WHERE id = ?", [$user_id]);
```

### Common Patterns

#### 1. Fetch Single Record
```php
// OLD
$query = "SELECT * FROM login WHERE id = '$_GET[id]'";
$row = mysqli_fetch_array(mysqli_query($con, $query));

// NEW
$row = db()->fetchOne("SELECT * FROM login WHERE id = ?", [getInt('id')]);
```

#### 2. Fetch All Records
```php
// OLD
$query = "SELECT * FROM login";
$data = mysqli_query($con, $query);
while ($row = mysqli_fetch_array($data)) {
    // ...
}

// NEW
$users = db()->fetchAll("SELECT * FROM login");
foreach ($users as $user) {
    // ...
}
```

#### 3. Insert Data
```php
// OLD
$name = $_POST['name'];
$email = $_POST['email'];
$sql = "INSERT INTO users (name, email) VALUES ('$name', '$email')";
mysqli_query($con, $sql);

// NEW
db()->execute(
    "INSERT INTO users (name, email) VALUES (?, ?)",
    [post('name'), post('email')]
);
```

#### 4. Update Data
```php
// OLD
$id = $_GET['id'];
$status = $_POST['status'];
$sql = "UPDATE users SET status = '$status' WHERE id = $id";
mysqli_query($con, $sql);

// NEW
db()->execute(
    "UPDATE users SET status = ? WHERE id = ?",
    [post('status'), getInt('id')]
);
```

#### 5. Delete Data
```php
// OLD
$id = $_GET['id'];
$sql = "DELETE FROM users WHERE id = $id";
$con->query($sql);

// NEW
db()->execute("DELETE FROM users WHERE id = ?", [getInt('id')]);
```

## Available Functions

### Database Functions
- `db()` - Get Database instance
- `db()->query($sql, $params)` - Execute SELECT query
- `db()->execute($sql, $params)` - Execute INSERT/UPDATE/DELETE
- `db()->fetchOne($sql, $params)` - Fetch single row
- `db()->fetchAll($sql, $params)` - Fetch all rows
- `db()->lastInsertId()` - Get last insert ID
- `db()->affectedRows()` - Get affected rows count

### Input Functions (SAFE)
- `get($key, $default)` - Get sanitized GET parameter
- `getInt($key, $default)` - Get integer GET parameter
- `post($key, $default)` - Get sanitized POST parameter
- `postInt($key, $default)` - Get integer POST parameter

### Security Functions
- `sanitize($input)` - Sanitize string (XSS prevention)
- `isValidEmail($email)` - Validate email
- `hashPassword($password)` - Hash password
- `verifyPassword($password, $hash)` - Verify password
- `generateCsrfToken()` - Generate CSRF token
- `validateCsrfToken($token)` - Validate CSRF token
- `csrfField()` - Output CSRF field

### Utility Functions
- `redirect($url, $message, $type)` - Redirect with flash message
- `getFlash($type)` - Get flash message
- `isLoggedIn()` - Check if user is logged in
- `requireLogin($redirect)` - Require login or redirect
- `currentUserId()` - Get current user ID
- `formatDate($date, $format)` - Format date
- `debug($data, $die)` - Debug output (local only)

## Migration Steps

### Step 1: Identify Files to Update
Look for files with:
- Direct `$_GET` or `$_POST` usage
- Multiple `include 'config.php'` lines
- Raw SQL concatenation with variables

### Step 2: Update Each File

#### Before:
```php
<?php
include '../../config.php';
include '../../config.php';

$id = $_GET['id'];
$sql = "SELECT * FROM login WHERE id = '$id'";
$result = mysqli_query($con, $sql);
?>
```

#### After:
```php
<?php
require_once __DIR__ . '/../../config.php';

$id = getInt('id');
if (!$id) {
    redirect('index.php', 'Invalid ID', 'error');
}

$user = db()->fetchOne("SELECT * FROM login WHERE id = ?", [$id]);
?>
```

### Step 3: Test
1. Test in local environment first
2. Check for any errors
3. Push to GitHub
4. Deploy to Hostinger
5. Verify production works

## File Structure

```
studium-cat/
├── core/
│   ├── Database.php      # Database class
│   └── Security.php      # Security helpers
├── config.php            # Main configuration (NEW)
├── config_old.php        # Old config (backup)
└── ...
```

## Important Notes

1. **Only include config.php once** at the top of each file
2. **Always use `getInt()` or `post()`** for user input - NEVER use `$_GET`/`$_POST` directly
3. **Always use prepared statements** with `?` placeholders
4. **Test in local first** before pushing to production

## Need Help?

Check the examples in:
- `core/Database.php` - Database usage examples
- `core/Security.php` - Security function examples

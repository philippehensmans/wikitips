# Deployment Instructions for Error Fixes

## Problem Summary

The server error logs show repeated PHP fatal errors:
```
PHP Fatal error: Uncaught Error: Failed opening required '/home/hensmans19824/www/shared-auth/config.php'
in /home/hensmans19824/www/index.php on line 7
```

This occurs because the root-level `index.php` at `/home/hensmans19824/www/index.php` requires a `shared-auth/config.php` file that doesn't exist.

## Solution Files

This directory contains deployment files to fix the errors:

### 1. shared-auth/config.php

A minimal shared authentication configuration file that provides:
- Session security configuration
- Timezone settings
- Error handling defaults
- Basic shared auth helper functions

**Deploy to:** `/home/hensmans19824/www/shared-auth/config.php`

```bash
# Create the directory on the server
mkdir -p /home/hensmans19824/www/shared-auth

# Copy the config file
cp shared-auth/config.php /home/hensmans19824/www/shared-auth/

# Copy the .htaccess to protect the directory
cp shared-auth/.htaccess /home/hensmans19824/www/shared-auth/
```

### 2. root-index.php

An improved root index.php that handles the missing shared-auth gracefully.

**Deploy to:** `/home/hensmans19824/www/index.php`

**Options:**
- **Option A (Recommended):** Uncomment line 14-15 to redirect directly to `/wikitips/`
- **Option B:** Use the landing page that shows links to available applications

```bash
# Backup the existing broken index.php
mv /home/hensmans19824/www/index.php /home/hensmans19824/www/index.php.bak

# Deploy the new one
cp root-index.php /home/hensmans19824/www/index.php
```

## Quick Fix (Redirect Only)

If you just want to redirect the root to wikitips, create this minimal file:

**`/home/hensmans19824/www/index.php`:**
```php
<?php
header('Location: /wikitips/');
exit;
```

## .htaccess Files Updated

The following .htaccess files have been updated for Apache 2.2/2.4 compatibility:

- `data/.htaccess` - Denies all access to the database directory
- `uploads/.htaccess` - Allows image access but blocks PHP execution
- `shared-auth/.htaccess` - Denies all web access to auth config

## Verification

After deployment, test by accessing:
1. `https://www.k1m.be/` - Should no longer show 500 errors
2. `https://www.k1m.be/wikitips/` - WikiTips application should work normally
3. `https://www.k1m.be/wikitips/config.php` - Should be denied (403 Forbidden)
4. `https://www.k1m.be/wikitips/data/wikitips.db` - Should be denied (403 Forbidden)

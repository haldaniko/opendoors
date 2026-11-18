<?php

/** Read an environment variable and return a default when it is not set. */
function opendoors_env(string $name, mixed $default = null): mixed
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

/** Docker/Compose environment values are strings, so cast boolean flags safely. */
function opendoors_env_bool(string $name, bool $default = false): bool
{
    $value = opendoors_env($name);
    if ($value === null) {
        return $default;
    }
    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

// Honour HTTPS when TLS is terminated by a trusted reverse proxy.
$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
if (strtolower(trim(explode(',', $forwardedProto)[0])) === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

define('DB_NAME', opendoors_env('DB_DATABASE', 'opendoors'));
define('DB_USER', opendoors_env('DB_USER', 'opendoors'));
define('DB_PASSWORD', opendoors_env('DB_PASSWORD', ''));
define('DB_HOST', opendoors_env('DB_SERVER', 'db:3306'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('AUTH_KEY', opendoors_env('AUTH_KEY', 'development-only-auth-key'));
define('SECURE_AUTH_KEY', opendoors_env('SECURE_AUTH_KEY', 'development-only-secure-auth-key'));
define('LOGGED_IN_KEY', opendoors_env('LOGGED_IN_KEY', 'development-only-logged-in-key'));
define('NONCE_KEY', opendoors_env('NONCE_KEY', 'development-only-nonce-key'));
define('AUTH_SALT', opendoors_env('AUTH_SALT', 'development-only-auth-salt'));
define('SECURE_AUTH_SALT', opendoors_env('SECURE_AUTH_SALT', 'development-only-secure-auth-salt'));
define('LOGGED_IN_SALT', opendoors_env('LOGGED_IN_SALT', 'development-only-logged-in-salt'));
define('NONCE_SALT', opendoors_env('NONCE_SALT', 'development-only-nonce-salt'));

$table_prefix = opendoors_env('DB_TABLE_PREFIX', 'wp_opendoors_');

define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', opendoors_env_bool('WP_DISALLOW_FILE_MODS', false));
define('AUTOMATIC_UPDATER_DISABLED', opendoors_env_bool('WP_AUTOMATIC_UPDATER_DISABLED', false));
define('WP_AUTO_UPDATE_CORE', opendoors_env_bool('WP_AUTO_UPDATE_CORE', false));
define('WP_MEMORY_LIMIT', opendoors_env('WP_MEMORY_LIMIT', '256M'));
define('WP_POST_REVISIONS', (int) opendoors_env('WP_POST_REVISIONS', 10));
define('AUTOSAVE_INTERVAL', (int) opendoors_env('WP_AUTOSAVE_INTERVAL', 60));
define('EMPTY_TRASH_DAYS', (int) opendoors_env('WP_EMPTY_TRASH_DAYS', 30));
define('WP_DEBUG', opendoors_env_bool('WP_DEBUG', false));
define('WP_DEBUG_LOG', opendoors_env_bool('WP_DEBUG_LOG', false));
define('WP_DEBUG_DISPLAY', opendoors_env_bool('WP_DEBUG_DISPLAY', false));
define('FORCE_SSL_ADMIN', opendoors_env_bool('FORCE_SSL_ADMIN', false));
define('DISABLE_WP_CRON', opendoors_env_bool('DISABLE_WP_CRON', false));
define('FS_METHOD', 'direct');

$environment = opendoors_env('ENV', 'production');
define('WP_ENVIRONMENT_TYPE', $environment === 'development' ? 'development' : 'production');

$configuredHome = opendoors_env('WP_HOME');
$configuredSiteUrl = opendoors_env('WP_SITEURL', $configuredHome);
if ($configuredHome !== null) {
    define('WP_HOME', rtrim($configuredHome, '/'));
    define('WP_SITEURL', rtrim($configuredSiteUrl, '/'));
} elseif ($environment === 'development') {
    $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('WP_HOME', $scheme . $host);
    define('WP_SITEURL', $scheme . $host);
}

$recoveryModeEmail = opendoors_env('RECOVERY_MODE_EMAIL');
if ($recoveryModeEmail !== null) {
    define('RECOVERY_MODE_EMAIL', $recoveryModeEmail);
}

define('WP_REDIS_HOST', opendoors_env('WP_REDIS_HOST', 'redis'));
define('WP_REDIS_PORT', (int) opendoors_env('WP_REDIS_PORT', 6379));
define('WP_REDIS_PREFIX', opendoors_env('WP_REDIS_PREFIX', 'opendoors:'));
define('WP_REDIS_CLIENT', opendoors_env('WP_REDIS_CLIENT', 'predis'));
define('WP_REDIS_DATABASE', (int) opendoors_env('WP_REDIS_DATABASE', 0));
define('WP_REDIS_TIMEOUT', (float) opendoors_env('WP_REDIS_TIMEOUT', 1));
define('WP_REDIS_READ_TIMEOUT', (float) opendoors_env('WP_REDIS_READ_TIMEOUT', 1));
define('WP_REDIS_PERSISTENT', opendoors_env_bool('WP_REDIS_PERSISTENT', true));
define('WP_REDIS_DISABLE_BANNERS', true);

$redisUsername = opendoors_env('WP_REDIS_USERNAME');
$redisPassword = opendoors_env('WP_REDIS_PASSWORD');
if ($redisUsername !== null && $redisPassword !== null) {
    define('WP_REDIS_PASSWORD', [$redisUsername, $redisPassword]);
} elseif ($redisPassword !== null) {
    define('WP_REDIS_PASSWORD', $redisPassword);
}

define('WP_REDIS_SELECTIVE_FLUSH', true);
define('WP_REDIS_DISABLED', opendoors_env_bool('WP_REDIS_DISABLED', false));

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';

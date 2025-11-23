<?php
/**
 * The base configuration for WordPress
 * Hardened configuration for AutoWP
 *
 * This file is used by the provisioning script.
 * Manual configuration: https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings ** //
define( 'DB_NAME', 'database_name_here' );
define( 'DB_USER', 'username_here' );
define( 'DB_PASSWORD', 'password_here' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts
 * Generate new keys: https://api.wordpress.org/secret-key/1.1/salt/
 * Replace with unique values generated during provisioning
 */
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');
/**#@-*/

/**
 * WordPress database table prefix
 * Use custom prefix for additional security
 */
$table_prefix = 'wp_awp_';

/**
 * Security configurations
 */
// Disable file editing in WordPress admin
define( 'DISALLOW_FILE_EDIT', true );

// Disable file modifications (plugin/theme install/update)
// Set to false if you need to install/update plugins via admin
define( 'DISALLOW_FILE_MODS', false );

// Force SSL for admin and login
define( 'FORCE_SSL_ADMIN', true );

// Limit post revisions
define( 'WP_POST_REVISIONS', 5 );

// Set autosave interval (in seconds)
define( 'AUTOSAVE_INTERVAL', 300 );

// Enable automatic core updates for minor releases
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

// Set memory limits
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

/**
 * Debugging configuration
 * Set to false in production
 */
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

// Enable Script Debug (for development)
define( 'SCRIPT_DEBUG', false );

// Save database queries in an array
define( 'SAVEQUERIES', false );

/**
 * Performance configurations
 */
// Enable Cron
define( 'DISABLE_WP_CRON', false );

// Alternative Cron (use system cron instead)
// define( 'DISABLE_WP_CRON', true );
// Add to system crontab: */15 * * * * wget -q -O - https://yourdomain.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1

// Empty trash automatically
define( 'EMPTY_TRASH_DAYS', 30 );

/**
 * Cookie configuration
 */
// Use secure cookies
define( 'COOKIE_DOMAIN', false );

// Increase cookie security
@ini_set( 'session.cookie_httponly', true );
@ini_set( 'session.cookie_secure', true );
@ini_set( 'session.use_only_cookies', true );

/**
 * FTP/SSH configuration (if needed for updates)
 * Uncomment and configure if file operations fail
 */
// define( 'FS_METHOD', 'direct' );
// define( 'FTP_HOST', 'ftp.example.com' );
// define( 'FTP_USER', 'username' );
// define( 'FTP_PASS', 'password' );
// define( 'FTP_SSL', true );

/**
 * Custom content directory (optional)
 * Uncomment if you want to move wp-content
 */
// define( 'WP_CONTENT_DIR', dirname(__FILE__) . '/wp-content' );
// define( 'WP_CONTENT_URL', 'https://yourdomain.com/wp-content' );

/**
 * Custom plugin directory (optional)
 */
// define( 'WP_PLUGIN_DIR', dirname(__FILE__) . '/wp-content/plugins' );
// define( 'WP_PLUGIN_URL', 'https://yourdomain.com/wp-content/plugins' );

/**
 * Multisite configuration (if needed)
 */
// define( 'WP_ALLOW_MULTISITE', true );

/**
 * Custom user and usermeta tables
 * Useful for sharing users across multiple WP installations
 */
// define( 'CUSTOM_USER_TABLE', $table_prefix . 'users' );
// define( 'CUSTOM_USER_META_TABLE', $table_prefix . 'usermeta' );

/**
 * Increase PHP limits
 */
@ini_set( 'upload_max_filesize', '64M' );
@ini_set( 'post_max_size', '64M' );
@ini_set( 'max_execution_time', '300' );
@ini_set( 'max_input_time', '300' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

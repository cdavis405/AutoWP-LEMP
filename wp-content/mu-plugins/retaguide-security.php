<?php
/**
 * Plugin Name: RetaGuide Security
 * Description: Security hardening for RetaGuide WordPress site
 * Version: 1.0.0
 * Author: RetaGuide Team
 * Network: true
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable file editing in admin
 */
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

/**
 * Disable XML-RPC
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Remove X-Pingback header
 */
add_filter('wp_headers', function($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

/**
 * Disable REST API for non-authenticated users (optional)
 */
add_filter('rest_authentication_errors', function($result) {
    if (!empty($result)) {
        return $result;
    }
    
    if (!is_user_logged_in()) {
        // Allow specific endpoints
        $allowed_routes = array(
            '/wp-json/wp/v2/posts',
            '/wp-json/wp/v2/pages',
            '/wp-json/wp/v2/guide',
        );
        
        $current_route = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($allowed_routes as $route) {
            if (strpos($current_route, $route) !== false) {
                return $result;
            }
        }
        
        // Uncomment to restrict REST API
        // return new WP_Error('rest_forbidden', __('REST API restricted.'), array('status' => 401));
    }
    
    return $result;
});

/**
 * Add login attempt tracking
 */
class RetaGuide_Login_Security {
    private $max_attempts = 5;
    private $lockout_duration = 900; // 15 minutes
    
    public function __construct() {
        add_filter('authenticate', array($this, 'check_login_attempts'), 30, 3);
        add_action('wp_login_failed', array($this, 'login_failed'));
        add_action('wp_login', array($this, 'login_success'), 10, 2);
    }
    
    public function check_login_attempts($user, $username, $password) {
        if (empty($username)) {
            return $user;
        }
        
        $transient_name = 'login_attempts_' . md5($username);
        $attempts = get_transient($transient_name);
        
        if ($attempts && $attempts >= $this->max_attempts) {
            $this->log_security_event('login_blocked', $username);
            return new WP_Error(
                'too_many_attempts',
                sprintf(
                    __('Too many failed login attempts. Please try again in %d minutes.'),
                    ceil($this->lockout_duration / 60)
                )
            );
        }
        
        return $user;
    }
    
    public function login_failed($username) {
        $transient_name = 'login_attempts_' . md5($username);
        $attempts = get_transient($transient_name);
        
        if (!$attempts) {
            $attempts = 1;
        } else {
            $attempts++;
        }
        
        set_transient($transient_name, $attempts, $this->lockout_duration);
        
        $this->log_security_event('login_failed', $username, array(
            'attempts' => $attempts,
            'ip' => $this->get_client_ip()
        ));
    }
    
    public function login_success($username, $user) {
        $transient_name = 'login_attempts_' . md5($username);
        delete_transient($transient_name);
    }
    
    private function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }
    
    private function log_security_event($event, $username, $data = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event' => $event,
            'username' => $username,
            'ip' => $this->get_client_ip(),
            'data' => $data,
        );
        
        // Log to custom table or file
        error_log('[RetaGuide Security] ' . json_encode($log_entry));
    }
}

new RetaGuide_Login_Security();

/**
 * Add security headers
 */
add_action('send_headers', function() {
    if (!is_admin()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
});

/**
 * Disable user enumeration via REST API
 */
add_filter('rest_endpoints', function($endpoints) {
    if (isset($endpoints['/wp/v2/users'])) {
        unset($endpoints['/wp/v2/users']);
    }
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});

/**
 * Disable user enumeration via author archives
 */
add_action('template_redirect', function() {
    if (is_author()) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
    }
});

/**
 * Remove WordPress version from RSS feeds
 */
add_filter('the_generator', '__return_empty_string');

/**
 * Disable application passwords (if not needed)
 */
add_filter('wp_is_application_passwords_available', '__return_false');

/**
 * Add admin notice for security recommendations
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $issues = array();
    
    // Check if HTTPS is enabled
    if (!is_ssl()) {
        $issues[] = 'HTTPS is not enabled. Enable SSL certificate for security.';
    }
    
    // Check file permissions
    if (is_writable(ABSPATH . 'wp-config.php')) {
        $issues[] = 'wp-config.php is writable. Set permissions to 600.';
    }
    
    // Check for default admin username
    $admin_user = get_user_by('login', 'admin');
    if ($admin_user) {
        $issues[] = 'Default "admin" username detected. Consider using a different username.';
    }
    
    if (!empty($issues)) {
        echo '<div class="notice notice-warning"><p><strong>Security Recommendations:</strong></p><ul>';
        foreach ($issues as $issue) {
            echo '<li>' . esc_html($issue) . '</li>';
        }
        echo '</ul></div>';
    }
});

/**
 * Database backup helper function
 */
function retaguide_backup_database() {
    if (!current_user_can('manage_options')) {
        return false;
    }
    
    $backup_dir = WP_CONTENT_DIR . '/backups';
    
    if (!file_exists($backup_dir)) {
        wp_mkdir_p($backup_dir);
    }
    
    $filename = 'retaguide-db-' . date('Y-m-d-H-i-s') . '.sql';
    $filepath = $backup_dir . '/' . $filename;
    
    $command = sprintf(
        'mysqldump -u%s -p%s %s > %s',
        DB_USER,
        DB_PASSWORD,
        DB_NAME,
        $filepath
    );
    
    exec($command, $output, $return);
    
    if ($return === 0) {
        // Compress backup
        exec("gzip $filepath");
        return $filepath . '.gz';
    }
    
    return false;
}

/**
 * Schedule daily database backups
 */
add_action('retaguide_daily_backup', 'retaguide_backup_database');

if (!wp_next_scheduled('retaguide_daily_backup')) {
    wp_schedule_event(time(), 'daily', 'retaguide_daily_backup');
}

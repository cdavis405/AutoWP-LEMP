<?php
/**
 * Security Enhancements
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove WordPress version from head and feeds
 */
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

/**
 * Hide WordPress version in scripts and styles
 */
function autowp_remove_version_strings($src) {
    if (strpos($src, 'ver=' . get_bloginfo('version'))) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('style_loader_src', 'autowp_remove_version_strings', 9999);
add_filter('script_loader_src', 'autowp_remove_version_strings', 9999);

/**
 * Disable XML-RPC
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Remove RSD link
 */
remove_action('wp_head', 'rsd_link');

/**
 * Remove Windows Live Writer manifest link
 */
remove_action('wp_head', 'wlwmanifest_link');

/**
 * Disable file editing in admin
 */
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

/**
 * Add security headers
 */
function autowp_security_headers() {
    if (!is_admin()) {
        // X-Frame-Options
        header('X-Frame-Options: SAMEORIGIN');
        
        // X-Content-Type-Options
        header('X-Content-Type-Options: nosniff');
        
        // X-XSS-Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer-Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions-Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}
add_action('send_headers', 'autowp_security_headers');

/**
 * Sanitize file uploads
 */
function autowp_sanitize_file_name($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}
add_filter('sanitize_file_name', 'autowp_sanitize_file_name', 10);

/**
 * Limit login attempts (basic implementation)
 */
function autowp_check_login_attempts($user, $username, $password) {
    $transient_name = 'autowp_login_attempts_' . sanitize_user($username);
    $attempts = get_transient($transient_name);
    
    if ($attempts && $attempts >= 5) {
        return new WP_Error('too_many_attempts', __('Too many failed login attempts. Please try again in 15 minutes.', 'autowp'));
    }
    
    return $user;
}
add_filter('authenticate', 'autowp_check_login_attempts', 30, 3);

/**
 * Track failed login attempts
 */
function autowp_failed_login($username) {
    $transient_name = 'autowp_login_attempts_' . sanitize_user($username);
    $attempts = get_transient($transient_name);
    
    if (!$attempts) {
        $attempts = 1;
    } else {
        $attempts++;
    }
    
    set_transient($transient_name, $attempts, 15 * MINUTE_IN_SECONDS);
}
add_action('wp_login_failed', 'autowp_failed_login');

/**
 * Clear failed login attempts on successful login
 */
function autowp_clear_login_attempts($username, $user) {
    $transient_name = 'autowp_login_attempts_' . sanitize_user($username);
    delete_transient($transient_name);
}
add_action('wp_login', 'autowp_clear_login_attempts', 10, 2);

/**
 * Remove login error messages
 */
function autowp_login_errors() {
    return __('Login failed. Please check your credentials.', 'autowp');
}
add_filter('login_errors', 'autowp_login_errors');

/**
 * Add nonce to comment forms
 */
function autowp_comment_form_nonce() {
    wp_nonce_field('autowp_comment_nonce', 'autowp_comment_nonce_field');
}
add_action('comment_form', 'autowp_comment_form_nonce');

/**
 * Verify comment nonce
 */
function autowp_verify_comment_nonce($commentdata) {
    if (!isset($_POST['autowp_comment_nonce_field']) || 
        !wp_verify_nonce($_POST['autowp_comment_nonce_field'], 'autowp_comment_nonce')) {
        wp_die(__('Security check failed. Please try again.', 'autowp'));
    }
    return $commentdata;
}
add_filter('preprocess_comment', 'autowp_verify_comment_nonce');

/**
 * Disable user enumeration
 */
function autowp_disable_user_enumeration() {
    if (!is_admin() && isset($_SERVER['REQUEST_URI'])) {
        if (preg_match('/(author=\d+|\/\?author=\d+)/i', $_SERVER['REQUEST_URI']) || 
            (isset($_REQUEST['author']) && is_numeric($_REQUEST['author']))) {
            wp_die(__('Access denied.', 'autowp'));
        }
    }
}
add_action('init', 'autowp_disable_user_enumeration');

/**
 * Secure wp-config.php location recommendations
 * Note: This is a comment/documentation function
 */
function autowp_security_recommendations() {
    /*
     * Security Recommendations:
     * 
     * 1. Move wp-config.php one directory above WordPress root
     * 2. Set file permissions:
     *    - Directories: 755
     *    - Files: 644
     *    - wp-config.php: 600
     * 
     * 3. Use security keys from https://api.wordpress.org/secret-key/1.1/salt/
     * 
     * 4. Add to wp-config.php:
     *    define('DISALLOW_FILE_EDIT', true);
     *    define('FORCE_SSL_ADMIN', true);
     * 
     * 5. Keep WordPress, themes, and plugins updated
     * 
     * 6. Use strong passwords and 2FA
     * 
     * 7. Regular backups
     * 
     * 8. Monitor for suspicious activity
     */
}

/**
 * Add Content Security Policy (basic)
 */
function autowp_content_security_policy() {
    if (!is_admin()) {
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ";
        $csp .= "style-src 'self' 'unsafe-inline'; ";
        $csp .= "img-src 'self' data: https:; ";
        $csp .= "font-src 'self' data:; ";
        $csp .= "connect-src 'self'; ";
        $csp .= "frame-ancestors 'self';";
        
        // Report only mode for testing
        // header("Content-Security-Policy-Report-Only: " . $csp);
        
        // Uncomment when ready to enforce
        // header("Content-Security-Policy: " . $csp);
    }
}
// add_action('send_headers', 'autowp_content_security_policy');

/**
 * Anonymize IP addresses for privacy compliance
 */
function autowp_anonymize_ip($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return preg_replace('/\.\d+$/', '.0', $ip);
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return preg_replace('/:[^:]+$/', ':0', $ip);
    }
    return $ip;
}

/**
 * GDPR/Privacy: Add consent for cookies
 */
function autowp_cookie_notice() {
    if (!isset($_COOKIE['autowp_cookie_consent']) && !is_admin()) {
        ?>
        <div id="cookie-notice" class="cookie-notice" role="dialog" aria-label="<?php esc_attr_e('Cookie Consent', 'autowp'); ?>">
            <div class="cookie-notice-content">
                <p><?php _e('This website uses cookies to improve your experience. By continuing to use this site, you consent to our use of cookies.', 'autowp'); ?></p>
                <button type="button" id="cookie-accept" class="button">
                    <?php _e('Accept', 'autowp'); ?>
                </button>
                <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">
                    <?php _e('Privacy Policy', 'autowp'); ?>
                </a>
            </div>
        </div>
        <style>
            .cookie-notice {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #2b2b2b;
                color: #fff;
                padding: 20px;
                z-index: 9999;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
            }
            .cookie-notice-content {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                gap: 20px;
                flex-wrap: wrap;
            }
            .cookie-notice p {
                margin: 0;
                flex-grow: 1;
            }
            .cookie-notice .button {
                background: #4FB3BF;
                color: #fff;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
            }
            .cookie-notice a {
                color: #E6F2FF;
                text-decoration: underline;
            }
        </style>
        <script>
            document.getElementById('cookie-accept').addEventListener('click', function() {
                document.cookie = 'autowp_cookie_consent=1; max-age=31536000; path=/; SameSite=Lax';
                document.getElementById('cookie-notice').style.display = 'none';
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'autowp_cookie_notice');

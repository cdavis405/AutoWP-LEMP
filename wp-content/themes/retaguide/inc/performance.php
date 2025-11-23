<?php
/**
 * Performance Optimizations
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enable lazy loading for images
 */
add_filter('wp_lazy_loading_enabled', '__return_true');

/**
 * Add loading="lazy" to images
 */
function autowp_add_lazy_loading($content) {
    if (is_admin() || is_feed()) {
        return $content;
    }
    
    $content = preg_replace('/<img((?![^>]*loading=)[^>]*)>/i', '<img$1 loading="lazy">', $content);
    return $content;
}
add_filter('the_content', 'autowp_add_lazy_loading', 20);

/**
 * Enable WebP support
 */
function autowp_webp_upload_mimes($mimes) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter('upload_mimes', 'autowp_webp_upload_mimes');

/**
 * Add WebP support to media library
 */
function autowp_webp_is_displayable($result, $path) {
    if ($result === false) {
        $displayable_image_types = array(IMAGETYPE_WEBP);
        $info = @getimagesize($path);
        
        if (empty($info)) {
            $result = false;
        } elseif (!in_array($info[2], $displayable_image_types)) {
            $result = false;
        } else {
            $result = true;
        }
    }
    
    return $result;
}
add_filter('file_is_displayable_image', 'autowp_webp_is_displayable', 10, 2);

/**
 * Add responsive image sizes
 */
function autowp_add_image_sizes() {
    // Already added in functions.php, but ensure srcset is generated
    add_filter('wp_calculate_image_srcset', 'autowp_calculate_srcset', 10, 5);
}
add_action('after_setup_theme', 'autowp_add_image_sizes');

/**
 * Optimize image srcset
 */
function autowp_calculate_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    // WordPress handles this automatically, just ensure it's enabled
    return $sources;
}

/**
 * Preload critical resources
 */
function autowp_preload_resources() {
    // Preload main stylesheet
    echo '<link rel="preload" href="' . esc_url(get_stylesheet_uri()) . '" as="style" />' . "\n";
    
    // Preload custom CSS
    echo '<link rel="preload" href="' . esc_url(AUTOWP_THEME_URI . '/assets/css/custom.css') . '" as="style" />' . "\n";
}
add_action('wp_head', 'autowp_preload_resources', 1);

/**
 * Defer non-critical JavaScript
 */
function autowp_defer_scripts($tag, $handle, $src) {
    // List of scripts to defer
    $defer_scripts = array(
        'autowp-main',
    );
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'autowp_defer_scripts', 10, 3);

/**
 * Remove jQuery migrate
 */
function autowp_remove_jquery_migrate($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        
        if ($script->deps) {
            $script->deps = array_diff($script->deps, array('jquery-migrate'));
        }
    }
}
add_action('wp_default_scripts', 'autowp_remove_jquery_migrate');

/**
 * Remove emoji scripts
 */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

/**
 * Disable embeds
 */
function autowp_disable_embeds() {
    // Remove the REST API endpoint
    remove_action('rest_api_init', 'wp_oembed_register_route');
    
    // Turn off oEmbed auto discovery
    add_filter('embed_oembed_discover', '__return_false');
    
    // Don't filter oEmbed results
    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    
    // Remove oEmbed discovery links
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    
    // Remove oEmbed-specific JavaScript from the front-end and back-end
    remove_action('wp_head', 'wp_oembed_add_host_js');
}
add_action('init', 'autowp_disable_embeds', 9999);

/**
 * Clean up wp_head
 */
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');

/**
 * Add cache headers for static assets
 */
function autowp_add_cache_headers() {
    if (!is_admin()) {
        // Browser cache for 1 year
        header('Cache-Control: public, max-age=31536000');
    }
}
// Note: This should be handled by NGINX, but add as fallback

/**
 * Database query optimization
 */
function autowp_optimize_queries($query) {
    if (!is_admin() && $query->is_main_query()) {
        // Limit posts per page for better performance
        if (is_archive() && !is_post_type_archive()) {
            $query->set('posts_per_page', 12);
        }
    }
}
add_action('pre_get_posts', 'autowp_optimize_queries');

/**
 * Enable output buffering for full-page caching
 */
function autowp_start_buffer() {
    if (!is_admin() && !is_user_logged_in()) {
        ob_start('autowp_cache_buffer');
    }
}
add_action('template_redirect', 'autowp_start_buffer', 1);

/**
 * Cache buffer callback
 */
function autowp_cache_buffer($buffer) {
    // Add cache comment
    $buffer .= "\n<!-- Cached on " . date('Y-m-d H:i:s') . " -->";
    return $buffer;
}

/**
 * Purge cache hook for plugins
 */
function autowp_purge_cache() {
    // Hook for cache plugins to clear cache
    do_action('autowp_purge_all_cache');
    
    // If using specific caching plugins, add their purge functions here
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

/**
 * Purge cache on post update
 */
function autowp_purge_post_cache($post_id) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    autowp_purge_cache();
}
add_action('save_post', 'autowp_purge_post_cache');
add_action('deleted_post', 'autowp_purge_post_cache');

/**
 * Preconnect to external domains
 */
function autowp_resource_hints($urls, $relation_type) {
    if ('dns-prefetch' === $relation_type) {
        $urls[] = array(
            'href' => '//fonts.googleapis.com',
        );
    }
    
    if ('preconnect' === $relation_type) {
        $urls[] = array(
            'href' => '//fonts.gstatic.com',
            'crossorigin',
        );
    }
    
    return $urls;
}
add_filter('wp_resource_hints', 'autowp_resource_hints', 10, 2);

/**
 * Optimize heartbeat API
 */
function autowp_heartbeat_settings($settings) {
    // Slow down heartbeat to every 60 seconds
    $settings['interval'] = 60;
    return $settings;
}
add_filter('heartbeat_settings', 'autowp_heartbeat_settings');

/**
 * Limit post revisions
 */
if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 5);
}

/**
 * Increase autosave interval
 */
if (!defined('AUTOSAVE_INTERVAL')) {
    define('AUTOSAVE_INTERVAL', 300); // 5 minutes
}

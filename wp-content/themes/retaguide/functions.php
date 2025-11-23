<?php
/**
 * AutoWP Theme Functions
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme version
define('AUTOWP_VERSION', '1.0.0');
define('AUTOWP_THEME_DIR', get_template_directory());
define('AUTOWP_THEME_URI', get_template_directory_uri());

/**
 * Theme setup
 */
function autowp_setup() {
    // Add theme support
    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));

    // Add editor stylesheet
    add_editor_style('style.css');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'autowp'),
        'footer' => __('Footer Menu', 'autowp'),
    ));

    // Set content width
    if (!isset($content_width)) {
        $content_width = 720;
    }
}
add_action('after_setup_theme', 'autowp_setup');

/**
 * Enqueue scripts and styles
 */
function autowp_enqueue_scripts() {
    // Main stylesheet
    wp_enqueue_style('autowp-style', get_stylesheet_uri(), array(), AUTOWP_VERSION);
    
    // Custom styles
    wp_enqueue_style('autowp-custom', AUTOWP_THEME_URI . '/assets/css/custom.css', array(), AUTOWP_VERSION);
    
    // Thread comments
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    // Custom scripts
    wp_enqueue_script('autowp-main', AUTOWP_THEME_URI . '/assets/js/main.js', array(), AUTOWP_VERSION, true);
}
add_action('wp_enqueue_scripts', 'autowp_enqueue_scripts');

/**
 * Include required files
 */
require_once AUTOWP_THEME_DIR . '/inc/custom-post-types.php';
require_once AUTOWP_THEME_DIR . '/inc/taxonomies.php';
require_once AUTOWP_THEME_DIR . '/inc/disclaimer.php';
require_once AUTOWP_THEME_DIR . '/inc/pinned-nav.php';
require_once AUTOWP_THEME_DIR . '/inc/seo.php';
require_once AUTOWP_THEME_DIR . '/inc/performance.php';
require_once AUTOWP_THEME_DIR . '/inc/security.php';
require_once AUTOWP_THEME_DIR . '/inc/theme-settings.php';
require_once AUTOWP_THEME_DIR . '/inc/block-patterns.php';
require_once AUTOWP_THEME_DIR . '/inc/breadcrumbs.php';

/**
 * Add custom image sizes
 */
function autowp_image_sizes() {
    add_image_size('autowp-hero', 1200, 600, true);
    add_image_size('autowp-featured', 800, 450, true);
    add_image_size('autowp-thumbnail', 400, 300, true);
}
add_action('after_setup_theme', 'autowp_image_sizes');

/**
 * Filter excerpt length
 */
function autowp_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'autowp_excerpt_length');

/**
 * Filter excerpt more
 */
function autowp_excerpt_more($more) {
    return '&hellip;';
}
add_filter('excerpt_more', 'autowp_excerpt_more');

/**
 * Add body classes
 */
function autowp_body_classes($classes) {
    // Add class for singular posts
    if (is_singular()) {
        $classes[] = 'singular';
    }

    // Add class for has-sidebar
    if (is_active_sidebar('sidebar-1')) {
        $classes[] = 'has-sidebar';
    }

    return $classes;
}
add_filter('body_class', 'autowp_body_classes');

/**
 * Flush rewrite rules on theme activation
 */
function autowp_activate() {
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'autowp_activate');

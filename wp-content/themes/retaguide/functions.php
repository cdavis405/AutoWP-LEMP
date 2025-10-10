<?php
/**
 * RetaGuide Theme Functions
 *
 * @package RetaGuide
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme version
define('RETAGUIDE_VERSION', '1.0.0');
define('RETAGUIDE_THEME_DIR', get_template_directory());
define('RETAGUIDE_THEME_URI', get_template_directory_uri());

/**
 * Theme setup
 */
function retaguide_setup() {
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
        'primary' => __('Primary Menu', 'retaguide'),
        'footer' => __('Footer Menu', 'retaguide'),
    ));

    // Set content width
    if (!isset($content_width)) {
        $content_width = 720;
    }
}
add_action('after_setup_theme', 'retaguide_setup');

/**
 * Enqueue scripts and styles
 */
function retaguide_enqueue_scripts() {
    // Main stylesheet
    wp_enqueue_style('retaguide-style', get_stylesheet_uri(), array(), RETAGUIDE_VERSION);
    
    // Custom styles
    wp_enqueue_style('retaguide-custom', RETAGUIDE_THEME_URI . '/assets/css/custom.css', array(), RETAGUIDE_VERSION);
    
    // Thread comments
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    // Custom scripts
    wp_enqueue_script('retaguide-main', RETAGUIDE_THEME_URI . '/assets/js/main.js', array(), RETAGUIDE_VERSION, true);
}
add_action('wp_enqueue_scripts', 'retaguide_enqueue_scripts');

/**
 * Include required files
 */
require_once RETAGUIDE_THEME_DIR . '/inc/custom-post-types.php';
require_once RETAGUIDE_THEME_DIR . '/inc/taxonomies.php';
require_once RETAGUIDE_THEME_DIR . '/inc/disclaimer.php';
require_once RETAGUIDE_THEME_DIR . '/inc/pinned-nav.php';
require_once RETAGUIDE_THEME_DIR . '/inc/seo.php';
require_once RETAGUIDE_THEME_DIR . '/inc/performance.php';
require_once RETAGUIDE_THEME_DIR . '/inc/security.php';
require_once RETAGUIDE_THEME_DIR . '/inc/theme-settings.php';
require_once RETAGUIDE_THEME_DIR . '/inc/block-patterns.php';
require_once RETAGUIDE_THEME_DIR . '/inc/breadcrumbs.php';

/**
 * Add custom image sizes
 */
function retaguide_image_sizes() {
    add_image_size('retaguide-hero', 1200, 600, true);
    add_image_size('retaguide-featured', 800, 450, true);
    add_image_size('retaguide-thumbnail', 400, 300, true);
}
add_action('after_setup_theme', 'retaguide_image_sizes');

/**
 * Filter excerpt length
 */
function retaguide_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'retaguide_excerpt_length');

/**
 * Filter excerpt more
 */
function retaguide_excerpt_more($more) {
    return '&hellip;';
}
add_filter('excerpt_more', 'retaguide_excerpt_more');

/**
 * Add body classes
 */
function retaguide_body_classes($classes) {
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
add_filter('body_class', 'retaguide_body_classes');

/**
 * Flush rewrite rules on theme activation
 */
function retaguide_activate() {
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'retaguide_activate');

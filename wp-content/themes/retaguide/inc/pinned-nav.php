<?php
/**
 * Pinned Navigation System
 *
 * @package RetaGuide
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get pinned navigation items
 */
function retaguide_get_pinned_nav_items() {
    $pinned_items = get_option('retaguide_pinned_nav_items', array());
    
    if (empty($pinned_items) || !is_array($pinned_items)) {
        return array();
    }
    
    // Filter out items that no longer exist
    $valid_items = array();
    foreach ($pinned_items as $item) {
        if (isset($item['id']) && get_post_status($item['id'])) {
            $valid_items[] = $item;
        }
    }
    
    return $valid_items;
}

/**
 * Render pinned navigation items
 */
function retaguide_render_pinned_nav() {
    $pinned_items = retaguide_get_pinned_nav_items();
    
    if (empty($pinned_items)) {
        return '';
    }
    
    $output = '<div class="pinned-nav-items" role="navigation" aria-label="' . esc_attr__('Pinned Navigation', 'retaguide') . '">';
    
    foreach ($pinned_items as $item) {
        $post_id = $item['id'];
        $title = isset($item['custom_title']) && !empty($item['custom_title']) 
            ? $item['custom_title'] 
            : get_the_title($post_id);
        
        $url = get_permalink($post_id);
        
        $output .= sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html($title)
        );
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Add pinned items to navigation block
 */
function retaguide_add_pinned_to_navigation($block_content, $block) {
    // Only modify navigation blocks
    if ($block['blockName'] !== 'core/navigation') {
        return $block_content;
    }
    
    // Check if this is the primary navigation
    $attrs = isset($block['attrs']) ? $block['attrs'] : array();
    $is_primary = isset($attrs['ref']) || strpos($block_content, 'primary') !== false;
    
    if ($is_primary) {
        $pinned_nav = retaguide_render_pinned_nav();
        
        if (!empty($pinned_nav)) {
            // Insert pinned items before closing navigation tag
            $block_content = str_replace(
                '</nav>',
                $pinned_nav . '</nav>',
                $block_content
            );
        }
    }
    
    return $block_content;
}
add_filter('render_block', 'retaguide_add_pinned_to_navigation', 10, 2);

/**
 * Enqueue admin scripts for pinned nav
 */
function retaguide_enqueue_pinned_nav_admin_scripts($hook) {
    if ('appearance_page_retaguide-pinned-nav' !== $hook) {
        return;
    }
    
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style('retaguide-admin', RETAGUIDE_THEME_URI . '/assets/css/admin.css', array(), RETAGUIDE_VERSION);
    wp_enqueue_script('retaguide-pinned-nav', RETAGUIDE_THEME_URI . '/assets/js/pinned-nav-admin.js', array('jquery', 'jquery-ui-sortable'), RETAGUIDE_VERSION, true);
}
add_action('admin_enqueue_scripts', 'retaguide_enqueue_pinned_nav_admin_scripts');

/**
 * AJAX handler to search posts/pages
 */
function retaguide_search_posts_ajax() {
    check_ajax_referer('retaguide_pinned_nav_search', 'nonce');
    
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    $args = array(
        'post_type' => array('post', 'page', 'guide'),
        'post_status' => 'publish',
        'posts_per_page' => 20,
        's' => $search,
        'orderby' => 'relevance',
    );
    
    $query = new WP_Query($args);
    $results = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'type' => get_post_type(),
                'url' => get_permalink(),
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success($results);
}
add_action('wp_ajax_retaguide_search_posts', 'retaguide_search_posts_ajax');

/**
 * AJAX handler to save pinned items
 */
function retaguide_save_pinned_items_ajax() {
    check_ajax_referer('retaguide_save_pinned_items', 'nonce');
    
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    $items = isset($_POST['items']) ? $_POST['items'] : array();
    
    // Sanitize items
    $sanitized_items = array();
    foreach ($items as $item) {
        if (isset($item['id'])) {
            $sanitized_items[] = array(
                'id' => absint($item['id']),
                'custom_title' => isset($item['custom_title']) ? sanitize_text_field($item['custom_title']) : '',
            );
        }
    }
    
    update_option('retaguide_pinned_nav_items', $sanitized_items);
    
    wp_send_json_success(array('message' => 'Pinned items saved successfully'));
}
add_action('wp_ajax_retaguide_save_pinned_items', 'retaguide_save_pinned_items_ajax');

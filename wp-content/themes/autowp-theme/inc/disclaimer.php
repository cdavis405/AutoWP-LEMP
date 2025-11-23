<?php
/**
 * Disclaimer System
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get global disclaimer text
 */
function autowp_get_global_disclaimer() {
    $disclaimer = get_option('autowp_global_disclaimer', '');
    
    if (empty($disclaimer)) {
        $disclaimer = '<p><strong>Disclaimer:</strong> This content is for informational and educational purposes only. This is a generic disclaimer placeholder.</p>';
    }
    
    return wp_kses_post($disclaimer);
}

/**
 * Render disclaimer HTML
 */
function autowp_render_disclaimer($content = '', $type = 'standard') {
    if (empty($content)) {
        $content = autowp_get_global_disclaimer();
    }
    
    $classes = 'disclaimer-banner';
    if ($type === 'alert') {
        $classes .= ' alert';
    }
    
    $title = ($type === 'alert') ? __('Important Information', 'autowp') : __('Disclaimer', 'autowp');
    
    $html = sprintf(
        '<div class="%s" role="alert" aria-label="%s">
            <h3>%s</h3>
            %s
        </div>',
        esc_attr($classes),
        esc_attr($title),
        esc_html($title),
        $content
    );
    
    return $html;
}

/**
 * Check if post should show disclaimer
 */
function autowp_should_show_disclaimer($post_id) {
    // Check if disclaimer is globally disabled
    if (!get_option('autowp_enable_global_disclaimer', true)) {
        return false;
    }
    
    // Check if this specific post has disabled the disclaimer
    $hide_disclaimer = get_post_meta($post_id, '_autowp_hide_disclaimer', true);
    if ($hide_disclaimer === 'yes') {
        return false;
    }
    
    return true;
}

/**
 * Get post-specific disclaimer override
 */
function autowp_get_post_disclaimer($post_id) {
    $custom_disclaimer = get_post_meta($post_id, '_autowp_custom_disclaimer', true);
    
    if (!empty($custom_disclaimer)) {
        return wp_kses_post($custom_disclaimer);
    }
    
    return '';
}

/**
 * Prepend disclaimer to post content
 */
function autowp_prepend_disclaimer($content) {
    if (!is_singular(array('post', 'guide'))) {
        return $content;
    }
    
    $post_id = get_the_ID();
    
    if (!autowp_should_show_disclaimer($post_id)) {
        return $content;
    }
    
    // Check for custom disclaimer first
    $disclaimer_text = autowp_get_post_disclaimer($post_id);
    if (empty($disclaimer_text)) {
        $disclaimer_text = autowp_get_global_disclaimer();
    }
    
    $disclaimer_html = autowp_render_disclaimer($disclaimer_text);
    
    return $disclaimer_html . $content;
}
add_filter('the_content', 'autowp_prepend_disclaimer', 10);

/**
 * Disclaimer shortcode
 * Usage: [disclaimer] or [disclaimer type="alert"]
 */
function autowp_disclaimer_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'standard',
        'custom' => '',
    ), $atts, 'disclaimer');
    
    $post_id = get_the_ID();
    $disclaimer_text = '';
    
    // Check for custom text in shortcode
    if (!empty($atts['custom'])) {
        $disclaimer_text = $atts['custom'];
    } else {
        // Check for post-specific override
        $disclaimer_text = autowp_get_post_disclaimer($post_id);
        
        // Fall back to global
        if (empty($disclaimer_text)) {
            $disclaimer_text = autowp_get_global_disclaimer();
        }
    }
    
    return autowp_render_disclaimer($disclaimer_text, $atts['type']);
}
add_shortcode('disclaimer', 'autowp_disclaimer_shortcode');

/**
 * Add disclaimer meta box to posts and guides
 */
function autowp_add_disclaimer_meta_box() {
    $post_types = array('post', 'guide');
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'autowp_disclaimer_settings',
            __('Disclaimer Settings', 'autowp'),
            'autowp_disclaimer_meta_box_callback',
            $post_type,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'autowp_add_disclaimer_meta_box');

/**
 * Disclaimer meta box callback
 */
function autowp_disclaimer_meta_box_callback($post) {
    wp_nonce_field('autowp_save_disclaimer', 'autowp_disclaimer_nonce');
    
    $hide_disclaimer = get_post_meta($post->ID, '_autowp_hide_disclaimer', true);
    $custom_disclaimer = get_post_meta($post->ID, '_autowp_custom_disclaimer', true);
    ?>
    <p>
        <label>
            <input type="checkbox" name="autowp_hide_disclaimer" value="yes" 
                   <?php checked($hide_disclaimer, 'yes'); ?> />
            <?php _e('Hide global disclaimer on this post', 'autowp'); ?>
        </label>
    </p>
    
    <p>
        <label for="autowp_custom_disclaimer">
            <strong><?php _e('Custom Disclaimer (optional):', 'autowp'); ?></strong>
        </label>
        <?php
        wp_editor($custom_disclaimer, 'autowp_custom_disclaimer', array(
            'textarea_rows' => 5,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => false,
        ));
        ?>
        <span class="description">
            <?php _e('Leave blank to use the global disclaimer. This will override the global disclaimer if provided.', 'autowp'); ?>
        </span>
    </p>
    <?php
}

/**
 * Save disclaimer meta
 */
function autowp_save_disclaimer_meta($post_id) {
    // Check nonce
    if (!isset($_POST['autowp_disclaimer_nonce']) || 
        !wp_verify_nonce($_POST['autowp_disclaimer_nonce'], 'autowp_save_disclaimer')) {
        return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save hide disclaimer setting
    if (isset($_POST['autowp_hide_disclaimer'])) {
        update_post_meta($post_id, '_autowp_hide_disclaimer', 'yes');
    } else {
        delete_post_meta($post_id, '_autowp_hide_disclaimer');
    }

    // Save custom disclaimer
    if (isset($_POST['autowp_custom_disclaimer'])) {
        update_post_meta($post_id, '_autowp_custom_disclaimer', wp_kses_post($_POST['autowp_custom_disclaimer']));
    }
}
add_action('save_post', 'autowp_save_disclaimer_meta');

/**
 * Register disclaimer block (Gutenberg reusable block)
 */
function autowp_register_disclaimer_block() {
    // Register the block pattern for disclaimer
    register_block_pattern(
        'autowp/disclaimer-block',
        array(
            'title'       => __('Disclaimer Block', 'autowp'),
            'description' => _x('A standardized disclaimer notice', 'Block pattern description', 'autowp'),
            'content'     => '<!-- wp:group {"backgroundColor":"warning-amber","className":"disclaimer-banner"} -->
<div class="wp-block-group disclaimer-banner has-warning-amber-background-color has-background">
    <!-- wp:heading {"level":3} -->
    <h3>Disclaimer</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph -->
    <p><strong>Disclaimer:</strong> This content is for informational and educational purposes only. This is a generic disclaimer placeholder.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
            'categories'  => array('text'),
            'keywords'    => array('disclaimer', 'warning', 'legal'),
        )
    );
}
add_action('init', 'autowp_register_disclaimer_block');

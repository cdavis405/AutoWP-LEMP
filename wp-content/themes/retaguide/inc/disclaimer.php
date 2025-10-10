<?php
/**
 * Disclaimer System
 *
 * @package RetaGuide
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get global disclaimer text
 */
function retaguide_get_global_disclaimer() {
    $disclaimer = get_option('retaguide_global_disclaimer', '');
    
    if (empty($disclaimer)) {
        $disclaimer = '<p><strong>Medical Disclaimer:</strong> This content is for informational and educational purposes only. Retatrutide is an experimental research peptide not approved by the FDA for human use. The information provided does not constitute medical advice and should not be used for diagnosis or treatment. Always consult with a qualified healthcare provider before making any decisions related to your health or treatment.</p>';
    }
    
    return wp_kses_post($disclaimer);
}

/**
 * Render disclaimer HTML
 */
function retaguide_render_disclaimer($content = '', $type = 'standard') {
    if (empty($content)) {
        $content = retaguide_get_global_disclaimer();
    }
    
    $classes = 'disclaimer-banner';
    if ($type === 'alert') {
        $classes .= ' alert';
    }
    
    $title = ($type === 'alert') ? __('Important Safety Information', 'retaguide') : __('Disclaimer', 'retaguide');
    
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
function retaguide_should_show_disclaimer($post_id) {
    // Check if disclaimer is globally disabled
    if (!get_option('retaguide_enable_global_disclaimer', true)) {
        return false;
    }
    
    // Check if this specific post has disabled the disclaimer
    $hide_disclaimer = get_post_meta($post_id, '_retaguide_hide_disclaimer', true);
    if ($hide_disclaimer === 'yes') {
        return false;
    }
    
    return true;
}

/**
 * Get post-specific disclaimer override
 */
function retaguide_get_post_disclaimer($post_id) {
    $custom_disclaimer = get_post_meta($post_id, '_retaguide_custom_disclaimer', true);
    
    if (!empty($custom_disclaimer)) {
        return wp_kses_post($custom_disclaimer);
    }
    
    return '';
}

/**
 * Prepend disclaimer to post content
 */
function retaguide_prepend_disclaimer($content) {
    if (!is_singular(array('post', 'guide'))) {
        return $content;
    }
    
    $post_id = get_the_ID();
    
    if (!retaguide_should_show_disclaimer($post_id)) {
        return $content;
    }
    
    // Check for custom disclaimer first
    $disclaimer_text = retaguide_get_post_disclaimer($post_id);
    if (empty($disclaimer_text)) {
        $disclaimer_text = retaguide_get_global_disclaimer();
    }
    
    $disclaimer_html = retaguide_render_disclaimer($disclaimer_text);
    
    return $disclaimer_html . $content;
}
add_filter('the_content', 'retaguide_prepend_disclaimer', 10);

/**
 * Disclaimer shortcode
 * Usage: [disclaimer] or [disclaimer type="alert"]
 */
function retaguide_disclaimer_shortcode($atts) {
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
        $disclaimer_text = retaguide_get_post_disclaimer($post_id);
        
        // Fall back to global
        if (empty($disclaimer_text)) {
            $disclaimer_text = retaguide_get_global_disclaimer();
        }
    }
    
    return retaguide_render_disclaimer($disclaimer_text, $atts['type']);
}
add_shortcode('disclaimer', 'retaguide_disclaimer_shortcode');

/**
 * Add disclaimer meta box to posts and guides
 */
function retaguide_add_disclaimer_meta_box() {
    $post_types = array('post', 'guide');
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'retaguide_disclaimer_settings',
            __('Disclaimer Settings', 'retaguide'),
            'retaguide_disclaimer_meta_box_callback',
            $post_type,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'retaguide_add_disclaimer_meta_box');

/**
 * Disclaimer meta box callback
 */
function retaguide_disclaimer_meta_box_callback($post) {
    wp_nonce_field('retaguide_save_disclaimer', 'retaguide_disclaimer_nonce');
    
    $hide_disclaimer = get_post_meta($post->ID, '_retaguide_hide_disclaimer', true);
    $custom_disclaimer = get_post_meta($post->ID, '_retaguide_custom_disclaimer', true);
    ?>
    <p>
        <label>
            <input type="checkbox" name="retaguide_hide_disclaimer" value="yes" 
                   <?php checked($hide_disclaimer, 'yes'); ?> />
            <?php _e('Hide global disclaimer on this post', 'retaguide'); ?>
        </label>
    </p>
    
    <p>
        <label for="retaguide_custom_disclaimer">
            <strong><?php _e('Custom Disclaimer (optional):', 'retaguide'); ?></strong>
        </label>
        <?php
        wp_editor($custom_disclaimer, 'retaguide_custom_disclaimer', array(
            'textarea_rows' => 5,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => false,
        ));
        ?>
        <span class="description">
            <?php _e('Leave blank to use the global disclaimer. This will override the global disclaimer if provided.', 'retaguide'); ?>
        </span>
    </p>
    <?php
}

/**
 * Save disclaimer meta
 */
function retaguide_save_disclaimer_meta($post_id) {
    // Check nonce
    if (!isset($_POST['retaguide_disclaimer_nonce']) || 
        !wp_verify_nonce($_POST['retaguide_disclaimer_nonce'], 'retaguide_save_disclaimer')) {
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
    if (isset($_POST['retaguide_hide_disclaimer'])) {
        update_post_meta($post_id, '_retaguide_hide_disclaimer', 'yes');
    } else {
        delete_post_meta($post_id, '_retaguide_hide_disclaimer');
    }

    // Save custom disclaimer
    if (isset($_POST['retaguide_custom_disclaimer'])) {
        update_post_meta($post_id, '_retaguide_custom_disclaimer', wp_kses_post($_POST['retaguide_custom_disclaimer']));
    }
}
add_action('save_post', 'retaguide_save_disclaimer_meta');

/**
 * Register disclaimer block (Gutenberg reusable block)
 */
function retaguide_register_disclaimer_block() {
    // Register the block pattern for disclaimer
    register_block_pattern(
        'retaguide/disclaimer-block',
        array(
            'title'       => __('Disclaimer Block', 'retaguide'),
            'description' => _x('A standardized disclaimer notice', 'Block pattern description', 'retaguide'),
            'content'     => '<!-- wp:group {"backgroundColor":"warning-amber","className":"disclaimer-banner"} -->
<div class="wp-block-group disclaimer-banner has-warning-amber-background-color has-background">
    <!-- wp:heading {"level":3} -->
    <h3>Disclaimer</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph -->
    <p><strong>Medical Disclaimer:</strong> This content is for informational and educational purposes only. Retatrutide is an experimental research peptide not approved by the FDA for human use. The information provided does not constitute medical advice and should not be used for diagnosis or treatment. Always consult with a qualified healthcare provider before making any decisions related to your health or treatment.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
            'categories'  => array('text'),
            'keywords'    => array('disclaimer', 'warning', 'legal', 'medical'),
        )
    );
}
add_action('init', 'retaguide_register_disclaimer_block');

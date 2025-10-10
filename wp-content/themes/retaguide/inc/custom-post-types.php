<?php
/**
 * Custom Post Types
 *
 * @package RetaGuide
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Guides Custom Post Type
 */
function retaguide_register_guide_cpt() {
    $labels = array(
        'name'                  => _x('Guides', 'Post type general name', 'retaguide'),
        'singular_name'         => _x('Guide', 'Post type singular name', 'retaguide'),
        'menu_name'             => _x('Guides', 'Admin Menu text', 'retaguide'),
        'name_admin_bar'        => _x('Guide', 'Add New on Toolbar', 'retaguide'),
        'add_new'               => __('Add New', 'retaguide'),
        'add_new_item'          => __('Add New Guide', 'retaguide'),
        'new_item'              => __('New Guide', 'retaguide'),
        'edit_item'             => __('Edit Guide', 'retaguide'),
        'view_item'             => __('View Guide', 'retaguide'),
        'all_items'             => __('All Guides', 'retaguide'),
        'search_items'          => __('Search Guides', 'retaguide'),
        'parent_item_colon'     => __('Parent Guides:', 'retaguide'),
        'not_found'             => __('No guides found.', 'retaguide'),
        'not_found_in_trash'    => __('No guides found in Trash.', 'retaguide'),
        'featured_image'        => _x('Guide Cover Image', 'Overrides the "Featured Image" phrase', 'retaguide'),
        'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'retaguide'),
        'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'retaguide'),
        'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'retaguide'),
        'archives'              => _x('Guide archives', 'The post type archive label used in nav menus', 'retaguide'),
        'insert_into_item'      => _x('Insert into guide', 'Overrides the "Insert into post"/"Insert into page" phrase', 'retaguide'),
        'uploaded_to_this_item' => _x('Uploaded to this guide', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'retaguide'),
        'filter_items_list'     => _x('Filter guides list', 'Screen reader text for the filter links', 'retaguide'),
        'items_list_navigation' => _x('Guides list navigation', 'Screen reader text for the pagination', 'retaguide'),
        'items_list'            => _x('Guides list', 'Screen reader text for the items list', 'retaguide'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'guides'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-book-alt',
        'show_in_rest'       => true,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields'),
        'taxonomies'         => array('guide_level', 'guide_topic'),
        'template'           => array(
            array('core/paragraph', array(
                'placeholder' => 'Start writing your guide...',
            )),
        ),
    );

    register_post_type('guide', $args);
}
add_action('init', 'retaguide_register_guide_cpt');

/**
 * Add custom meta boxes for guides
 */
function retaguide_add_guide_meta_boxes() {
    add_meta_box(
        'retaguide_guide_details',
        __('Guide Details', 'retaguide'),
        'retaguide_guide_details_callback',
        'guide',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'retaguide_add_guide_meta_boxes');

/**
 * Guide details meta box callback
 */
function retaguide_guide_details_callback($post) {
    wp_nonce_field('retaguide_save_guide_details', 'retaguide_guide_details_nonce');

    $last_reviewed = get_post_meta($post->ID, '_retaguide_last_reviewed', true);
    $version = get_post_meta($post->ID, '_retaguide_version', true);
    $reading_time = get_post_meta($post->ID, '_retaguide_reading_time', true);
    ?>
    <p>
        <label for="retaguide_last_reviewed">
            <strong><?php _e('Last Reviewed:', 'retaguide'); ?></strong>
        </label>
        <input type="date" id="retaguide_last_reviewed" name="retaguide_last_reviewed" 
               value="<?php echo esc_attr($last_reviewed); ?>" style="width: 100%;" />
    </p>
    <p>
        <label for="retaguide_version">
            <strong><?php _e('Version:', 'retaguide'); ?></strong>
        </label>
        <input type="text" id="retaguide_version" name="retaguide_version" 
               value="<?php echo esc_attr($version); ?>" placeholder="1.0" style="width: 100%;" />
    </p>
    <p>
        <label for="retaguide_reading_time">
            <strong><?php _e('Reading Time (minutes):', 'retaguide'); ?></strong>
        </label>
        <input type="number" id="retaguide_reading_time" name="retaguide_reading_time" 
               value="<?php echo esc_attr($reading_time); ?>" min="1" placeholder="5" style="width: 100%;" />
    </p>
    <?php
}

/**
 * Save guide details meta
 */
function retaguide_save_guide_details($post_id) {
    // Check nonce
    if (!isset($_POST['retaguide_guide_details_nonce']) || 
        !wp_verify_nonce($_POST['retaguide_guide_details_nonce'], 'retaguide_save_guide_details')) {
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

    // Save last reviewed
    if (isset($_POST['retaguide_last_reviewed'])) {
        update_post_meta($post_id, '_retaguide_last_reviewed', sanitize_text_field($_POST['retaguide_last_reviewed']));
    }

    // Save version
    if (isset($_POST['retaguide_version'])) {
        update_post_meta($post_id, '_retaguide_version', sanitize_text_field($_POST['retaguide_version']));
    }

    // Save reading time
    if (isset($_POST['retaguide_reading_time'])) {
        update_post_meta($post_id, '_retaguide_reading_time', absint($_POST['retaguide_reading_time']));
    }
}
add_action('save_post_guide', 'retaguide_save_guide_details');

/**
 * Customize post permalink structure
 */
function retaguide_custom_permalinks() {
    // News posts at /news/{post-name}
    add_rewrite_rule(
        '^news/([^/]+)/?$',
        'index.php?name=$matches[1]',
        'top'
    );

    // News category archives at /news/category/{slug}
    add_rewrite_rule(
        '^news/category/([^/]+)/?$',
        'index.php?category_name=$matches[1]',
        'top'
    );

    // News tag archives at /news/tag/{slug}
    add_rewrite_rule(
        '^news/tag/([^/]+)/?$',
        'index.php?tag=$matches[1]',
        'top'
    );
}
add_action('init', 'retaguide_custom_permalinks');

/**
 * Seed default categories for News
 */
function retaguide_seed_news_categories() {
    $categories = array(
        'Research' => 'Latest research findings and clinical studies on Retatrutide',
        'Safety' => 'Safety information, side effects, and contraindications',
        'Regulatory' => 'FDA updates, regulations, and compliance news',
        'Market' => 'Market availability, pricing, and distribution updates',
        'Reviews' => 'Expert reviews and analysis',
    );

    foreach ($categories as $name => $description) {
        if (!term_exists($name, 'category')) {
            wp_insert_term($name, 'category', array(
                'description' => $description,
                'slug' => sanitize_title($name),
            ));
        }
    }
}
add_action('after_switch_theme', 'retaguide_seed_news_categories');

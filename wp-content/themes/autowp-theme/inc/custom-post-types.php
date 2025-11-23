<?php
/**
 * Custom Post Types
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Guides Custom Post Type
 */
function autowp_register_guide_cpt() {
    $labels = array(
        'name'                  => _x('Guides', 'Post type general name', 'autowp'),
        'singular_name'         => _x('Guide', 'Post type singular name', 'autowp'),
        'menu_name'             => _x('Guides', 'Admin Menu text', 'autowp'),
        'name_admin_bar'        => _x('Guide', 'Add New on Toolbar', 'autowp'),
        'add_new'               => __('Add New', 'autowp'),
        'add_new_item'          => __('Add New Guide', 'autowp'),
        'new_item'              => __('New Guide', 'autowp'),
        'edit_item'             => __('Edit Guide', 'autowp'),
        'view_item'             => __('View Guide', 'autowp'),
        'all_items'             => __('All Guides', 'autowp'),
        'search_items'          => __('Search Guides', 'autowp'),
        'parent_item_colon'     => __('Parent Guides:', 'autowp'),
        'not_found'             => __('No guides found.', 'autowp'),
        'not_found_in_trash'    => __('No guides found in Trash.', 'autowp'),
        'featured_image'        => _x('Guide Cover Image', 'Overrides the "Featured Image" phrase', 'autowp'),
        'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'autowp'),
        'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'autowp'),
        'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'autowp'),
        'archives'              => _x('Guide archives', 'The post type archive label used in nav menus', 'autowp'),
        'insert_into_item'      => _x('Insert into guide', 'Overrides the "Insert into post"/"Insert into page" phrase', 'autowp'),
        'uploaded_to_this_item' => _x('Uploaded to this guide', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'autowp'),
        'filter_items_list'     => _x('Filter guides list', 'Screen reader text for the filter links', 'autowp'),
        'items_list_navigation' => _x('Guides list navigation', 'Screen reader text for the pagination', 'autowp'),
        'items_list'            => _x('Guides list', 'Screen reader text for the items list', 'autowp'),
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
add_action('init', 'autowp_register_guide_cpt');

/**
 * Add custom meta boxes for guides
 */
function autowp_add_guide_meta_boxes() {
    add_meta_box(
        'autowp_guide_details',
        __('Guide Details', 'autowp'),
        'autowp_guide_details_callback',
        'guide',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'autowp_add_guide_meta_boxes');

/**
 * Guide details meta box callback
 */
function autowp_guide_details_callback($post) {
    wp_nonce_field('autowp_save_guide_details', 'autowp_guide_details_nonce');

    $last_reviewed = get_post_meta($post->ID, '_autowp_last_reviewed', true);
    $version = get_post_meta($post->ID, '_autowp_version', true);
    $reading_time = get_post_meta($post->ID, '_autowp_reading_time', true);
    ?>
    <p>
        <label for="autowp_last_reviewed">
            <strong><?php _e('Last Reviewed:', 'autowp'); ?></strong>
        </label>
        <input type="date" id="autowp_last_reviewed" name="autowp_last_reviewed" 
               value="<?php echo esc_attr($last_reviewed); ?>" style="width: 100%;" />
    </p>
    <p>
        <label for="autowp_version">
            <strong><?php _e('Version:', 'autowp'); ?></strong>
        </label>
        <input type="text" id="autowp_version" name="autowp_version" 
               value="<?php echo esc_attr($version); ?>" placeholder="1.0" style="width: 100%;" />
    </p>
    <p>
        <label for="autowp_reading_time">
            <strong><?php _e('Reading Time (minutes):', 'autowp'); ?></strong>
        </label>
        <input type="number" id="autowp_reading_time" name="autowp_reading_time" 
               value="<?php echo esc_attr($reading_time); ?>" min="1" placeholder="5" style="width: 100%;" />
    </p>
    <?php
}

/**
 * Save guide details meta
 */
function autowp_save_guide_details($post_id) {
    // Check nonce
    if (!isset($_POST['autowp_guide_details_nonce']) || 
        !wp_verify_nonce($_POST['autowp_guide_details_nonce'], 'autowp_save_guide_details')) {
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
    if (isset($_POST['autowp_last_reviewed'])) {
        update_post_meta($post_id, '_autowp_last_reviewed', sanitize_text_field($_POST['autowp_last_reviewed']));
    }

    // Save version
    if (isset($_POST['autowp_version'])) {
        update_post_meta($post_id, '_autowp_version', sanitize_text_field($_POST['autowp_version']));
    }

    // Save reading time
    if (isset($_POST['autowp_reading_time'])) {
        update_post_meta($post_id, '_autowp_reading_time', absint($_POST['autowp_reading_time']));
    }
}
add_action('save_post_guide', 'autowp_save_guide_details');

/**
 * Customize post permalink structure
 */
function autowp_custom_permalinks() {
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
add_action('init', 'autowp_custom_permalinks');

/**
 * Seed default categories for News
 */
function autowp_seed_news_categories() {
    $categories = array(
        'Updates' => 'Latest updates and news',
        'Features' => 'New features and capabilities',
        'Community' => 'Community news and events',
        'Tutorials' => 'Tutorials and how-to guides',
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
add_action('after_switch_theme', 'autowp_seed_news_categories');

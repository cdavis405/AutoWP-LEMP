<?php
/**
 * Custom Taxonomies
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Guide Level taxonomy
 */
function autowp_register_guide_level_taxonomy() {
    $labels = array(
        'name'                       => _x('Guide Levels', 'taxonomy general name', 'autowp'),
        'singular_name'              => _x('Guide Level', 'taxonomy singular name', 'autowp'),
        'search_items'               => __('Search Guide Levels', 'autowp'),
        'popular_items'              => __('Popular Guide Levels', 'autowp'),
        'all_items'                  => __('All Guide Levels', 'autowp'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __('Edit Guide Level', 'autowp'),
        'update_item'                => __('Update Guide Level', 'autowp'),
        'add_new_item'               => __('Add New Guide Level', 'autowp'),
        'new_item_name'              => __('New Guide Level Name', 'autowp'),
        'separate_items_with_commas' => __('Separate guide levels with commas', 'autowp'),
        'add_or_remove_items'        => __('Add or remove guide levels', 'autowp'),
        'choose_from_most_used'      => __('Choose from the most used guide levels', 'autowp'),
        'not_found'                  => __('No guide levels found.', 'autowp'),
        'menu_name'                  => __('Guide Levels', 'autowp'),
    );

    $args = array(
        'hierarchical'          => true,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'show_in_rest'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'guides/level'),
        'show_in_quick_edit'    => true,
    );

    register_taxonomy('guide_level', array('guide'), $args);
}
add_action('init', 'autowp_register_guide_level_taxonomy', 0);

/**
 * Register Guide Topic taxonomy
 */
function autowp_register_guide_topic_taxonomy() {
    $labels = array(
        'name'                       => _x('Guide Topics', 'taxonomy general name', 'autowp'),
        'singular_name'              => _x('Guide Topic', 'taxonomy singular name', 'autowp'),
        'search_items'               => __('Search Guide Topics', 'autowp'),
        'popular_items'              => __('Popular Guide Topics', 'autowp'),
        'all_items'                  => __('All Guide Topics', 'autowp'),
        'parent_item'                => __('Parent Guide Topic', 'autowp'),
        'parent_item_colon'          => __('Parent Guide Topic:', 'autowp'),
        'edit_item'                  => __('Edit Guide Topic', 'autowp'),
        'update_item'                => __('Update Guide Topic', 'autowp'),
        'add_new_item'               => __('Add New Guide Topic', 'autowp'),
        'new_item_name'              => __('New Guide Topic Name', 'autowp'),
        'separate_items_with_commas' => __('Separate guide topics with commas', 'autowp'),
        'add_or_remove_items'        => __('Add or remove guide topics', 'autowp'),
        'choose_from_most_used'      => __('Choose from the most used guide topics', 'autowp'),
        'not_found'                  => __('No guide topics found.', 'autowp'),
        'menu_name'                  => __('Guide Topics', 'autowp'),
    );

    $args = array(
        'hierarchical'          => true,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'show_in_rest'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'guides/topic'),
        'show_in_quick_edit'    => true,
    );

    register_taxonomy('guide_topic', array('guide'), $args);
}
add_action('init', 'autowp_register_guide_topic_taxonomy', 0);

/**
 * Seed default Guide Level terms
 */
function autowp_seed_guide_levels() {
    $levels = array(
        'Beginner' => array(
            'description' => 'Introductory guides',
            'slug' => 'beginner',
        ),
        'Intermediate' => array(
            'description' => 'Intermediate guides',
            'slug' => 'intermediate',
        ),
        'Advanced' => array(
            'description' => 'Advanced guides',
            'slug' => 'advanced',
        ),
    );

    foreach ($levels as $name => $data) {
        if (!term_exists($name, 'guide_level')) {
            wp_insert_term($name, 'guide_level', array(
                'description' => $data['description'],
                'slug' => $data['slug'],
            ));
        }
    }
}
add_action('after_switch_theme', 'autowp_seed_guide_levels');

/**
 * Seed default Guide Topic terms
 */
function autowp_seed_guide_topics() {
    $topics = array(
        'Installation' => array(
            'description' => 'Installation guides',
            'slug' => 'installation',
        ),
        'Configuration' => array(
            'description' => 'Configuration guides',
            'slug' => 'configuration',
        ),
        'Maintenance' => array(
            'description' => 'Maintenance guides',
            'slug' => 'maintenance',
        ),
        'Security' => array(
            'description' => 'Security guides',
            'slug' => 'security',
        ),
    );

    foreach ($topics as $name => $data) {
        if (!term_exists($name, 'guide_topic')) {
            wp_insert_term($name, 'guide_topic', array(
                'description' => $data['description'],
                'slug' => $data['slug'],
            ));
        }
    }
}
add_action('after_switch_theme', 'autowp_seed_guide_topics');

/**
 * Add custom columns to guide admin list
 */
function autowp_guide_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['guide_level'] = __('Level', 'autowp');
            $new_columns['guide_topic'] = __('Topics', 'autowp');
            $new_columns['last_reviewed'] = __('Last Reviewed', 'autowp');
        }
    }
    
    return $new_columns;
}
add_filter('manage_guide_posts_columns', 'autowp_guide_columns');

/**
 * Populate custom columns
 */
function autowp_guide_column_content($column, $post_id) {
    switch ($column) {
        case 'guide_level':
            $terms = get_the_terms($post_id, 'guide_level');
            if ($terms && !is_wp_error($terms)) {
                $level_names = array_map(function($term) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(admin_url('edit.php?post_type=guide&guide_level=' . $term->slug)),
                        esc_html($term->name)
                    );
                }, $terms);
                echo implode(', ', $level_names);
            } else {
                echo '—';
            }
            break;

        case 'guide_topic':
            $terms = get_the_terms($post_id, 'guide_topic');
            if ($terms && !is_wp_error($terms)) {
                $topic_names = array_map(function($term) {
                    return esc_html($term->name);
                }, $terms);
                echo implode(', ', $topic_names);
            } else {
                echo '—';
            }
            break;

        case 'last_reviewed':
            $last_reviewed = get_post_meta($post_id, '_autowp_last_reviewed', true);
            if ($last_reviewed) {
                echo date('M j, Y', strtotime($last_reviewed));
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_guide_posts_custom_column', 'autowp_guide_column_content', 10, 2);

/**
 * Make custom columns sortable
 */
function autowp_guide_sortable_columns($columns) {
    $columns['last_reviewed'] = 'last_reviewed';
    return $columns;
}
add_filter('manage_edit-guide_sortable_columns', 'autowp_guide_sortable_columns');

/**
 * Handle sorting by last reviewed
 */
function autowp_guide_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ('last_reviewed' === $query->get('orderby')) {
        $query->set('meta_key', '_autowp_last_reviewed');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'autowp_guide_orderby');

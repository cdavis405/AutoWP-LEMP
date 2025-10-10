<?php
/**
 * Custom Taxonomies
 *
 * @package RetaGuide
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Guide Level taxonomy
 */
function retaguide_register_guide_level_taxonomy() {
    $labels = array(
        'name'                       => _x('Guide Levels', 'taxonomy general name', 'retaguide'),
        'singular_name'              => _x('Guide Level', 'taxonomy singular name', 'retaguide'),
        'search_items'               => __('Search Guide Levels', 'retaguide'),
        'popular_items'              => __('Popular Guide Levels', 'retaguide'),
        'all_items'                  => __('All Guide Levels', 'retaguide'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __('Edit Guide Level', 'retaguide'),
        'update_item'                => __('Update Guide Level', 'retaguide'),
        'add_new_item'               => __('Add New Guide Level', 'retaguide'),
        'new_item_name'              => __('New Guide Level Name', 'retaguide'),
        'separate_items_with_commas' => __('Separate guide levels with commas', 'retaguide'),
        'add_or_remove_items'        => __('Add or remove guide levels', 'retaguide'),
        'choose_from_most_used'      => __('Choose from the most used guide levels', 'retaguide'),
        'not_found'                  => __('No guide levels found.', 'retaguide'),
        'menu_name'                  => __('Guide Levels', 'retaguide'),
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
add_action('init', 'retaguide_register_guide_level_taxonomy', 0);

/**
 * Register Guide Topic taxonomy
 */
function retaguide_register_guide_topic_taxonomy() {
    $labels = array(
        'name'                       => _x('Guide Topics', 'taxonomy general name', 'retaguide'),
        'singular_name'              => _x('Guide Topic', 'taxonomy singular name', 'retaguide'),
        'search_items'               => __('Search Guide Topics', 'retaguide'),
        'popular_items'              => __('Popular Guide Topics', 'retaguide'),
        'all_items'                  => __('All Guide Topics', 'retaguide'),
        'parent_item'                => __('Parent Guide Topic', 'retaguide'),
        'parent_item_colon'          => __('Parent Guide Topic:', 'retaguide'),
        'edit_item'                  => __('Edit Guide Topic', 'retaguide'),
        'update_item'                => __('Update Guide Topic', 'retaguide'),
        'add_new_item'               => __('Add New Guide Topic', 'retaguide'),
        'new_item_name'              => __('New Guide Topic Name', 'retaguide'),
        'separate_items_with_commas' => __('Separate guide topics with commas', 'retaguide'),
        'add_or_remove_items'        => __('Add or remove guide topics', 'retaguide'),
        'choose_from_most_used'      => __('Choose from the most used guide topics', 'retaguide'),
        'not_found'                  => __('No guide topics found.', 'retaguide'),
        'menu_name'                  => __('Guide Topics', 'retaguide'),
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
add_action('init', 'retaguide_register_guide_topic_taxonomy', 0);

/**
 * Seed default Guide Level terms
 */
function retaguide_seed_guide_levels() {
    $levels = array(
        'Beginner' => array(
            'description' => 'Introductory guides for those new to Retatrutide research',
            'slug' => 'beginner',
        ),
        'Protocol' => array(
            'description' => 'Detailed research protocols and methodologies',
            'slug' => 'protocol',
        ),
        'Safety' => array(
            'description' => 'Safety guidelines and risk management information',
            'slug' => 'safety',
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
add_action('after_switch_theme', 'retaguide_seed_guide_levels');

/**
 * Seed default Guide Topic terms
 */
function retaguide_seed_guide_topics() {
    $topics = array(
        'Mechanism' => array(
            'description' => 'How Retatrutide works at the molecular level',
            'slug' => 'mechanism',
        ),
        'Dosing' => array(
            'description' => 'Dosing guidelines and administration protocols',
            'slug' => 'dosing',
        ),
        'Monitoring' => array(
            'description' => 'Monitoring parameters and follow-up procedures',
            'slug' => 'monitoring',
        ),
        'Interactions' => array(
            'description' => 'Drug interactions and contraindications',
            'slug' => 'interactions',
        ),
        'Clinical Trials' => array(
            'description' => 'Information about ongoing and completed clinical trials',
            'slug' => 'clinical-trials',
        ),
        'Patient Education' => array(
            'description' => 'Educational materials for patients and caregivers',
            'slug' => 'patient-education',
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
add_action('after_switch_theme', 'retaguide_seed_guide_topics');

/**
 * Add custom columns to guide admin list
 */
function retaguide_guide_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['guide_level'] = __('Level', 'retaguide');
            $new_columns['guide_topic'] = __('Topics', 'retaguide');
            $new_columns['last_reviewed'] = __('Last Reviewed', 'retaguide');
        }
    }
    
    return $new_columns;
}
add_filter('manage_guide_posts_columns', 'retaguide_guide_columns');

/**
 * Populate custom columns
 */
function retaguide_guide_column_content($column, $post_id) {
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
            $last_reviewed = get_post_meta($post_id, '_retaguide_last_reviewed', true);
            if ($last_reviewed) {
                echo date('M j, Y', strtotime($last_reviewed));
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_guide_posts_custom_column', 'retaguide_guide_column_content', 10, 2);

/**
 * Make custom columns sortable
 */
function retaguide_guide_sortable_columns($columns) {
    $columns['last_reviewed'] = 'last_reviewed';
    return $columns;
}
add_filter('manage_edit-guide_sortable_columns', 'retaguide_guide_sortable_columns');

/**
 * Handle sorting by last reviewed
 */
function retaguide_guide_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ('last_reviewed' === $query->get('orderby')) {
        $query->set('meta_key', '_retaguide_last_reviewed');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'retaguide_guide_orderby');

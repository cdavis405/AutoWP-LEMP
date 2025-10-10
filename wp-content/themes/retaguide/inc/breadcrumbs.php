<?php
/**
 * Breadcrumbs
 *
 * @package RetaGuide
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate breadcrumbs
 */
function retaguide_breadcrumbs($args = array()) {
    $defaults = array(
        'home_label' => __('Home', 'retaguide'),
        'separator' => '<span class="separator" aria-hidden="true">/</span>',
        'show_current' => true,
        'echo' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    if (is_front_page() || is_home()) {
        return '';
    }
    
    $breadcrumbs = array();
    $schema_items = array();
    $position = 1;
    
    // Home
    $breadcrumbs[] = '<a href="' . esc_url(home_url('/')) . '">' . esc_html($args['home_label']) . '</a>';
    $schema_items[] = array(
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => $args['home_label'],
        'item' => home_url('/'),
    );
    
    // Build breadcrumb trail
    if (is_single()) {
        $post_type = get_post_type();
        
        if ($post_type === 'post') {
            // News archive
            $breadcrumbs[] = '<a href="' . esc_url(home_url('/news/')) . '">' . __('News', 'retaguide') . '</a>';
            $schema_items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('News', 'retaguide'),
                'item' => home_url('/news/'),
            );
            
            // Category
            $categories = get_the_category();
            if ($categories) {
                $category = $categories[0];
                $breadcrumbs[] = '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a>';
                $schema_items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $category->name,
                    'item' => get_category_link($category->term_id),
                );
            }
        } elseif ($post_type === 'guide') {
            // Guides archive
            $breadcrumbs[] = '<a href="' . esc_url(get_post_type_archive_link('guide')) . '">' . __('Guides', 'retaguide') . '</a>';
            $schema_items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('Guides', 'retaguide'),
                'item' => get_post_type_archive_link('guide'),
            );
            
            // Guide Level
            $levels = get_the_terms(get_the_ID(), 'guide_level');
            if ($levels && !is_wp_error($levels)) {
                $level = $levels[0];
                $breadcrumbs[] = '<a href="' . esc_url(get_term_link($level)) . '">' . esc_html($level->name) . '</a>';
                $schema_items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $level->name,
                    'item' => get_term_link($level),
                );
            }
        }
        
        // Current post
        if ($args['show_current']) {
            $breadcrumbs[] = '<span class="current" aria-current="page">' . esc_html(get_the_title()) . '</span>';
            $schema_items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title(),
                'item' => get_permalink(),
            );
        }
    } elseif (is_page()) {
        // Page hierarchy
        $post = get_post();
        $parents = array();
        
        if ($post->post_parent) {
            $parent_id = $post->post_parent;
            
            while ($parent_id) {
                $page = get_post($parent_id);
                $parents[] = $page;
                $parent_id = $page->post_parent;
            }
            
            $parents = array_reverse($parents);
            
            foreach ($parents as $parent) {
                $breadcrumbs[] = '<a href="' . esc_url(get_permalink($parent->ID)) . '">' . esc_html(get_the_title($parent->ID)) . '</a>';
                $schema_items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => get_the_title($parent->ID),
                    'item' => get_permalink($parent->ID),
                );
            }
        }
        
        if ($args['show_current']) {
            $breadcrumbs[] = '<span class="current" aria-current="page">' . esc_html(get_the_title()) . '</span>';
            $schema_items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title(),
                'item' => get_permalink(),
            );
        }
    } elseif (is_category()) {
        $breadcrumbs[] = '<a href="' . esc_url(home_url('/news/')) . '">' . __('News', 'retaguide') . '</a>';
        $schema_items[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('News', 'retaguide'),
            'item' => home_url('/news/'),
        );
        
        if ($args['show_current']) {
            $breadcrumbs[] = '<span class="current" aria-current="page">' . esc_html(single_cat_title('', false)) . '</span>';
            $schema_items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => single_cat_title('', false),
                'item' => get_category_link(get_queried_object_id()),
            );
        }
    } elseif (is_tag()) {
        $breadcrumbs[] = '<a href="' . esc_url(home_url('/news/')) . '">' . __('News', 'retaguide') . '</a>';
        $schema_items[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('News', 'retaguide'),
            'item' => home_url('/news/'),
        );
        
        if ($args['show_current']) {
            $breadcrumbs[] = '<span class="current" aria-current="page">' . esc_html(single_tag_title('', false)) . '</span>';
            $schema_items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => single_tag_title('', false),
                'item' => get_tag_link(get_queried_object_id()),
            );
        }
    } elseif (is_tax('guide_level') || is_tax('guide_topic')) {
        $breadcrumbs[] = '<a href="' . esc_url(get_post_type_archive_link('guide')) . '">' . __('Guides', 'retaguide') . '</a>';
        $schema_items[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('Guides', 'retaguide'),
            'item' => get_post_type_archive_link('guide'),
        );
        
        if ($args['show_current']) {
            $term = get_queried_object();
            $breadcrumbs[] = '<span class="current" aria-current="page">' . esc_html($term->name) . '</span>';
            $schema_items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $term->name,
                'item' => get_term_link($term),
            );
        }
    } elseif (is_post_type_archive('guide')) {
        if ($args['show_current']) {
            $breadcrumbs[] = '<span class="current" aria-current="page">' . __('Guides', 'retaguide') . '</span>';
            $schema_items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('Guides', 'retaguide'),
                'item' => get_post_type_archive_link('guide'),
            );
        }
    } elseif (is_search()) {
        if ($args['show_current']) {
            $breadcrumbs[] = '<span class="current" aria-current="page">' . sprintf(__('Search Results for: %s', 'retaguide'), esc_html(get_search_query())) . '</span>';
        }
    } elseif (is_404()) {
        if ($args['show_current']) {
            $breadcrumbs[] = '<span class="current" aria-current="page">' . __('404 Not Found', 'retaguide') . '</span>';
        }
    }
    
    // Build output
    $output = '<nav class="breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'retaguide') . '">';
    $output .= implode(' ' . $args['separator'] . ' ', $breadcrumbs);
    $output .= '</nav>';
    
    // Add schema
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $schema_items,
    );
    
    $output .= '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    
    if ($args['echo']) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Breadcrumb shortcode
 */
function retaguide_breadcrumb_shortcode($atts) {
    $atts = shortcode_atts(array(
        'separator' => '/',
        'show_current' => true,
    ), $atts, 'breadcrumb');
    
    return retaguide_breadcrumbs(array(
        'separator' => $atts['separator'],
        'show_current' => $atts['show_current'],
        'echo' => false,
    ));
}
add_shortcode('breadcrumb', 'retaguide_breadcrumb_shortcode');

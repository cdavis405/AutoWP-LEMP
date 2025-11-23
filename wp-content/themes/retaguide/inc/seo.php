<?php
/**
 * SEO and Meta Tags
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta tags to head
 */
function autowp_add_meta_tags() {
    if (is_singular()) {
        autowp_singular_meta_tags();
    } elseif (is_home() || is_front_page()) {
        autowp_homepage_meta_tags();
    } elseif (is_archive()) {
        autowp_archive_meta_tags();
    }
}
add_action('wp_head', 'autowp_add_meta_tags');

/**
 * Singular post meta tags
 */
function autowp_singular_meta_tags() {
    global $post;
    
    $title = get_the_title();
    $description = get_the_excerpt() ?: wp_trim_words(strip_tags($post->post_content), 30);
    $url = get_permalink();
    $image = get_the_post_thumbnail_url($post->ID, 'large') ?: AUTOWP_THEME_URI . '/assets/images/default-og.jpg';
    $type = is_singular('guide') ? 'article' : 'article';
    
    // Canonical URL
    echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
    
    // Open Graph
    echo '<meta property="og:type" content="' . esc_attr($type) . '" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
    
    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
    
    // Article specific
    if (is_singular('post')) {
        echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '" />' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '" />' . "\n";
        
        $categories = get_the_category();
        if ($categories) {
            foreach ($categories as $category) {
                echo '<meta property="article:section" content="' . esc_attr($category->name) . '" />' . "\n";
            }
        }
        
        $tags = get_the_tags();
        if ($tags) {
            foreach ($tags as $tag) {
                echo '<meta property="article:tag" content="' . esc_attr($tag->name) . '" />' . "\n";
            }
        }
    }
}

/**
 * Homepage meta tags
 */
function autowp_homepage_meta_tags() {
    $title = get_bloginfo('name');
    $description = get_bloginfo('description');
    $url = home_url('/');
    $image = AUTOWP_THEME_URI . '/assets/images/default-og.jpg';
    
    echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
    
    echo '<meta property="og:type" content="website" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
    
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
}

/**
 * Archive meta tags
 */
function autowp_archive_meta_tags() {
    $title = get_the_archive_title();
    $description = get_the_archive_description() ?: 'Browse ' . $title;
    $url = get_term_link(get_queried_object());
    
    if (is_wp_error($url)) {
        $url = home_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
    }
    
    echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
    
    echo '<meta property="og:type" content="website" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags($description)) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
}

/**
 * Add JSON-LD structured data
 */
function autowp_add_structured_data() {
    if (is_singular('post')) {
        autowp_article_schema();
    } elseif (is_singular('guide')) {
        autowp_guide_schema();
    } elseif (is_home() || is_front_page()) {
        autowp_organization_schema();
    }
}
add_action('wp_footer', 'autowp_add_structured_data');

/**
 * Article schema
 */
function autowp_article_schema() {
    global $post;
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title(),
        'description' => get_the_excerpt(),
        'image' => get_the_post_thumbnail_url($post->ID, 'large'),
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'author' => array(
            '@type' => 'Person',
            'name' => get_the_author(),
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => AUTOWP_THEME_URI . '/assets/images/logo.png',
            ),
        ),
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id' => get_permalink(),
        ),
    );
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}

/**
 * Guide schema (HowTo)
 */
function autowp_guide_schema() {
    global $post;
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => get_the_title(),
        'description' => get_the_excerpt(),
        'image' => get_the_post_thumbnail_url($post->ID, 'large'),
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
    );
    
    // Add estimated time if available
    $reading_time = get_post_meta($post->ID, '_autowp_reading_time', true);
    if ($reading_time) {
        $schema['totalTime'] = 'PT' . $reading_time . 'M';
    }
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}

/**
 * Organization schema
 */
function autowp_organization_schema() {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => get_bloginfo('name'),
        'description' => get_bloginfo('description'),
        'url' => home_url('/'),
        'logo' => AUTOWP_THEME_URI . '/assets/images/logo.png',
    );
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}

/**
 * Enable XML sitemap
 */
add_filter('wp_sitemaps_enabled', '__return_true');

/**
 * Add custom post types to sitemap
 */
function autowp_add_guides_to_sitemap($post_types) {
    $post_types['guide'] = 'guide';
    return $post_types;
}
add_filter('wp_sitemaps_post_types', 'autowp_add_guides_to_sitemap');

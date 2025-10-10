<?php
/**
 * Retaguide theme bootstrap.
 */

define( 'RETAGUIDE_VERSION', '1.0.0' );

define( 'RETAGUIDE_PATH', get_template_directory() );
define( 'RETAGUIDE_URI', get_template_directory_uri() );

define( 'RETAGUIDE_OPTION_NAV_PINNED', 'retaguide_pinned_nav_items' );
define( 'RETAGUIDE_OPTION_GLOBAL_DISCLAIMER', 'retaguide_global_disclaimer' );
require_once RETAGUIDE_PATH . '/inc/setup.php';
require_once RETAGUIDE_PATH . '/inc/navigation.php';
require_once RETAGUIDE_PATH . '/inc/cpt-guides.php';
require_once RETAGUIDE_PATH . '/inc/taxonomies.php';
require_once RETAGUIDE_PATH . '/inc/disclaimer.php';
require_once RETAGUIDE_PATH . '/inc/breadcrumbs.php';
require_once RETAGUIDE_PATH . '/inc/patterns.php';
require_once RETAGUIDE_PATH . '/inc/seo.php';

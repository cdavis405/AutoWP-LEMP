<?php
/**
 * Theme setup and helpers.
 */

declare(strict_types=1);

add_action(
	'after_setup_theme',
	function (): void {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/editor.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function (): void {
		wp_enqueue_style( 'retaguide-style', RETAGUIDE_URI . '/assets/css/front.css', array(), RETAGUIDE_VERSION );
		wp_enqueue_script( 'retaguide-nav', RETAGUIDE_URI . '/assets/js/navigation.js', array(), RETAGUIDE_VERSION, true );
	}
);

add_action(
	'enqueue_block_editor_assets',
	function (): void {
		wp_enqueue_script( 'retaguide-editor', RETAGUIDE_URI . '/assets/js/editor.js', array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ), RETAGUIDE_VERSION, true );
	}
);

add_action(
	'after_switch_theme',
	function (): void {
		// Ensure seeded taxonomies and reusable blocks exist once theme activates.
		require_once RETAGUIDE_PATH . '/inc/taxonomies.php';
		require_once RETAGUIDE_PATH . '/inc/disclaimer.php';
		retaguide_seed_taxonomies();
		retaguide_ensure_disclaimer_block();
		update_option( 'category_base', 'news/category' );
		update_option( 'tag_base', 'news/tag' );
		flush_rewrite_rules();
	}
);

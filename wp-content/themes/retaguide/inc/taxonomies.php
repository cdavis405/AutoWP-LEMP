<?php
/**
 * Taxonomies for News and Guides.
 */

declare(strict_types=1);

use WP_Post;

add_action(
	'init',
	function (): void {
		register_taxonomy(
			'guide-level',
			array( 'guide' ),
			array(
				'labels'            => array(
					'name'          => __( 'Guide Levels', 'retaguide' ),
					'singular_name' => __( 'Guide Level', 'retaguide' ),
				),
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'guides/level',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'guide-topic',
			array( 'guide' ),
			array(
				'labels'            => array(
					'name'          => __( 'Guide Topics', 'retaguide' ),
					'singular_name' => __( 'Guide Topic', 'retaguide' ),
				),
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'guides/topic',
					'with_front' => false,
				),
			)
		);
	}
);

/**
 * Seed the initial taxonomy terms.
 */
function retaguide_seed_taxonomies(): void {
	$categories = array( 'Research', 'Safety', 'Regulatory', 'Market', 'Reviews' );
	foreach ( $categories as $category ) {
		if ( ! term_exists( $category, 'category' ) ) {
			wp_insert_term( $category, 'category' );
		}
	}

	$levels = array( 'Beginner', 'Protocol', 'Safety' );
	foreach ( $levels as $level ) {
		if ( ! term_exists( $level, 'guide-level' ) ) {
			wp_insert_term( $level, 'guide-level' );
		}
	}

	$topics = array( 'Mechanism', 'Dosing', 'Monitoring' );
	foreach ( $topics as $topic ) {
		if ( ! term_exists( $topic, 'guide-topic' ) ) {
			wp_insert_term( $topic, 'guide-topic' );
		}
	}
}

/**
 * Prefix news categories and tags with /news.
 */
add_filter(
	'category_rewrite_rules',
	function ( array $rules ): array {
		$new_rules = array();
		foreach ( $rules as $regex => $query ) {
			$new_rules[ 'news/' . $regex ] = $query;
		}

		return $new_rules;
	}
);

add_filter(
	'tag_rewrite_rules',
	function ( array $rules ): array {
		$new_rules = array();
		foreach ( $rules as $regex => $query ) {
			$new_rules[ 'news/' . $regex ] = $query;
		}

		return $new_rules;
	}
);

add_action(
	'init',
	function (): void {
		add_rewrite_rule( '^news/([^/]+)/?$', 'index.php?name=$matches[1]', 'top' );
	}
);

add_filter(
	'post_type_link',
	function ( string $permalink, WP_Post $post ): string {
		if ( 'post' !== $post->post_type ) {
			return $permalink;
		}

		return home_url( '/news/' . $post->post_name . '/' );
	},
	10,
	2
);

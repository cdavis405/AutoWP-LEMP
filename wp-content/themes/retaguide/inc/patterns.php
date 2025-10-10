<?php
/**
 * Block pattern registration.
 */

declare(strict_types=1);

add_action(
	'init',
	function (): void {
		register_block_pattern_category( 'retaguide-news', array( 'label' => __( 'News', 'retaguide' ) ) );
		register_block_pattern_category( 'retaguide-guides', array( 'label' => __( 'Guides', 'retaguide' ) ) );
		register_block_pattern_category( 'retaguide-callouts', array( 'label' => __( 'Callouts', 'retaguide' ) ) );
	}
);

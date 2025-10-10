<?php
/**
 * Navigation helpers.
 */

declare(strict_types=1);

add_action(
	'init',
	function (): void {
		register_nav_menus(
			array(
				'primary' => __( 'Primary Navigation', 'retaguide' ),
			)
		);
	}
);

/**
 * Retrieve pinned nav items configured in settings.
 *
 * @return array<int,array<string,mixed>>
 */
function retaguide_get_pinned_nav_items(): array {
	$ids = get_option( RETAGUIDE_OPTION_NAV_PINNED, array() );
	if ( ! is_array( $ids ) ) {
		$ids = array_filter( array_map( 'absint', explode( ',', (string) $ids ) ) );
	}

	if ( empty( $ids ) ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'   => array( 'post', 'page', 'guide' ),
			'post__in'    => $ids,
			'orderby'     => 'post__in',
			'numberposts' => -1,
		)
	);

	$items = array();

	foreach ( $posts as $post ) {
		$items[] = array(
			'id'    => $post->ID,
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
		);
	}

	return $items;
}

add_shortcode(
	'retaguide_pinned_nav',
	function (): string {
		$items = retaguide_get_pinned_nav_items();
		if ( empty( $items ) ) {
			return '';
		}

		$markup = '<div class="retaguide-pinned-nav" aria-label="Pinned navigation"><ul class="retaguide-pinned-nav__list">';
		foreach ( $items as $item ) {
			$markup .= sprintf(
				'<li class="retaguide-pinned-nav__item"><a href="%1$s">%2$s</a></li>',
				esc_url( $item['url'] ),
				esc_html( $item['title'] )
			);
		}
		$markup .= '</ul></div>';

		return $markup;
	}
);

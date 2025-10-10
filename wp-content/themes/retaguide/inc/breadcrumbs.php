<?php
/**
 * Breadcrumb utilities and shortcode.
 */

declare(strict_types=1);

/**
 * Build breadcrumbs for current context.
 *
 * @return array<int,array<string,string>>
 */
function retaguide_build_breadcrumbs(): array {
	$items   = array();
	$items[] = array(
		'url'   => home_url( '/' ),
		'label' => __( 'Home', 'retaguide' ),
	);

	if ( is_singular( 'guide' ) || is_post_type_archive( 'guide' ) || is_tax( array( 'guide-level', 'guide-topic' ) ) ) {
		$items[] = array(
			'url'   => home_url( '/guides/' ),
			'label' => __( 'Guides', 'retaguide' ),
		);
	} elseif ( is_home() || is_singular( 'post' ) || is_category() || is_tag() ) {
		$items[] = array(
			'url'   => home_url( '/news/' ),
			'label' => __( 'News', 'retaguide' ),
		);
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( (int) get_queried_object_id() );
		if ( ! is_wp_error( $link ) ) {
			$items[] = array(
				'url'   => $link,
				'label' => single_term_title( '', false ),
			);
		}
	} elseif ( is_singular() && ! is_front_page() ) {
		$items[] = array(
			'url'   => get_permalink(),
			'label' => get_the_title(),
		);
	}

	return $items;
}

add_shortcode(
	'retaguide_breadcrumbs',
	function (): string {
		if ( is_front_page() ) {
			return '';
		}

		$crumbs = retaguide_build_breadcrumbs();
		if ( count( $crumbs ) <= 1 ) {
			return '';
		}

		$parts      = array();
		$last_index = array_key_last( $crumbs );
		foreach ( $crumbs as $index => $item ) {
			$label = esc_html( $item['label'] );
			if ( ! empty( $item['url'] ) && $index !== $last_index ) {
				$parts[] = sprintf( '<a href="%s">%s</a>', esc_url( $item['url'] ), $label );
			} else {
				$parts[] = $label;
			}
		}

		return '<nav class="retaguide-breadcrumb" aria-label="Breadcrumb">' . implode( ' <span aria-hidden="true">/</span> ', $parts ) . '</nav>';
	}
);

add_action(
	'wp_head',
	function (): void {
		if ( is_front_page() ) {
			return;
		}

		$crumbs = retaguide_build_breadcrumbs();
		if ( count( $crumbs ) <= 1 ) {
			return;
		}

		$graph = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(),
		);

		foreach ( $crumbs as $index => $item ) {
			$graph['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $item['label'],
				'item'     => $item['url'],
			);
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	}
);

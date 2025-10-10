<?php
/**
 * SEO and structured data helpers.
 */

declare(strict_types=1);

add_action(
	'wp_head',
	function (): void {
		if ( is_singular() ) {
			global $post;
			$url     = get_permalink( $post );
			$title   = get_the_title( $post );
			$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
			$image   = get_the_post_thumbnail_url( $post, 'large' );

			printf( '<link rel="canonical" href="%s" />', esc_url( $url ) );

			printf( '<meta property="og:title" content="%s" />', esc_attr( $title ) );
			printf( '<meta property="og:type" content="article" />' );
			printf( '<meta property="og:url" content="%s" />', esc_url( $url ) );
			if ( $image ) {
				printf( '<meta property="og:image" content="%s" />', esc_url( $image ) );
			}
			printf( '<meta property="og:description" content="%s" />', esc_attr( $excerpt ) );

			printf( '<meta name="twitter:card" content="summary_large_image" />' );
			printf( '<meta name="twitter:title" content="%s" />', esc_attr( $title ) );
			printf( '<meta name="twitter:description" content="%s" />', esc_attr( $excerpt ) );
			if ( $image ) {
				printf( '<meta name="twitter:image" content="%s" />', esc_url( $image ) );
			}

			$schema = array(
				'@context'      => 'https://schema.org',
				'@type'         => is_singular( 'guide' ) ? 'HowTo' : 'Article',
				'headline'      => $title,
				'description'   => $excerpt,
				'datePublished' => get_the_date( DATE_W3C, $post ),
				'dateModified'  => get_the_modified_date( DATE_W3C, $post ),
				'author'        => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
				),
				'url'           => $url,
			);

			if ( $image ) {
				$schema['image'] = $image;
			}

			printf( '<script type="application/ld+json">%s</script>', wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		} elseif ( is_home() ) {
			printf( '<link rel="canonical" href="%s" />', esc_url( home_url( '/news/' ) ) );
		}
	},
	5
);

add_filter(
	'robots_txt',
	function ( string $output, bool $public ): string {
		if ( ! $public ) {
			return "User-agent: *\nDisallow: /";
		}

		$extra = 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";

		return $output . "\n" . $extra;
	},
	10,
	2
);

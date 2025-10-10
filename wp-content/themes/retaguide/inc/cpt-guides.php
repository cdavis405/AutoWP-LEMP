<?php
/**
 * Guides custom post type.
 */

declare(strict_types=1);

use WP_Post;

add_action(
	'init',
	function (): void {
		register_post_type(
			'guide',
			array(
				'labels'        => array(
					'name'               => __( 'Guides', 'retaguide' ),
					'singular_name'      => __( 'Guide', 'retaguide' ),
					'add_new'            => __( 'Add Guide', 'retaguide' ),
					'add_new_item'       => __( 'Add New Guide', 'retaguide' ),
					'edit_item'          => __( 'Edit Guide', 'retaguide' ),
					'new_item'           => __( 'New Guide', 'retaguide' ),
					'view_item'          => __( 'View Guide', 'retaguide' ),
					'view_items'         => __( 'View Guides', 'retaguide' ),
					'all_items'          => __( 'All Guides', 'retaguide' ),
					'search_items'       => __( 'Search Guides', 'retaguide' ),
					'not_found'          => __( 'No guides found.', 'retaguide' ),
					'not_found_in_trash' => __( 'No guides found in Trash.', 'retaguide' ),
				),
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-clipboard',
				'menu_position' => 21,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
				'rewrite'       => array(
					'slug'       => 'guides',
					'with_front' => false,
				),
			)
		);
	}
);

register_post_meta(
	'guide',
	'retaguide_last_reviewed',
	array(
		'single'       => true,
		'show_in_rest' => true,
		'type'         => 'string',
	)
);

register_post_meta(
	'guide',
	'retaguide_version',
	array(
		'single'       => true,
		'show_in_rest' => true,
		'type'         => 'string',
	)
);

add_action(
	'add_meta_boxes_guide',
	function (): void {
		add_meta_box(
			'retaguide-guide-meta',
			__( 'Guide Details', 'retaguide' ),
			'retaguide_render_guide_meta_box',
			'guide',
			'side'
		);
	}
);

function retaguide_render_guide_meta_box( WP_Post $post ): void {
	$last_reviewed = get_post_meta( $post->ID, 'retaguide_last_reviewed', true );
	$version       = get_post_meta( $post->ID, 'retaguide_version', true );
	wp_nonce_field( 'retaguide_guide_meta', 'retaguide_guide_meta_nonce' );
	?>
	<p><label for="retaguide_last_reviewed"><strong><?php esc_html_e( 'Last reviewed', 'retaguide' ); ?></strong></label><br />
	<input type="date" id="retaguide_last_reviewed" name="retaguide_last_reviewed" value="<?php echo esc_attr( $last_reviewed ); ?>" /></p>
	<p><label for="retaguide_version"><strong><?php esc_html_e( 'Version', 'retaguide' ); ?></strong></label><br />
	<input type="text" id="retaguide_version" name="retaguide_version" value="<?php echo esc_attr( $version ); ?>" /></p>
	<?php
}

add_action(
	'save_post_guide',
	function ( int $post_id ): void {
		if ( ! isset( $_POST['retaguide_guide_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['retaguide_guide_meta_nonce'] ) ), 'retaguide_guide_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['retaguide_last_reviewed'] ) ) {
			$last_reviewed = sanitize_text_field( wp_unslash( $_POST['retaguide_last_reviewed'] ) );
			if ( $last_reviewed ) {
				update_post_meta( $post_id, 'retaguide_last_reviewed', $last_reviewed );
			} else {
				delete_post_meta( $post_id, 'retaguide_last_reviewed' );
			}
		}

		if ( isset( $_POST['retaguide_version'] ) ) {
			$version = sanitize_text_field( wp_unslash( $_POST['retaguide_version'] ) );
			if ( $version ) {
				update_post_meta( $post_id, 'retaguide_version', $version );
			} else {
				delete_post_meta( $post_id, 'retaguide_version' );
			}
		}
	}
);

add_shortcode(
	'retaguide_last_reviewed',
	function (): string {
		$post = get_post();
		if ( ! $post || 'guide' !== $post->post_type ) {
			return '';
		}

		$last_reviewed = get_post_meta( $post->ID, 'retaguide_last_reviewed', true );
		$version       = get_post_meta( $post->ID, 'retaguide_version', true );

		if ( ! $last_reviewed && ! $version ) {
			return '';
		}

		$parts = array();
		if ( $last_reviewed ) {
			$parts[] = sprintf( __( 'Last reviewed %s', 'retaguide' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $last_reviewed ) ) ) );
		}
		if ( $version ) {
			$parts[] = sprintf( __( 'Version %s', 'retaguide' ), esc_html( $version ) );
		}

		return '<div class="retaguide-last-reviewed" role="status">' . implode( ' · ', $parts ) . '</div>';
	}
);


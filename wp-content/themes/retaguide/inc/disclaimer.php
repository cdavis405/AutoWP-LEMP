<?php
/**
 * Disclaimer handling.
 */

declare(strict_types=1);

use WP_Post;

const RETAGUIDE_META_DISCLAIMER_OVERRIDE = 'retaguide_override_disclaimer';
const RETAGUIDE_META_DISCLAIMER_HIDE     = 'retaguide_hide_global_disclaimer';

register_post_meta(
	'post',
	RETAGUIDE_META_DISCLAIMER_OVERRIDE,
	array(
		'show_in_rest' => true,
		'single'       => true,
		'type'         => 'string',
	)
);

register_post_meta(
	'post',
	RETAGUIDE_META_DISCLAIMER_HIDE,
	array(
		'show_in_rest' => true,
		'single'       => true,
		'type'         => 'boolean',
		'default'      => false,
	)
);

register_post_meta(
	'guide',
	RETAGUIDE_META_DISCLAIMER_OVERRIDE,
	array(
		'show_in_rest' => true,
		'single'       => true,
		'type'         => 'string',
	)
);

register_post_meta(
	'guide',
	RETAGUIDE_META_DISCLAIMER_HIDE,
	array(
		'show_in_rest' => true,
		'single'       => true,
		'type'         => 'boolean',
		'default'      => false,
	)
);

add_action(
	'add_meta_boxes',
	function (): void {
		$post_types = array( 'post', 'guide' );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'retaguide-legal',
				__( 'Disclaimer Settings', 'retaguide' ),
				'retaguide_render_disclaimer_meta_box',
				$post_type,
				'side'
			);
		}
	}
);

function retaguide_render_disclaimer_meta_box( WP_Post $post ): void {
	$override = get_post_meta( $post->ID, RETAGUIDE_META_DISCLAIMER_OVERRIDE, true );
	$hide     = (bool) get_post_meta( $post->ID, RETAGUIDE_META_DISCLAIMER_HIDE, true );
	wp_nonce_field( 'retaguide_disclaimer_meta', 'retaguide_disclaimer_meta_nonce' );
	?>
	<p><label for="retaguide_disclaimer_override"><strong><?php esc_html_e( 'Override Disclaimer', 'retaguide' ); ?></strong></label></p>
	<textarea id="retaguide_disclaimer_override" name="retaguide_disclaimer_override" rows="4" style="width:100%;"><?php echo esc_textarea( (string) $override ); ?></textarea>
	<p class="description"><?php esc_html_e( 'Leave empty to use the global disclaimer.', 'retaguide' ); ?></p>
	<p><label><input type="checkbox" name="retaguide_disclaimer_hide" value="1" <?php checked( $hide ); ?> /> <?php esc_html_e( 'Hide global disclaimer', 'retaguide' ); ?></label></p>
	<?php
}

add_action(
	'save_post',
	function ( int $post_id ): void {
		if ( ! isset( $_POST['retaguide_disclaimer_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['retaguide_disclaimer_meta_nonce'] ) ), 'retaguide_disclaimer_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['retaguide_disclaimer_override'] ) ) {
			$override = wp_kses_post( wp_unslash( $_POST['retaguide_disclaimer_override'] ) );
			if ( '' === trim( $override ) ) {
				delete_post_meta( $post_id, RETAGUIDE_META_DISCLAIMER_OVERRIDE );
			} else {
				update_post_meta( $post_id, RETAGUIDE_META_DISCLAIMER_OVERRIDE, $override );
			}
		}

		$hide = isset( $_POST['retaguide_disclaimer_hide'] );
		update_post_meta( $post_id, RETAGUIDE_META_DISCLAIMER_HIDE, $hide ? '1' : '0' );
	}
);

/**
 * Compute the effective disclaimer for a given post.
 */
function retaguide_get_disclaimer_content( ?int $post_id = null ): string {
	$post_id = $post_id ?: get_the_ID();
	if ( ! $post_id ) {
		return (string) get_option( RETAGUIDE_OPTION_GLOBAL_DISCLAIMER, '' );
	}

	$hide = (bool) get_post_meta( $post_id, RETAGUIDE_META_DISCLAIMER_HIDE, true );
	if ( $hide ) {
		return '';
	}

	$override = get_post_meta( $post_id, RETAGUIDE_META_DISCLAIMER_OVERRIDE, true );
	if ( $override ) {
		return (string) $override;
	}

	return (string) get_option( RETAGUIDE_OPTION_GLOBAL_DISCLAIMER, '' );
}

add_filter(
	'the_content',
	function ( string $content ): string {
		if ( ! is_singular( array( 'post', 'guide' ) ) ) {
			return $content;
		}

		$disclaimer = retaguide_get_disclaimer_content();
		if ( '' === trim( wp_strip_all_tags( $disclaimer ) ) ) {
			return $content;
		}

		if ( has_shortcode( $content, 'disclaimer' ) || str_contains( $content, 'retaguide-disclaimer' ) ) {
			return $content;
		}

		$markup = sprintf( '<div class="retaguide-disclaimer" role="note">%s</div>', wp_kses_post( $disclaimer ) );

		return $markup . $content;
	},
	5
);

add_shortcode(
	'disclaimer',
	function (): string {
		$disclaimer = retaguide_get_disclaimer_content();
		if ( '' === trim( wp_strip_all_tags( $disclaimer ) ) ) {
			return '';
		}

		return sprintf( '<div class="retaguide-disclaimer" role="note">%s</div>', wp_kses_post( $disclaimer ) );
	}
);

/**
 * Ensure the reusable disclaimer block exists for editors.
 */
function retaguide_ensure_disclaimer_block(): void {
	$global = get_option( RETAGUIDE_OPTION_GLOBAL_DISCLAIMER, '' );
	$title  = __( 'Retaguide Disclaimer', 'retaguide' );

	$existing = get_posts(
		array(
			'post_type'      => 'wp_block',
			'post_status'    => array( 'publish', 'draft' ),
			'title'          => $title,
			'posts_per_page' => 1,
		)
	);

	$content = '<!-- wp:shortcode -->[disclaimer]<!-- /wp:shortcode -->';

	if ( empty( $existing ) ) {
		wp_insert_post(
			array(
				'post_type'    => 'wp_block',
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);
	} else {
		wp_update_post(
			array(
				'ID'           => $existing[0]->ID,
				'post_content' => $content,
			)
		);
	}

	if ( empty( $global ) ) {
		update_option( RETAGUIDE_OPTION_GLOBAL_DISCLAIMER, __( 'This content is provided for informational purposes about Retatrutide research only and is not medical advice.', 'retaguide' ) );
	}
}

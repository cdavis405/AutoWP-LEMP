<?php
/**
 * Plugin Name: Retaguide Settings
 * Description: Theme settings pages for pinned navigation and legal disclaimers.
 * Author: Retaguide Team
 */

declare(strict_types=1);

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'RETAGUIDE_VERSION' ) ) {
	define( 'RETAGUIDE_VERSION', '1.0.0' );
}

if ( ! defined( 'RETAGUIDE_OPTION_NAV_PINNED' ) ) {
	define( 'RETAGUIDE_OPTION_NAV_PINNED', 'retaguide_pinned_nav_items' );
}

if ( ! defined( 'RETAGUIDE_OPTION_GLOBAL_DISCLAIMER' ) ) {
	define( 'RETAGUIDE_OPTION_GLOBAL_DISCLAIMER', 'retaguide_global_disclaimer' );
}

add_action(
	'admin_menu',
	function (): void {
		add_theme_page(
			__( 'Retaguide Settings', 'retaguide' ),
			__( 'Retaguide Settings', 'retaguide' ),
			'manage_options',
			'retaguide-settings',
			'retaguide_render_settings_page'
		);
	}
);

add_action(
	'admin_init',
	function (): void {
		register_setting(
			'retaguide_nav',
			RETAGUIDE_OPTION_NAV_PINNED,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'retaguide_sanitize_pinned_items',
				'default'           => array(),
			)
		);

		register_setting(
			'retaguide_legal',
			RETAGUIDE_OPTION_GLOBAL_DISCLAIMER,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'default'           => __( 'This content is provided for informational purposes about Retatrutide research only and is not medical advice.', 'retaguide' ),
			)
		);
	}
);

/**
 * Sanitize pinned IDs.
 *
 * @param mixed $value Raw value.
 * @return array<int,int>
 */
function retaguide_sanitize_pinned_items( $value ): array {
	if ( is_string( $value ) ) {
		$value = explode( ',', $value );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$ids = array();
	foreach ( $value as $id ) {
		$id = absint( $id );
		if ( $id && get_post( $id ) ) {
			$ids[] = $id;
		}
	}

	return array_values( array_unique( $ids ) );
}

add_action(
	'admin_enqueue_scripts',
	function ( string $hook ): void {
		if ( 'appearance_page_retaguide-settings' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'retaguide-settings', get_template_directory_uri() . '/assets/js/settings.js', array( 'jquery', 'jquery-ui-sortable' ), RETAGUIDE_VERSION, true );
		wp_enqueue_style( 'retaguide-settings', get_template_directory_uri() . '/assets/css/settings.css', array(), RETAGUIDE_VERSION );
		wp_localize_script(
			'retaguide-settings',
			'retaguideSettings',
			array(
				'nonce'  => wp_create_nonce( 'retaguide_lookup_post' ),
				'labels' => array(
					'failed' => __( 'Unable to locate that ID. Confirm the content exists and is published.', 'retaguide' ),
				),
			)
		);
	}
);

add_action(
	'wp_ajax_retaguide_lookup_post',
	function (): void {
		check_ajax_referer( 'retaguide_lookup_post', 'nonce' );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error();
		}

		$post = get_post( $id );
		if ( ! $post ) {
			wp_send_json_error();
		}

		wp_send_json_success(
			array(
				'id'    => $post->ID,
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'type'  => get_post_type( $post ),
			)
		);
	}
);

/**
 * Render settings page.
 */
function retaguide_render_settings_page(): void {
	$active_tab = $_GET['tab'] ?? 'navigation';
	$tabs       = array(
		'navigation' => __( 'Navigation', 'retaguide' ),
		'legal'      => __( 'Legal', 'retaguide' ),
	);
	?>
	<div class="wrap retaguide-settings">
		<h1><?php esc_html_e( 'Retaguide Settings', 'retaguide' ); ?></h1>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $tab_id => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => $tab_id ) ) ); ?>" class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php if ( 'navigation' === $active_tab ) : ?>
			<form method="post" action="options.php" id="retaguide-nav-form">
				<?php
				settings_fields( 'retaguide_nav' );
				$pinned_ids = get_option( RETAGUIDE_OPTION_NAV_PINNED, array() );
				if ( ! is_array( $pinned_ids ) ) {
					$pinned_ids = retaguide_sanitize_pinned_items( $pinned_ids );
				}
				?>
				<p><?php esc_html_e( 'Select posts, pages, or guides to pin on the right side of the primary navigation. Drag to reorder.', 'retaguide' ); ?></p>
				<div class="retaguide-pinned-control">
					<label for="retaguide-pinned-id"><?php esc_html_e( 'Add item by ID', 'retaguide' ); ?></label>
					<input type="number" id="retaguide-pinned-id" min="1" />
					<button type="button" class="button" id="retaguide-add-pinned"><?php esc_html_e( 'Add', 'retaguide' ); ?></button>
				</div>
				<ul id="retaguide-pinned-list">
					<?php
					foreach ( $pinned_ids as $id ) :
						$post = get_post( $id );
						if ( ! $post ) {
							continue;
						}
						?>
						<li data-id="<?php echo esc_attr( $post->ID ); ?>">
							<span class="label"><?php echo esc_html( get_the_title( $post ) ); ?> <span class="type">(<?php echo esc_html( $post->post_type ); ?>)</span></span>
							<button type="button" class="button-link delete" aria-label="<?php esc_attr_e( 'Remove', 'retaguide' ); ?>">&times;</button>
						</li>
					<?php endforeach; ?>
				</ul>
				<input type="hidden" name="<?php echo esc_attr( RETAGUIDE_OPTION_NAV_PINNED ); ?>" id="retaguide-pinned-field" value="<?php echo esc_attr( implode( ',', $pinned_ids ) ); ?>" />
				<?php submit_button(); ?>
			</form>
		<?php else : ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'retaguide_legal' );
				do_settings_sections( 'retaguide_legal' );
				$disclaimer = get_option( RETAGUIDE_OPTION_GLOBAL_DISCLAIMER, '' );
				?>
				<h2><?php esc_html_e( 'Global Disclaimer', 'retaguide' ); ?></h2>
				<p><?php esc_html_e( 'This disclaimer is prepended to all News posts and Guides unless a post-specific override is provided.', 'retaguide' ); ?></p>
				<?php
				wp_editor(
					$disclaimer,
					'retaguide_global_disclaimer',
					array(
						'textarea_name' => RETAGUIDE_OPTION_GLOBAL_DISCLAIMER,
						'textarea_rows' => 8,
					)
				);
				?>
				<p class="description"><?php esc_html_e( 'Per-post override: use the “Override Disclaimer” field in the editor sidebar. Toggle “Hide global disclaimer” to remove it completely for that entry.', 'retaguide' ); ?></p>
				<?php submit_button(); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

<?php
/**
 * Theme Settings Pages
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add theme settings menu
 */
function autowp_add_theme_settings_menu() {
    // Main settings page
    add_theme_page(
        __('AutoWP Settings', 'autowp'),
        __('Theme Settings', 'autowp'),
        'edit_theme_options',
        'autowp-settings',
        'autowp_settings_page'
    );
    
    // Pinned Navigation submenu
    add_theme_page(
        __('Pinned Navigation', 'autowp'),
        __('Pinned Navigation', 'autowp'),
        'edit_theme_options',
        'autowp-pinned-nav',
        'autowp_pinned_nav_page'
    );
    
    // Legal/Disclaimer submenu
    add_theme_page(
        __('Legal & Disclaimer', 'autowp'),
        __('Legal & Disclaimer', 'autowp'),
        'edit_theme_options',
        'autowp-legal',
        'autowp_legal_page'
    );
}
add_action('admin_menu', 'autowp_add_theme_settings_menu');

/**
 * Main settings page
 */
function autowp_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="card">
            <h2><?php _e('AutoWP Theme Settings', 'autowp'); ?></h2>
            <p><?php _e('Configure your AutoWP theme settings:', 'autowp'); ?></p>
            
            <ul>
                <li>
                    <a href="<?php echo admin_url('themes.php?page=autowp-pinned-nav'); ?>">
                        <strong><?php _e('Pinned Navigation', 'autowp'); ?></strong>
                    </a> - 
                    <?php _e('Manage pinned items in the navigation bar', 'autowp'); ?>
                </li>
                <li>
                    <a href="<?php echo admin_url('themes.php?page=autowp-legal'); ?>">
                        <strong><?php _e('Legal & Disclaimer', 'autowp'); ?></strong>
                    </a> - 
                    <?php _e('Configure global disclaimer and legal notices', 'autowp'); ?>
                </li>
                <li>
                    <a href="<?php echo admin_url('customize.php'); ?>">
                        <strong><?php _e('Customize Appearance', 'autowp'); ?></strong>
                    </a> - 
                    <?php _e('Customize colors, typography, and layout', 'autowp'); ?>
                </li>
            </ul>
        </div>
        
        <div class="card">
            <h2><?php _e('Quick Links', 'autowp'); ?></h2>
            <ul>
                <li><a href="<?php echo admin_url('edit.php'); ?>"><?php _e('Manage News Posts', 'autowp'); ?></a></li>
                <li><a href="<?php echo admin_url('edit.php?post_type=guide'); ?>"><?php _e('Manage Guides', 'autowp'); ?></a></li>
                <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>"><?php _e('News Categories', 'autowp'); ?></a></li>
                <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=guide_level&post_type=guide'); ?>"><?php _e('Guide Levels', 'autowp'); ?></a></li>
                <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=guide_topic&post_type=guide'); ?>"><?php _e('Guide Topics', 'autowp'); ?></a></li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Pinned Navigation settings page
 */
function autowp_pinned_nav_page() {
    $pinned_items = autowp_get_pinned_nav_items();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="card">
            <h2><?php _e('Manage Pinned Navigation Items', 'autowp'); ?></h2>
            <p><?php _e('Add and arrange items to appear on the right side of your main navigation. Drag to reorder.', 'autowp'); ?></p>
            
            <div class="pinned-nav-search">
                <input type="text" id="pinned-nav-search-input" placeholder="<?php esc_attr_e('Search for posts, pages, or guides...', 'autowp'); ?>" />
                <button type="button" id="pinned-nav-search-btn" class="button"><?php _e('Search', 'autowp'); ?></button>
                <div id="pinned-nav-search-results"></div>
            </div>
            
            <div class="pinned-nav-items-list">
                <h3><?php _e('Current Pinned Items', 'autowp'); ?></h3>
                <ul id="pinned-items-sortable">
                    <?php if (empty($pinned_items)) : ?>
                        <li class="no-items"><?php _e('No pinned items yet. Search and add items above.', 'autowp'); ?></li>
                    <?php else : ?>
                        <?php foreach ($pinned_items as $item) : ?>
                            <li data-id="<?php echo esc_attr($item['id']); ?>">
                                <span class="dashicons dashicons-menu handle"></span>
                                <span class="item-title"><?php echo esc_html(get_the_title($item['id'])); ?></span>
                                <input type="text" class="custom-title" 
                                       placeholder="<?php esc_attr_e('Custom title (optional)', 'autowp'); ?>"
                                       value="<?php echo esc_attr($item['custom_title'] ?? ''); ?>" />
                                <button type="button" class="button remove-item"><?php _e('Remove', 'autowp'); ?></button>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                
                <button type="button" id="save-pinned-items" class="button button-primary">
                    <?php _e('Save Changes', 'autowp'); ?>
                </button>
                <span class="spinner"></span>
                <span class="save-message"></span>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
        var autowpAdmin = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            searchNonce: '<?php echo wp_create_nonce('autowp_pinned_nav_search'); ?>',
            saveNonce: '<?php echo wp_create_nonce('autowp_save_pinned_items'); ?>'
        };
    </script>
    <?php
}

/**
 * Legal & Disclaimer settings page
 */
function autowp_legal_page() {
    // Handle form submission
    if (isset($_POST['autowp_save_legal_settings']) && 
        check_admin_referer('autowp_legal_settings', 'autowp_legal_nonce')) {
        
        $enable_disclaimer = isset($_POST['autowp_enable_global_disclaimer']) ? 1 : 0;
        $disclaimer_text = isset($_POST['autowp_global_disclaimer']) 
            ? wp_kses_post($_POST['autowp_global_disclaimer']) 
            : '';
        
        update_option('autowp_enable_global_disclaimer', $enable_disclaimer);
        update_option('autowp_global_disclaimer', $disclaimer_text);
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'autowp') . '</p></div>';
    }
    
    $enable_disclaimer = get_option('autowp_enable_global_disclaimer', true);
    $disclaimer_text = get_option('autowp_global_disclaimer', '');
    
    if (empty($disclaimer_text)) {
        $disclaimer_text = '<p><strong>Disclaimer:</strong> This content is for informational and educational purposes only. This is a generic disclaimer placeholder.</p>';
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('autowp_legal_settings', 'autowp_legal_nonce'); ?>
            
            <div class="card">
                <h2><?php _e('Global Disclaimer', 'autowp'); ?></h2>
                <p><?php _e('This disclaimer will be automatically shown at the top of all posts and guides unless disabled per-post.', 'autowp'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php _e('Enable Global Disclaimer', 'autowp'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="autowp_enable_global_disclaimer" value="1" 
                                       <?php checked($enable_disclaimer, 1); ?> />
                                <?php _e('Automatically show disclaimer on all posts and guides', 'autowp'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="autowp_global_disclaimer">
                                <?php _e('Disclaimer Text', 'autowp'); ?>
                            </label>
                        </th>
                        <td>
                            <?php
                            wp_editor($disclaimer_text, 'autowp_global_disclaimer', array(
                                'textarea_rows' => 10,
                                'media_buttons' => true,
                                'teeny' => false,
                            ));
                            ?>
                            <p class="description">
                                <?php _e('This text will appear at the top of all posts and guides. You can override this on individual posts using the "Disclaimer Settings" meta box.', 'autowp'); ?>
                            </p>
                            <p class="description">
                                <?php _e('You can also use the [disclaimer] shortcode to place the disclaimer anywhere in your content.', 'autowp'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="autowp_save_legal_settings" 
                       class="button button-primary" 
                       value="<?php esc_attr_e('Save Changes', 'autowp'); ?>" />
            </p>
        </form>
        
        <div class="card">
            <h2><?php _e('Privacy & Compliance', 'autowp'); ?></h2>
            <p><?php _e('Additional legal pages and settings:', 'autowp'); ?></p>
            <ul>
                <li>
                    <a href="<?php echo admin_url('privacy.php'); ?>">
                        <?php _e('Privacy Policy Page', 'autowp'); ?>
                    </a> - 
                    <?php _e('Configure your privacy policy', 'autowp'); ?>
                </li>
                <li>
                    <a href="<?php echo admin_url('options-general.php'); ?>">
                        <?php _e('General Settings', 'autowp'); ?>
                    </a> - 
                    <?php _e('Site title, tagline, and timezone', 'autowp'); ?>
                </li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Enqueue admin styles
 */
function autowp_enqueue_admin_styles($hook) {
    if (strpos($hook, 'autowp') === false) {
        return;
    }
    
    wp_enqueue_style('autowp-admin', get_template_directory_uri() . '/assets/css/admin.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'autowp_enqueue_admin_styles');

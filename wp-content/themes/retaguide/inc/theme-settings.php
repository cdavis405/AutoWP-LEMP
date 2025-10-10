<?php
/**
 * Theme Settings Pages
 *
 * @package RetaGuide
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add theme settings menu
 */
function retaguide_add_theme_settings_menu() {
    // Main settings page
    add_theme_page(
        __('RetaGuide Settings', 'retaguide'),
        __('Theme Settings', 'retaguide'),
        'edit_theme_options',
        'retaguide-settings',
        'retaguide_settings_page'
    );
    
    // Pinned Navigation submenu
    add_theme_page(
        __('Pinned Navigation', 'retaguide'),
        __('Pinned Navigation', 'retaguide'),
        'edit_theme_options',
        'retaguide-pinned-nav',
        'retaguide_pinned_nav_page'
    );
    
    // Legal/Disclaimer submenu
    add_theme_page(
        __('Legal & Disclaimer', 'retaguide'),
        __('Legal & Disclaimer', 'retaguide'),
        'edit_theme_options',
        'retaguide-legal',
        'retaguide_legal_page'
    );
}
add_action('admin_menu', 'retaguide_add_theme_settings_menu');

/**
 * Main settings page
 */
function retaguide_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="card">
            <h2><?php _e('RetaGuide Theme Settings', 'retaguide'); ?></h2>
            <p><?php _e('Configure your RetaGuide theme settings:', 'retaguide'); ?></p>
            
            <ul>
                <li>
                    <a href="<?php echo admin_url('themes.php?page=retaguide-pinned-nav'); ?>">
                        <strong><?php _e('Pinned Navigation', 'retaguide'); ?></strong>
                    </a> - 
                    <?php _e('Manage pinned items in the navigation bar', 'retaguide'); ?>
                </li>
                <li>
                    <a href="<?php echo admin_url('themes.php?page=retaguide-legal'); ?>">
                        <strong><?php _e('Legal & Disclaimer', 'retaguide'); ?></strong>
                    </a> - 
                    <?php _e('Configure global disclaimer and legal notices', 'retaguide'); ?>
                </li>
                <li>
                    <a href="<?php echo admin_url('customize.php'); ?>">
                        <strong><?php _e('Customize Appearance', 'retaguide'); ?></strong>
                    </a> - 
                    <?php _e('Customize colors, typography, and layout', 'retaguide'); ?>
                </li>
            </ul>
        </div>
        
        <div class="card">
            <h2><?php _e('Quick Links', 'retaguide'); ?></h2>
            <ul>
                <li><a href="<?php echo admin_url('edit.php'); ?>"><?php _e('Manage News Posts', 'retaguide'); ?></a></li>
                <li><a href="<?php echo admin_url('edit.php?post_type=guide'); ?>"><?php _e('Manage Guides', 'retaguide'); ?></a></li>
                <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>"><?php _e('News Categories', 'retaguide'); ?></a></li>
                <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=guide_level&post_type=guide'); ?>"><?php _e('Guide Levels', 'retaguide'); ?></a></li>
                <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=guide_topic&post_type=guide'); ?>"><?php _e('Guide Topics', 'retaguide'); ?></a></li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Pinned Navigation settings page
 */
function retaguide_pinned_nav_page() {
    $pinned_items = retaguide_get_pinned_nav_items();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="card">
            <h2><?php _e('Manage Pinned Navigation Items', 'retaguide'); ?></h2>
            <p><?php _e('Add and arrange items to appear on the right side of your main navigation. Drag to reorder.', 'retaguide'); ?></p>
            
            <div class="pinned-nav-search">
                <input type="text" id="pinned-nav-search-input" placeholder="<?php esc_attr_e('Search for posts, pages, or guides...', 'retaguide'); ?>" />
                <button type="button" id="pinned-nav-search-btn" class="button"><?php _e('Search', 'retaguide'); ?></button>
                <div id="pinned-nav-search-results"></div>
            </div>
            
            <div class="pinned-nav-items-list">
                <h3><?php _e('Current Pinned Items', 'retaguide'); ?></h3>
                <ul id="pinned-items-sortable">
                    <?php if (empty($pinned_items)) : ?>
                        <li class="no-items"><?php _e('No pinned items yet. Search and add items above.', 'retaguide'); ?></li>
                    <?php else : ?>
                        <?php foreach ($pinned_items as $item) : ?>
                            <li data-id="<?php echo esc_attr($item['id']); ?>">
                                <span class="dashicons dashicons-menu handle"></span>
                                <span class="item-title"><?php echo esc_html(get_the_title($item['id'])); ?></span>
                                <input type="text" class="custom-title" 
                                       placeholder="<?php esc_attr_e('Custom title (optional)', 'retaguide'); ?>"
                                       value="<?php echo esc_attr($item['custom_title'] ?? ''); ?>" />
                                <button type="button" class="button remove-item"><?php _e('Remove', 'retaguide'); ?></button>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                
                <button type="button" id="save-pinned-items" class="button button-primary">
                    <?php _e('Save Changes', 'retaguide'); ?>
                </button>
                <span class="spinner"></span>
                <span class="save-message"></span>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
        var retaguideAdmin = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            searchNonce: '<?php echo wp_create_nonce('retaguide_pinned_nav_search'); ?>',
            saveNonce: '<?php echo wp_create_nonce('retaguide_save_pinned_items'); ?>'
        };
    </script>
    <?php
}

/**
 * Legal & Disclaimer settings page
 */
function retaguide_legal_page() {
    // Handle form submission
    if (isset($_POST['retaguide_save_legal_settings']) && 
        check_admin_referer('retaguide_legal_settings', 'retaguide_legal_nonce')) {
        
        $enable_disclaimer = isset($_POST['retaguide_enable_global_disclaimer']) ? 1 : 0;
        $disclaimer_text = isset($_POST['retaguide_global_disclaimer']) 
            ? wp_kses_post($_POST['retaguide_global_disclaimer']) 
            : '';
        
        update_option('retaguide_enable_global_disclaimer', $enable_disclaimer);
        update_option('retaguide_global_disclaimer', $disclaimer_text);
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'retaguide') . '</p></div>';
    }
    
    $enable_disclaimer = get_option('retaguide_enable_global_disclaimer', true);
    $disclaimer_text = get_option('retaguide_global_disclaimer', '');
    
    if (empty($disclaimer_text)) {
        $disclaimer_text = '<p><strong>Medical Disclaimer:</strong> This content is for informational and educational purposes only. Retatrutide is an experimental research peptide not approved by the FDA for human use. The information provided does not constitute medical advice and should not be used for diagnosis or treatment. Always consult with a qualified healthcare provider before making any decisions related to your health or treatment.</p>';
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('retaguide_legal_settings', 'retaguide_legal_nonce'); ?>
            
            <div class="card">
                <h2><?php _e('Global Disclaimer', 'retaguide'); ?></h2>
                <p><?php _e('This disclaimer will be automatically shown at the top of all posts and guides unless disabled per-post.', 'retaguide'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php _e('Enable Global Disclaimer', 'retaguide'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="retaguide_enable_global_disclaimer" value="1" 
                                       <?php checked($enable_disclaimer, 1); ?> />
                                <?php _e('Automatically show disclaimer on all posts and guides', 'retaguide'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="retaguide_global_disclaimer">
                                <?php _e('Disclaimer Text', 'retaguide'); ?>
                            </label>
                        </th>
                        <td>
                            <?php
                            wp_editor($disclaimer_text, 'retaguide_global_disclaimer', array(
                                'textarea_rows' => 10,
                                'media_buttons' => true,
                                'teeny' => false,
                            ));
                            ?>
                            <p class="description">
                                <?php _e('This text will appear at the top of all posts and guides. You can override this on individual posts using the "Disclaimer Settings" meta box.', 'retaguide'); ?>
                            </p>
                            <p class="description">
                                <?php _e('You can also use the [disclaimer] shortcode to place the disclaimer anywhere in your content.', 'retaguide'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="retaguide_save_legal_settings" 
                       class="button button-primary" 
                       value="<?php esc_attr_e('Save Changes', 'retaguide'); ?>" />
            </p>
        </form>
        
        <div class="card">
            <h2><?php _e('Privacy & Compliance', 'retaguide'); ?></h2>
            <p><?php _e('Additional legal pages and settings:', 'retaguide'); ?></p>
            <ul>
                <li>
                    <a href="<?php echo admin_url('privacy.php'); ?>">
                        <?php _e('Privacy Policy Page', 'retaguide'); ?>
                    </a> - 
                    <?php _e('Configure your privacy policy', 'retaguide'); ?>
                </li>
                <li>
                    <a href="<?php echo admin_url('options-general.php'); ?>">
                        <?php _e('General Settings', 'retaguide'); ?>
                    </a> - 
                    <?php _e('Site title, tagline, and timezone', 'retaguide'); ?>
                </li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Enqueue admin styles
 */
function retaguide_enqueue_admin_styles($hook) {
    if (strpos($hook, 'retaguide') === false) {
        return;
    }
    
    wp_enqueue_style('retaguide-admin', RETAGUIDE_THEME_URI . '/assets/css/admin.css', array(), RETAGUIDE_VERSION);
}
add_action('admin_enqueue_scripts', 'retaguide_enqueue_admin_styles');

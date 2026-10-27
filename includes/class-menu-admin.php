<?php
/**
 * In-WordPress Menu Manager Admin Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Menu_Admin {

    public static function init() {
        // AJAX Endpoints for Menu Operations
        $ajax_actions = array(
            'get_menu_data',
            'create_menu',
            'update_menu',
            'delete_menu',
            'create_section',
            'update_section',
            'delete_section',
            'reorder_sections',
            'create_item',
            'update_item',
            'delete_item',
            'create_option',
            'delete_option',
            'save_display_settings',
        );

        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_attention_network_' . $action, array(__CLASS__, 'handle_' . $action));
        }
    }

    /**
     * Render the Menu Manager Admin Screen
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $is_configured = Attention_Network_API_Client::is_configured();
        $menus = array();
        $error_message = '';

        if ($is_configured) {
            $response = Attention_Network_API_Client::get_menus(true);
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
            } else {
                $menus = is_array($response) ? $response : array();
            }
        }

        $active_menu_id = isset($_GET['menu_id']) ? sanitize_text_field($_GET['menu_id']) : '';
        if (empty($active_menu_id) && !empty($menus)) {
            $active_menu_id = $menus[0]['id'];
        }

        // Fetch full data for the active menu
        $active_menu = null;
        if (!empty($active_menu_id)) {
            $full_response = Attention_Network_API_Client::get_full_menu($active_menu_id, true);
            if (!is_wp_error($full_response)) {
                $active_menu = $full_response;
            }
        }
        ?>
        <div class="wrap attn-admin-wrap" id="attn-menu-app" data-menu-id="<?php echo esc_attr($active_menu_id); ?>">
            <!-- Header Bar -->
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge">Food & Drink Menus</span>
                    <h1 class="attn-title"><?php esc_html_e('Menu Manager', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php esc_html_e('Directly manage your restaurant menus, categories, pricing, and live website displays.', 'attention-network'); ?></p>
                </div>
                <div class="attn-header-right">
                    <?php if ($is_configured && !empty($menus)) : ?>
                        <div class="attn-menu-switcher">
                            <label for="attn-active-menu-select" class="screen-reader-text"><?php esc_html_e('Select Menu', 'attention-network'); ?></label>
                            <select id="attn-active-menu-select" class="attn-select">
                                <?php foreach ($menus as $m) : ?>
                                    <option value="<?php echo esc_attr($m['id']); ?>" <?php selected($m['id'], $active_menu_id); ?>>
                                        <?php echo esc_html($m['name']); ?> <?php echo empty($m['is_active']) ? __('(Inactive)', 'attention-network') : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_configured) : ?>
                        <button type="button" class="button button-primary" id="attn-btn-open-create-menu">
                            <span class="dashicons dashicons-plus-alt2" style="line-height:1.4"></span> <?php esc_html_e('New Menu', 'attention-network'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Configuration / Capability Alert -->
            <?php if (!$is_configured) : ?>
                <div class="attn-notice-banner warning">
                    <div class="attn-notice-icon"><span class="dashicons dashicons-warning"></span></div>
                    <div class="attn-notice-content">
                        <h3><?php esc_html_e('API Setup Required', 'attention-network'); ?></h3>
                        <p><?php esc_html_e('Please enter your Organization ID and API Base URL in the settings to activate the menu manager.', 'attention-network'); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=attention-network-settings')); ?>" class="button button-secondary">
                            <?php esc_html_e('Configure Settings →', 'attention-network'); ?>
                        </a>
                    </div>
                </div>
            <?php elseif (!empty($error_message)) : ?>
                <div class="attn-notice-banner error">
                    <div class="attn-notice-icon"><span class="dashicons dashicons-dismiss"></span></div>
                    <div class="attn-notice-content">
                        <h3><?php esc_html_e('Failed to load menus from Attention Platform', 'attention-network'); ?></h3>
                        <p><?php echo esc_html($error_message); ?></p>
                        <button type="button" class="button button-secondary" onclick="window.location.reload();">
                            <?php esc_html_e('Retry', 'attention-network'); ?>
                        </button>
                    </div>
                </div>
            <?php elseif (empty($menus)) : ?>
                <!-- Empty State -->
                <div class="attn-empty-state">
                    <div class="attn-empty-icon"><span class="dashicons dashicons-food"></span></div>
                    <h2><?php esc_html_e('No Menus Created Yet', 'attention-network'); ?></h2>
                    <p><?php esc_html_e('Get started by creating your first food, drink, or taplist menu for this organization.', 'attention-network'); ?></p>
                    <button type="button" class="button button-primary button-hero" id="attn-btn-open-create-menu-empty">
                        <?php esc_html_e('+ Create Your First Menu', 'attention-network'); ?>
                    </button>
                </div>
            <?php else : ?>
                <!-- Main Workspace with Tabs -->
                <div class="attn-menu-container">
                    <!-- Menu Metadata Banner -->
                    <div class="attn-menu-meta-bar">
                        <div class="attn-menu-meta-details">
                            <span class="attn-menu-name"><?php echo esc_html($active_menu['name'] ?? ''); ?></span>
                            <?php if (!empty($active_menu['description'])) : ?>
                                <span class="attn-menu-desc"><?php echo esc_html($active_menu['description']); ?></span>
                            <?php endif; ?>
                            <span class="attn-badge <?php echo (!empty($active_menu['is_active'])) ? 'badge-success' : 'badge-muted'; ?>">
                                <?php echo (!empty($active_menu['is_active'])) ? __('Active', 'attention-network') : __('Draft', 'attention-network'); ?>
                            </span>
                        </div>
                        <div class="attn-menu-meta-actions">
                            <button type="button" class="button button-secondary" id="attn-btn-edit-active-menu">
                                <span class="dashicons dashicons-edit"></span> <?php esc_html_e('Edit Menu Details', 'attention-network'); ?>
                            </button>
                            <button type="button" class="button button-link-delete" id="attn-btn-delete-active-menu" data-menu-id="<?php echo esc_attr($active_menu_id); ?>">
                                <?php esc_html_e('Delete Menu', 'attention-network'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="attn-tabs-nav">
                        <button type="button" class="attn-tab-btn active" data-tab="editor">
                            <span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Menu Sections & Items', 'attention-network'); ?>
                        </button>
                        <button type="button" class="attn-tab-btn" data-tab="settings">
                            <span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e('Display & Layout Settings', 'attention-network'); ?>
                        </button>
                        <button type="button" class="attn-tab-btn" data-tab="preview">
                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Live Website Preview', 'attention-network'); ?>
                        </button>
                    </div>

                    <!-- TAB 1: MENU SECTIONS & ITEMS -->
                    <div class="attn-tab-content active" id="attn-tab-editor">
                        <div class="attn-editor-toolbar">
                            <button type="button" class="button button-primary" id="attn-btn-add-section">
                                <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add New Section', 'attention-network'); ?>
                            </button>
                            <span class="attn-shortcode-pill">
                                <?php esc_html_e('Shortcode:', 'attention-network'); ?>
                                <code>[attention_menu id="<?php echo esc_attr($active_menu_id); ?>"]</code>
                            </span>
                        </div>

                        <div class="attn-sections-list" id="attn-sections-container">
                            <?php
                            $sections = $active_menu['sections'] ?? array();
                            if (empty($sections)) :
                            ?>
                                <div class="attn-empty-sections">
                                    <p><?php esc_html_e('This menu has no sections yet (e.g. "Appetizers", "Main Courses", "Draft Beers").', 'attention-network'); ?></p>
                                    <button type="button" class="button button-secondary" id="attn-btn-add-section-empty">
                                        <?php esc_html_e('+ Add First Section', 'attention-network'); ?>
                                    </button>
                                </div>
                            <?php else : ?>
                                <?php foreach ($sections as $sIndex => $sec) : ?>
                                    <div class="attn-section-card" data-section-id="<?php echo esc_attr($sec['id']); ?>" data-order="<?php echo esc_attr($sec['display_order'] ?? $sIndex); ?>">
                                        <div class="attn-section-header">
                                            <div class="attn-section-title-wrap">
                                                <span class="dashicons dashicons-menu attn-drag-handle"></span>
                                                <h3 class="attn-section-title"><?php echo esc_html($sec['name']); ?></h3>
                                                <?php if (!empty($sec['description'])) : ?>
                                                    <span class="attn-section-desc"><?php echo esc_html($sec['description']); ?></span>
                                                <?php endif; ?>
                                                <span class="attn-items-count"><?php echo count($sec['items'] ?? array()); ?> <?php esc_html_e('items', 'attention-network'); ?></span>
                                            </div>
                                            <div class="attn-section-actions">
                                                <button type="button" class="button button-small attn-btn-move-section" data-direction="up" title="<?php esc_attr_e('Move Up', 'attention-network'); ?>">▲</button>
                                                <button type="button" class="button button-small attn-btn-move-section" data-direction="down" title="<?php esc_attr_e('Move Down', 'attention-network'); ?>">▼</button>
                                                <button type="button" class="button button-small attn-btn-edit-section" data-section-id="<?php echo esc_attr($sec['id']); ?>" data-name="<?php echo esc_attr($sec['name']); ?>" data-desc="<?php echo esc_attr($sec['description'] ?? ''); ?>">
                                                    <?php esc_html_e('Edit', 'attention-network'); ?>
                                                </button>
                                                <button type="button" class="button button-small button-link-delete attn-btn-delete-section" data-section-id="<?php echo esc_attr($sec['id']); ?>">
                                                    <?php esc_html_e('Delete', 'attention-network'); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Section Items -->
                                        <div class="attn-items-list">
                                            <?php
                                            $items = $sec['items'] ?? array();
                                            if (empty($items)) :
                                            ?>
                                                <div class="attn-no-items">
                                                    <span><?php esc_html_e('No items in this section.', 'attention-network'); ?></span>
                                                    <button type="button" class="button button-small button-primary attn-btn-add-item" data-section-id="<?php echo esc_attr($sec['id']); ?>">
                                                        <?php esc_html_e('+ Add Item', 'attention-network'); ?>
                                                    </button>
                                                </div>
                                            <?php else : ?>
                                                <div class="attn-items-grid">
                                                    <?php foreach ($items as $item) : ?>
                                                        <div class="attn-item-card" data-item-id="<?php echo esc_attr($item['id']); ?>" data-section-id="<?php echo esc_attr($sec['id']); ?>">
                                                            <?php if (!empty($item['photo_url'])) : ?>
                                                                <div class="attn-item-thumb">
                                                                    <img src="<?php echo esc_url($item['photo_url']); ?>" alt="<?php echo esc_attr($item['name']); ?>" loading="lazy" />
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="attn-item-content">
                                                                <div class="attn-item-top">
                                                                    <strong class="attn-item-name"><?php echo esc_html($item['name']); ?></strong>
                                                                    <span class="attn-item-price">
                                                                        <?php
                                                                        $cur = $item['price_currency'] ?? 'USD';
                                                                        $sym = ($cur === 'USD') ? '$' : $cur . ' ';
                                                                        echo esc_html($sym . number_format($item['price_amount'] ?? 0, 2));
                                                                        ?>
                                                                    </span>
                                                                </div>
                                                                <?php if (!empty($item['description'])) : ?>
                                                                    <p class="attn-item-desc"><?php echo esc_html($item['description']); ?></p>
                                                                <?php endif; ?>
                                                                <div class="attn-item-meta">
                                                                    <?php if (!empty($item['calories'])) : ?>
                                                                        <span class="attn-tag-calories"><?php echo intval($item['calories']); ?> cal</span>
                                                                    <?php endif; ?>
                                                                    <?php
                                                                    $dietary = $item['dietary_tags'] ?? array();
                                                                    if (is_array($dietary)) {
                                                                        foreach ($dietary as $tag) {
                                                                            echo '<span class="attn-tag-dietary">' . esc_html($tag) . '</span>';
                                                                        }
                                                                    }
                                                                    ?>
                                                                </div>

                                                                <!-- Options / Modifiers summary -->
                                                                <div class="attn-item-options-row">
                                                                    <span class="attn-options-count">
                                                                        <?php
                                                                        $opts = $item['options'] ?? array();
                                                                        echo count($opts) . ' ' . esc_html__('modifiers', 'attention-network');
                                                                        ?>
                                                                    </span>
                                                                    <button type="button" class="button button-small attn-btn-manage-options" data-item-id="<?php echo esc_attr($item['id']); ?>" data-item-name="<?php echo esc_attr($item['name']); ?>" data-section-id="<?php echo esc_attr($sec['id']); ?>">
                                                                        <?php esc_html_e('+ Modifiers', 'attention-network'); ?>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="attn-item-actions">
                                                                <button type="button" class="button button-small attn-btn-edit-item"
                                                                    data-item='<?php echo esc_attr(wp_json_encode($item)); ?>'
                                                                    data-section-id="<?php echo esc_attr($sec['id']); ?>">
                                                                    <?php esc_html_e('Edit', 'attention-network'); ?>
                                                                </button>
                                                                <button type="button" class="button button-small button-link-delete attn-btn-delete-item"
                                                                    data-item-id="<?php echo esc_attr($item['id']); ?>"
                                                                    data-section-id="<?php echo esc_attr($sec['id']); ?>">
                                                                    <?php esc_html_e('Delete', 'attention-network'); ?>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="attn-section-footer">
                                                    <button type="button" class="button button-secondary button-small attn-btn-add-item" data-section-id="<?php echo esc_attr($sec['id']); ?>">
                                                        <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add Item to this Section', 'attention-network'); ?>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- TAB 2: DISPLAY & LAYOUT SETTINGS -->
                    <div class="attn-tab-content" id="attn-tab-settings">
                        <?php
                        $ds = $active_menu['displaySettings'] ?? array();
                        $layout_mode = $ds['layout_mode'] ?? 'standard';
                        $theme_preset = $ds['theme_preset'] ?? 'light';
                        ?>
                        <div class="attn-settings-card">
                            <h2><?php esc_html_e('Website Display & Styling Settings', 'attention-network'); ?></h2>
                            <p class="attn-card-desc"><?php esc_html_e('Choose how this menu renders on client websites, mobile QR code scanners, and on-site TV screens.', 'attention-network'); ?></p>

                            <form id="attn-form-display-settings">
                                <div class="attn-setting-group">
                                    <label class="attn-setting-label"><?php esc_html_e('Display Layout Mode', 'attention-network'); ?></label>
                                    <div class="attn-layout-selector">
                                        <label class="attn-radio-card <?php echo $layout_mode === 'standard' ? 'selected' : ''; ?>">
                                            <input type="radio" name="layout_mode" value="standard" <?php checked($layout_mode, 'standard'); ?> />
                                            <strong><?php esc_html_e('Standard Menu', 'attention-network'); ?></strong>
                                            <span><?php esc_html_e('Classic restaurant card grid with photography & dietary tags.', 'attention-network'); ?></span>
                                        </label>
                                        <label class="attn-radio-card <?php echo $layout_mode === 'taplist' ? 'selected' : ''; ?>">
                                            <input type="radio" name="layout_mode" value="taplist" <?php checked($layout_mode, 'taplist'); ?> />
                                            <strong><?php esc_html_e('Taplist / Drink Board', 'attention-network'); ?></strong>
                                            <span><?php esc_html_e('Brewery & bar layout featuring ABV, IBU, and glassware sizes.', 'attention-network'); ?></span>
                                        </label>
                                        <label class="attn-radio-card <?php echo $layout_mode === 'qr' ? 'selected' : ''; ?>">
                                            <input type="radio" name="layout_mode" value="qr" <?php checked($layout_mode, 'qr'); ?> />
                                            <strong><?php esc_html_e('QR Mobile Menu', 'attention-network'); ?></strong>
                                            <span><?php esc_html_e('Fast mobile view optimized for smartphone QR code scanning.', 'attention-network'); ?></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="attn-setting-group">
                                    <label class="attn-setting-label"><?php esc_html_e('Visual Theme Preset', 'attention-network'); ?></label>
                                    <select name="theme_preset" class="attn-select">
                                        <option value="light" <?php selected($theme_preset, 'light'); ?>><?php esc_html_e('Clean Crisp (Light)', 'attention-network'); ?></option>
                                        <option value="dark" <?php selected($theme_preset, 'dark'); ?>><?php esc_html_e('Midnight Slate (Dark)', 'attention-network'); ?></option>
                                        <option value="amber" <?php selected($theme_preset, 'amber'); ?>><?php esc_html_e('Warm Amber / Bistro', 'attention-network'); ?></option>
                                        <option value="emerald" <?php selected($theme_preset, 'emerald'); ?>><?php esc_html_e('Emerald Fresh / Cafe', 'attention-network'); ?></option>
                                        <option value="brewery" <?php selected($theme_preset, 'brewery'); ?>><?php esc_html_e('Craft Taproom (High Contrast)', 'attention-network'); ?></option>
                                    </select>
                                </div>

                                <div class="attn-toggles-grid">
                                    <label class="attn-toggle-label">
                                        <input type="checkbox" name="show_images" value="1" <?php checked($ds['show_images'] ?? true); ?> />
                                        <span><?php esc_html_e('Show Dish Photos & Media', 'attention-network'); ?></span>
                                    </label>
                                    <label class="attn-toggle-label">
                                        <input type="checkbox" name="show_prices" value="1" <?php checked($ds['show_prices'] ?? true); ?> />
                                        <span><?php esc_html_e('Show Item Prices', 'attention-network'); ?></span>
                                    </label>
                                    <label class="attn-toggle-label">
                                        <input type="checkbox" name="show_calories" value="1" <?php checked($ds['show_calories'] ?? true); ?> />
                                        <span><?php esc_html_e('Show Calorie Badges', 'attention-network'); ?></span>
                                    </label>
                                    <label class="attn-toggle-label">
                                        <input type="checkbox" name="show_dietary_tags" value="1" <?php checked($ds['show_dietary_tags'] ?? true); ?> />
                                        <span><?php esc_html_e('Show Dietary Badges (GF, Vegan, etc.)', 'attention-network'); ?></span>
                                    </label>
                                    <label class="attn-toggle-label">
                                        <input type="checkbox" name="show_abv" value="1" <?php checked($ds['show_abv'] ?? false); ?> />
                                        <span><?php esc_html_e('Show ABV % (Alcohol by Volume)', 'attention-network'); ?></span>
                                    </label>
                                    <label class="attn-toggle-label">
                                        <input type="checkbox" name="show_ibu" value="1" <?php checked($ds['show_ibu'] ?? false); ?> />
                                        <span><?php esc_html_e('Show IBU (Bitterness Rating)', 'attention-network'); ?></span>
                                    </label>
                                </div>

                                <div class="attn-form-actions" style="margin-top: 24px;">
                                    <button type="submit" class="button button-primary">
                                        <?php esc_html_e('Save Display Settings', 'attention-network'); ?>
                                    </button>
                                    <span id="attn-save-settings-status" class="attn-status-text"></span>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TAB 3: LIVE PREVIEW -->
                    <div class="attn-tab-content" id="attn-tab-preview">
                        <div class="attn-preview-wrapper">
                            <div class="attn-preview-header">
                                <span class="attn-preview-badge"><?php esc_html_e('Simulated Client View', 'attention-network'); ?></span>
                                <span><?php esc_html_e('This is exactly how your menu renders on your website pages.', 'attention-network'); ?></span>
                            </div>
                            <div class="attn-preview-frame">
                                <?php
                                echo Attention_Network_Shortcodes::render_menu(array(
                                    'id' => $active_menu_id,
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= MODALS ================= -->

            <!-- 1. Create/Edit Menu Modal -->
            <div class="attn-modal-backdrop" id="attn-modal-menu" style="display:none;">
                <div class="attn-modal">
                    <div class="attn-modal-header">
                        <h2 id="attn-menu-modal-title"><?php esc_html_e('Create New Menu', 'attention-network'); ?></h2>
                        <button type="button" class="attn-modal-close">&times;</button>
                    </div>
                    <form id="attn-form-menu">
                        <input type="hidden" id="attn-menu-field-id" value="" />
                        <div class="attn-form-row">
                            <label for="attn-menu-field-name"><?php esc_html_e('Menu Name', 'attention-network'); ?> *</label>
                            <input type="text" id="attn-menu-field-name" required placeholder="e.g. Dinner Menu, Craft Beer List" class="widefat" />
                        </div>
                        <div class="attn-form-row">
                            <label for="attn-menu-field-desc"><?php esc_html_e('Description / Subtitle', 'attention-network'); ?></label>
                            <textarea id="attn-menu-field-desc" rows="3" placeholder="e.g. Served daily from 5:00 PM to 10:00 PM" class="widefat"></textarea>
                        </div>
                        <div class="attn-form-row">
                            <label class="attn-toggle-label">
                                <input type="checkbox" id="attn-menu-field-active" checked />
                                <span><?php esc_html_e('Active (Visible to website visitors)', 'attention-network'); ?></span>
                            </label>
                        </div>
                        <div class="attn-modal-footer">
                            <button type="button" class="button button-secondary attn-modal-cancel"><?php esc_html_e('Cancel', 'attention-network'); ?></button>
                            <button type="submit" class="button button-primary" id="attn-btn-save-menu"><?php esc_html_e('Save Menu', 'attention-network'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. Section Modal -->
            <div class="attn-modal-backdrop" id="attn-modal-section" style="display:none;">
                <div class="attn-modal">
                    <div class="attn-modal-header">
                        <h2 id="attn-section-modal-title"><?php esc_html_e('Add Menu Section', 'attention-network'); ?></h2>
                        <button type="button" class="attn-modal-close">&times;</button>
                    </div>
                    <form id="attn-form-section">
                        <input type="hidden" id="attn-section-field-id" value="" />
                        <div class="attn-form-row">
                            <label for="attn-section-field-name"><?php esc_html_e('Section Title', 'attention-network'); ?> *</label>
                            <input type="text" id="attn-section-field-name" required placeholder="e.g. Starters, Wood-Fired Pizza, Cocktails" class="widefat" />
                        </div>
                        <div class="attn-form-row">
                            <label for="attn-section-field-desc"><?php esc_html_e('Section Description', 'attention-network'); ?></label>
                            <input type="text" id="attn-section-field-desc" placeholder="e.g. Served with house-made sourdough" class="widefat" />
                        </div>
                        <div class="attn-modal-footer">
                            <button type="button" class="button button-secondary attn-modal-cancel"><?php esc_html_e('Cancel', 'attention-network'); ?></button>
                            <button type="submit" class="button button-primary" id="attn-btn-save-section"><?php esc_html_e('Save Section', 'attention-network'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. Item Modal -->
            <div class="attn-modal-backdrop" id="attn-modal-item" style="display:none;">
                <div class="attn-modal attn-modal-lg">
                    <div class="attn-modal-header">
                        <h2 id="attn-item-modal-title"><?php esc_html_e('Add Menu Item', 'attention-network'); ?></h2>
                        <button type="button" class="attn-modal-close">&times;</button>
                    </div>
                    <form id="attn-form-item">
                        <input type="hidden" id="attn-item-field-id" value="" />
                        <input type="hidden" id="attn-item-field-section-id" value="" />
                        
                        <div class="attn-modal-grid">
                            <div class="attn-form-row">
                                <label for="attn-item-field-name"><?php esc_html_e('Item Name', 'attention-network'); ?> *</label>
                                <input type="text" id="attn-item-field-name" required placeholder="e.g. Truffle Parmesan Fries" class="widefat" />
                            </div>
                            <div class="attn-form-row">
                                <label for="attn-item-field-price"><?php esc_html_e('Price ($ USD)', 'attention-network'); ?> *</label>
                                <input type="number" step="0.01" min="0" id="attn-item-field-price" required placeholder="14.00" class="widefat" />
                            </div>
                        </div>

                        <div class="attn-form-row">
                            <label for="attn-item-field-desc"><?php esc_html_e('Description & Ingredients', 'attention-network'); ?></label>
                            <textarea id="attn-item-field-desc" rows="3" placeholder="Crispy hand-cut russet potatoes, black truffle oil, aged parmesan, rosemary aioli..." class="widefat"></textarea>
                        </div>

                        <div class="attn-modal-grid">
                            <div class="attn-form-row">
                                <label for="attn-item-field-calories"><?php esc_html_e('Calories (optional)', 'attention-network'); ?></label>
                                <input type="number" id="attn-item-field-calories" placeholder="e.g. 580" class="widefat" />
                            </div>
                            <div class="attn-form-row">
                                <label for="attn-item-field-photo"><?php esc_html_e('Photo URL (optional)', 'attention-network'); ?></label>
                                <input type="url" id="attn-item-field-photo" placeholder="https://..." class="widefat" />
                            </div>
                        </div>

                        <div class="attn-form-row">
                            <label><?php esc_html_e('Dietary Badges', 'attention-network'); ?></label>
                            <div class="attn-dietary-checkboxes">
                                <?php
                                $badges = array('GF' => 'Gluten Free', 'Vegan' => 'Vegan', 'Vegetarian' => 'Vegetarian', 'Chef Special' => 'Chef Special', 'Spicy' => 'Spicy', 'Halal' => 'Halal', 'Dairy-Free' => 'Dairy-Free', 'Nut-Free' => 'Nut-Free');
                                foreach ($badges as $code => $lbl) :
                                ?>
                                    <label class="attn-checkbox-pill">
                                        <input type="checkbox" name="dietary_tags[]" value="<?php echo esc_attr($code); ?>" />
                                        <span><?php echo esc_html($lbl); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="attn-modal-footer">
                            <button type="button" class="button button-secondary attn-modal-cancel"><?php esc_html_e('Cancel', 'attention-network'); ?></button>
                            <button type="submit" class="button button-primary" id="attn-btn-save-item"><?php esc_html_e('Save Item', 'attention-network'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 4. Modifiers / Options Modal -->
            <div class="attn-modal-backdrop" id="attn-modal-options" style="display:none;">
                <div class="attn-modal">
                    <div class="attn-modal-header">
                        <h2><?php esc_html_e('Modifiers & Add-ons for:', 'attention-network'); ?> <span id="attn-options-item-name"></span></h2>
                        <button type="button" class="attn-modal-close">&times;</button>
                    </div>
                    <div class="attn-options-body">
                        <p class="attn-card-desc"><?php esc_html_e('Add modifiers, sizes, or toppings (e.g. "Add Avocado +$2.50", "Gluten-Free Crust +$3.00").', 'attention-network'); ?></p>
                        
                        <div class="attn-options-list" id="attn-options-container">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <form id="attn-form-add-option" style="margin-top:16px; padding-top:12px; border-top:1px solid #e2e8f0;">
                            <input type="hidden" id="attn-opt-item-id" value="" />
                            <input type="hidden" id="attn-opt-section-id" value="" />
                            <div style="display:flex; gap:8px;">
                                <input type="text" id="attn-opt-name" placeholder="Modifier name (e.g. Extra Cheese)" required style="flex:2;" />
                                <input type="number" step="0.01" id="attn-opt-price" placeholder="+$2.00" style="flex:1;" />
                                <button type="submit" class="button button-primary"><?php esc_html_e('+ Add', 'attention-network'); ?></button>
                            </div>
                        </form>
                    </div>
                    <div class="attn-modal-footer">
                        <button type="button" class="button button-secondary attn-modal-cancel"><?php esc_html_e('Done', 'attention-network'); ?></button>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }

    /* -------------------------------------------------------------------------
     * AJAX Handlers
     * ------------------------------------------------------------------------- */

    private static function check_auth() {
        check_ajax_referer('attention_network_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission.', 'attention-network')));
        }
    }

    public static function handle_get_menu_data() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $data = Attention_Network_API_Client::get_full_menu($menu_id, true);
        if (is_wp_error($data)) {
            wp_send_json_error(array('message' => $data->get_error_message()));
        }
        wp_send_json_success($data);
    }

    public static function handle_create_menu() {
        self::check_auth();
        $payload = array(
            'name'        => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'is_active'   => !empty($_POST['is_active']),
        );
        $result = Attention_Network_API_Client::create_menu($payload);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_update_menu() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $payload = array(
            'name'        => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'is_active'   => !empty($_POST['is_active']),
        );
        $result = Attention_Network_API_Client::update_menu($menu_id, $payload);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_delete_menu() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $result = Attention_Network_API_Client::delete_menu($menu_id);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_create_section() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $payload = array(
            'name'        => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_text_field($_POST['description'] ?? ''),
        );
        $result = Attention_Network_API_Client::create_section($menu_id, $payload);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_update_section() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $payload = array(
            'name'        => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_text_field($_POST['description'] ?? ''),
        );
        $result = Attention_Network_API_Client::update_section($menu_id, $section_id, $payload);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_delete_section() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $result = Attention_Network_API_Client::delete_section($menu_id, $section_id);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_reorder_sections() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $section_ids = isset($_POST['section_ids']) ? array_map('sanitize_text_field', (array)$_POST['section_ids']) : array();
        $result = Attention_Network_API_Client::reorder_sections($menu_id, $section_ids);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_create_item() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $dietary = isset($_POST['dietary_tags']) ? array_map('sanitize_text_field', (array)$_POST['dietary_tags']) : array();

        $payload = array(
            'name'           => sanitize_text_field($_POST['name'] ?? ''),
            'description'    => sanitize_textarea_field($_POST['description'] ?? ''),
            'price_amount'   => floatval($_POST['price_amount'] ?? 0),
            'price_currency' => 'USD',
            'is_available'   => true,
            'photo_url'      => esc_url_raw($_POST['photo_url'] ?? ''),
            'dietary_tags'   => $dietary,
            'calories'       => !empty($_POST['calories']) ? intval($_POST['calories']) : null,
        );

        $result = Attention_Network_API_Client::create_item($menu_id, $section_id, $payload);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_update_item() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $item_id = sanitize_text_field($_POST['item_id'] ?? '');
        $dietary = isset($_POST['dietary_tags']) ? array_map('sanitize_text_field', (array)$_POST['dietary_tags']) : array();

        $payload = array(
            'name'           => sanitize_text_field($_POST['name'] ?? ''),
            'description'    => sanitize_textarea_field($_POST['description'] ?? ''),
            'price_amount'   => floatval($_POST['price_amount'] ?? 0),
            'photo_url'      => esc_url_raw($_POST['photo_url'] ?? ''),
            'dietary_tags'   => $dietary,
            'calories'       => !empty($_POST['calories']) ? intval($_POST['calories']) : null,
        );

        $result = Attention_Network_API_Client::update_item($menu_id, $section_id, $item_id, $payload);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_delete_item() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $item_id = sanitize_text_field($_POST['item_id'] ?? '');
        $result = Attention_Network_API_Client::delete_item($menu_id, $section_id, $item_id);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_create_option() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $item_id = sanitize_text_field($_POST['item_id'] ?? '');

        $payload = array(
            'name'           => sanitize_text_field($_POST['name'] ?? ''),
            'price_modifier' => floatval($_POST['price_modifier'] ?? 0),
            'is_available'   => true,
        );

        $result = Attention_Network_API_Client::create_option($menu_id, $section_id, $item_id, $payload);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_delete_option() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $item_id = sanitize_text_field($_POST['item_id'] ?? '');
        $option_id = sanitize_text_field($_POST['option_id'] ?? '');

        $result = Attention_Network_API_Client::delete_option($menu_id, $section_id, $item_id, $option_id);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    public static function handle_save_display_settings() {
        self::check_auth();
        $menu_id = sanitize_text_field($_POST['menu_id'] ?? '');

        $payload = array(
            'layout_mode'        => sanitize_text_field($_POST['layout_mode'] ?? 'standard'),
            'theme_preset'       => sanitize_text_field($_POST['theme_preset'] ?? 'light'),
            'show_images'        => !empty($_POST['show_images']),
            'show_prices'        => !empty($_POST['show_prices']),
            'show_calories'      => !empty($_POST['show_calories']),
            'show_dietary_tags'  => !empty($_POST['show_dietary_tags']),
            'show_abv'           => !empty($_POST['show_abv']),
            'show_ibu'           => !empty($_POST['show_ibu']),
        );

        $result = Attention_Network_API_Client::upsert_display_settings($menu_id, $payload);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }
}

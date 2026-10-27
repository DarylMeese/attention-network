<?php
/**
 * Settings & Admin Navigation for Attention Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Settings {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_admin_menus'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('wp_ajax_attention_network_test_connection', array(__CLASS__, 'ajax_test_connection'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_settings_assets'));
    }

    /**
     * Register Top Level and Submenu Pages
     */
    public static function register_admin_menus() {
        // Main Menu: Attention Network (defaults to Services & Menus)
        add_menu_page(
            __('Attention Network', 'attention-network'),
            __('Attention Network', 'attention-network'),
            'manage_options',
            'attention-network-menus',
            array('Attention_Network_Menu_Admin', 'render_page'),
            'dashicons-store',
            30
        );

        // Submenu: Services & Menus
        add_submenu_page(
            'attention-network-menus',
            __('Services & Menus', 'attention-network'),
            __('Services & Menus', 'attention-network'),
            'manage_options',
            'attention-network-menus',
            array('Attention_Network_Menu_Admin', 'render_page')
        );

        // Submenu: Hours & Availability
        add_submenu_page(
            'attention-network-menus',
            __('Operating Hours & Availability', 'attention-network'),
            __('Hours & Availability', 'attention-network'),
            'manage_options',
            'attention-network-hours',
            array('Attention_Network_Business_Admin', 'render_hours_page')
        );

        // Submenu: Staff & Field Pros
        add_submenu_page(
            'attention-network-menus',
            __('Staff Directory & Pros', 'attention-network'),
            __('Staff & Field Pros', 'attention-network'),
            'manage_options',
            'attention-network-staff',
            array('Attention_Network_Staff_Admin', 'render_page')
        );

        // Submenu: Offers & Coupons
        add_submenu_page(
            'attention-network-menus',
            __('Offers & Promotional Coupons', 'attention-network'),
            __('Offers & Coupons', 'attention-network'),
            'manage_options',
            'attention-network-offers',
            array('Attention_Network_Offers_Admin', 'render_page')
        );

        // Submenu: Customer Reviews & Reputation
        add_submenu_page(
            'attention-network-menus',
            __('Customer Reviews & Ratings', 'attention-network'),
            __('Reviews & Ratings', 'attention-network'),
            'manage_options',
            'attention-network-reviews',
            array('Attention_Network_Reviews_Admin', 'render_page')
        );

        // Submenu: Inbound Leads & Quotes
        add_submenu_page(
            'attention-network-menus',
            __('Inbound Leads & Quotes Queue', 'attention-network'),
            __('Inbound Leads', 'attention-network'),
            'manage_options',
            'attention-network-leads',
            array('Attention_Network_Leads_Admin', 'render_page')
        );

        // Submenu: Before & After Portfolio
        add_submenu_page(
            'attention-network-menus',
            __('Project Showcase & Transformations', 'attention-network'),
            __('Project Portfolio', 'attention-network'),
            'manage_options',
            'attention-network-portfolio',
            array('Attention_Network_Portfolio_Admin', 'render_page')
        );

        // Submenu: Attention Push
        add_submenu_page(
            'attention-network-menus',
            __('Attention Push Notifications', 'attention-network'),
            __('Attention Push', 'attention-network'),
            'manage_options',
            'attention-network-push',
            array('Attention_Network_Push_Admin', 'render_page')
        );

        // Submenu: Settings
        add_submenu_page(
            'attention-network-menus',
            __('Platform Settings', 'attention-network'),
            __('Settings', 'attention-network'),
            'manage_options',
            'attention-network-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Register Settings in Options API
     */
    public static function register_settings() {
        register_setting('attention_network_settings', 'attention_network_api_url', array(
            'type'              => 'string',
            'sanitize_callback' => array('Attention_Network_API_Client', 'sanitize_api_url'),
            'default'           => 'http://localhost:3000',
        ));
        register_setting('attention_network_settings', 'attention_network_org_id', 'sanitize_text_field');
        register_setting('attention_network_settings', 'attention_network_api_key', 'sanitize_text_field');
        register_setting('attention_network_settings', 'attention_network_cache_ttl', 'intval');
    }

    /**
     * Enqueue Admin Assets on Settings Page
     */
    public static function enqueue_settings_assets($hook) {
        if (strpos($hook, 'attention-network') === false) {
            return;
        }

        wp_enqueue_style(
            'attention-network-admin',
            ATTENTION_NETWORK_URL . 'assets/css/admin-menu.css',
            array(),
            ATTENTION_NETWORK_VERSION
        );

        wp_enqueue_script(
            'attention-network-admin',
            ATTENTION_NETWORK_URL . 'assets/js/admin-menu.js',
            array('jquery'),
            ATTENTION_NETWORK_VERSION,
            true
        );

        wp_localize_script('attention-network-admin', 'attnAdmin', array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('attention_network_admin_nonce'),
            'strings'   => array(
                'testing'          => __('Testing connection...', 'attention-network'),
                'connected'        => __('Connection verified successfully!', 'attention-network'),
                'connectionFailed' => __('Connection failed. Please check your credentials.', 'attention-network'),
                'confirmDelete'    => __('Are you sure you want to delete this item? This action cannot be undone.', 'attention-network'),
            ),
        ));
    }

    /**
     * AJAX Test Connection Handler
     */
    public static function ajax_test_connection() {
        check_ajax_referer('attention_network_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission level.', 'attention-network')));
        }

        $result = Attention_Network_API_Client::test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ));
        }

        wp_send_json_success($result);
    }

    /**
     * Render Settings Page
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $apiUrl   = Attention_Network_API_Client::get_api_url();
        $orgId    = Attention_Network_API_Client::get_org_id();
        $apiKey   = Attention_Network_API_Client::get_api_key();
        $cacheTtl = Attention_Network_API_Client::get_cache_ttl();
        $isConfigured = Attention_Network_API_Client::is_configured();
        ?>
        <div class="wrap attn-admin-wrap">
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge">Attention Platform</span>
                    <h1 class="attn-title"><?php esc_html_e('Attention Network Settings', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php esc_html_e('Configure your organization credentials to enable live bi-directional menu management and embeds.', 'attention-network'); ?></p>
                </div>
                <div class="attn-header-right">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=attention-network-menus')); ?>" class="button button-primary">
                        <?php esc_html_e('Manage Food & Menus →', 'attention-network'); ?>
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Settings saved successfully.', 'attention-network'); ?></strong></p>
                </div>
            <?php endif; ?>

            <div class="attn-grid">
                <div class="attn-col-main">
                    <div class="attn-card">
                        <h2 class="attn-card-title"><?php esc_html_e('Organization API Credentials', 'attention-network'); ?></h2>
                        <p class="attn-card-desc"><?php esc_html_e('These credentials authenticate this website with your Attention Network organization profile.', 'attention-network'); ?></p>

                        <form method="post" action="options.php">
                            <?php
                            settings_fields('attention_network_settings');
                            do_settings_sections('attention_network_settings');
                            ?>

                            <table class="form-table attn-form-table">
                                <tr>
                                    <th scope="row"><label for="attention_network_api_url"><?php esc_html_e('API Base URL', 'attention-network'); ?></label></th>
                                    <td>
                                        <input type="url" id="attention_network_api_url" name="attention_network_api_url" value="<?php echo esc_attr($apiUrl); ?>" class="regular-text" placeholder="https://api.attentionnetwork.com" required />
                                        <p class="description"><?php esc_html_e('Default: http://localhost:3000 for local development, or your Attention Platform API URL.', 'attention-network'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="attention_network_org_id"><?php esc_html_e('Organization ID', 'attention-network'); ?></label></th>
                                    <td>
                                        <input type="text" id="attention_network_org_id" name="attention_network_org_id" value="<?php echo esc_attr($orgId); ?>" class="regular-text" placeholder="e.g. org_4f89d..." required />
                                        <p class="description"><?php esc_html_e('Your Attention Network Organization UUID or slug found in Organization Settings.', 'attention-network'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="attention_network_api_key"><?php esc_html_e('API Key / Bearer Token', 'attention-network'); ?></label></th>
                                    <td>
                                        <input type="password" id="attention_network_api_key" name="attention_network_api_key" value="<?php echo esc_attr($apiKey); ?>" class="regular-text" placeholder="••••••••••••••••" />
                                        <p class="description"><?php esc_html_e('Secure token to authenticate writes and modifications. Keep this confidential.', 'attention-network'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><label for="attention_network_cache_ttl"><?php esc_html_e('Frontend Cache (Seconds)', 'attention-network'); ?></label></th>
                                    <td>
                                        <input type="number" id="attention_network_cache_ttl" name="attention_network_cache_ttl" value="<?php echo esc_attr($cacheTtl); ?>" min="0" max="86400" step="60" class="small-text" />
                                        <p class="description"><?php esc_html_e('Time to cache menu data in WordPress transients (Default: 600s = 10 mins). Caches are automatically flushed whenever you save edits in the admin.', 'attention-network'); ?></p>
                                    </td>
                                </tr>
                            </table>

                            <div class="attn-form-actions">
                                <?php submit_button(__('Save Settings', 'attention-network'), 'primary', 'submit', false); ?>
                                <button type="button" id="attn-btn-test-connection" class="button button-secondary" style="margin-left: 10px;">
                                    <?php esc_html_e('Test Connection', 'attention-network'); ?>
                                </button>
                                <span id="attn-connection-status" class="attn-status-text"></span>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="attn-col-sidebar">
                    <div class="attn-card attn-card-info">
                        <h3><?php esc_html_e('Status & Integration', 'attention-network'); ?></h3>
                        <div class="attn-status-row">
                            <span class="attn-status-dot <?php echo $isConfigured ? 'active' : 'inactive'; ?>"></span>
                            <strong><?php echo $isConfigured ? esc_html__('Configured', 'attention-network') : esc_html__('Missing Organization ID', 'attention-network'); ?></strong>
                        </div>
                        <p class="attn-info-p">
                            <?php esc_html_e('Once connected, your menus are accessible anywhere with shortcodes or Gutenberg blocks:', 'attention-network'); ?>
                        </p>
                        <code class="attn-code">[attention_menu]</code>
                        <code class="attn-code">[attention_menu layout="taplist"]</code>
                        <code class="attn-code">[attention_menu layout="qr"]</code>
                    </div>

                    <div class="attn-card">
                        <h3><?php esc_html_e('Need Help?', 'attention-network'); ?></h3>
                        <p><?php esc_html_e('To generate an API key or find your Organization ID, log in to your Attention Admin Console at /settings/integrations.', 'attention-network'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

<?php
/**
 * Settings & Admin Navigation for Attention Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Settings {

    public static function render_unavailable($title) {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap attn-admin-wrap"><h1>'.esc_html($title).'</h1><div class="notice notice-info"><p>'.esc_html__('This feature is managed in the Attention Network app. This plugin does not have a compatible connection for these actions, and will not save a separate copy or report an unsent update as successful.', 'attention-network').'</p><p><a class="button button-primary" href="https://app.attn.systems/" target="_blank" rel="noopener noreferrer">'.esc_html__('Open Attention Network', 'attention-network').'</a></p></div></div>';
    }

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_admin_menus'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_init', array(__CLASS__, 'retire_shared_credentials'));
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
            'default'           => 'https://app.attentionnetwork.net/api',
        ));
        register_setting('attention_network_settings', 'attention_network_org_id', array('sanitize_callback'=>array(__CLASS__, 'sanitize_org')));
        register_setting('attention_network_settings', 'attention_network_site_id', array('sanitize_callback'=>array(__CLASS__, 'sanitize_site')));
    }

    public static function sanitize_org($value) {
        $value = trim(sanitize_text_field($value));
        if (Attention_Network_API_Client::valid_uuid($value)) return strtolower($value);
        add_settings_error('attention_network_org_id', 'invalid_org', __('Enter the organization UUID shown by the platform.', 'attention-network'));
        return Attention_Network_API_Client::get_org_id();
    }

    public static function sanitize_site($value) {
        $value = trim(sanitize_text_field($value));
        if ($value === '' || Attention_Network_API_Client::valid_uuid($value)) return strtolower($value);
        add_settings_error('attention_network_site_id', 'invalid_site', __('Enter a site UUID, or leave it blank to disable public menu embeds.', 'attention-network'));
        return Attention_Network_API_Client::get_site_id();
    }

    public static function retire_shared_credentials() {
        if (current_user_can('manage_options') && get_option('attention_network_api_key', false) !== false) {
            delete_option('attention_network_api_key');
            Attention_Network_API_Client::purge_menu_cache();
        }
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
        if (!current_user_can('manage_options')) return;
        $status = Attention_Network_Platform_Session::status();
        $connected = !is_wp_error($status) && !empty($status['connected']);
        ?>
        <div class="wrap attn-admin-wrap">
            <h1><?php esc_html_e('Attention Network Settings', 'attention-network'); ?></h1>
            <p><?php esc_html_e('Connect this website to its organization. Public menus use the site’s publishing rules. Management uses your own platform permissions.', 'attention-network'); ?></p>
            <?php settings_errors(); ?>
            <div class="attn-card">
                <h2><?php esc_html_e('Website connection', 'attention-network'); ?></h2>
                <form method="post" action="options.php">
                    <?php settings_fields('attention_network_settings'); ?>
                    <table class="form-table"><tbody>
                        <tr><th><label for="attention_network_api_url"><?php esc_html_e('Platform API address', 'attention-network'); ?></label></th><td><input class="regular-text" type="url" id="attention_network_api_url" name="attention_network_api_url" value="<?php echo esc_attr(Attention_Network_API_Client::get_api_url()); ?>" required /><p class="description">https://app.attentionnetwork.net/api</p></td></tr>
                        <tr><th><label for="attention_network_org_id"><?php esc_html_e('Organization UUID', 'attention-network'); ?></label></th><td><input class="regular-text" id="attention_network_org_id" name="attention_network_org_id" value="<?php echo esc_attr(Attention_Network_API_Client::get_org_id()); ?>" required pattern="[a-fA-F0-9-]{36}" /><p class="description"><?php esc_html_e('Use the organization UUID, not its Relationship ID or name.', 'attention-network'); ?></p></td></tr>
                        <tr><th><label for="attention_network_site_id"><?php esc_html_e('Public site UUID', 'attention-network'); ?></label></th><td><input class="regular-text" id="attention_network_site_id" name="attention_network_site_id" value="<?php echo esc_attr(Attention_Network_API_Client::get_site_id()); ?>" pattern="[a-fA-F0-9-]{36}" /><p class="description"><?php esc_html_e('The site/app publishing this organization. Required for public menu embeds. Only active, eligible public menus are displayed; private management data is never used as a fallback.', 'attention-network'); ?></p></td></tr>
                    </tbody></table>
                    <?php submit_button(__('Save connection', 'attention-network')); ?>
                </form>
                <p><?php esc_html_e('Save connection changes before signing in. Switching the API address or organization requires a separate platform connection.', 'attention-network'); ?></p>
            </div>
            <div class="attn-card">
                <h2><?php esc_html_e('Your platform account', 'attention-network'); ?></h2>
                <p><?php esc_html_e('This sign-in belongs only to your current WordPress user and browser session. Your password is never stored. Other WordPress administrators must sign in with their own platform accounts.', 'attention-network'); ?></p>
                <p id="attn-platform-status" role="status" aria-live="polite"><?php echo $connected ? esc_html(sprintf(__('Connected as %s', 'attention-network'), $status['email'] ?? '')) : esc_html__('Not connected. Sign in to manage menus, hours, and review replies.', 'attention-network'); ?></p>
                <form id="attn-platform-login" autocomplete="on">
                    <p><label for="attn-platform-email"><?php esc_html_e('Platform email', 'attention-network'); ?></label><br/><input type="email" id="attn-platform-email" class="regular-text" autocomplete="username" required /></p>
                    <p><label for="attn-platform-password"><?php esc_html_e('Platform password', 'attention-network'); ?></label><br/><input type="password" id="attn-platform-password" class="regular-text" autocomplete="current-password" required /></p>
                    <button class="button button-primary" type="submit"><?php esc_html_e('Sign in to platform', 'attention-network'); ?></button>
                </form>
                <p><button type="button" class="button" id="attn-platform-logout"><?php esc_html_e('Disconnect this session', 'attention-network'); ?></button> <button type="button" class="button" id="attn-btn-test-connection"><?php esc_html_e('Check menu access', 'attention-network'); ?></button> <span id="attn-connection-status" role="status" aria-live="polite"></span></p>
            </div>
            <div class="attn-card"><h2><?php esc_html_e('Available website content', 'attention-network'); ?></h2><p><code>[attention_menu]</code> <code>[attention_menu layout="taplist"]</code> <code>[attention_menu layout="qr"]</code> <code>[attention_reviews]</code></p><p><?php esc_html_e('Inquiries and project portfolios are WordPress-local features. Staff, offer management, push subscriptions, and public hours widgets require their platform or directory integration; this plugin does not pretend to synchronize them.', 'attention-network'); ?></p></div>
        </div>
        <?php
    }
}

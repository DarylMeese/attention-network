<?php
/**
 * Plugin Name: Attention Network
 * Plugin URI: https://attentionnetwork.com
 * Description: Connect your website directly to your Attention Network organization. Manage your food and drink menus directly from WordPress, sync with the platform in real time, and display menus anywhere using shortcodes or Gutenberg blocks.
 * Version: 1.0.0
 * Author: Attention Network
 * Author URI: https://attentionnetwork.com
 * License: GPLv2 or later
 * Text Domain: attention-network
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ATTENTION_NETWORK_VERSION', '1.0.0');
define('ATTENTION_NETWORK_PATH', plugin_dir_path(__FILE__));
define('ATTENTION_NETWORK_URL', plugin_dir_url(__FILE__));
define('ATTENTION_NETWORK_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader / Includes
 */
require_once ATTENTION_NETWORK_PATH . 'includes/class-api-client.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-settings.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-menu-admin.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-business-admin.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-staff-admin.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-offers-admin.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-reviews-admin.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-leads-admin.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-portfolio-admin.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-push-admin.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-shortcodes.php';
require_once ATTENTION_NETWORK_PATH . 'includes/class-blocks.php';

/**
 * Initialize Plugin Components
 */
function attention_network_init() {
    Attention_Network_Settings::init();
    Attention_Network_Menu_Admin::init();
    Attention_Network_Business_Admin::init();
    Attention_Network_Staff_Admin::init();
    Attention_Network_Offers_Admin::init();
    Attention_Network_Reviews_Admin::init();
    Attention_Network_Leads_Admin::init();
    Attention_Network_Portfolio_Admin::init();
    Attention_Network_Push_Admin::init();
    Attention_Network_Shortcodes::init();
    Attention_Network_Blocks::init();

    // Register frontend styles
    add_action('wp_enqueue_scripts', 'attention_network_enqueue_frontend_assets');
}
add_action('plugins_loaded', 'attention_network_init');

/**
 * Enqueue Frontend Assets
 */
function attention_network_enqueue_frontend_assets() {
    wp_register_style(
        'attention-network-menus',
        ATTENTION_NETWORK_URL . 'assets/css/frontend-menu.css',
        array(),
        ATTENTION_NETWORK_VERSION
    );
}

/**
 * Activation Hook
 */
register_activation_hook(__FILE__, function () {
    if (!get_option('attention_network_api_url')) {
        update_option('attention_network_api_url', 'http://localhost:3000');
    }
});

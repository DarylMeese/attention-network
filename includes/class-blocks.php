<?php
/**
 * Gutenberg Block Registration for Attention Network Food & Drink Menus
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Blocks {

    public static function init() {
        add_action('init', array(__CLASS__, 'register_block'));
        add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueue_block_editor_assets'));
    }

    /**
     * Register dynamic Gutenberg block
     */
    public static function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type('attention-network/food-menu', array(
            'api_version'     => 2,
            'attributes'      => array(
                'menuId'      => array('type' => 'string', 'default' => ''),
                'layout'      => array('type' => 'string', 'default' => ''),
                'theme'       => array('type' => 'string', 'default' => ''),
                'showImages'  => array('type' => 'boolean', 'default' => true),
                'showPrices'  => array('type' => 'boolean', 'default' => true),
            ),
            'render_callback' => array(__CLASS__, 'render_block'),
        ));
    }

    /**
     * Enqueue block editor assets
     */
    public static function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'attention-network-block-editor',
            ATTENTION_NETWORK_URL . 'assets/js/block.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor'),
            ATTENTION_NETWORK_VERSION,
            true
        );

        // Fetch menus to pass to Gutenberg inspector
        $menus = array();
        if (Attention_Network_API_Client::is_configured()) {
            $resp = Attention_Network_API_Client::get_menus();
            if (!is_wp_error($resp) && is_array($resp)) {
                $menus = $resp;
            }
        }

        wp_localize_script('attention-network-block-editor', 'attnBlockData', array(
            'menus' => $menus,
            'i18n'  => array(
                'title'       => __('Attention Food Menu', 'attention-network'),
                'description' => __('Display a live synced food or drink menu from Attention Network.', 'attention-network'),
                'selectMenu'  => __('Select Menu', 'attention-network'),
                'layout'      => __('Display Layout', 'attention-network'),
                'theme'       => __('Color Theme', 'attention-network'),
            ),
        ));
    }

    /**
     * Render block server-side
     */
    public static function render_block($attributes) {
        return Attention_Network_Shortcodes::render_menu(array(
            'id'          => $attributes['menuId'] ?? '',
            'layout'      => $attributes['layout'] ?? '',
            'theme'       => $attributes['theme'] ?? '',
            'show_images' => !empty($attributes['showImages']) ? 'yes' : 'no',
            'show_prices' => !empty($attributes['showPrices']) ? 'yes' : 'no',
        ));
    }
}

<?php
/**
 * Attention Push Admin Interface (Web Push Broadcasts & Opt-In Configuration)
 *
 * Implements strict government & enterprise security standards:
 * - Cryptographic nonce verification on push broadcast submissions
 * - Capability enforcement (current_user_can('manage_options'))
 * - URL and payload validation
 * - Output escaping
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Push_Admin {

    public static function init() {
        add_action('wp_ajax_attention_network_send_push', array(__CLASS__, 'handle_send_push'));
        add_action('wp_ajax_attention_network_save_push_settings', array(__CLASS__, 'handle_save_push_settings'));
    }

    /**
     * Common security guard for AJAX
     */
    private static function verify_ajax_security() {
        check_ajax_referer('attention_network_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission level.', 'attention-network')), 403);
            exit;
        }

        if (!Attention_Network_API_Client::is_configured()) {
            wp_send_json_error(array('message' => __('Attention Network API is not configured.', 'attention-network')), 400);
            exit;
        }
    }

    /**
     * Render Attention Push Admin Screen
     */
    public static function render_page() { Attention_Network_Settings::render_unavailable('Push Notifications'); }

    /**
     * AJAX Handler: Send Push Notification
     */
    public static function handle_send_push() {
        self::verify_ajax_security();

        $title   = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $body    = isset($_POST['body']) ? sanitize_textarea_field($_POST['body']) : '';
        $url     = isset($_POST['url']) ? esc_url_raw($_POST['url']) : home_url('/');
        $segment = isset($_POST['segment']) ? sanitize_text_field($_POST['segment']) : '';

        if (empty($title) || empty($body)) {
            wp_send_json_error(array('message' => __('Title and message body are required.', 'attention-network')), 400);
        }

        $payload = array(
            'title'       => $title,
            'body'        => $body,
            'redirectUrl' => $url,
            'filterTag'   => !empty($segment) ? $segment : null,
        );

        $result = Attention_Network_API_Client::send_push_notification($payload);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Attention Push broadcast sent successfully.', 'attention-network')));
    }

    /**
     * AJAX Handler: Save Push Settings
     */
    public static function handle_save_push_settings() { self::verify_ajax_security(); $error = Attention_Network_API_Client::unsupported('This action'); wp_send_json_error(array('message' => $error->get_error_message()), 409); }
}

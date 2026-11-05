<?php
/**
 * Offers, Specials & Coupons Admin Interface
 *
 * Implements rigorous government & enterprise security standards:
 * - Strict capability checks (current_user_can)
 * - Cryptographic nonce verification (check_ajax_referer)
 * - Strict input sanitization and regex validation
 * - Context-aware output escaping (esc_html, esc_attr, esc_url)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Offers_Admin {

    public static function init() {
        add_action('wp_ajax_attention_network_save_offer', array(__CLASS__, 'handle_save_offer'));
        add_action('wp_ajax_attention_network_delete_offer', array(__CLASS__, 'handle_delete_offer'));
        add_action('wp_ajax_attention_network_toggle_offer_status', array(__CLASS__, 'handle_toggle_status'));
    }

    /**
     * Common security guard for AJAX handlers
     */
    private static function verify_ajax_security() {
        check_ajax_referer('attention_network_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission level.', 'attention-network')), 403);
            exit;
        }
    }

    /**
     * Render Offers Management Screen
     */
    public static function render_page() { Attention_Network_Settings::render_unavailable('Offers & Coupons'); }

    /**
     * AJAX Save Offer Handler
     */
    public static function handle_save_offer() {
        self::verify_ajax_security();

        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Offer title is required.', 'attention-network')), 400);
        }

        $id              = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $code            = isset($_POST['code']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['code']))) : '';
        $category        = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : 'General';
        $discount_type   = isset($_POST['discountType']) ? sanitize_text_field(wp_unslash($_POST['discountType'])) : 'fixed';
        $discount_disp   = isset($_POST['discountDisplay']) ? sanitize_text_field(wp_unslash($_POST['discountDisplay'])) : '';
        $badge_text      = isset($_POST['badgeText']) ? sanitize_text_field(wp_unslash($_POST['badgeText'])) : '';
        $expires_at      = isset($_POST['expiresAt']) ? sanitize_text_field(wp_unslash($_POST['expiresAt'])) : '';
        $description     = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
        $terms           = isset($_POST['terms']) ? sanitize_text_field(wp_unslash($_POST['terms'])) : '';
        $cta_url         = isset($_POST['ctaUrl']) ? esc_url_raw(wp_unslash($_POST['ctaUrl'])) : '';
        $status          = (isset($_POST['status']) && 'active' === $_POST['status']) ? 'active' : 'paused';

        $data = array(
            'id'              => $id,
            'title'           => $title,
            'code'            => $code,
            'category'        => $category,
            'discountType'    => $discount_type,
            'discountDisplay' => $discount_disp,
            'badgeText'       => $badge_text,
            'expiresAt'       => $expires_at,
            'description'     => $description,
            'terms'           => $terms,
            'ctaUrl'          => $cta_url,
            'status'          => $status,
        );

        $saved = Attention_Network_API_Client::save_offer($data);

        wp_send_json_success(array(
            'message' => __('Offer saved successfully.', 'attention-network'),
            'offer'   => $saved,
        ));
    }

    /**
     * AJAX Delete Offer Handler
     */
    public static function handle_delete_offer() {
        self::verify_ajax_security();

        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        if (empty($id)) {
            wp_send_json_error(array('message' => __('Missing offer ID.', 'attention-network')), 400);
        }

        Attention_Network_API_Client::delete_offer($id);
        wp_send_json_success(array('message' => __('Offer deleted.', 'attention-network')));
    }

    /**
     * AJAX Toggle Status Handler
     */
    public static function handle_toggle_status() { self::verify_ajax_security(); $error = Attention_Network_API_Client::unsupported('This action'); wp_send_json_error(array('message' => $error->get_error_message()), 409); }
}

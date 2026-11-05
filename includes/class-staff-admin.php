<?php
/**
 * Staff Directory & "Meet Your Pro" Admin Interface
 *
 * Provides operations to manage staff members, field service technicians,
 * stylists, medical professionals, and company leadership.
 *
 * Implements strict security:
 * - Nonce verification on all operations
 * - Capability checks
 * - Output escaping and input sanitization
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Staff_Admin {

    public static function init() {
        $ajax_actions = array(
            'save_staff_member',
            'delete_staff_member',
        );

        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_attention_network_' . $action, array(__CLASS__, 'handle_' . $action));
        }
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
     * Render Staff & Field Pros Admin Screen
     */
    public static function render_page() { Attention_Network_Settings::render_unavailable('Staff & Field Pros'); }

    /**
     * AJAX Handler: Save Staff Member
     */
    public static function handle_save_staff_member() {
        self::verify_ajax_security();

        $card_id     = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
        $first_name  = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name   = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $title       = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $license     = isset($_POST['license']) ? sanitize_text_field($_POST['license']) : '';
        $phone       = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $email       = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $photo       = isset($_POST['photo']) ? esc_url_raw($_POST['photo']) : '';
        $bio         = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
        $tip_url     = isset($_POST['tip_url']) ? esc_url_raw($_POST['tip_url']) : '';
        $booking_url = isset($_POST['booking_url']) ? esc_url_raw($_POST['booking_url']) : '';

        if (empty($first_name) || empty($last_name) || empty($title)) {
            wp_send_json_error(array('message' => __('First name, last name, and title are required.', 'attention-network')), 400);
        }

        $links = array();
        if (!empty($tip_url)) {
            $links[] = array(
                'label'     => __('Direct Tip / Gratuity', 'attention-network'),
                'url'       => $tip_url,
                'linkKind'  => 'generic',
                'sortOrder' => 1,
                'isActive'  => true,
            );
        }
        if (!empty($booking_url)) {
            $links[] = array(
                'label'     => __('Book with Me', 'attention-network'),
                'url'       => $booking_url,
                'linkKind'  => 'booking',
                'sortOrder' => 2,
                'isActive'  => true,
            );
        }

        $payload = array(
            'firstName'     => $first_name,
            'lastName'      => $last_name,
            'title'         => $title,
            'licenseNumber' => !empty($license) ? $license : null,
            'phone'         => !empty($phone) ? $phone : null,
            'email'         => !empty($email) ? $email : null,
            'profileImage'  => !empty($photo) ? $photo : null,
            'bio'           => !empty($bio) ? $bio : null,
            'layoutKey'     => 'standard',
            'status'        => 'active',
            'links'         => $links,
        );

        if (!empty($card_id)) {
            $result = Attention_Network_API_Client::update_staff_member($card_id, $payload);
        } else {
            $result = Attention_Network_API_Client::create_staff_member($payload);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Staff member saved successfully.', 'attention-network')));
    }

    /**
     * AJAX Handler: Delete Staff Member
     */
    public static function handle_delete_staff_member() {
        self::verify_ajax_security();

        $card_id = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
        if (empty($card_id)) {
            wp_send_json_error(array('message' => __('Staff ID is required.', 'attention-network')), 400);
        }

        $result = Attention_Network_API_Client::delete_staff_member($card_id);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Staff member removed.', 'attention-network')));
    }
}

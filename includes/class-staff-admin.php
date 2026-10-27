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
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'attention-network'));
        }

        $cards_response = Attention_Network_API_Client::get_staff_members(false);
        $cards = (!is_wp_error($cards_response) && isset($cards_response['cards']))
            ? $cards_response['cards']
            : (is_array($cards_response) ? $cards_response : array());
        ?>
        <div class="wrap attn-admin-wrap">
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge"><?php esc_html_e('Security & Team', 'attention-network'); ?></span>
                    <h1 class="attn-title"><?php esc_html_e('Staff Directory & "Meet Your Pro"', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php esc_html_e('Manage staff profiles, field service technician verification cards, master license numbers, and direct tipping links.', 'attention-network'); ?></p>
                </div>
                <div class="attn-header-right">
                    <button type="button" id="attn-btn-add-staff" class="button button-primary">
                        <?php esc_html_e('+ Add Team Member / Pro', 'attention-network'); ?>
                    </button>
                </div>
            </div>

            <!-- Modal / Add Form -->
            <div id="attn-staff-modal" class="attn-modal" style="display:none;">
                <div class="attn-modal-content" style="max-width:680px;background:#fff;border-radius:12px;padding:24px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
                    <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:12px;margin-bottom:16px;">
                        <h2 id="attn-staff-modal-title" style="margin:0;font-size:18px;"><?php esc_html_e('Add Team Member / Field Pro', 'attention-network'); ?></h2>
                        <button type="button" class="attn-modal-close" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
                    </div>

                    <form id="attn-staff-form">
                        <input type="hidden" id="attn_staff_card_id" value="" />
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('First Name', 'attention-network'); ?> *</label>
                                <input type="text" id="attn_staff_first_name" required class="regular-text" style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Last Name', 'attention-network'); ?> *</label>
                                <input type="text" id="attn_staff_last_name" required class="regular-text" style="width:100%;" />
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:12px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Title / Specialty', 'attention-network'); ?> *</label>
                                <input type="text" id="attn_staff_title" placeholder="e.g. Master Plumber, Senior Stylist" required class="regular-text" style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Master License / Cert #', 'attention-network'); ?></label>
                                <input type="text" id="attn_staff_license" placeholder="e.g. Lic #HVAC-82914" class="regular-text" style="width:100%;" />
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:12px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Direct Phone', 'attention-network'); ?></label>
                                <input type="tel" id="attn_staff_phone" class="regular-text" style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Direct Email', 'attention-network'); ?></label>
                                <input type="email" id="attn_staff_email" class="regular-text" style="width:100%;" />
                            </div>
                        </div>

                        <div style="margin-top:12px;">
                            <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Photo / Avatar URL', 'attention-network'); ?></label>
                            <input type="url" id="attn_staff_photo" placeholder="https://..." class="regular-text" style="width:100%;" />
                        </div>

                        <div style="margin-top:12px;">
                            <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Bio & Credentials', 'attention-network'); ?></label>
                            <textarea id="attn_staff_bio" rows="3" class="regular-text" style="width:100%;" placeholder="10+ years experience, background checked, EPA certified..."></textarea>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:12px;">
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Tipping Link (CashApp/Venmo)', 'attention-network'); ?></label>
                                <input type="url" id="attn_staff_tip_url" placeholder="https://venmo.com/..." class="regular-text" style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Booking / Appointment Link', 'attention-network'); ?></label>
                                <input type="url" id="attn_staff_booking_url" placeholder="https://..." class="regular-text" style="width:100%;" />
                            </div>
                        </div>

                        <div style="margin-top:16px;">
                            <label style="font-weight:600;">
                                <input type="checkbox" id="attn_staff_bg_checked" value="1" checked />
                                <?php esc_html_e('Verified Background Check Cleared', 'attention-network'); ?>
                            </label>
                        </div>

                        <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
                            <button type="button" class="button attn-modal-close"><?php esc_html_e('Cancel', 'attention-network'); ?></button>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Save Team Member', 'attention-network'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Team Members Grid -->
            <div class="attn-grid" style="margin-top:20px;">
                <div class="attn-col-main">
                    <div class="attn-card">
                        <?php if (empty($cards)): ?>
                            <div style="text-align:center;padding:48px 16px;color:#64748b;">
                                <div style="font-size:36px;margin-bottom:12px;">🛡️</div>
                                <h3 style="margin:0 0 8px 0;"><?php esc_html_e('No Team Members Added Yet', 'attention-network'); ?></h3>
                                <p style="margin:0 0 16px 0;"><?php esc_html_e('Add technicians, stylists, or professionals to display credentials and direct tipping cards.', 'attention-network'); ?></p>
                                <button type="button" class="button button-primary attn-trigger-add-staff">
                                    <?php esc_html_e('+ Add First Pro', 'attention-network'); ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:20px;">
                                <?php foreach ($cards as $c): 
                                    $cid = $c['businessCardId'] ?? ($c['id'] ?? '');
                                    $contact = $c['contact'] ?? $c;
                                    $fname = $contact['firstName'] ?? '';
                                    $lname = $contact['lastName'] ?? '';
                                    $fullname = trim($fname . ' ' . $lname) ?: ($c['name'] ?? __('Team Member', 'attention-network'));
                                    $title = $contact['title'] ?? ($c['title'] ?? '');
                                    $photo = $c['profileImage'] ?? ($contact['profileImage'] ?? '');
                                    $license = $c['licenseNumber'] ?? ($contact['licenseNumber'] ?? '');
                                ?>
                                    <div class="attn-pro-card" style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;background:#fff;display:flex;flex-direction:column;justify-content:between;">
                                        <div style="display:flex;gap:12px;align-items:center;">
                                            <?php if (!empty($photo)): ?>
                                                <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($fullname); ?>" style="width:52px;height:52px;border-radius:999px;object-fit:cover;border:2px solid #e2e8f0;" />
                                            <?php else: ?>
                                                <div style="width:52px;height:52px;border-radius:999px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:700;color:#64748b;">
                                                    <?php echo esc_html(strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1))); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h3 style="margin:0;font-size:15px;font-weight:700;"><?php echo esc_html($fullname); ?></h3>
                                                <div style="font-size:13px;color:#0284c7;font-weight:500;"><?php echo esc_html($title); ?></div>
                                                <?php if (!empty($license)): ?>
                                                    <div style="font-size:11px;color:#64748b;font-family:monospace;"><?php echo esc_html($license); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div style="margin-top:12px;border-top:1px solid #f1f5f9;padding-top:12px;display:flex;justify-content:space-between;align-items:center;">
                                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:999px;">
                                                ✓ <?php esc_html_e('Verified Pro', 'attention-network'); ?>
                                            </span>
                                            <div>
                                                <button type="button" class="button-link-delete attn-delete-staff" data-card-id="<?php echo esc_attr($cid); ?>">
                                                    <?php esc_html_e('Remove', 'attention-network'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="attn-col-sidebar">
                    <div class="attn-card attn-card-info">
                        <h3><?php esc_html_e('Security & Embeds', 'attention-network'); ?></h3>
                        <p class="attn-info-p">
                            <?php esc_html_e('Display your verified team on your website:', 'attention-network'); ?>
                        </p>
                        <code class="attn-code">[attention_staff_directory]</code>
                        <p class="attn-info-p" style="margin-top:8px;">
                            <?php esc_html_e('Single technician safety badge for confirmation emails or booking pages:', 'attention-network'); ?>
                        </p>
                        <code class="attn-code">[attention_pro id="..."]</code>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

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

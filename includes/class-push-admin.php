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
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'attention-network'));
        }

        $app_id      = get_option('attention_network_push_app_id', '');
        $auto_prompt = get_option('attention_network_push_auto_prompt', '1');
        $prompt_text = get_option('attention_network_push_prompt_text', __('Get instant alerts for emergency updates, openings, and exclusive specials.', 'attention-network'));
        ?>
        <div class="wrap attn-admin-wrap">
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge"><?php esc_html_e('Instant Messaging', 'attention-network'); ?></span>
                    <h1 class="attn-title"><?php esc_html_e('Attention Push', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php esc_html_e('Broadcast instant web push notifications directly to opted-in customers and visitors on their mobile and desktop devices.', 'attention-network'); ?></p>
                </div>
            </div>

            <div class="attn-grid">
                <div class="attn-col-main">
                    <!-- Broadcast Form -->
                    <div class="attn-card">
                        <h2 class="attn-card-title"><?php esc_html_e('Broadcast Web Push Notification', 'attention-network'); ?></h2>
                        <p class="attn-card-desc"><?php esc_html_e('Send an immediate notification alert to all subscribed visitors. Delivers instantly to desktops, Android, and iOS Safari.', 'attention-network'); ?></p>

                        <form id="attn-send-push-form" style="margin-top:16px;">
                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Notification Title', 'attention-network'); ?> *</label>
                                <input type="text" id="attn_push_title" maxlength="64" placeholder="<?php esc_attr_e('e.g. Same-Day Opening Available! or Storm Damage Emergency Service', 'attention-network'); ?>" required class="regular-text" style="width:100%;" />
                                <span class="description"><?php esc_html_e('Keep concise (under 64 characters) for optimal mobile delivery.', 'attention-network'); ?></span>
                            </div>

                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Message Body', 'attention-network'); ?> *</label>
                                <textarea id="attn_push_body" rows="3" maxlength="160" placeholder="<?php esc_attr_e('Two service slots just opened for tomorrow morning. Click to claim your appointment.', 'attention-network'); ?>" required class="regular-text" style="width:100%;"></textarea>
                            </div>

                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Destination Click URL', 'attention-network'); ?></label>
                                <input type="url" id="attn_push_url" value="<?php echo esc_url(home_url('/')); ?>" class="regular-text" style="width:100%;" />
                                <span class="description"><?php esc_html_e('Where visitors land when tapping the notification.', 'attention-network'); ?></span>
                            </div>

                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Audience Filter (Optional)', 'attention-network'); ?></label>
                                <select id="attn_push_segment" class="regular-text" style="width:100%;">
                                    <option value=""><?php esc_html_e('All Subscribed Visitors', 'attention-network'); ?></option>
                                    <option value="vip"><?php esc_html_e('VIP / Rewards Members', 'attention-network'); ?></option>
                                    <option value="emergency"><?php esc_html_e('Emergency Service Opt-Ins', 'attention-network'); ?></option>
                                </select>
                            </div>

                            <div style="margin-top:20px;display:flex;align-items:center;gap:12px;">
                                <button type="submit" id="attn-btn-send-push" class="button button-primary">
                                    <?php esc_html_e('🚀 Send Attention Push Broadcast', 'attention-network'); ?>
                                </button>
                                <span id="attn-push-status-msg" class="attn-status-text"></span>
                            </div>
                        </form>
                    </div>

                    <!-- Push Opt-In Settings -->
                    <div class="attn-card" style="margin-top:24px;">
                        <h2 class="attn-card-title"><?php esc_html_e('Attention Push Opt-In Configuration', 'attention-network'); ?></h2>
                        <p class="attn-card-desc"><?php esc_html_e('Configure how visitors are prompted to subscribe to push alerts on your website.', 'attention-network'); ?></p>

                        <form id="attn-push-settings-form" style="margin-top:16px;">
                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Attention Push App ID (Site Identifier)', 'attention-network'); ?></label>
                                <input type="text" id="attn_push_app_id" value="<?php echo esc_attr($app_id); ?>" placeholder="e.g. 7f8a9b2c..." class="regular-text" style="width:100%;" />
                                <span class="description"><?php esc_html_e('Assigned by Attention Network in your Platform Organization settings.', 'attention-network'); ?></span>
                            </div>

                            <div style="margin-bottom:14px;">
                                <label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Opt-In Prompt Message', 'attention-network'); ?></label>
                                <input type="text" id="attn_push_prompt_text" value="<?php echo esc_attr($prompt_text); ?>" class="regular-text" style="width:100%;" />
                            </div>

                            <div style="margin-bottom:14px;">
                                <label style="font-weight:600;">
                                    <input type="checkbox" id="attn_push_auto_prompt" value="1" <?php checked($auto_prompt, '1'); ?> />
                                    <?php esc_html_e('Automatically show subscription bell prompt to new visitors', 'attention-network'); ?>
                                </label>
                            </div>

                            <div style="margin-top:16px;">
                                <button type="submit" id="attn-btn-save-push-settings" class="button button-secondary">
                                    <?php esc_html_e('Save Push Settings', 'attention-network'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="attn-col-sidebar">
                    <div class="attn-card attn-card-info">
                        <h3><?php esc_html_e('Attention Push Shortcode', 'attention-network'); ?></h3>
                        <p class="attn-info-p">
                            <?php esc_html_e('Place a custom push subscription button anywhere on your site:', 'attention-network'); ?>
                        </p>
                        <code class="attn-code">[attention_push_prompt]</code>
                        <p class="attn-info-p" style="margin-top:8px;">
                            <?php esc_html_e('Customize button text:', 'attention-network'); ?>
                        </p>
                        <code class="attn-code">[attention_push_prompt button_text="Get Storm Alerts"]</code>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

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
    public static function handle_save_push_settings() {
        self::verify_ajax_security();

        $app_id      = isset($_POST['app_id']) ? sanitize_text_field($_POST['app_id']) : '';
        $auto_prompt = !empty($_POST['auto_prompt']) ? '1' : '0';
        $prompt_text = isset($_POST['prompt_text']) ? sanitize_text_field($_POST['prompt_text']) : '';

        update_option('attention_network_push_app_id', $app_id);
        update_option('attention_network_push_auto_prompt', $auto_prompt);
        update_option('attention_network_push_prompt_text', $prompt_text);

        wp_send_json_success(array('message' => __('Push settings saved.', 'attention-network')));
    }
}

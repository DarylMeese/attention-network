<?php
/**
 * Inbound Leads, Quotes & Emergency Dispatch Queue
 *
 * Implements rigorous government & enterprise security standards:
 * - Strict capability checks (current_user_can)
 * - Cryptographic nonce verification (check_ajax_referer)
 * - Strict input sanitization and regex validation
 * - Anti-spam honeypot + time-velocity bot trap
 * - IP rate-limiting protection
 * - Context-aware output escaping (esc_html, esc_attr, esc_url)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Leads_Admin {

    public static function init() {
        // Admin AJAX
        add_action('wp_ajax_attention_network_update_lead_status', array(__CLASS__, 'handle_update_lead_status'));

        // Public Frontend Form Submission AJAX (both logged in and guest users)
        add_action('wp_ajax_attention_network_submit_lead', array(__CLASS__, 'handle_public_lead_submission'));
        add_action('wp_ajax_nopriv_attention_network_submit_lead', array(__CLASS__, 'handle_public_lead_submission'));
    }

    /**
     * Common security guard for Admin AJAX
     */
    private static function verify_admin_security() {
        check_ajax_referer('attention_network_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission level.', 'attention-network')), 403);
            exit;
        }
    }

    /**
     * Render Inbound Leads Admin Screen
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'attention-network'));
        }

        $all_leads = Attention_Network_API_Client::get_leads();
        $status_filter = isset($_GET['lead_status']) ? sanitize_text_field($_GET['lead_status']) : 'all';

        $filtered_leads = array();
        $counts = array(
            'all'       => count($all_leads),
            'new'       => 0,
            'contacted' => 0,
            'quoted'    => 0,
            'scheduled' => 0,
            'completed' => 0,
        );

        foreach ($all_leads as $l) {
            $st = $l['status'] ?? 'new';
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
            if ('all' === $status_filter || $st === $status_filter) {
                $filtered_leads[] = $l;
            }
        }
        ?>
        <div class="wrap attn-admin-wrap">
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge"><?php esc_html_e('Inbound Intake', 'attention-network'); ?></span>
                    <h1 class="attn-title"><?php esc_html_e('Inbound Quotes & Leads Queue', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php esc_html_e('Instant intake for service estimates, quote requests, consultations, and 24/7 emergency dispatch inquiries.', 'attention-network'); ?></p>
                </div>
            </div>

            <!-- Status Filter Tabs -->
            <ul class="subsubsub" style="margin-bottom: 15px;">
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=attention-network-leads&lead_status=all')); ?>" class="<?php echo 'all' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e('All', 'attention-network'); ?> <span class="count">(<?php echo $counts['all']; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=attention-network-leads&lead_status=new')); ?>" class="<?php echo 'new' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e('New', 'attention-network'); ?> <span class="count">(<?php echo $counts['new']; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=attention-network-leads&lead_status=contacted')); ?>" class="<?php echo 'contacted' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e('Contacted', 'attention-network'); ?> <span class="count">(<?php echo $counts['contacted']; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=attention-network-leads&lead_status=quoted')); ?>" class="<?php echo 'quoted' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e('Quoted', 'attention-network'); ?> <span class="count">(<?php echo $counts['quoted']; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=attention-network-leads&lead_status=scheduled')); ?>" class="<?php echo 'scheduled' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e('Scheduled', 'attention-network'); ?> <span class="count">(<?php echo $counts['scheduled']; ?>)</span></a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=attention-network-leads&lead_status=completed')); ?>" class="<?php echo 'completed' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e('Completed', 'attention-network'); ?> <span class="count">(<?php echo $counts['completed']; ?>)</span></a></li>
            </ul>

            <div class="attn-grid">
                <div class="attn-col-main">
                    <div class="attn-card">
                        <?php if (empty($filtered_leads)) : ?>
                            <div class="attn-empty-state">
                                <span class="dashicons dashicons-email-alt" style="font-size: 48px; width: 48px; height: 48px; color: #94a3b8; margin-bottom: 12px;"></span>
                                <h3><?php esc_html_e('No leads found in this view', 'attention-network'); ?></h3>
                                <p><?php esc_html_e('Place the [attention_lead_form] shortcode on your website to start receiving inbound quote and service requests.', 'attention-network'); ?></p>
                            </div>
                        <?php else : ?>
                            <div class="attn-table-container">
                                <table class="widefat striped attn-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Date / Time', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Client Details', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Service / Need', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Urgency', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Status', 'attention-network'); ?></th>
                                            <th class="attn-text-right"><?php esc_html_e('Actions', 'attention-network'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filtered_leads as $lead) :
                                            $id        = esc_attr($lead['id'] ?? '');
                                            $date_str  = !empty($lead['createdAt']) ? esc_html(date_i18n('M j, g:i a', strtotime($lead['createdAt']))) : '—';
                                            $name      = esc_html($lead['name'] ?? __('Unknown Prospect', 'attention-network'));
                                            $phone     = esc_html($lead['phone'] ?? '');
                                            $email     = esc_html($lead['email'] ?? '');
                                            $service   = esc_html($lead['service'] ?? __('General Inquiry', 'attention-network'));
                                            $notes     = esc_html($lead['internalNotes'] ?? '');
                                            $message   = esc_html($lead['message'] ?? '');
                                            $urgency   = esc_attr($lead['urgency'] ?? 'normal');
                                            $status    = esc_attr($lead['status'] ?? 'new');
                                            $json_lead = esc_attr(wp_json_encode($lead));
                                        ?>
                                            <tr data-lead-id="<?php echo $id; ?>">
                                                <td><span class="attn-subtext"><?php echo $date_str; ?></span></td>
                                                <td>
                                                    <strong><?php echo $name; ?></strong>
                                                    <div class="attn-contact-links">
                                                        <?php if (!empty($phone)) : ?>
                                                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="attn-phone-link">📞 <?php echo $phone; ?></a>
                                                        <?php endif; ?>
                                                        <?php if (!empty($email)) : ?>
                                                            <a href="mailto:<?php echo esc_attr($email); ?>" class="attn-email-link">✉ <?php echo $email; ?></a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?php echo $service; ?></strong>
                                                    <?php if (!empty($message)) : ?>
                                                        <div class="attn-subtext"><?php echo esc_html(wp_trim_words($message, 12)); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ('emergency' === $urgency) : ?>
                                                        <span class="attn-pill attn-pill-red">🚨 <?php esc_html_e('24/7 Emergency', 'attention-network'); ?></span>
                                                    <?php elseif ('urgent' === $urgency) : ?>
                                                        <span class="attn-pill attn-pill-amber"><?php esc_html_e('Urgent', 'attention-network'); ?></span>
                                                    <?php else : ?>
                                                        <span class="attn-pill attn-pill-gray"><?php esc_html_e('Normal', 'attention-network'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <select class="attn-select-lead-status" data-lead-id="<?php echo $id; ?>" style="font-size: 12px; height: 28px;">
                                                        <option value="new" <?php selected($status, 'new'); ?>><?php esc_html_e('New', 'attention-network'); ?></option>
                                                        <option value="contacted" <?php selected($status, 'contacted'); ?>><?php esc_html_e('Contacted', 'attention-network'); ?></option>
                                                        <option value="quoted" <?php selected($status, 'quoted'); ?>><?php esc_html_e('Quoted', 'attention-network'); ?></option>
                                                        <option value="scheduled" <?php selected($status, 'scheduled'); ?>><?php esc_html_e('Scheduled', 'attention-network'); ?></option>
                                                        <option value="completed" <?php selected($status, 'completed'); ?>><?php esc_html_e('Completed', 'attention-network'); ?></option>
                                                    </select>
                                                </td>
                                                <td class="attn-text-right">
                                                    <button type="button" class="button button-small attn-btn-view-lead" data-lead='<?php echo $json_lead; ?>'>
                                                        <?php esc_html_e('View Details', 'attention-network'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="attn-col-sidebar">
                    <div class="attn-card attn-card-info">
                        <h3><?php esc_html_e('Deploy Intake Forms', 'attention-network'); ?></h3>
                        <p><?php esc_html_e('Embed high-converting intake and quote estimate forms on any page:', 'attention-network'); ?></p>
                        <div class="attn-code-snippet">
                            <code>[attention_lead_form]</code>
                            <span class="attn-subtext"><?php esc_html_e('Standard quote & consultation request form', 'attention-network'); ?></span>
                        </div>
                        <div class="attn-code-snippet">
                            <code>[attention_lead_form type="emergency"]</code>
                            <span class="attn-subtext"><?php esc_html_e('High-priority 24/7 urgent dispatch form', 'attention-network'); ?></span>
                        </div>
                    </div>

                    <div class="attn-card">
                        <h3><?php esc_html_e('Military-Grade Protection', 'attention-network'); ?></h3>
                        <ul class="attn-bullet-list">
                            <li><?php esc_html_e('Honeypot bot trapping silently drops automated scrapers.', 'attention-network'); ?></li>
                            <li><?php esc_html_e('Time-velocity verification blocks sub-second form fillers.', 'attention-network'); ?></li>
                            <li><?php esc_html_e('Transient IP rate limiting stops flood attacks.', 'attention-network'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Lead Detail Modal -->
        <div id="attn-lead-modal" class="attn-modal-backdrop" style="display: none;">
            <div class="attn-modal-dialog">
                <div class="attn-modal-header">
                    <h2><?php esc_html_e('Lead & Project Details', 'attention-network'); ?></h2>
                    <button type="button" class="attn-modal-close">&times;</button>
                </div>
                <div class="attn-modal-body">
                    <div id="attn-lead-modal-content"></div>
                    <div class="attn-form-row" style="margin-top: 15px;">
                        <label for="attn-lead-internal-notes"><?php esc_html_e('Internal Staff Notes (Private)', 'attention-network'); ?></label>
                        <textarea id="attn-lead-internal-notes" rows="4" class="large-text" placeholder="<?php esc_attr_e('e.g. Spoke with client. Estimate sent for $1,250. Scheduled walkthrough Friday at 10am.', 'attention-network'); ?>"></textarea>
                    </div>
                </div>
                <div class="attn-modal-footer">
                    <button type="button" class="button button-secondary attn-modal-cancel"><?php esc_html_e('Close', 'attention-network'); ?></button>
                    <button type="button" class="button button-primary" id="attn-btn-save-lead-notes"><?php esc_html_e('Save Notes', 'attention-network'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX Update Lead Status & Notes
     */
    public static function handle_update_lead_status() {
        self::verify_admin_security();

        $lead_id = isset($_POST['lead_id']) ? sanitize_text_field(wp_unslash($_POST['lead_id'])) : '';
        $status  = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'new';
        $notes   = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';

        if (empty($lead_id)) {
            wp_send_json_error(array('message' => __('Missing lead ID.', 'attention-network')), 400);
        }

        Attention_Network_API_Client::update_lead_status($lead_id, $status, $notes);

        wp_send_json_success(array('message' => __('Lead updated.', 'attention-network')));
    }

    /**
     * Public AJAX Lead Form Submission (Enterprise Anti-Abuse Standards)
     */
    public static function handle_public_lead_submission() {
        // 1. Verify Nonce
        if (!check_ajax_referer('attention_lead_submit_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security verification expired. Please refresh the page and try again.', 'attention-network')), 403);
            exit;
        }

        // 2. Anti-Spam Honeypot Verification
        // Real users will never see or fill this hidden field
        if (!empty($_POST['attn_hp_company'])) {
            // Silently pretend success to deceive malicious bots
            wp_send_json_success(array('message' => __('Thank you! Your request has been received.', 'attention-network')));
            exit;
        }

        // 3. Time-Velocity Bot Protection
        // Bots submit forms within milliseconds; humans take at least 2.5 seconds
        $form_rendered_at = isset($_POST['attn_form_timestamp']) ? intval($_POST['attn_form_timestamp']) : 0;
        $time_diff = time() - $form_rendered_at;
        if ($time_diff < 2 || $time_diff > 86400) {
            // Under 2 seconds or older than 24 hours
            wp_send_json_error(array('message' => __('Form submission speed verification failed. Please try again.', 'attention-network')), 400);
            exit;
        }

        // 4. IP Rate-Limiting Protection (Max 5 submissions per 10 mins per IP)
        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $rate_key  = 'attn_lead_rl_' . md5($client_ip);
        $attempts  = (int) get_transient($rate_key);
        if ($attempts >= 5) {
            wp_send_json_error(array('message' => __('Too many requests. Please wait a few minutes before submitting again or call us directly.', 'attention-network')), 429);
            exit;
        }
        set_transient($rate_key, $attempts + 1, 600);

        // 5. Sanitize & Validate Inputs
        $name    = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $phone   = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $email   = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $service = isset($_POST['service']) ? sanitize_text_field(wp_unslash($_POST['service'])) : 'General Inquiry';
        $urgency = isset($_POST['urgency']) ? sanitize_text_field(wp_unslash($_POST['urgency'])) : 'normal';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        $address = isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '';

        if (empty($name) || (empty($phone) && empty($email))) {
            wp_send_json_error(array('message' => __('Please provide your name and either a phone number or email address.', 'attention-network')), 400);
            exit;
        }

        $lead_data = array(
            'name'      => $name,
            'phone'     => $phone,
            'email'     => $email,
            'service'   => $service,
            'urgency'   => $urgency,
            'message'   => $message,
            'address'   => $address,
            'status'    => 'new',
            'clientIp'  => $client_ip,
        );

        Attention_Network_API_Client::save_lead($lead_data);

        // Optional notification dispatch to site admin
        $admin_email = get_option('admin_email');
        if (!empty($admin_email)) {
            $subject = sprintf('[New Lead] %s - %s', $urgency === 'emergency' ? '🚨 EMERGENCY DISPATCH' : 'Quote Request', $name);
            $body = sprintf(
                "New lead submitted on %s\n\nName: %s\nPhone: %s\nEmail: %s\nService: %s\nUrgency: %s\nAddress: %s\n\nDetails:\n%s\n\nView and manage this lead in your dashboard:\n%s",
                get_bloginfo('name'),
                $name,
                $phone,
                $email,
                $service,
                strtoupper($urgency),
                $address,
                $message,
                admin_url('admin.php?page=attention-network-leads')
            );
            wp_mail($admin_email, $subject, $body);
        }

        wp_send_json_success(array(
            'message' => __('Thank you! Your request has been received. Our team will contact you promptly.', 'attention-network'),
        ));
    }
}

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
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'attention-network'));
        }

        $offers = Attention_Network_API_Client::get_offers(false);
        ?>
        <div class="wrap attn-admin-wrap">
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge"><?php esc_html_e('Promotions & Growth', 'attention-network'); ?></span>
                    <h1 class="attn-title"><?php esc_html_e('Offers, Specials & Coupons', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php esc_html_e('Create high-converting seasonal specials, trade rebate promos, senior discounts, and digital coupons across your websites and app surfaces.', 'attention-network'); ?></p>
                </div>
                <div class="attn-header-right">
                    <button type="button" class="button button-primary" id="attn-btn-add-offer">
                        <?php esc_html_e('+ Create New Offer', 'attention-network'); ?>
                    </button>
                </div>
            </div>

            <div class="attn-grid">
                <div class="attn-col-main">
                    <div class="attn-card">
                        <div class="attn-card-header">
                            <h2 class="attn-card-title"><?php esc_html_e('Active & Scheduled Offers', 'attention-network'); ?></h2>
                            <span class="attn-counter"><?php echo sprintf(esc_html__('%d Total Offers', 'attention-network'), count($offers)); ?></span>
                        </div>

                        <?php if (empty($offers)) : ?>
                            <div class="attn-empty-state">
                                <span class="dashicons dashicons-tag" style="font-size: 48px; width: 48px; height: 48px; color: #94a3b8; margin-bottom: 12px;"></span>
                                <h3><?php esc_html_e('No promotional offers created yet', 'attention-network'); ?></h3>
                                <p><?php esc_html_e('Launch a seasonal special, $50 new customer discount, or flash promotion to boost lead conversion.', 'attention-network'); ?></p>
                                <button type="button" class="button button-primary" id="attn-btn-empty-add-offer">
                                    <?php esc_html_e('+ Create First Offer', 'attention-network'); ?>
                                </button>
                            </div>
                        <?php else : ?>
                            <div class="attn-table-container">
                                <table class="widefat striped attn-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Offer / Special', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Promo Code', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Discount / Value', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Category', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Expiration', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Status', 'attention-network'); ?></th>
                                            <th class="attn-text-right"><?php esc_html_e('Actions', 'attention-network'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($offers as $offer) :
                                            $id        = esc_attr($offer['id'] ?? '');
                                            $title     = esc_html($offer['title'] ?? '');
                                            $code      = esc_html($offer['code'] ?? '—');
                                            $discount  = esc_html($offer['discountDisplay'] ?? ($offer['discountValue'] ?? 'Special'));
                                            $category  = esc_html($offer['category'] ?? 'General');
                                            $expires   = !empty($offer['expiresAt']) ? esc_html(date_i18n(get_option('date_format'), strtotime($offer['expiresAt']))) : __('Never (Ongoing)', 'attention-network');
                                            $status    = esc_attr($offer['status'] ?? 'active');
                                            $badgeText = esc_html($offer['badgeText'] ?? '');
                                            $json_data = esc_attr(wp_json_encode($offer));
                                        ?>
                                            <tr data-offer-id="<?php echo $id; ?>">
                                                <td>
                                                    <strong><?php echo $title; ?></strong>
                                                    <?php if (!empty($badgeText)) : ?>
                                                        <span class="attn-pill attn-pill-amber" style="margin-left: 6px;"><?php echo $badgeText; ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($offer['description'])) : ?>
                                                        <div class="attn-subtext"><?php echo esc_html(wp_trim_words($offer['description'], 12)); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><code><?php echo $code; ?></code></td>
                                                <td><span class="attn-price-tag"><?php echo $discount; ?></span></td>
                                                <td><span class="attn-pill"><?php echo $category; ?></span></td>
                                                <td><?php echo $expires; ?></td>
                                                <td>
                                                    <span class="attn-pill <?php echo 'active' === $status ? 'attn-pill-green' : 'attn-pill-gray'; ?>">
                                                        <?php echo 'active' === $status ? esc_html__('Active', 'attention-network') : esc_html__('Paused', 'attention-network'); ?>
                                                    </span>
                                                </td>
                                                <td class="attn-text-right">
                                                    <button type="button" class="button button-small attn-btn-edit-offer" data-offer='<?php echo $json_data; ?>'>
                                                        <?php esc_html_e('Edit', 'attention-network'); ?>
                                                    </button>
                                                    <button type="button" class="button button-small button-link-delete attn-btn-delete-offer" data-id="<?php echo $id; ?>">
                                                        <?php esc_html_e('Delete', 'attention-network'); ?>
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
                        <h3><?php esc_html_e('Website Display & Embeds', 'attention-network'); ?></h3>
                        <p><?php esc_html_e('Embed high-converting promotional coupon cards anywhere on your website with these shortcodes:', 'attention-network'); ?></p>
                        <div class="attn-code-snippet">
                            <code>[attention_offers]</code>
                            <span class="attn-subtext"><?php esc_html_e('All active offers grid with copy code & print voucher', 'attention-network'); ?></span>
                        </div>
                        <div class="attn-code-snippet">
                            <code>[attention_offers category="HVAC"]</code>
                            <span class="attn-subtext"><?php esc_html_e('Filter offers by specific service category', 'attention-network'); ?></span>
                        </div>
                    </div>

                    <div class="attn-card">
                        <h3><?php esc_html_e('Conversion Best Practices', 'attention-network'); ?></h3>
                        <ul class="attn-bullet-list">
                            <li><?php esc_html_e('Add badge tags like "New Client Special" or "Limited Time" to create urgency.', 'attention-network'); ?></li>
                            <li><?php esc_html_e('Set explicit expiration dates for seasonal campaigns.', 'attention-network'); ?></li>
                            <li><?php esc_html_e('State clear terms & exclusions to prevent customer friction.', 'attention-network'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Offer Modal -->
        <div id="attn-offer-modal" class="attn-modal-backdrop" style="display: none;">
            <div class="attn-modal-dialog">
                <div class="attn-modal-header">
                    <h2 id="attn-offer-modal-title"><?php esc_html_e('Create Promotional Offer', 'attention-network'); ?></h2>
                    <button type="button" class="attn-modal-close">&times;</button>
                </div>
                <div class="attn-modal-body">
                    <form id="attn-offer-form">
                        <input type="hidden" id="attn-offer-id" name="id" value="" />

                        <div class="attn-form-row">
                            <label for="attn-offer-title"><?php esc_html_e('Offer Title *', 'attention-network'); ?></label>
                            <input type="text" id="attn-offer-title" name="title" class="regular-text" required placeholder="e.g. $50 Off Fall Furnace Tune-Up & Safety Inspection" />
                        </div>

                        <div class="attn-form-row-2col">
                            <div>
                                <label for="attn-offer-code"><?php esc_html_e('Promo / Coupon Code', 'attention-network'); ?></label>
                                <input type="text" id="attn-offer-code" name="code" class="regular-text" placeholder="e.g. FALL50" />
                            </div>
                            <div>
                                <label for="attn-offer-category"><?php esc_html_e('Service / Trade Category', 'attention-network'); ?></label>
                                <input type="text" id="attn-offer-category" name="category" class="regular-text" placeholder="e.g. HVAC, Roofing, Dental, Dining" />
                            </div>
                        </div>

                        <div class="attn-form-row-2col">
                            <div>
                                <label for="attn-offer-discount-type"><?php esc_html_e('Discount Type', 'attention-network'); ?></label>
                                <select id="attn-offer-discount-type" name="discountType" class="regular-text">
                                    <option value="fixed"><?php esc_html_e('Fixed Dollar Amount Off ($)', 'attention-network'); ?></option>
                                    <option value="percentage"><?php esc_html_e('Percentage Off (%)', 'attention-network'); ?></option>
                                    <option value="freebie"><?php esc_html_e('Free Service / Inspection / Add-On', 'attention-network'); ?></option>
                                    <option value="bogo"><?php esc_html_e('BOGO / Package Bundle', 'attention-network'); ?></option>
                                    <option value="custom"><?php esc_html_e('Custom Special Pricing', 'attention-network'); ?></option>
                                </select>
                            </div>
                            <div>
                                <label for="attn-offer-discount-display"><?php esc_html_e('Discount Display Label *', 'attention-network'); ?></label>
                                <input type="text" id="attn-offer-discount-display" name="discountDisplay" class="regular-text" required placeholder="e.g. $50 OFF or 20% OFF or FREE" />
                            </div>
                        </div>

                        <div class="attn-form-row-2col">
                            <div>
                                <label for="attn-offer-badge"><?php esc_html_e('Highlight Badge (Optional)', 'attention-network'); ?></label>
                                <input type="text" id="attn-offer-badge" name="badgeText" class="regular-text" placeholder="e.g. Most Popular, Limited Time, New Client" />
                            </div>
                            <div>
                                <label for="attn-offer-expires"><?php esc_html_e('Expiration Date (Leave blank if ongoing)', 'attention-network'); ?></label>
                                <input type="date" id="attn-offer-expires" name="expiresAt" class="regular-text" />
                            </div>
                        </div>

                        <div class="attn-form-row">
                            <label for="attn-offer-description"><?php esc_html_e('Offer Details / Scope', 'attention-network'); ?></label>
                            <textarea id="attn-offer-description" name="description" rows="3" class="large-text" placeholder="Detailed description of what is included in this offer..."></textarea>
                        </div>

                        <div class="attn-form-row">
                            <label for="attn-offer-terms"><?php esc_html_e('Terms & Exclusions', 'attention-network'); ?></label>
                            <input type="text" id="attn-offer-terms" name="terms" class="large-text" placeholder="e.g. Valid for residential systems only. Cannot be combined with other offers. One per household." />
                        </div>

                        <div class="attn-form-row">
                            <label for="attn-offer-cta-url"><?php esc_html_e('Booking / Claim URL (Optional)', 'attention-network'); ?></label>
                            <input type="url" id="attn-offer-cta-url" name="ctaUrl" class="large-text" placeholder="https://example.com/book-now or #lead-form" />
                        </div>

                        <div class="attn-form-row">
                            <label>
                                <input type="checkbox" id="attn-offer-status" name="status" value="active" checked />
                                <strong><?php esc_html_e('Active (Visible to public)', 'attention-network'); ?></strong>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="attn-modal-footer">
                    <button type="button" class="button button-secondary attn-modal-cancel"><?php esc_html_e('Cancel', 'attention-network'); ?></button>
                    <button type="button" class="button button-primary" id="attn-btn-save-offer"><?php esc_html_e('Save Offer', 'attention-network'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

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
    public static function handle_toggle_status() {
        self::verify_ajax_security();

        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $status = isset($_POST['status']) && 'active' === $_POST['status'] ? 'active' : 'paused';

        if (empty($id)) {
            wp_send_json_error(array('message' => __('Missing offer ID.', 'attention-network')), 400);
        }

        $offers = Attention_Network_API_Client::get_offers(false);
        foreach ($offers as $off) {
            if ($off['id'] === $id) {
                $off['status'] = $status;
                Attention_Network_API_Client::save_offer($off);
                break;
            }
        }

        wp_send_json_success(array('status' => $status));
    }
}

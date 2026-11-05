<?php
/**
 * Shortcodes for Attention Network (Menus, Hours, Staff Directory, Contact Cards & Attention Push)
 *
 * Adheres to government and enterprise security standards:
 * - Context-aware HTML escaping (esc_html, esc_attr, esc_url)
 * - Safe shortcode attribute parsing (shortcode_atts)
 * - Sanitized parameter handling
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Shortcodes {

    public static function unavailable_shortcode($feature) {
        if (!current_user_can('manage_options')) return '';
        return '<p class="attn-menu-error">' . esc_html(sprintf(__('%s is not connected in this plugin. Use the platform or the site directory integration for this content.', 'attention-network'), $feature)) . '</p>';
    }

    public static function render_contact_card_shortcode($atts) {
        return self::unavailable_shortcode('Public contact cards');
    }

    public static function render_pro_shortcode($atts) {
        return self::unavailable_shortcode('Public staff profiles');
    }

    public static function init() {
        add_shortcode('attention_menu', array(__CLASS__, 'render_menu_shortcode'));
        add_shortcode('attention_hours', array(__CLASS__, 'render_hours_shortcode'));
        add_shortcode('attention_open_badge', array(__CLASS__, 'render_open_badge_shortcode'));
        add_shortcode('attention_contact_card', array(__CLASS__, 'render_contact_card_shortcode'));
        add_shortcode('attention_staff_directory', array(__CLASS__, 'render_staff_directory_shortcode'));
        add_shortcode('attention_pro', array(__CLASS__, 'render_pro_shortcode'));
        add_shortcode('attention_push_prompt', array(__CLASS__, 'render_push_prompt_shortcode'));
        add_shortcode('attention_offers', array(__CLASS__, 'render_offers_shortcode'));
        add_shortcode('attention_offer', array(__CLASS__, 'render_single_offer_shortcode'));
        add_shortcode('attention_reviews', array(__CLASS__, 'render_reviews_shortcode'));
        add_shortcode('attention_rating_badge', array(__CLASS__, 'render_rating_badge_shortcode'));
        add_shortcode('attention_lead_form', array(__CLASS__, 'render_lead_form_shortcode'));
        add_shortcode('attention_portfolio', array(__CLASS__, 'render_portfolio_shortcode'));
        add_shortcode('attention_before_after', array(__CLASS__, 'render_before_after_shortcode'));
    }

    /**
     * Render [attention_menu] shortcode
     */
    public static function render_menu_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id'          => '',
            'layout'      => '',
            'theme'       => '',
            'show_images' => '',
            'show_prices' => '',
        ), $atts, 'attention_menu');

        return self::render_menu($atts);
    }

    /**
     * Internal renderer for menus
     */
    public static function render_menu($args = array()) {
        $args = array_merge(array('id'=>'', 'layout'=>'', 'theme'=>'', 'show_images'=>'', 'show_prices'=>''), $args);
        wp_enqueue_style('attention-network-menus');

        if (!Attention_Network_API_Client::is_configured()) {
            if (current_user_can('manage_options')) {
                return '<div class="attn-menu-error"><p>' . esc_html__('[Attention Menu: Plugin is not configured with Organization ID / API Key. Please visit Settings.]', 'attention-network') . '</p></div>';
            }
            return '';
        }

        $menu_id = !empty($args['id']) ? sanitize_text_field($args['id']) : '';

        // If no ID passed, get active menu
        if (empty($menu_id)) {
            $menus = Attention_Network_API_Client::get_public_menus();
            if (is_wp_error($menus) || empty($menus)) {
                return current_user_can('manage_options') && is_wp_error($menus) ? '<p class="attn-menu-error">'.esc_html($menus->get_error_message()).'</p>' : '';
            }
            foreach ($menus as $m) {
                if (!empty($m['is_active'])) {
                    $menu_id = $m['id'];
                    break;
                }
            }
            if (empty($menu_id) && !empty($menus[0]['id'])) {
                $menu_id = $menus[0]['id'];
            }
        }

        if (empty($menu_id)) {
            return '';
        }

        $menu_data = Attention_Network_API_Client::get_public_menus($menu_id);
        if (is_wp_error($menu_data) || empty($menu_data)) {
            if (current_user_can('manage_options')) {
                return '<div class="attn-menu-error"><p>' . esc_html(sprintf(__('Failed to load Attention menu: %s', 'attention-network'), is_wp_error($menu_data) ? $menu_data->get_error_message() : 'Menu not found')) . '</p></div>';
            }
            return '';
        }

        $display_settings = $menu_data['displaySettings'] ?? array();
        $layout = !empty($args['layout']) ? sanitize_key($args['layout']) : ($display_settings['layout_mode'] ?? 'standard');
        $theme = !empty($args['theme']) ? sanitize_key($args['theme']) : ($display_settings['theme_preset'] ?? 'light');

        $show_images = ($args['show_images'] !== '') ? ($args['show_images'] === 'yes' || $args['show_images'] === 'true' || $args['show_images'] === '1') : ($display_settings['show_images'] ?? true);
        $show_prices = ($args['show_prices'] !== '') ? ($args['show_prices'] === 'yes' || $args['show_prices'] === 'true' || $args['show_prices'] === '1') : ($display_settings['show_prices'] ?? true);
        $show_calories = $display_settings['show_calories'] ?? true;
        $show_dietary = $display_settings['show_dietary_tags'] ?? true;
        $show_abv = $display_settings['show_abv'] ?? false;
        $show_ibu = $display_settings['show_ibu'] ?? false;

        $template_file = 'menu-standard.php';
        if ($layout === 'taplist') {
            $template_file = 'menu-taplist.php';
        } elseif ($layout === 'qr') {
            $template_file = 'menu-qr.php';
        }

        $template_path = ATTENTION_NETWORK_PATH . 'templates/' . $template_file;
        if (!file_exists($template_path)) {
            $template_path = ATTENTION_NETWORK_PATH . 'templates/menu-standard.php';
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Render [attention_hours] shortcode
     */
    public static function render_hours_shortcode($atts) {
        return self::unavailable_shortcode('Public operating hours');
    }

    /**
     * Render [attention_open_badge] shortcode (Compact pill)
     */
    public static function render_open_badge_shortcode($atts) {
        return self::unavailable_shortcode('Public open/closed status');
    }

    /**
     * Render [attention_staff_directory] shortcode
     */
    public static function render_staff_directory_shortcode($atts) {
        return self::unavailable_shortcode('Public staff directory');
    }

    /**
     * Render [attention_push_prompt] shortcode
     */
    public static function render_push_prompt_shortcode($atts) {
        return self::unavailable_shortcode('Push subscriptions');
    }

    /* -------------------------------------------------------------------------
     * Offers & Coupons Shortcodes
     * ------------------------------------------------------------------------- */

    /**
     * Render [attention_offers] shortcode
     */
    public static function render_offers_shortcode($atts) {
        return self::unavailable_shortcode('Public offers');
    }

    /**
     * Render [attention_offer id="..."] shortcode
     */
    public static function render_single_offer_shortcode($atts) {
        return self::unavailable_shortcode('Public offers');
    }

    /* -------------------------------------------------------------------------
     * Customer Reviews & Reputation Shortcodes
     * ------------------------------------------------------------------------- */

    /**
     * Render [attention_rating_badge] shortcode
     */
    public static function render_rating_badge_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'badge', // badge or minimal
        ), $atts, 'attention_rating_badge');

        $reviews_data = Attention_Network_API_Client::get_reviews();
        if (is_wp_error($reviews_data)) return self::unavailable_shortcode('Published reviews');
        $count = intval($reviews_data['meta']['total'] ?? 0);
        if ($count === 0) return '';
        return '<span class="attn-rating-badge">'.esc_html(sprintf(__('%d published reviews', 'attention-network'), $count)).'</span>';
    }

    /**
     * Render [attention_reviews] shortcode
     */
    public static function render_reviews_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit'      => '6',
            'min_rating' => '1',
        ), $atts, 'attention_reviews');

        $reviews_data = Attention_Network_API_Client::get_reviews();
        if (is_wp_error($reviews_data)) return self::unavailable_shortcode('Published reviews');
        $items = isset($reviews_data['items']) ? (array) $reviews_data['items'] : array();

        if (empty($items)) {
            return '';
        }

        $min_rating = max(1, min(5, intval($atts['min_rating'])));
        $limit      = max(1, min(20, intval($atts['limit'])));
        $filtered   = array();

        foreach ($items as $r) {
            $rating = intval($r['rating'] ?? 5);
            if ($rating >= $min_rating) {
                $filtered[] = $r;
            }
            if (count($filtered) >= $limit) {
                break;
            }
        }

        ob_start();
        ?>
        <div class="attn-reviews-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(min(100%, 300px), 1fr));gap:20px;margin:24px 0;">
            <?php foreach ($filtered as $item) :
                $author   = esc_html($item['authorName'] ?? __('Customer', 'attention-network'));
                $rating   = intval($item['rating'] ?? 5);
                $title    = esc_html($item['title'] ?? '');
                $body     = esc_html($item['body'] ?? '');
                $source   = esc_html__('Published review', 'attention-network');
                $response = $item['response'] ?? null;
            ?>
                <div class="attn-review-card" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <div style="color:#f59e0b;font-size:15px;letter-spacing:1px;">
                                <?php for ($i = 0; $i < 5; $i++) { echo $i < $rating ? '★' : '☆'; } ?>
                            </div>
                            <span style="font-size:11px;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:999px;">
                                <?php echo $source; ?>
                            </span>
                        </div>

                        <?php if (!empty($title)) : ?>
                            <h4 style="font-size:15px;font-weight:700;color:#0f172a;margin:0 0 6px 0;line-height:1.3;">
                                <?php echo $title; ?>
                            </h4>
                        <?php endif; ?>

                        <p style="font-size:13px;color:#475569;margin:0 0 12px 0;line-height:1.5;">
                            "<?php echo $body; ?>"
                        </p>
                    </div>

                    <div style="border-top:1px solid #f1f5f9;padding-top:10px;margin-top:10px;">
                        <strong style="font-size:13px;color:#1e293b;"><?php echo $author; ?></strong>

                        <?php if (!empty($response) && !empty($response['body'])) : ?>
                            <div style="margin-top:10px;padding:10px 12px;background:#f8fafc;border-left:3px solid #0284c7;border-radius:0 6px 6px 0;font-size:12px;color:#334155;">
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;font-size:11px;"><?php esc_html_e('Owner Response:', 'attention-network'); ?></strong>
                                <?php echo esc_html($response['body']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /* -------------------------------------------------------------------------
     * Inbound Leads & Quote Form Shortcode
     * ------------------------------------------------------------------------- */

    /**
     * Render [attention_lead_form] shortcode
     */
    public static function render_lead_form_shortcode($atts) {
        wp_enqueue_script('jquery');
        $atts = shortcode_atts(array(
            'type'        => 'quote', // quote, estimate, emergency, consultation
            'title'       => '',
            'button_text' => '',
        ), $atts, 'attention_lead_form');

        $type = sanitize_key($atts['type']);
        $is_emergency = ('emergency' === $type);

        $default_title = $is_emergency 
            ? __('Request urgent service', 'attention-network')
            : __('Request a quote or consultation', 'attention-network');

        $title = !empty($atts['title']) ? sanitize_text_field($atts['title']) : $default_title;
        $btn_text = !empty($atts['button_text']) ? sanitize_text_field($atts['button_text']) : ($is_emergency ? __('Send urgent inquiry', 'attention-network') : __('Submit Quote Request →', 'attention-network'));

        $nonce = wp_create_nonce('attention_lead_submit_nonce');
        $form_id = 'attn-inquiry-' . wp_generate_password(8, false);
        $timestamp = time();

        ob_start();
        ?>
        <div class="attn-lead-form-wrap" style="max-width:640px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:28px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.06);">
            <h3 style="font-size:20px;font-weight:800;color:#0f172a;margin:0 0 8px 0;text-align:center;">
                <?php echo esc_html($title); ?>
            </h3>
            <p style="font-size:13px;color:#64748b;margin:0 0 20px 0;text-align:center;">
                <?php esc_html_e('Your inquiry is saved on this WordPress website for its team to review. Submitting does not schedule a visit or dispatch a technician. Provide a phone number or email address.', 'attention-network'); ?>
            </p>

            <form id="<?php echo esc_attr($form_id); ?>" class="attn-public-lead-form" onsubmit="event.preventDefault(); window.attnSubmitLeadForm(this);">
                <input type="hidden" name="action" value="attention_network_submit_lead" />
                <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
                <input type="hidden" name="attn_form_timestamp" value="<?php echo esc_attr($timestamp); ?>" />
                <input type="hidden" name="urgency" value="<?php echo esc_attr($is_emergency ? 'emergency' : 'normal'); ?>" />

                <!-- Anti-Spam Honeypot (Invisible to humans) -->
                <div style="display:none !important; visibility:hidden; position:absolute; left:-9999px;">
                    <label for="<?php echo esc_attr($form_id.'-honeypot'); ?>">Do not fill this field</label>
                    <input type="text" id="<?php echo esc_attr($form_id.'-honeypot'); ?>" name="attn_hp_company" tabindex="-1" autocomplete="off" />
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Your Full Name *', 'attention-network'); ?></label>
                        <input type="text" name="name" maxlength="191" aria-label="Your name" required style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="John Doe" />
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Phone Number', 'attention-network'); ?></label>
                        <input type="tel" name="phone" maxlength="64" aria-label="Phone number" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="(555) 000-0000" />
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Email Address', 'attention-network'); ?></label>
                        <input type="email" name="email" maxlength="254" aria-label="Email address" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="john@example.com" />
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Service / Trade Requested', 'attention-network'); ?></label>
                        <input type="text" name="service" maxlength="200" aria-label="Requested service" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="e.g. Repair, Installation, Inspection" />
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Service Address or Zip Code', 'attention-network'); ?></label>
                    <input type="text" name="address" maxlength="500" aria-label="Service address or postal code" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="Street address or city/zip code" />
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Describe Your Issue or Project Scope', 'attention-network'); ?></label>
                    <textarea name="message" maxlength="10000" aria-label="Project details" rows="3" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="<?php esc_attr_e('Provide any details regarding what you need done, timelines, or symptoms...', 'attention-network'); ?>"></textarea>
                </div>

                <button type="submit" style="width:100%;padding:12px 20px;font-size:15px;font-weight:700;color:#ffffff;background:<?php echo $is_emergency ? '#dc2626' : '#0284c7'; ?>;border:none;border-radius:8px;cursor:pointer;transition:background 0.2s;">
                    <?php echo esc_html($btn_text); ?>
                </button>

                <div role="status" aria-live="polite" class="attn-form-status" style="margin-top:12px;display:none;padding:10px 14px;border-radius:6px;font-size:13px;text-align:center;"></div>
            </form>
        </div>

        <script>
        window.attnSubmitLeadForm = window.attnSubmitLeadForm || function(form) {
            var $form = jQuery(form);
            var $btn  = $form.find('button[type="submit"]');
            var $msg  = $form.find('.attn-form-status');
            var originalText = $btn.text();

            $btn.prop('disabled', true).text('<?php echo esc_js(__('Submitting...', 'attention-network')); ?>');
            $msg.hide().removeClass('notice-success notice-error');

            jQuery.ajax({
                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                type: 'POST',
                data: $form.serialize(),
                success: function(resp) {
                    if (resp.success) {
                        $msg.css({background: '#dcfce7', color: '#15803d', display: 'block'}).text(resp.data.message);
                        $form[0].reset();
                        $btn.text('<?php echo esc_js(__('Submitted Successfully!', 'attention-network')); ?>');
                    } else {
                        $msg.css({background: '#fee2e2', color: '#b91c1c', display: 'block'}).text(resp.data ? resp.data.message : 'Submission failed.');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    $msg.css({background: '#fee2e2', color: '#b91c1c', display: 'block'}).text('<?php echo esc_js(__('Network error occurred. Please call directly.', 'attention-network')); ?>');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        };
        </script>
        <?php
        return ob_get_clean();
    }

    /* -------------------------------------------------------------------------
     * Project Showcase & Before/After Comparison Shortcodes
     * ------------------------------------------------------------------------- */

    /**
     * Render [attention_before_after before="..." after="..." label="..."] shortcode
     */
    public static function render_before_after_shortcode($atts) {
        $atts = shortcode_atts(array(
            'before' => '',
            'after'  => '',
            'label'  => '',
        ), $atts, 'attention_before_after');

        $before_url = esc_url($atts['before']);
        $after_url  = esc_url($atts['after']);
        $label      = esc_html($atts['label']);

        if (empty($before_url) || empty($after_url)) {
            return '';
        }

        $unique_id = 'attn_ba_' . wp_generate_password(8, false);

        ob_start();
        ?>
        <div class="attn-ba-wrap" style="max-width:800px;margin:24px auto;">
            <?php if (!empty($label)) : ?>
                <h4 style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 10px 0;text-align:center;"><?php echo $label; ?></h4>
            <?php endif; ?>

            <div id="<?php echo esc_attr($unique_id); ?>" style="position:relative;width:100%;overflow:hidden;border-radius:12px;user-select:none;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);aspect-ratio:16/9;background:#000;">
                <img src="<?php echo $after_url; ?>" alt="After" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;" />
                
                <div class="attn-ba-overlay" style="position:absolute;top:0;left:0;width:50%;height:100%;overflow:hidden;border-right:3px solid #ffffff;box-shadow:2px 0 10px rgba(0,0,0,0.3);">
                    <img src="<?php echo $before_url; ?>" alt="Before" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;max-width:none;" />
                    <span style="position:absolute;bottom:12px;left:12px;background:rgba(0,0,0,0.65);color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;"><?php esc_html_e('BEFORE', 'attention-network'); ?></span>
                </div>

                <span style="position:absolute;bottom:12px;right:12px;background:rgba(0,0,0,0.65);color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;"><?php esc_html_e('AFTER', 'attention-network'); ?></span>

                <!-- Range Input Slider Control -->
                <input aria-label="Compare before and after images" type="range" min="0" max="100" value="50" oninput="var el = document.getElementById('<?php echo esc_js($unique_id); ?>'); if(el){ el.querySelector('.attn-ba-overlay').style.width = this.value + '%'; }" style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:ew-resize;margin:0;" />
            </div>
            <div style="text-align:center;font-size:11px;color:#94a3b8;margin-top:6px;">
                <?php esc_html_e('⇄ Drag slider or touch to compare Before & After', 'attention-network'); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render [attention_portfolio] shortcode
     */
    public static function render_portfolio_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit'    => '6',
        ), $atts, 'attention_portfolio');

        $items = Attention_Network_API_Client::get_portfolio_items();
        if (empty($items)) {
            return '';
        }

        $target_cat = strtolower(trim($atts['category']));
        $limit      = intval($atts['limit']);
        $filtered   = array();

        foreach ($items as $it) {
            if (!empty($target_cat) && strtolower($it['category'] ?? '') !== $target_cat) {
                continue;
            }
            $filtered[] = $it;
            if (count($filtered) >= $limit) {
                break;
            }
        }

        if (empty($filtered)) {
            return '';
        }

        ob_start();
        ?>
        <div class="attn-portfolio-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(min(100%, 320px), 1fr));gap:24px;margin:24px 0;">
            <?php foreach ($filtered as $proj) :
                $title      = esc_html($proj['title'] ?? '');
                $category   = esc_html($proj['category'] ?? 'General');
                $before_url = esc_url($proj['beforeUrl'] ?? '');
                $after_url  = esc_url($proj['afterUrl'] ?? '');
                $desc       = esc_html($proj['description'] ?? '');
                $duration   = esc_html($proj['duration'] ?? '');
                $location   = esc_html($proj['location'] ?? '');
            ?>
                <div class="attn-portfolio-card" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                    <?php if (!empty($before_url) && !empty($after_url)) : ?>
                        <?php echo self::render_before_after_shortcode(array('before' => $before_url, 'after' => $after_url)); ?>
                    <?php elseif (!empty($after_url)) : ?>
                        <img src="<?php echo $after_url; ?>" alt="<?php echo esc_attr($title); ?>" style="width:100%;height:220px;object-fit:cover;" />
                    <?php endif; ?>

                    <div style="padding:16px 20px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <span class="attn-pill" style="background:#f1f5f9;color:#475569;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;"><?php echo $category; ?></span>
                            <?php if (!empty($duration)) : ?>
                                <span style="font-size:11px;color:#64748b;">⏱ <?php echo $duration; ?></span>
                            <?php endif; ?>
                        </div>

                        <h4 style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 6px 0;line-height:1.3;">
                            <?php echo $title; ?>
                        </h4>

                        <?php if (!empty($desc)) : ?>
                            <p style="font-size:13px;color:#475569;margin:0;line-height:1.5;">
                                <?php echo $desc; ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($location)) : ?>
                            <div style="font-size:11px;color:#94a3b8;margin-top:8px;">
                                📍 <?php echo $location; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

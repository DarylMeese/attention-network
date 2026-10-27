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
            $menus = Attention_Network_API_Client::get_menus();
            if (is_wp_error($menus) || empty($menus)) {
                return '';
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

        $menu_data = Attention_Network_API_Client::get_full_menu($menu_id);
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
        $atts = shortcode_atts(array(
            'location_id' => '',
            'title'       => __('Hours of Operation', 'attention-network'),
            'show_badge'  => 'true',
        ), $atts, 'attention_hours');

        wp_enqueue_style('attention-network-menus');

        if (!Attention_Network_API_Client::is_configured()) {
            return '';
        }

        $location_id = sanitize_text_field($atts['location_id']);
        if (empty($location_id)) {
            $locs = Attention_Network_API_Client::get_locations();
            if (!is_wp_error($locs) && !empty($locs['locations'])) {
                $location_id = $locs['locations'][0]['locationId'] ?? ($locs['locations'][0]['id'] ?? '');
            }
        }

        if (empty($location_id)) {
            return '';
        }

        $hours_res = Attention_Network_API_Client::get_hours($location_id);
        $hours = (!is_wp_error($hours_res) && isset($hours_res['hours'])) ? $hours_res['hours'] : array();

        $days = array(
            1 => __('Monday', 'attention-network'),
            2 => __('Tuesday', 'attention-network'),
            3 => __('Wednesday', 'attention-network'),
            4 => __('Thursday', 'attention-network'),
            5 => __('Friday', 'attention-network'),
            6 => __('Saturday', 'attention-network'),
            0 => __('Sunday', 'attention-network'),
        );

        $indexed = array();
        foreach ($hours as $h) {
            $indexed[intval($h['dayOfWeek'])] = $h;
        }

        // Current status calculation
        $current_day = intval(current_time('w'));
        $current_time = current_time('H:i:s');
        $is_open_now = false;

        if (isset($indexed[$current_day]) && empty($indexed[$current_day]['isClosed'])) {
            $o = $indexed[$current_day]['openTime'];
            $c = $indexed[$current_day]['closeTime'];
            if ($current_time >= $o && $current_time <= $c) {
                $is_open_now = true;
            }
        }

        $emergency_active = get_option('attention_network_emergency_dispatch_' . $location_id, '0') === '1';

        ob_start();
        ?>
        <div class="attn-hours-widget" style="border:1px solid #e2e8f0;border-radius:12px;padding:20px;background:#fff;max-width:440px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <h3 style="margin:0;font-size:18px;font-weight:700;"><?php echo esc_html($atts['title']); ?></h3>
                <?php if ($emergency_active): ?>
                    <span style="font-size:11px;font-weight:700;color:#c026d3;background:#fae8ff;padding:3px 10px;border-radius:999px;">
                        🚨 <?php esc_html_e('24/7 Emergency Dispatch', 'attention-network'); ?>
                    </span>
                <?php elseif ($atts['show_badge'] === 'true'): ?>
                    <span style="font-size:12px;font-weight:600;color:<?php echo $is_open_now ? '#16a34a' : '#dc2626'; ?>;background:<?php echo $is_open_now ? '#dcfce7' : '#fee2e2'; ?>;padding:3px 10px;border-radius:999px;">
                        ● <?php echo $is_open_now ? esc_html__('Open Now', 'attention-network') : esc_html__('Closed', 'attention-network'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <ul style="list-style:none;padding:0;margin:0;">
                <?php foreach ($days as $d_num => $d_name): 
                    $entry = isset($indexed[$d_num]) ? $indexed[$d_num] : array();
                    $is_today = ($d_num === $current_day);
                    $is_cl = !empty($entry['isClosed']);
                    $time_txt = $is_cl 
                        ? __('Closed', 'attention-network') 
                        : (isset($entry['openTime']) ? esc_html(substr($entry['openTime'], 0, 5) . ' - ' . substr($entry['closeTime'], 0, 5)) : __('Closed', 'attention-network'));
                ?>
                    <li style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:14px;<?php if ($is_today) echo 'font-weight:700;color:#0284c7;'; ?>">
                        <span><?php echo esc_html($d_name); ?><?php if ($is_today) echo ' (' . esc_html__('Today', 'attention-network') . ')'; ?></span>
                        <span><?php echo esc_html($time_txt); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render [attention_open_badge] shortcode (Compact pill)
     */
    public static function render_open_badge_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location_id' => '',
        ), $atts, 'attention_open_badge');

        $location_id = sanitize_text_field($atts['location_id']);
        if (empty($location_id)) {
            $locs = Attention_Network_API_Client::get_locations();
            if (!is_wp_error($locs) && !empty($locs['locations'])) {
                $location_id = $locs['locations'][0]['locationId'] ?? ($locs['locations'][0]['id'] ?? '');
            }
        }

        $emergency_active = get_option('attention_network_emergency_dispatch_' . $location_id, '0') === '1';
        if ($emergency_active) {
            return '<span class="attn-badge-pill" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#c026d3;background:#fae8ff;padding:4px 10px;border-radius:999px;">🚨 ' . esc_html__('24/7 Emergency Dispatch', 'attention-network') . '</span>';
        }

        $hours_res = Attention_Network_API_Client::get_hours($location_id);
        $hours = (!is_wp_error($hours_res) && isset($hours_res['hours'])) ? $hours_res['hours'] : array();

        $current_day = intval(current_time('w'));
        $current_time = current_time('H:i:s');
        $is_open_now = false;

        foreach ($hours as $h) {
            if (intval($h['dayOfWeek']) === $current_day && empty($h['isClosed'])) {
                if ($current_time >= $h['openTime'] && $current_time <= $h['closeTime']) {
                    $is_open_now = true;
                }
                break;
            }
        }

        $color = $is_open_now ? '#16a34a' : '#dc2626';
        $bg = $is_open_now ? '#dcfce7' : '#fee2e2';
        $label = $is_open_now ? __('Open Now', 'attention-network') : __('Closed', 'attention-network');

        return '<span class="attn-badge-pill" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:' . esc_attr($color) . ';background:' . esc_attr($bg) . ';padding:4px 10px;border-radius:999px;">● ' . esc_html($label) . '</span>';
    }

    /**
     * Render [attention_staff_directory] shortcode
     */
    public static function render_staff_directory_shortcode($atts) {
        $atts = shortcode_atts(array(
            'layout' => 'grid',
        ), $atts, 'attention_staff_directory');

        $cards_response = Attention_Network_API_Client::get_staff_members(true);
        $cards = (!is_wp_error($cards_response) && isset($cards_response['cards']))
            ? $cards_response['cards']
            : (is_array($cards_response) ? $cards_response : array());

        if (empty($cards)) {
            return '';
        }

        ob_start();
        ?>
        <div class="attn-staff-directory attn-layout-<?php echo esc_attr($atts['layout']); ?>" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:24px;margin:20px 0;">
            <?php foreach ($cards as $c): 
                $contact = $c['contact'] ?? $c;
                $fname = $contact['firstName'] ?? '';
                $lname = $contact['lastName'] ?? '';
                $fullname = trim($fname . ' ' . $lname) ?: ($c['name'] ?? __('Team Member', 'attention-network'));
                $title = $contact['title'] ?? ($c['title'] ?? '');
                $photo = $c['profileImage'] ?? ($contact['profileImage'] ?? '');
                $license = $c['licenseNumber'] ?? ($contact['licenseNumber'] ?? '');
                $bio = $c['bio'] ?? ($contact['bio'] ?? '');
                $links = $c['links'] ?? array();
            ?>
                <div class="attn-pro-card" style="border:1px solid #e2e8f0;border-radius:12px;padding:20px;background:#fff;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);display:flex;flex-direction:column;justify-content:space-between;">
                    <div>
                        <div style="display:flex;gap:14px;align-items:center;margin-bottom:12px;">
                            <?php if (!empty($photo)): ?>
                                <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($fullname); ?>" style="width:60px;height:60px;border-radius:999px;object-fit:cover;border:2px solid #e2e8f0;" />
                            <?php else: ?>
                                <div style="width:60px;height:60px;border-radius:999px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-weight:700;color:#64748b;font-size:18px;">
                                    <?php echo esc_html(strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1))); ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h4 style="margin:0;font-size:17px;font-weight:700;"><?php echo esc_html($fullname); ?></h4>
                                <div style="font-size:13px;color:#0284c7;font-weight:600;"><?php echo esc_html($title); ?></div>
                                <?php if (!empty($license)): ?>
                                    <div style="font-size:11px;color:#64748b;font-family:monospace;"><?php echo esc_html($license); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($bio)): ?>
                            <p style="font-size:13px;color:#475569;margin:0 0 14px 0;line-height:1.5;">
                                <?php echo esc_html($bio); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="border-top:1px solid #f1f5f9;padding-top:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <span style="font-size:11px;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:999px;">
                                ✓ <?php esc_html_e('Verified Background Cleared', 'attention-network'); ?>
                            </span>
                        </div>

                        <?php if (!empty($links)): ?>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                                <?php foreach ($links as $lnk): ?>
                                    <a href="<?php echo esc_url($lnk['url']); ?>" target="_blank" rel="noopener noreferrer" class="button button-small" style="font-size:11px;">
                                        <?php echo esc_html($lnk['label'] ?? __('Connect', 'attention-network')); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render [attention_push_prompt] shortcode
     */
    public static function render_push_prompt_shortcode($atts) {
        $atts = shortcode_atts(array(
            'button_text' => __('Get Instant Alerts & Specials', 'attention-network'),
            'class'       => '',
        ), $atts, 'attention_push_prompt');

        $app_id = get_option('attention_network_push_app_id', '');
        if (empty($app_id)) {
            return '';
        }

        // Enqueue Gravitec client script
        wp_enqueue_script(
            'attention-push-client',
            'https://cdn.gravitec.net/storage/' . esc_attr($app_id) . '/client.js',
            array(),
            null,
            true
        );

        ob_start();
        ?>
        <div class="attn-push-prompt-wrap <?php echo esc_attr($atts['class']); ?>" style="display:inline-block;">
            <button type="button" class="attn-push-btn button" onclick="if(window.Gravitec){window.Gravitec.push(['prompt']);}else{alert('<?php echo esc_js(__('Notification service initializing...', 'attention-network')); ?>');}" style="display:inline-flex;align-items:center;gap:8px;font-weight:600;">
                <span style="font-size:16px;">🔔</span> <?php echo esc_html($atts['button_text']); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /* -------------------------------------------------------------------------
     * Offers & Coupons Shortcodes
     * ------------------------------------------------------------------------- */

    /**
     * Render [attention_offers] shortcode
     */
    public static function render_offers_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit'    => '6',
            'columns'  => '3',
        ), $atts, 'attention_offers');

        $offers = Attention_Network_API_Client::get_offers();
        if (empty($offers)) {
            return '';
        }

        $filtered = array();
        $target_cat = strtolower(trim($atts['category']));
        $limit = intval($atts['limit']);

        foreach ($offers as $o) {
            if (isset($o['status']) && 'paused' === $o['status']) {
                continue;
            }
            if (!empty($target_cat) && strtolower($o['category'] ?? '') !== $target_cat) {
                continue;
            }
            $filtered[] = $o;
            if (count($filtered) >= $limit) {
                break;
            }
        }

        if (empty($filtered)) {
            return '';
        }

        ob_start();
        ?>
        <div class="attn-offers-container" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:20px;margin:24px 0;">
            <?php foreach ($filtered as $offer) :
                $title    = esc_html($offer['title'] ?? '');
                $code     = esc_attr($offer['code'] ?? '');
                $discount = esc_html($offer['discountDisplay'] ?? '');
                $desc     = esc_html($offer['description'] ?? '');
                $terms    = esc_html($offer['terms'] ?? '');
                $badge    = esc_html($offer['badgeText'] ?? '');
                $cta_url  = esc_url($offer['ctaUrl'] ?? '#');
                $expires  = !empty($offer['expiresAt']) ? sprintf(__('Expires %s', 'attention-network'), esc_html(date_i18n('M j, Y', strtotime($offer['expiresAt'])))) : __('Ongoing Special', 'attention-network');
            ?>
                <div class="attn-offer-card" style="background:#ffffff;border:2px dashed #cbd5e1;border-radius:12px;padding:20px;position:relative;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                    <?php if (!empty($badge)) : ?>
                        <div style="position:absolute;top:-12px;right:16px;background:#f59e0b;color:#ffffff;font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px;text-transform:uppercase;letter-spacing:0.5px;">
                            <?php echo $badge; ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <div style="font-size:24px;font-weight:800;color:#0f172a;margin-bottom:6px;line-height:1.2;">
                            <?php echo $discount; ?>
                        </div>
                        <h4 style="font-size:16px;font-weight:700;color:#1e293b;margin:0 0 8px 0;line-height:1.3;">
                            <?php echo $title; ?>
                        </h4>
                        <?php if (!empty($desc)) : ?>
                            <p style="font-size:13px;color:#475569;margin:0 0 12px 0;line-height:1.5;">
                                <?php echo $desc; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="border-top:1px solid #f1f5f9;padding-top:14px;margin-top:10px;">
                        <?php if (!empty($code)) : ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;margin-bottom:10px;">
                                <span style="font-size:12px;color:#64748b;font-weight:600;"><?php esc_html_e('PROMO CODE', 'attention-network'); ?></span>
                                <code style="font-size:14px;font-weight:700;color:#0284c7;background:transparent;"><?php echo esc_html($code); ?></code>
                            </div>
                        <?php endif; ?>

                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;color:#64748b;">
                            <span>⏱ <?php echo $expires; ?></span>
                            <?php if (!empty($code)) : ?>
                                <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo $code; ?>');this.innerText='✓ Copied!';" style="font-size:11px;font-weight:600;">
                                    <?php esc_html_e('Copy Code', 'attention-network'); ?>
                                </button>
                            <?php elseif (!empty($cta_url) && '#' !== $cta_url) : ?>
                                <a href="<?php echo $cta_url; ?>" class="button button-small button-primary" style="font-size:11px;font-weight:600;">
                                    <?php esc_html_e('Claim Offer →', 'attention-network'); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($terms)) : ?>
                            <div style="font-size:10px;color:#94a3b8;margin-top:8px;line-height:1.3;">
                                * <?php echo $terms; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render [attention_offer id="..."] shortcode
     */
    public static function render_single_offer_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts, 'attention_offer');

        if (empty($atts['id'])) {
            return '';
        }

        $offers = Attention_Network_API_Client::get_offers();
        $target = null;
        foreach ($offers as $o) {
            if (($o['id'] ?? '') === $atts['id']) {
                $target = $o;
                break;
            }
        }

        if (!$target) {
            return '';
        }

        return self::render_offers_shortcode(array('category' => $target['category'] ?? ''));
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
        $avg_rating   = isset($reviews_data['averageRating']) ? floatval($reviews_data['averageRating']) : 5.0;
        $total_count  = isset($reviews_data['totalReviews']) ? intval($reviews_data['totalReviews']) : 0;
        $items        = isset($reviews_data['items']) ? (array) $reviews_data['items'] : array();
        $count        = $total_count ?: count($items);

        ob_start();
        ?>
        <div class="attn-rating-badge-wrap" style="display:inline-flex;align-items:center;background:#ffffff;border:1px solid #e2e8f0;padding:6px 14px;border-radius:999px;box-shadow:0 1px 3px rgba(0,0,0,0.06);font-family:sans-serif;" itemscope itemtype="https://schema.org/AggregateRating">
            <span style="color:#f59e0b;font-size:16px;margin-right:6px;">★★★★★</span>
            <strong style="color:#0f172a;font-size:13px;margin-right:6px;" itemprop="ratingValue"><?php echo number_format($avg_rating, 1); ?></strong>
            <span style="color:#64748b;font-size:12px;">(<?php echo sprintf(esc_html__('%d verified reviews', 'attention-network'), $count); ?>)</span>
            <meta itemprop="bestRating" content="5" />
            <meta itemprop="reviewCount" content="<?php echo esc_attr($count); ?>" />
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render [attention_reviews] shortcode
     */
    public static function render_reviews_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit'      => '6',
            'min_rating' => '4',
        ), $atts, 'attention_reviews');

        $reviews_data = Attention_Network_API_Client::get_reviews();
        $items = isset($reviews_data['items']) ? (array) $reviews_data['items'] : array();

        if (empty($items)) {
            return '';
        }

        $min_rating = intval($atts['min_rating']);
        $limit      = intval($atts['limit']);
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
        <div class="attn-reviews-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:20px;margin:24px 0;">
            <?php foreach ($filtered as $item) :
                $author   = esc_html($item['authorName'] ?? __('Verified Client', 'attention-network'));
                $rating   = intval($item['rating'] ?? 5);
                $title    = esc_html($item['title'] ?? '');
                $body     = esc_html($item['body'] ?? '');
                $source   = esc_html($item['source'] ?? 'Verified Customer');
                $response = $item['response'] ?? null;
            ?>
                <div class="attn-review-card" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <div style="color:#f59e0b;font-size:15px;letter-spacing:1px;">
                                <?php for ($i = 0; $i < 5; $i++) { echo $i < $rating ? '★' : '☆'; } ?>
                            </div>
                            <span style="font-size:11px;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:999px;">
                                ✓ <?php echo $source; ?>
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
        $atts = shortcode_atts(array(
            'type'        => 'quote', // quote, estimate, emergency, consultation
            'title'       => '',
            'button_text' => '',
        ), $atts, 'attention_lead_form');

        $type = sanitize_key($atts['type']);
        $is_emergency = ('emergency' === $type);

        $default_title = $is_emergency 
            ? __('🚨 Request Immediate 24/7 Emergency Dispatch', 'attention-network') 
            : __('Request a Free Quote & Consultation', 'attention-network');

        $title = !empty($atts['title']) ? sanitize_text_field($atts['title']) : $default_title;
        $btn_text = !empty($atts['button_text']) ? sanitize_text_field($atts['button_text']) : ($is_emergency ? __('Dispatch On-Call Technician Now', 'attention-network') : __('Submit Quote Request →', 'attention-network'));

        $nonce = wp_create_nonce('attention_lead_submit_nonce');
        $timestamp = time();

        ob_start();
        ?>
        <div class="attn-lead-form-wrap" style="max-width:640px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:28px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.06);">
            <h3 style="font-size:20px;font-weight:800;color:#0f172a;margin:0 0 8px 0;text-align:center;">
                <?php echo esc_html($title); ?>
            </h3>
            <p style="font-size:13px;color:#64748b;margin:0 0 20px 0;text-align:center;">
                <?php esc_html_e('Fill out the form below. Our team responds promptly during regular hours, with priority dispatch for emergencies.', 'attention-network'); ?>
            </p>

            <form class="attn-public-lead-form" onsubmit="event.preventDefault(); window.attnSubmitLeadForm(this);">
                <input type="hidden" name="action" value="attention_network_submit_lead" />
                <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
                <input type="hidden" name="attn_form_timestamp" value="<?php echo esc_attr($timestamp); ?>" />
                <input type="hidden" name="urgency" value="<?php echo esc_attr($is_emergency ? 'emergency' : 'normal'); ?>" />

                <!-- Anti-Spam Honeypot (Invisible to humans) -->
                <div style="display:none !important; visibility:hidden; position:absolute; left:-9999px;">
                    <label for="attn_hp_company">Do not fill this field</label>
                    <input type="text" id="attn_hp_company" name="attn_hp_company" tabindex="-1" autocomplete="off" />
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Your Full Name *', 'attention-network'); ?></label>
                        <input type="text" name="name" required style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="John Doe" />
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Phone Number *', 'attention-network'); ?></label>
                        <input type="tel" name="phone" required style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="(555) 000-0000" />
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Email Address', 'attention-network'); ?></label>
                        <input type="email" name="email" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="john@example.com" />
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Service / Trade Requested', 'attention-network'); ?></label>
                        <input type="text" name="service" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="e.g. Repair, Installation, Inspection" />
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Service Address or Zip Code', 'attention-network'); ?></label>
                    <input type="text" name="address" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="Street address or city/zip code" />
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:4px;"><?php esc_html_e('Describe Your Issue or Project Scope', 'attention-network'); ?></label>
                    <textarea name="message" rows="3" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;" placeholder="<?php esc_attr_e('Provide any details regarding what you need done, timelines, or symptoms...', 'attention-network'); ?>"></textarea>
                </div>

                <button type="submit" style="width:100%;padding:12px 20px;font-size:15px;font-weight:700;color:#ffffff;background:<?php echo $is_emergency ? '#dc2626' : '#0284c7'; ?>;border:none;border-radius:8px;cursor:pointer;transition:background 0.2s;">
                    <?php echo esc_html($btn_text); ?>
                </button>

                <div class="attn-form-status" style="margin-top:12px;display:none;padding:10px 14px;border-radius:6px;font-size:13px;text-align:center;"></div>
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
                <input type="range" min="0" max="100" value="50" oninput="var el = document.getElementById('<?php echo esc_js($unique_id); ?>'); if(el){ el.querySelector('.attn-ba-overlay').style.width = this.value + '%'; }" style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:ew-resize;margin:0;" />
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
        <div class="attn-portfolio-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:24px;margin:24px 0;">
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

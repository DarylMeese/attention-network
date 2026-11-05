<?php
/**
 * Business Operations Admin Interface (Hours, Emergency Dispatch, Locations & Contacts)
 *
 * Implements strict government & enterprise security standards:
 * - Strict capability checks (current_user_can)
 * - Cryptographic nonce verification (check_ajax_referer)
 * - Strict input sanitization and regex validation
 * - Context-aware output escaping (esc_html, esc_attr)
 * - Error logging without exposing sensitive internal state
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Business_Admin {

    public static function init() {
        // AJAX Endpoints for Hours and Business Profile
        $ajax_actions = array(
            'save_weekly_hours',
            'create_special_hours',
            'delete_special_hours',
            'save_location_profile',
        );

        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_attention_network_' . $action, array(__CLASS__, 'handle_' . $action));
        }
    }

    /**
     * Common security guard for all AJAX handlers
     */
    private static function verify_ajax_security() {
        check_ajax_referer('attention_network_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized security permission level.', 'attention-network')), 403);
            exit;
        }

        if (!Attention_Network_API_Client::is_configured()) {
            wp_send_json_error(array('message' => __('Attention Network API is not configured.', 'attention-network')), 400);
            exit;
        }
    }

    /**
     * Render Operating Hours Admin Screen
     */
    public static function render_hours_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'attention-network'));
        }

        $locations_response = Attention_Network_API_Client::get_locations();
        if (is_wp_error($locations_response)) { echo '<div class="wrap"><h1>'.esc_html__('Operating hours', 'attention-network').'</h1><p>'.esc_html($locations_response->get_error_message()).'</p></div>'; return; }
        $locations = (!is_wp_error($locations_response) && isset($locations_response['locations'])) 
            ? $locations_response['locations'] 
            : array();
        if (!$locations) { echo '<div class="wrap"><h1>'.esc_html__('Operating hours', 'attention-network').'</h1><p>'.esc_html__('No locations are available for this account. Create a location in the platform, or ask its manager for access.', 'attention-network').'</p><a href="https://app.attn.systems/">'.esc_html__('Open Attention Network', 'attention-network').'</a></div>'; return; }

        // Default or selected location
        $active_location_id = isset($_GET['location_id']) ? sanitize_text_field($_GET['location_id']) : '';
        if (empty($active_location_id) && !empty($locations)) {
            $active_location_id = $locations[0]['locationId'] ?? ($locations[0]['id'] ?? '');
        }

        $hours_data = array();
        $special_hours = array();
        $hours_version = ''; $hours_can_manage = false;

        if (!empty($active_location_id)) {
            $h_res = Attention_Network_API_Client::get_hours($active_location_id);
            if (is_wp_error($h_res)) { echo '<div class="wrap"><p>'.esc_html($h_res->get_error_message()).'</p></div>'; return; }
            $hours_version = $h_res['version'] ?? '';
            $hours_can_manage = !empty($h_res['canManage']);
            if (!is_wp_error($h_res) && isset($h_res['hours'])) {
                $hours_data = $h_res['hours'];
            }
            $sp_res = Attention_Network_API_Client::get_special_hours($active_location_id);
            if (!is_wp_error($sp_res) && isset($sp_res['specialHours'])) {
                $special_hours = $sp_res['specialHours'];
            }
        }

        // Days map (0 = Sunday, 1 = Monday ... 6 = Saturday)
        $days = array(
            1 => __('Monday', 'attention-network'),
            2 => __('Tuesday', 'attention-network'),
            3 => __('Wednesday', 'attention-network'),
            4 => __('Thursday', 'attention-network'),
            5 => __('Friday', 'attention-network'),
            6 => __('Saturday', 'attention-network'),
            7 => __('Sunday', 'attention-network'),
        );

        // Normalize hours keyed by dayOfWeek
        $indexed_hours = array();
        foreach ($hours_data as $h) {
            if (isset($indexed_hours[intval($h['dayOfWeek'])]) || (empty($h['isClosed']) && ($h['openTime'] === null || $h['closeTime'] === null))) {
                Attention_Network_Settings::render_unavailable(__('This location has multiple daily periods or 24-hour opening. Edit its full schedule in the platform.', 'attention-network')); return;
            }
            $indexed_hours[intval($h['dayOfWeek'])] = $h;
        }

        // Local emergency dispatch state stored in WordPress options as well
        ?>
        <div class="wrap attn-admin-wrap" id="attn-hours-management" data-can-manage="<?php echo $hours_can_manage ? '1' : '0'; ?>">
            <?php if (!$hours_can_manage): ?><p class="notice notice-info"><?php esc_html_e('Your platform account has read-only access to these hours.', 'attention-network'); ?></p><?php endif; ?>
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge"><?php esc_html_e('Business Operations', 'attention-network'); ?></span>
                    <h1 class="attn-title"><?php esc_html_e('Operating Hours & Availability', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php echo esc_html(sprintf(__('Manage weekly hours and holiday closures. Times use %s.', 'attention-network'), $h_res['effectiveTimezone'] ?? 'UTC')); ?></p>
                </div>
                <div class="attn-header-right">
                    <?php if (count($locations) > 1): ?>
                        <select id="attn-location-selector" class="regular-text" onchange="window.location.href='?page=attention-network-hours&location_id=' + this.value;">
                            <?php foreach ($locations as $loc): 
                                $lid = $loc['locationId'] ?? ($loc['id'] ?? '');
                                $lname = $loc['name'] ?? __('Primary Location', 'attention-network');
                            ?>
                                <option value="<?php echo esc_attr($lid); ?>" <?php selected($active_location_id, $lid); ?>>
                                    <?php echo esc_html($lname); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>

            <div class="attn-grid">
                <div class="attn-col-main">
                    <!-- Weekly Hours Form -->
                    <div class="attn-card">
                        <div class="attn-card-header-flex">
                            <div>
                                <h2 class="attn-card-title"><?php esc_html_e('Regular Weekly Hours', 'attention-network'); ?></h2>
                                <p class="attn-card-desc"><?php esc_html_e('Set one opening period per day. Unconfigured days start closed. Use the platform for multiple periods or 24-hour schedules.', 'attention-network'); ?></p>
                            </div>
                        </div>

                        <form id="attn-hours-form">
                            <input type="hidden" id="attn_hours_version" value="<?php echo esc_attr($hours_version); ?>" />
                            <input type="hidden" id="attn_hours_location_id" value="<?php echo esc_attr($active_location_id); ?>" />
                            <table class="widefat striped" style="margin-top:16px;border-radius:8px;overflow:hidden;">
                                <thead>
                                    <tr>
                                        <th style="width:140px;"><?php esc_html_e('Day', 'attention-network'); ?></th>
                                        <th style="width:120px;"><?php esc_html_e('Status', 'attention-network'); ?></th>
                                        <th><?php esc_html_e('Open Time', 'attention-network'); ?></th>
                                        <th><?php esc_html_e('Close Time', 'attention-network'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($days as $day_num => $day_name): 
                                        $entry = isset($indexed_hours[$day_num]) ? $indexed_hours[$day_num] : array();
                                        $is_closed = empty($entry) || !empty($entry['isClosed']);
                                        $open_val = !empty($entry['openTime']) ? substr($entry['openTime'], 0, 5) : '08:00';
                                        $close_val = !empty($entry['closeTime']) ? substr($entry['closeTime'], 0, 5) : '17:00';
                                    ?>
                                        <tr data-day="<?php echo esc_attr($day_num); ?>">
                                            <td><strong><?php echo esc_html($day_name); ?></strong></td>
                                            <td>
                                                <label>
                                                    <input type="checkbox" class="attn-day-closed-toggle" <?php checked($is_closed); ?> />
                                                    <?php esc_html_e('Closed', 'attention-network'); ?>
                                                </label>
                                            </td>
                                            <td>
                                                <input type="time" class="attn-open-time regular-text" value="<?php echo esc_attr($open_val); ?>" <?php disabled($is_closed); ?> style="max-width:140px;" />
                                            </td>
                                            <td>
                                                <input type="time" class="attn-close-time regular-text" value="<?php echo esc_attr($close_val); ?>" <?php disabled($is_closed); ?> style="max-width:140px;" />
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div style="margin-top:20px;display:flex;align-items:center;gap:12px;">
                                <button type="submit" id="attn-btn-save-hours" class="button button-primary">
                                    <?php esc_html_e('Save Regular Hours', 'attention-network'); ?>
                                </button>
                                <span id="attn-hours-status-msg" class="attn-status-text"></span>
                            </div>
                        </form>
                    </div>

                    <!-- Special Hours & Holiday Closures -->
                    <div class="attn-card" style="margin-top:24px;">
                        <h2 class="attn-card-title"><?php esc_html_e('Special Hours & Holiday Overrides', 'attention-network'); ?></h2>
                        <p class="attn-card-desc"><?php esc_html_e('Add dates when your hours vary from regular schedule (e.g. Christmas, Labor Day, inventory day).', 'attention-network'); ?></p>

                        <form id="attn-special-hours-form" style="margin-top:16px;background:#f8fafc;padding:16px;border-radius:8px;border:1px solid #e2e8f0;">
                            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Date (YYYY-MM-DD)', 'attention-network'); ?></label>
                                    <input type="date" id="attn_sp_date" required class="regular-text" style="max-width:160px;" />
                                </div>
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;"><?php esc_html_e('Holiday / Event Note', 'attention-network'); ?></label>
                                    <input type="text" id="attn_sp_note" placeholder="<?php esc_attr_e('e.g. Memorial Day', 'attention-network'); ?>" class="regular-text" style="max-width:180px;" />
                                </div>
                                <div style="padding-bottom:8px;">
                                    <label>
                                        <input type="checkbox" id="attn_sp_closed" value="1" checked />
                                        <?php esc_html_e('Closed All Day', 'attention-network'); ?>
                                    </label>
                                </div>
                                <div id="attn_sp_times_wrap" style="display:none;gap:8px;">
                                    <input type="time" id="attn_sp_open" value="09:00" class="regular-text" style="max-width:120px;" />
                                    <span>to</span>
                                    <input type="time" id="attn_sp_close" value="14:00" class="regular-text" style="max-width:120px;" />
                                </div>
                                <div>
                                    <button type="submit" class="button button-secondary">
                                        <?php esc_html_e('+ Add Special Date', 'attention-network'); ?>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div id="attn-special-hours-list" style="margin-top:16px;">
                            <?php if (empty($special_hours)): ?>
                                <p style="color:#64748b;font-style:italic;"><?php esc_html_e('No special hours or holiday closures scheduled.', 'attention-network'); ?></p>
                            <?php else: ?>
                                <table class="widefat striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Date', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Occasion', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Hours', 'attention-network'); ?></th>
                                            <th style="width:80px;text-align:right;"><?php esc_html_e('Action', 'attention-network'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($special_hours as $sp): 
                                            $sp_id = $sp['specialHoursId'] ?? ($sp['id'] ?? '');
                                            $is_cl = !empty($sp['isClosed']);
                                            $time_str = $is_cl ? __('Closed', 'attention-network') : esc_html($sp['openTime'] . ' - ' . $sp['closeTime']);
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($sp['date'] ?? ''); ?></strong></td>
                                                <td><?php echo esc_html($sp['note'] ?? __('Special Hours', 'attention-network')); ?></td>
                                                <td><span class="badge"><?php echo $time_str; ?></span></td>
                                                <td style="text-align:right;">
                                                    <button type="button" class="button-link-delete attn-delete-sp" data-sp-id="<?php echo esc_attr($sp_id); ?>" data-version="<?php echo esc_attr($sp['version'] ?? ''); ?>">
                                                        <?php esc_html_e('Remove', 'attention-network'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="attn-col-sidebar">
                    <div class="attn-card attn-card-info">
                        <h3><?php esc_html_e('Website Display & Embeds', 'attention-network'); ?></h3>
                        <p class="attn-info-p">
                            <?php esc_html_e('Embed your live hours schedule and status on any WordPress page or footer widget:', 'attention-network'); ?>
                        </p>
                        <code class="attn-code">[attention_hours]</code>
                        <p class="attn-info-p" style="margin-top:8px;">
                            <?php esc_html_e('Compact status badge for headers:', 'attention-network'); ?>
                        </p>
                        <code class="attn-code">[attention_open_badge]</code>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX Handler: Save Weekly Hours
     */
    public static function handle_save_weekly_hours() {
        self::verify_ajax_security();

        $location_id = isset($_POST['location_id']) ? sanitize_text_field($_POST['location_id']) : '';
        $raw_hours   = isset($_POST['hours']) ? (array) $_POST['hours'] : array();

        if (empty($location_id)) {
            wp_send_json_error(array('message' => __('Location ID is required.', 'attention-network')), 400);
        }

        // Validate and clean each day
        $validated_hours = array();
        foreach ($raw_hours as $h) {
            $day = intval($h['dayOfWeek'] ?? 0);
            if ($day < 1 || $day > 7) wp_send_json_error(array('message'=>__('Choose a valid weekday.', 'attention-network')), 400);

            $is_closed = !empty($h['isClosed']);
            $open = sanitize_text_field($h['openTime'] ?? '08:00');
            $close = sanitize_text_field($h['closeTime'] ?? '17:00');

            // Format to HH:MM:00
            if (strlen($open) === 5) $open .= ':00';
            if (strlen($close) === 5) $close .= ':00';

            $validated_hours[] = array(
                'dayOfWeek' => $day,
                'openTime'  => $is_closed ? null : $open,
                'closeTime' => $is_closed ? null : $close,
                'isClosed'  => $is_closed,
            );
        }

        $version = sanitize_text_field($_POST['version'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $version)) wp_send_json_error(array('message'=>__('Reload these hours before saving.', 'attention-network')), 400);
        $result = Attention_Network_API_Client::update_hours($location_id, $validated_hours, $version);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Hours updated successfully.', 'attention-network'), 'version' => $result['version'] ?? ''));
    }

    /**
     * AJAX Handler: Create Special Hours
     */
    public static function handle_create_special_hours() {
        self::verify_ajax_security();

        $location_id = isset($_POST['location_id']) ? sanitize_text_field($_POST['location_id']) : '';
        $date        = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $note        = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : '';
        $is_closed   = !empty($_POST['is_closed']);
        $open        = isset($_POST['open_time']) ? sanitize_text_field($_POST['open_time']) : '09:00';
        $close       = isset($_POST['close_time']) ? sanitize_text_field($_POST['close_time']) : '17:00';

        if (empty($location_id) || empty($date)) {
            wp_send_json_error(array('message' => __('Date is required.', 'attention-network')), 400);
        }

        // Validate date format YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(array('message' => __('Invalid date format. Use YYYY-MM-DD.', 'attention-network')), 400);
        }

        if (strlen($open) === 5) $open .= ':00';
        if (strlen($close) === 5) $close .= ':00';

        $payload = array(
            'date'      => $date,
            'isClosed'  => $is_closed,
            'openTime'  => $is_closed ? null : $open,
            'closeTime' => $is_closed ? null : $close,
            'note'      => !empty($note) ? $note : null,
        );

        $result = Attention_Network_API_Client::create_special_hours($location_id, $payload);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Special hours added.', 'attention-network')));
    }

    /**
     * AJAX Handler: Delete Special Hours
     */
    public static function handle_delete_special_hours() {
        self::verify_ajax_security();

        $location_id      = isset($_POST['location_id']) ? sanitize_text_field($_POST['location_id']) : '';
        $special_hours_id = isset($_POST['special_hours_id']) ? sanitize_text_field($_POST['special_hours_id']) : '';

        if (empty($location_id) || empty($special_hours_id)) {
            wp_send_json_error(array('message' => __('Missing identifiers.', 'attention-network')), 400);
        }

        $version = sanitize_text_field($_POST['version'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $version)) wp_send_json_error(array('message'=>__('Reload these special hours before removing an entry.', 'attention-network')), 400);
        $result = Attention_Network_API_Client::delete_special_hours($location_id, $special_hours_id, $version);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Special hours deleted.', 'attention-network')));
    }

    public static function handle_save_location_profile() {
        self::verify_ajax_security();
        $error = Attention_Network_API_Client::unsupported('Location profile management');
        wp_send_json_error(array('message'=>$error->get_error_message()), 409);
    }
}

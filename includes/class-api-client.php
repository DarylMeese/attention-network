<?php
/**
 * Attention Network API Client
 *
 * Handles scoped HTTP requests and current public/management DTOs. Responses are never shared-cached.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_API_Client {

    /** Serialize read/modify/write of the website's local inquiry and portfolio options. */
    private static function local_write($option, $callback) {
        global $wpdb;
        $lock = 'attn_local_' . substr(hash('sha256', $wpdb->options . ':' . $option), 0, 48);
        if ((string)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 2)', $lock)) !== '1') return new WP_Error('local_write_busy', __('This website is saving another change. Try again in a moment; nothing was saved by this request.', 'attention-network'));
        try {
            wp_cache_delete($option, 'options');
            wp_cache_delete('alloptions', 'options');
            return call_user_func($callback);
        } finally {
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }

    /**
     * Get configured API Base URL
     */
    public static function get_api_url() {
        $url = get_option('attention_network_api_url', 'https://app.attentionnetwork.net/api');
        return untrailingslashit($url);
    }

    /**
     * Get configured Organization ID
     */
    public static function get_org_id() {
        return trim(get_option('attention_network_org_id', ''));
    }

    /**
     * Get configured API Key (Bearer Token)
     */
    public static function get_api_key() {
        return Attention_Network_Platform_Session::get_access_token();
    }

    /**
     * Get configured cache TTL in seconds (default 10 minutes)
     */
    public static function get_cache_ttl() {
        return max(0, min(86400, intval(get_option('attention_network_cache_ttl', 600))));
    }

    /**
     * Check if client is properly configured
     */
    public static function is_configured() {
        return !is_wp_error(self::validate_api_url(self::get_api_url())) && self::valid_uuid(self::get_org_id());
    }

    public static function valid_uuid($value) {
        return is_string($value) && 1 === preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', $value);
    }

    public static function get_site_id() {
        return trim(get_option('attention_network_site_id', ''));
    }

    public static function unsupported($feature) {
        return new WP_Error('platform_action_unavailable', sprintf(__('%s is not connected in this WordPress plugin. Manage it in the Attention Network app. Nothing was changed.', 'attention-network'), $feature));
    }

    /**
     * Validate API URL against SSRF and protocol manipulation
     *
     * @param string $url URL to validate
     * @return true|WP_Error True if valid, WP_Error if disallowed
     */
    public static function validate_api_url($url) {
        $parts = wp_parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return new WP_Error('invalid_api_url', __('Invalid API URL format.', 'attention-network'));
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https' || !empty($parts['user']) || !empty($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return new WP_Error('invalid_scheme', __('Use a public HTTPS API URL without credentials, a query, or a fragment.', 'attention-network'));
        }
        if (!wp_http_validate_url($url)) {
            return new WP_Error('ssrf_blocked', __('The API URL must resolve to an allowed public HTTPS address.', 'attention-network'));
        }

        return true;
    }

    /**
     * Sanitize and validate API URL setting
     */
    public static function sanitize_api_url($url) {
        $clean = esc_url_raw(trim($url));
        if (empty($clean)) {
            return '';
        }
        $validation = self::validate_api_url($clean);
        if (is_wp_error($validation)) {
            add_settings_error(
                'attention_network_api_url',
                'invalid_api_url',
                $validation->get_error_message()
            );
            return get_option('attention_network_api_url', 'https://app.attentionnetwork.net/api');
        }
        return untrailingslashit($clean);
    }

    /**
     * Send HTTP request to Attention platform API
     *
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $endpoint API endpoint relative to platform URL (e.g. '/organizations/xyz/menus')
     * @param array|null $body Request payload
     * @param bool $use_cache Whether to read/write transient cache for GET requests
     * @return array|WP_Error Parsed JSON response array or WP_Error on failure
     */
    public static function request($method, $endpoint, $body = null, $use_cache = false, $public = false) {
        $api_url = self::get_api_url();
        $api_key = $public ? '' : self::get_api_key();
        if (!$public && is_wp_error($api_key)) return $api_key;

        if (empty($api_url)) {
            return new WP_Error('config_missing', __('API Base URL is not configured.', 'attention-network'));
        }

        $url_validation = self::validate_api_url($api_url);
        if (is_wp_error($url_validation)) {
            return $url_validation;
        }

        if (!is_string($endpoint) || substr($endpoint, 0, 1) !== '/' || strpos($endpoint, '//') !== false || strpos($endpoint, '..') !== false || preg_match('/[\\\\\r\n#]/', $endpoint)) {
            return new WP_Error('invalid_endpoint', __('The requested platform address is invalid.', 'attention-network'));
        }
        if (!self::valid_uuid(self::get_org_id())) return new WP_Error('org_missing', __('Choose a valid organization UUID in Settings.', 'attention-network'));
        if (!$public && empty($api_key)) return new WP_Error('authentication_required', __('Sign in to the Attention Network platform before managing this organization.', 'attention-network'));
        $full_url = $api_url . $endpoint;

        $headers = array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'   => 'Attention-Network-WP/' . ATTENTION_NETWORK_VERSION,
            'X-Organization-Id' => self::get_org_id(),
        );

        if (!$public && !empty($api_key)) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }

        $args = array(
            'method'      => $method,
            'headers'     => $headers,
            'timeout'     => 15,
            'sslverify'   => true,
            'redirection' => 0,
            'limit_response_size' => 2 * 1024 * 1024,
        );

        if ($body !== null && in_array($method, array('POST', 'PUT', 'PATCH', 'DELETE'), true)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_safe_remote_request($full_url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $raw_body    = wp_remote_retrieve_body($response);
        $data        = json_decode($raw_body, true);

        if ($status_code < 200 || $status_code >= 300) {
            $message = is_array($data) && !empty($data['message']) && is_string($data['message']) ? $data['message'] : '';
            if (!$message && is_array($data) && !empty($data['error']) && is_string($data['error']) && strpos($data['error'], '_') === false) $message = $data['error'];
            if ($status_code === 401) $message = __('Your platform session has expired or is unavailable. Sign in again in Settings.', 'attention-network');
            if ($status_code === 403 && !$message) $message = __('Your platform account cannot perform this action for this organization.', 'attention-network');
            if ($status_code === 409 && !$message) $message = __('This record changed. Reload it before saving again.', 'attention-network');
            if ($status_code === 429) $message = __('Too many requests. Wait a moment before trying again.', 'attention-network');
            if (!$message || $status_code >= 500) $message = __('The platform request could not complete. Try again later; the plugin has not reported a successful save.', 'attention-network');
            return new WP_Error('api_error', sanitize_text_field($message), array('status' => $status_code));
        }
        if ($status_code === 204 || $raw_body === '') return array();
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) return new WP_Error('invalid_response', __('The platform returned an unreadable response. No successful save could be confirmed.', 'attention-network'));
        // Management data and changing public eligibility must never survive in shared transients.
        return $data;
    }

    public static function public_request($endpoint) {
        return self::request('GET', $endpoint, null, false, true);
    }

    /**
     * Purge transient caches for menus
     */
    public static function purge_menu_cache($menu_id = null) {
        global $wpdb;
        // Retire only this plugin's original attn_<MD5> cache entries, never other plugins' caches.
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name REGEXP '^_transient_(timeout_)?attn_[a-f0-9]{32}$'");
    }

    /* -------------------------------------------------------------------------
     * Menu Resource Operations
     * ------------------------------------------------------------------------- */

    private static function object_meta($value) {
        if (is_string($value)) $value = json_decode($value, true);
        return is_array($value) ? $value : array();
    }

    public static function normalize_menu($menu) {
        if (!is_array($menu)) return $menu;
        $menu['id'] = $menu['menuId'] ?? '';
        $menu['is_active'] = ($menu['status'] ?? '') === 'active';
        $menu['description'] = ''; // Menu/section descriptions are not part of the platform contract.
        foreach (($menu['sections'] ?? array()) as $s => $section) {
            $section['id'] = $section['sectionId'] ?? '';
            foreach (($section['items'] ?? array()) as $i => $item) {
                $meta = self::object_meta($item['meta'] ?? array());
                $item['id'] = $item['itemId'] ?? '';
                $item['price_amount'] = isset($item['price']) ? (float)$item['price'] : null;
                $item['photo_url'] = $meta['image_url'] ?? '';
                $item['dietary_tags'] = $meta['dietary_tags'] ?? array();
                $item['abv'] = $meta['abv'] ?? null;
                $item['ibu'] = $meta['ibu'] ?? null;
                $item['meta'] = $meta;
                foreach (($item['options'] ?? array()) as $o => $option) {
                    $option['id'] = $option['optionId'] ?? '';
                    $option['price_modifier'] = isset($option['priceDelta']) ? (float)$option['priceDelta'] : null;
                    $item['options'][$o] = $option;
                }
                $section['items'][$i] = $item;
            }
            $menu['sections'][$s] = $section;
        }
        $display = $menu['displaySettings'] ?? array();
        if (!is_array($display)) $display = array();
        foreach (array('displayMode'=>'layout_mode', 'theme'=>'theme_preset', 'showImages'=>'show_images', 'showPrices'=>'show_prices', 'showAbv'=>'show_abv', 'showIbu'=>'show_ibu') as $key=>$alias) {
            if (array_key_exists($key, $display)) $display[$alias] = $display[$key];
        }
        $menu['displaySettings'] = $display;
        return $menu;
    }

    private static function menu_payload($data) {
        return array('name' => trim($data['name'] ?? ''), 'status' => !empty($data['is_active']) ? 'active' : 'inactive');
    }

    private static function item_payload($data, $base_meta = array()) {
        $meta = array_merge($base_meta, array('dietary_tags' => array_values($data['dietary_tags'] ?? array()), 'image_url' => $data['photo_url'] ?? ''));
        return array('name' => trim($data['name'] ?? ''), 'description' => $data['description'] ?? '', 'price' => $data['price_amount'] ?? null, 'calories' => $data['calories'] ?? null, 'meta' => $meta);
    }

    public static function get_public_menus($menu_id = null) {
        $site = self::get_site_id(); $org = self::get_org_id();
        if (!self::valid_uuid($site) || !self::valid_uuid($org) || ($menu_id !== null && !self::valid_uuid($menu_id))) return new WP_Error('public_site_missing', __('Set the public Site UUID and Organization UUID in Settings before displaying menus.', 'attention-network'));
        $result = self::public_request("/tenants/{$site}/organizations/{$org}/menus" . ($menu_id ? '/' . $menu_id : ''));
        if (is_wp_error($result)) return $result;
        foreach ($menu_id ? array($result) : $result as $row) {
            if (!is_array($row) || ($row['status'] ?? '') !== 'active' || ($row['organizationId'] ?? '') !== $org || !self::valid_uuid($row['menuId'] ?? '') || ($menu_id && $row['menuId'] !== $menu_id)) return new WP_Error('public_menu_unavailable', __('This menu is not available for public display.', 'attention-network'));
        }
        return $menu_id ? self::normalize_menu($result) : array_map(array(__CLASS__, 'normalize_menu'), $result);
    }

    /**
     * Fetch all menus for this organization
     */
    public static function get_menus($bypass_cache = false) {
        $org_id = self::get_org_id();
        if (empty($org_id)) {
            return new WP_Error('org_missing', __('Organization ID is not configured.', 'attention-network'));
        }
        $result = self::request('GET', "/organizations/{$org_id}/menus");
        return is_wp_error($result) ? $result : array_map(array(__CLASS__, 'normalize_menu'), $result);
    }

    /**
     * Fetch a single menu
     */
    public static function get_menu($menu_id, $bypass_cache = false) {
        $org_id = self::get_org_id();
        return self::get_full_menu($menu_id, $bypass_cache);
    }

    /**
     * Fetch full menu hierarchy (sections, items, options, display settings)
     */
    public static function get_full_menu($menu_id, $bypass_cache = false) {
        $org_id = self::get_org_id();
        $result = self::request('GET', "/organizations/{$org_id}/menus/{$menu_id}/full");
        return is_wp_error($result) ? $result : self::normalize_menu($result);
    }

    /**
     * Create a menu
     */
    public static function create_menu($data) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        $result = self::request('POST', "/organizations/{$org_id}/menus", self::menu_payload($data));
        if (!is_wp_error($result) && isset($result['menuId'])) $result['id'] = $result['menuId'];
        return $result;
    }

    /**
     * Update a menu
     */
    public static function update_menu($menu_id, $data) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('PATCH', "/organizations/{$org_id}/menus/{$menu_id}", self::menu_payload($data));
    }

    /**
     * Delete a menu
     */
    public static function delete_menu($menu_id) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('DELETE', "/organizations/{$org_id}/menus/{$menu_id}");
    }

    /**
     * Create a section
     */
    public static function create_section($menu_id, $data) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('POST', "/organizations/{$org_id}/menus/{$menu_id}/sections", array('name' => $data['name'] ?? ''));
    }

    /**
     * Update a section
     */
    public static function update_section($menu_id, $section_id, $data) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('PATCH', "/organizations/{$org_id}/menus/{$menu_id}/sections/{$section_id}", array('name' => $data['name'] ?? ''));
    }

    /**
     * Delete a section
     */
    public static function delete_section($menu_id, $section_id) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('DELETE', "/organizations/{$org_id}/menus/{$menu_id}/sections/{$section_id}");
    }

    /**
     * Bulk reorder sections
     */
    public static function reorder_sections($menu_id, array $section_ids) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        $sections = array();
        foreach (array_values($section_ids) as $index => $id) $sections[] = array('sectionId' => $id, 'displayOrder' => $index);
        return self::request('PATCH', "/organizations/{$org_id}/menus/{$menu_id}/sections/reorder", array('sections' => $sections));
    }

    /**
     * Create an item in a section
     */
    public static function create_item($menu_id, $section_id, $data) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('POST', "/organizations/{$org_id}/menus/{$menu_id}/sections/{$section_id}/items", self::item_payload($data));
    }

    /**
     * Update an item
     */
    public static function update_item($menu_id, $section_id, $item_id, $data) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        $current = self::get_full_menu($menu_id, true);
        if (is_wp_error($current)) return $current;
        $base_meta = array(); $found = false;
        foreach (($current['sections'] ?? array()) as $section) if (($section['id'] ?? '') === $section_id) foreach (($section['items'] ?? array()) as $item) if (($item['id'] ?? '') === $item_id) { $base_meta = $item['meta']; $found = true; }
        if (!$found) return new WP_Error('item_missing', __('This item is no longer in the selected menu section. Reload the menu.', 'attention-network'));
        return self::request('PATCH', "/organizations/{$org_id}/menus/{$menu_id}/sections/{$section_id}/items/{$item_id}", self::item_payload($data, $base_meta));
    }

    /**
     * Delete an item
     */
    public static function delete_item($menu_id, $section_id, $item_id) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('DELETE', "/organizations/{$org_id}/menus/{$menu_id}/sections/{$section_id}/items/{$item_id}");
    }

    /**
     * Create an option for an item
     */
    public static function create_option($menu_id, $section_id, $item_id, $data) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('POST', "/organizations/{$org_id}/menus/{$menu_id}/sections/{$section_id}/items/{$item_id}/options", array('name' => $data['name'] ?? '', 'priceDelta' => $data['price_modifier'] ?? null));
    }

    /**
     * Delete an option
     */
    public static function delete_option($menu_id, $section_id, $item_id, $option_id) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('DELETE', "/organizations/{$org_id}/menus/{$menu_id}/sections/{$section_id}/items/{$item_id}/options/{$option_id}");
    }

    /**
     * Upsert display settings
     */
    public static function upsert_display_settings($menu_id, $data) {
        $org_id = self::get_org_id();
        self::purge_menu_cache();
        return self::request('PUT', "/organizations/{$org_id}/menus/{$menu_id}/display-settings", array('displayMode' => $data['layout_mode'] ?? 'standard', 'theme' => $data['theme_preset'] ?? 'light', 'showImages' => !empty($data['show_images']), 'showPrices' => !empty($data['show_prices']), 'showAbv' => !empty($data['show_abv']), 'showIbu' => !empty($data['show_ibu'])));
    }

    /**
     * Fetch menu capability status for organization
     */
    public static function get_capability() {
        $org_id = self::get_org_id();
        if (empty($org_id)) {
            return new WP_Error('org_missing', __('Organization ID is not configured.', 'attention-network'));
        }
        return self::request('GET', "/organizations/{$org_id}/menus/capability", null, false);
    }

    /**
     * Test connection to the Attention platform
     */
    public static function test_connection() {
        $capability = self::get_capability();
        if (is_wp_error($capability)) {
            return $capability;
        }
        return array(
            'success'    => true,
            'message'    => __('Successfully connected to Attention Network!', 'attention-network'),
            'capability' => $capability,
        );
    }

    /* -------------------------------------------------------------------------
     * Location & Hours Operations
     * ------------------------------------------------------------------------- */

    /**
     * Fetch all locations for this organization
     */
    public static function get_locations($use_cache = true) {
        $org_id = self::get_org_id();
        if (empty($org_id)) {
            return new WP_Error('org_missing', __('Organization ID is not configured.', 'attention-network'));
        }
        return self::request('GET', "/organizations/{$org_id}/locations", null, $use_cache);
    }

    /**
     * Fetch weekly hours for a specific location
     */
    public static function get_hours($location_id, $use_cache = true) {
        $org_id = self::get_org_id();
        $loc_id = sanitize_text_field($location_id);
        return self::request('GET', "/organizations/{$org_id}/locations/{$loc_id}/hours", null, $use_cache);
    }

    /**
     * Update weekly regular hours for a location
     */
    public static function update_hours($location_id, $hours_data, $version = null) {
        $org_id = self::get_org_id();
        $loc_id = sanitize_text_field($location_id);
        self::purge_menu_cache();
        return self::request('PUT', "/organizations/{$org_id}/locations/{$loc_id}/hours", array('hours' => $hours_data, 'version' => $version));
    }

    /**
     * Fetch special hours and holiday closures for a location
     */
    public static function get_special_hours($location_id, $use_cache = true) {
        $org_id = self::get_org_id();
        $loc_id = sanitize_text_field($location_id);
        return self::request('GET', "/organizations/{$org_id}/locations/{$loc_id}/special-hours", null, $use_cache);
    }

    /**
     * Create a special hours entry (holiday/event override)
     */
    public static function create_special_hours($location_id, $data) {
        $org_id = self::get_org_id();
        $loc_id = sanitize_text_field($location_id);
        self::purge_menu_cache();
        return self::request('POST', "/organizations/{$org_id}/locations/{$loc_id}/special-hours", $data);
    }

    /**
     * Delete a special hours entry
     */
    public static function delete_special_hours($location_id, $special_hours_id, $version = null) {
        $org_id = self::get_org_id();
        $loc_id = sanitize_text_field($location_id);
        $sp_id  = sanitize_text_field($special_hours_id);
        self::purge_menu_cache();
        return self::request('DELETE', "/organizations/{$org_id}/locations/{$loc_id}/special-hours/{$sp_id}?version=" . rawurlencode((string)$version));
    }

    /* -------------------------------------------------------------------------
     * Staff Directory & "Meet Your Pro" Operations
     * ------------------------------------------------------------------------- */

    /**
     * Fetch all staff members / pros for this organization
     */
    public static function get_staff_members($use_cache = true) {
        return self::unsupported('Staff directory');
    }

    /**
     * Create a staff member / pro card
     */
    public static function create_staff_member($data) {
        return self::unsupported('Staff management');
    }

    /**
     * Update a staff member / pro card
     */
    public static function update_staff_member($card_id, $data) {
        return self::unsupported('Staff management');
    }

    /**
     * Delete a staff member / pro card
     */
    public static function delete_staff_member($card_id) {
        return self::unsupported('Staff management');
    }

    /* -------------------------------------------------------------------------
     * Attention Push Notification Operations
     * ------------------------------------------------------------------------- */

    /**
     * Send an Attention Push notification broadcast to opted-in subscribers
     */
    public static function send_push_notification($payload) {
        return self::unsupported('Push notifications');
    }

    /* -------------------------------------------------------------------------
     * Offers, Specials & Coupons Operations
     * ------------------------------------------------------------------------- */

    /**
     * Fetch offers for organization (with local persistence fallback)
     */
    public static function get_offers($use_cache = true) {
        return self::unsupported('Offer displays');
    }

    /**
     * Save (create or update) an offer
     */
    public static function save_offer($data) {
        return self::unsupported('Offer management');
    }

    /**
     * Delete an offer
     */
    public static function delete_offer($offer_id) {
        return self::unsupported('Offer management');
    }

    /* -------------------------------------------------------------------------
     * Customer Reviews & Reputation Operations
     * ------------------------------------------------------------------------- */

    /**
     * Fetch customer reviews (from platform API or local verified store)
     */
    public static function get_reviews($use_cache = true) {
        $org = self::get_org_id();
        $result = self::public_request("/organizations/{$org}/reviews" . ($use_cache ? '' : '?limit=10'));
        if (is_wp_error($result)) return $result;
        $items = $result['items'] ?? array();
        if (!$use_cache) {
            foreach ($items as $index => $item) {
                $detail = self::public_request('/reviews/' . ($item['reviewId'] ?? ''));
                if (is_wp_error($detail)) return $detail;
                if (($detail['organizationId'] ?? '') !== $org || ($detail['status'] ?? '') !== 'published') return new WP_Error('review_scope', __('The published review changed. Reload this page.', 'attention-network'));
                $detail['authorName'] = $detail['author']['displayName'] ?? __('Customer', 'attention-network');
                $items[$index] = $detail;
            }
            $result['items'] = $items;
        }
        $result['averageRating'] = count($items) ? array_sum(array_column($items, 'rating')) / count($items) : null;
        $result['totalReviews'] = intval($result['meta']['total'] ?? 0);
        return $result;
    }

    /**
     * Submit an owner response to a review
     */
    public static function submit_review_response($review_id, $response_text) {
        if (!self::valid_uuid($review_id)) return new WP_Error('review_missing', __('Choose a valid published review.', 'attention-network'));
        $review = self::public_request('/reviews/' . $review_id);
        if (is_wp_error($review)) return $review;
        if (($review['organizationId'] ?? '') !== self::get_org_id()) return new WP_Error('review_scope', __('This review belongs to a different organization.', 'attention-network'));
        return self::request(!empty($review['response']) ? 'PATCH' : 'POST', "/reviews/{$review_id}/responses", array('body' => sanitize_textarea_field($response_text)));
    }

    /* -------------------------------------------------------------------------
     * Inbound Leads & Quote Requests Operations
     * ------------------------------------------------------------------------- */

    /**
     * Get inbound leads / quote requests
     */
    public static function get_leads() {
        return get_option('attention_network_leads', array());
    }

    /**
     * Save an inbound lead
     */
    public static function save_lead($data) {
        return self::local_write('attention_network_leads', function() use ($data) {
        $leads = get_option('attention_network_leads', array());
        $lead_id = !empty($data['id']) ? sanitize_text_field($data['id']) : 'lead_' . wp_generate_password(12, false);
        $data['id'] = $lead_id;
        $data['status'] = !empty($data['status']) ? sanitize_text_field($data['status']) : 'new';
        $data['createdAt'] = current_time('mysql');

        array_unshift($leads, $data);
        // Cap local leads queue at 200 items to protect storage limits
        if (count($leads) > 200) {
            $leads = array_slice($leads, 0, 200);
        }
        if (!update_option('attention_network_leads', $leads, false)) return new WP_Error('inquiry_save_failed', __('The inquiry could not be saved. Please contact the website team directly.', 'attention-network'));
        // Website inquiries are WordPress-local; the platform lead-routing API is a different resource.

        return $data;
        });
    }

    /**
     * Update lead status and internal staff notes
     */
    public static function update_lead_status($lead_id, $status, $notes = null) {
        return self::local_write('attention_network_leads', function() use ($lead_id, $status, $notes) {
        if (!in_array($status, array('new','contacted','quoted','scheduled','completed'), true)) return new WP_Error('invalid_status', __('Choose a valid inquiry status.', 'attention-network'));
        $leads = get_option('attention_network_leads', array());
        $found = false;
        foreach ($leads as &$lead) if ($lead['id'] === $lead_id) { $lead['status'] = $status; if ($notes !== null) $lead['internalNotes'] = sanitize_textarea_field($notes); $lead['updatedAt'] = gmdate('Y-m-d H:i:s'); $found = true; break; }
        unset($lead);
        if (!$found) return new WP_Error('inquiry_missing', __('This inquiry no longer exists. Reload the list.', 'attention-network'));
        if (!update_option('attention_network_leads', $leads, false) && get_option('attention_network_leads', array()) !== $leads) return new WP_Error('inquiry_save_failed', __('The inquiry update could not be saved.', 'attention-network'));
        return true;
        });
    }

    /* -------------------------------------------------------------------------
     * Project Showcase / Before & After Portfolio Operations
     * ------------------------------------------------------------------------- */

    /**
     * Get portfolio showcase items
     */
    public static function get_portfolio_items($use_cache = true) {
        return get_option('attention_network_portfolio', array());
    }

    /**
     * Save portfolio item
     */
    public static function save_portfolio_item($data) {
        return self::local_write('attention_network_portfolio', function() use ($data) {
        $items = get_option('attention_network_portfolio', array());
        $was_update = !empty($data['id']);
        $item_id = !empty($data['id']) ? sanitize_text_field($data['id']) : 'port_' . wp_generate_password(12, false);
        $data['id'] = $item_id;
        $data['updatedAt'] = current_time('mysql');
        if (empty($data['createdAt'])) {
            $data['createdAt'] = current_time('mysql');
        }

        $found = false;
        foreach ($items as $idx => $it) {
            if ($it['id'] === $item_id) {
                $items[$idx] = array_merge($it, $data);
                $found = true;
                break;
            }
        }
        if (!$found) {
            if ($was_update) return new WP_Error('project_missing', __('This project no longer exists. Reload the list.', 'attention-network'));
            array_unshift($items, $data);
        }

        if (!update_option('attention_network_portfolio', $items, false) && get_option('attention_network_portfolio', array()) !== $items) return new WP_Error('project_save_failed', __('This project could not be saved.', 'attention-network'));
        self::purge_menu_cache();
        return $data;
        });
    }

    /**
     * Delete portfolio item
     */
    public static function delete_portfolio_item($item_id) {
        return self::local_write('attention_network_portfolio', function() use ($item_id) {
        $item_id = sanitize_text_field($item_id);
        $items = get_option('attention_network_portfolio', array());
        $filtered = array();
        foreach ($items as $it) {
            if ($it['id'] !== $item_id) {
                $filtered[] = $it;
            }
        }
        if (count($filtered) === count($items)) return new WP_Error('project_missing', __('This project no longer exists. Reload the list.', 'attention-network'));
        if (!update_option('attention_network_portfolio', $filtered, false)) return new WP_Error('project_save_failed', __('This project could not be removed.', 'attention-network'));
        self::purge_menu_cache();
        return true;
        });
    }
}


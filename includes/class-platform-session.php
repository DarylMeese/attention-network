<?php
/** Private Platform sessions for the current WordPress administrator and login session. */
if (!defined('ABSPATH')) { exit; }

class Attention_Network_Platform_Session {
    const META_PREFIX = '_attention_network_session_';
    const LOCK_PREFIX = '_attention_network_session_lock_';

    public static function init() {
        add_action('wp_ajax_attention_network_platform_login', array(__CLASS__, 'ajax_login'));
        add_action('wp_ajax_attention_network_platform_logout', array(__CLASS__, 'ajax_logout'));
        add_action('wp_ajax_attention_network_platform_status', array(__CLASS__, 'ajax_status'));
    }

    private static function error($code, $message, $status = 503) {
        return new WP_Error($code, $message, array('status' => $status));
    }

    private static function context() {
        $user = get_current_user_id();
        $session = wp_get_session_token();
        if (!$user || !current_user_can('manage_options') || !is_string($session) || $session === '') {
            return self::error('platform_sign_in_required', 'Sign in to WordPress as an administrator first.', 403);
        }
        $base = Attention_Network_API_Client::get_api_url();
        $org = Attention_Network_API_Client::get_org_id();
        if (is_wp_error(Attention_Network_API_Client::validate_api_url($base)) || !Attention_Network_API_Client::valid_uuid($org)) {
            return self::error('platform_setup_required', 'Save a public HTTPS Platform API address and valid organization ID first.', 400);
        }
        if (!function_exists('openssl_encrypt') || !function_exists('hash_hkdf')) {
            return self::error('platform_crypto_unavailable', 'Secure session storage is unavailable on this WordPress server.');
        }
        $salt = wp_salt('auth');
        $binding = hash('sha256', wp_json_encode(array((int) $user, hash_hmac('sha256', $session, $salt), $base, $org)));
        return array('user' => (int) $user, 'base' => $base, 'org' => $org, 'binding' => $binding,
            'meta' => self::META_PREFIX . $binding, 'lock' => self::LOCK_PREFIX . $binding,
            'key' => hash_hkdf('sha256', $salt, 32, 'attention-network-platform-session-v1', $binding));
    }

    private static function current($context) {
        $now = self::context();
        return !is_wp_error($now) && hash_equals($context['binding'], $now['binding']);
    }

    private static function load($context) {
        $stored = get_user_meta($context['user'], $context['meta'], true);
        if (!is_string($stored) || $stored === '' || strlen($stored) > 65536) { return null; }
        $envelope = json_decode($stored, true);
        if (!is_array($envelope) || ($envelope['v'] ?? null) !== 1) { return null; }
        foreach (array('iv', 'tag', 'ciphertext') as $field) {
            if (!isset($envelope[$field]) || !is_string($envelope[$field])) { return null; }
        }
        $iv = base64_decode($envelope['iv'] ?? '', true);
        $tag = base64_decode($envelope['tag'] ?? '', true);
        $cipher = base64_decode($envelope['ciphertext'] ?? '', true);
        if ($iv === false || strlen($iv) !== 12 || $tag === false || strlen($tag) !== 16 || $cipher === false) { return null; }
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $context['key'], OPENSSL_RAW_DATA, $iv, $tag, $context['binding']);
        if (!is_string($plain)) { return null; }
        $value = json_decode($plain, true);
        return is_array($value) && self::token_expiry($value['access_token'] ?? '', 'access') && self::token_expiry($value['refresh_token'] ?? '', 'refresh') ? $value : null;
    }

    // The Platform authenticates these tokens; decoding here schedules refresh only, never grants authority.
    private static function token_expiry($token, $type) {
        if (!is_string($token) || strlen($token) > 16384) { return 0; }
        $parts = explode('.', $token);
        if (count($parts) !== 3) { return 0; }
        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $claims = json_decode(base64_decode($payload, true) ?: '', true);
        return is_array($claims) && ($claims['type'] ?? null) === $type && isset($claims['exp']) && is_int($claims['exp']) ? $claims['exp'] : 0;
    }

    private static function transport($context, $action, $body, $access = null) {
        $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
        if ($access) { $headers['Authorization'] = 'Bearer ' . $access; }
        $response = wp_safe_remote_request($context['base'] . '/auth/' . $action, array(
            'method' => 'POST', 'headers' => $headers, 'body' => wp_json_encode($body),
            'timeout' => 15, 'redirection' => 0, 'sslverify' => true, 'reject_unsafe_urls' => true,
            'limit_response_size' => 131072,
        ));
        if (is_wp_error($response)) { return self::error('platform_session_unconfirmed', 'The Platform session request could not be confirmed. Try again.'); }
        $status = wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return self::error($status === 401 || $status === 403 ? 'platform_sign_in_required' : 'platform_session_unconfirmed',
                $action === 'logout' ? 'Platform sign out could not be confirmed. Your saved session has not been cleared. Try again.' :
                ($status === 401 || $status === 403 ? 'Platform sign in was not accepted. Check your account and sign in again.' : 'The Platform session is temporarily unavailable. Try again.'),
                $status === 401 || $status === 403 ? 401 : 503);
        }
        $value = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($value)) { return self::error('platform_session_unconfirmed', 'The Platform session response could not be confirmed. Try again.'); }
        return $value;
    }

    private static function delete_lock($context, $value) {
        global $wpdb;
        // Compare-and-delete prevents an expired request from removing a newer request's lock.
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $context['lock'], $value));
        wp_cache_delete($context['lock'], 'options');
        return $deleted;
    }

    private static function lock($context) {
        $value = wp_json_encode(array('owner' => wp_generate_uuid4(), 'expires' => time() + 60));
        if (add_option($context['lock'], $value, '', 'no')) { return $value; }
        $old = get_option($context['lock'], '');
        $decoded = is_string($old) ? json_decode($old, true) : null;
        if (is_array($decoded) && is_int($decoded['expires'] ?? null) && $decoded['expires'] < time()) {
            self::delete_lock($context, $old);
            if (add_option($context['lock'], $value, '', 'no')) { return $value; }
        }
        return self::error('platform_session_busy', 'Another session request is finishing. Wait a moment and try again.', 409);
    }

    private static function save($context, $value, $lock) {
        if (!self::current($context) || get_option($context['lock'], '') !== $lock) {
            return self::error('platform_session_changed', 'The WordPress session or organization changed. Sign in again.', 409);
        }
        try {
            $iv = random_bytes(12);
            $tag = '';
            $cipher = openssl_encrypt(wp_json_encode($value), 'aes-256-gcm', $context['key'], OPENSSL_RAW_DATA, $iv, $tag, $context['binding']);
            if (!is_string($cipher)) { throw new RuntimeException('Encryption unavailable'); }
            $stored = wp_json_encode(array('v' => 1, 'iv' => base64_encode($iv), 'tag' => base64_encode($tag), 'ciphertext' => base64_encode($cipher)));
            update_user_meta($context['user'], $context['meta'], $stored);
            if (get_user_meta($context['user'], $context['meta'], true) !== $stored) { throw new RuntimeException('Storage unavailable'); }
            return true;
        } catch (Throwable $error) {
            return self::error('platform_session_storage_unavailable', 'The Platform session could not be saved securely. Sign in again.');
        }
    }

    private static function accepted_tokens($response) {
        return self::token_expiry($response['access_token'] ?? '', 'access') > time() && self::token_expiry($response['refresh_token'] ?? '', 'refresh') > time();
    }

    private static function status_value($value) {
        $connected = is_array($value) && self::token_expiry($value['refresh_token'] ?? '', 'refresh') > time();
        return array('connected' => $connected, 'email' => $connected && is_string($value['email'] ?? null) ? $value['email'] : '', 'needsLogin' => !$connected);
    }

    public static function status() {
        $context = self::context();
        return is_wp_error($context) ? $context : self::status_value(self::load($context));
    }

    public static function login($email, $password) {
        $context = self::context();
        if (is_wp_error($context)) { return $context; }
        if (!is_ssl()) { return self::error('platform_https_required', 'Open WordPress administration over HTTPS before signing in.', 400); }
        if (!is_string($email) || !is_email($email) || strlen($email) > 255 || !is_string($password) || $password === '' || strlen($password) > 1024) {
            return self::error('platform_credentials_required', 'Enter a valid email address and password.', 400);
        }
        $lock = self::lock($context);
        if (is_wp_error($lock)) { return $lock; }
        try {
            $response = self::transport($context, 'login', array('email' => $email, 'password' => $password, 'token_mode' => true));
            if (is_wp_error($response)) { return $response; }
            if (!self::accepted_tokens($response)) { return self::error('platform_session_unconfirmed', 'The Platform did not return a usable session. Try signing in again.'); }
            $value = array('access_token' => $response['access_token'], 'refresh_token' => $response['refresh_token'], 'email' => is_string($response['email'] ?? null) ? $response['email'] : $email);
            $saved = self::save($context, $value, $lock);
            if (is_wp_error($saved)) {
                self::transport($context, 'logout', array('refresh_token' => $value['refresh_token']), $value['access_token']);
                return $saved;
            }
            return self::status_value($value);
        } finally { self::delete_lock($context, $lock); }
    }

    public static function get_access_token() {
        $context = self::context();
        if (is_wp_error($context)) { return $context; }
        $value = self::load($context);
        if (!$value) { return self::error('platform_sign_in_required', 'Sign in to the Platform for this WordPress session and organization.', 401); }
        if (self::token_expiry($value['access_token'], 'access') > time() + 30) { return $value['access_token']; }
        $lock = self::lock($context);
        if (is_wp_error($lock)) { return $lock; }
        try {
            // Re-read only after acquiring the lock: a preceding request may already have rotated refresh.
            $value = self::load($context);
            if (!$value || self::token_expiry($value['refresh_token'] ?? '', 'refresh') <= time()) { return self::error('platform_sign_in_required', 'Your Platform session expired. Sign in again.', 401); }
            if (self::token_expiry($value['access_token'], 'access') > time() + 30) { return $value['access_token']; }
            $response = self::transport($context, 'refresh', array('refresh_token' => $value['refresh_token'], 'token_mode' => true));
            if (is_wp_error($response)) {
                if ($response->get_error_code() === 'platform_sign_in_required') { delete_user_meta($context['user'], $context['meta']); }
                return $response;
            }
            if (!self::accepted_tokens($response)) { return self::error('platform_session_unconfirmed', 'Session renewal could not be confirmed. Sign in again.'); }
            $value['access_token'] = $response['access_token'];
            $value['refresh_token'] = $response['refresh_token'];
            $saved = self::save($context, $value, $lock);
            if (is_wp_error($saved)) {
                self::transport($context, 'logout', array('refresh_token' => $value['refresh_token']), $value['access_token']);
                return $saved;
            }
            return $value['access_token'];
        } finally { self::delete_lock($context, $lock); }
    }

    public static function logout() {
        $context = self::context();
        if (is_wp_error($context)) { return $context; }
        $lock = self::lock($context);
        if (is_wp_error($lock)) { return $lock; }
        try {
            $value = self::load($context);
            if ($value) {
                $response = self::transport($context, 'logout', array('refresh_token' => $value['refresh_token']), $value['access_token']);
                if (is_wp_error($response)) { return $response; }
                if (($response['success'] ?? false) !== true) { return self::error('platform_logout_unconfirmed', 'Platform sign out could not be confirmed. Your saved session has not been cleared. Try again.'); }
            }
            delete_user_meta($context['user'], $context['meta']);
            return array('connected' => false, 'email' => '', 'needsLogin' => true);
        } finally { self::delete_lock($context, $lock); }
    }

    private static function ajax_allowed() {
        return current_user_can('manage_options') && check_ajax_referer('attention_network_admin_nonce', 'nonce', false);
    }
    private static function ajax_result($value) {
        if (is_wp_error($value)) {
            $data = $value->get_error_data();
            wp_send_json_error(array('code' => $value->get_error_code(), 'message' => $value->get_error_message()), is_array($data) ? ($data['status'] ?? 503) : 503);
        } else { wp_send_json_success($value); }
    }
    public static function ajax_login() {
        if (!self::ajax_allowed()) { self::ajax_result(self::error('forbidden', 'You are not allowed to manage this session.', 403)); return; }
        self::ajax_result(self::login(wp_unslash($_POST['email'] ?? ''), wp_unslash($_POST['password'] ?? '')));
    }
    public static function ajax_logout() {
        if (!self::ajax_allowed()) { self::ajax_result(self::error('forbidden', 'You are not allowed to manage this session.', 403)); return; }
        self::ajax_result(self::logout());
    }
    public static function ajax_status() {
        if (!self::ajax_allowed()) { self::ajax_result(self::error('forbidden', 'You are not allowed to manage this session.', 403)); return; }
        self::ajax_result(self::status());
    }
}

<?php
/**
 * WordPress Core Stubs for IDE Static Analysis
 *
 * This file provides function and class signatures for WordPress core APIs to
 * prevent false-positive "Call to unknown function" warnings in IDEs and linters.
 * Each function is wrapped in function_exists() to guarantee zero runtime collisions.
 */

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public function __construct( $code = '', $message = '', $data = '' ) {}
		public function get_error_message( $code = '' ) { return ''; }
		public function get_error_code() { return ''; }
		public function get_error_data( $code = '' ) { return null; }
		public function add( $code, $message, $data = '' ) {}
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) { return ''; }
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) { return ''; }
}
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) { return ''; }
}
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $callback ) {}
}
if ( ! function_exists( 'is_plugin_active' ) ) {
	function is_plugin_active( $plugin ) { return false; }
}
if ( ! function_exists( 'deactivate_plugins' ) ) {
	function deactivate_plugins( $plugins, $silent = false, $network_wide = null ) {}
}
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() { return false; }
}
if ( ! function_exists( 'is_ssl' ) ) {
	function is_ssl() { return false; }
}
if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $value ) { return ''; }
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) { return ''; }
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) { return array(); }
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value, ...$args ) { return $value; }
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook_name, ...$arg ) {}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url, $protocols = null ) { return ''; }
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url, $protocols = null, $_context = 'display' ) { return ''; }
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return ''; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return ''; }
}
if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {}
}
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {}
}
if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) { return ''; }
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {}
}
if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ) {}
}
if ( ! function_exists( 'settings_errors' ) ) {
	function settings_errors( $setting = '', $sanitize = false, $hide_on_update = false ) {}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) { return ''; }
}
if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( $url, $args = array() ) { return array(); }
}
if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) { return 200; }
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) { return ''; }
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) { return false; }
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) { return ''; }
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) { return ''; }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) { return ''; }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) { return ''; }
}
if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) { return ''; }
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) { return ''; }
}
if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) { return true; }
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) { return ''; }
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) { return 1; }
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) { return true; }
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null, $status_code = null, $options = 0 ) {}
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null, $status_code = null, $options = 0 ) {}
}
if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = array() ) {}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '', $scheme = 'admin' ) { return ''; }
}
if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format_string, $timestamp_with_offset = false, $gmt = false ) { return ''; }
}
if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text, $num_words = 55, $more = null ) { return ''; }
}
if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $echo = true ) { return ''; }
}
if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) { return ''; }
}
if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '', $filter = 'raw' ) { return ''; }
}
if ( ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) { return true; }
}
if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null ) { return ''; }
}
if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) { return ''; }
}
if ( ! function_exists( 'add_options_page' ) ) {
	function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null ) { return ''; }
}
if ( ! function_exists( 'register_setting' ) ) {
	function register_setting( $option_group, $option_name, $args = array() ) {}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default_value = false ) { return $default_value; }
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) { return true; }
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) { return true; }
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) { return false; }
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) { return true; }
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) { return true; }
}
if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {}
}
if ( ! function_exists( 'wp_register_style' ) ) {
	function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) { return true; }
}
if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {}
}
if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( $handle, $object_name, $l10n ) { return true; }
}
if ( ! function_exists( 'settings_fields' ) ) {
	function settings_fields( $option_group ) {}
}
if ( ! function_exists( 'do_settings_sections' ) ) {
	function do_settings_sections( $page ) {}
}
if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {}
}
if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode( $tag, $callback ) {}
}
if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( $pairs, $atts, $shortcode = '' ) { return array(); }
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) { return $value; }
}

<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

add_action('wp_ajax_allusfi_save_filter', 'allusfi_save_filter_fun');
add_action('wp_ajax_allusfi_delete_filter', 'allusfi_delete_filter_fun');

/**
 * AJAX handler: Save a named filter to the options table.
 *
 * Capability check mirrors the export handler:
 * - administrators always pass
 * - other users pass only if the 'allusfi_allowed_user_to_filter' filter returns true
 */
function allusfi_save_filter_fun()
{
	// 1) Nonce check.
	if (false === check_ajax_referer('allusfi_secure', 'nonce', false)) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Security check failed! May Be Session Expired!', 'all-users-filter'),
		));
	}

	// 2) Capability check (mirrors export handler).
	$allusfi_is_filter_allowed = apply_filters('allusfi_allowed_user_to_filter', false);
	if (!current_user_can('administrator') && !$allusfi_is_filter_allowed) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Insufficient permissions', 'all-users-filter'),
		));
	}

	// 3) Validate filter name.
	$filter_name = isset($_POST['filter_name'])
		? sanitize_text_field(wp_unslash($_POST['filter_name']))
		: '';

	if (empty($filter_name)) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Filter name is required.', 'all-users-filter'),
		));
	}

	// 4) Sanitize the params string using sanitize_url() (esc_url_raw).
	//    sanitize_url() requires a full URL, so we prepend a dummy base, let WordPress
	//    sanitize the whole thing (which correctly preserves %xx percent-encoding like
	//    %5B/%5D for array brackets and %3C for <), then strip the dummy base back off.
	$raw_params    = isset($_POST['filter_params']) ? wp_unslash($_POST['filter_params']) : '';
	$dummy_url     = 'http://x.localhost.x/?' . $raw_params;
	$clean_url     = sanitize_url($dummy_url);
	$filter_params = (strpos($clean_url, '?') !== false)
		? substr($clean_url, strpos($clean_url, '?') + 1)
		: '';
	$filter_params = substr($filter_params, 0, 4096); // reasonable upper bound

	// 5) Load existing saved filters.
	$saved = (array) get_option('allusfi_saved_filters', array());

	// 6) Prevent duplicate names (case-insensitive).
	foreach ($saved as $existing) {
		if (isset($existing['name']) && strtolower($existing['name']) === strtolower($filter_name)) {
			wp_send_json_error(array(
				'status' => 'failed',
				'msg'    => esc_html__('A filter with that name already exists. Please choose a different name.', 'all-users-filter'),
			));
		}
	}

	// 7) Append and persist.
	$saved[] = array(
		'name'   => $filter_name,
		'params' => $filter_params,
	);

	update_option('allusfi_saved_filters', $saved);

	wp_send_json_success(array('saved_filters' => array_values($saved)));
}

/**
 * AJAX handler: Delete a saved filter by its index in the options array.
 */
function allusfi_delete_filter_fun()
{
	// 1) Nonce check.
	if (false === check_ajax_referer('allusfi_secure', 'nonce', false)) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Security check failed! May Be Session Expired!', 'all-users-filter'),
		));
	}

	// 2) Capability check.
	$allusfi_is_filter_allowed = apply_filters('allusfi_allowed_user_to_filter', false);
	if (!current_user_can('administrator') && !$allusfi_is_filter_allowed) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Insufficient permissions', 'all-users-filter'),
		));
	}

	// 3) Validate filter ID.
	$filter_id = isset($_POST['filter_id']) ? absint($_POST['filter_id']) : -1;

	$saved = array_values((array) get_option('allusfi_saved_filters', array()));

	if ($filter_id < 0 || !isset($saved[$filter_id])) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Filter not found.', 'all-users-filter'),
		));
	}

	// 4) Remove and persist.
	array_splice($saved, $filter_id, 1);
	update_option('allusfi_saved_filters', $saved);

	wp_send_json_success(array('saved_filters' => array_values($saved)));
}

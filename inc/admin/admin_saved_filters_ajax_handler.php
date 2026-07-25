<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

add_action('wp_ajax_allusfi_save_filter', 'allusfi_save_filter_fun');
add_action('wp_ajax_allusfi_delete_filter', 'allusfi_delete_filter_fun');

/**
 * AJAX handler: Save the current filter state as a named saved filter.
 *
 * Instead of storing a URL query-string (old behaviour) the handler now reads
 * the current filter state from the per-user transient (or an existing saved
 * filter identified by allu_filter_id) and persists it as a structured array.
 * Export column preferences are captured from the additional export_settings
 * JSON field so they can be restored when the saved filter is later applied.
 *
 * Stored record shape:
 * [
 *   'id'              => '<8-char hex unique ID>',
 *   'name'            => '<filter name>',
 *   'filter_array'    => [ ... same keys as allusfi_get_query_params() ... ],
 *   'export_settings' => [ 'cols'=>[], 'include_meta'=>bool, 'meta_keys'=>[], 'extra_meta'=>[], 'separator'=>',' ],
 *   'created_at'      => <unix timestamp>,
 * ]
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

	// 4) Resolve the current filter state from transient or saved-filter option.
	$allu_filter_id = isset($_POST['allu_filter_id'])
		? sanitize_text_field(wp_unslash($_POST['allu_filter_id']))
		: '';

	$filter_array = null;

	if ('allusifi_current_state' === $allu_filter_id) {
		$user_id      = get_current_user_id();
		$stored       = get_transient('allusfi_state_' . $user_id);
		if (is_array($stored) && !empty($stored)) {
			$filter_array = $stored;
		}
	} elseif (0 === strpos($allu_filter_id, 'saved_filter_')) {
		// User is re-saving an existing saved filter under a new name.
		$sf_id          = substr($allu_filter_id, strlen('saved_filter_'));
		$existing_saved = (array) get_option('allusfi_saved_filters', array());
		foreach ($existing_saved as $sf) {
			if (isset($sf['id'], $sf['filter_array']) && $sf['id'] === $sf_id) {
				$filter_array = $sf['filter_array'];
				break;
			}
		}
	}

	if (!is_array($filter_array) || empty($filter_array)) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('No active filter state found. Please apply a filter first before saving.', 'all-users-filter'),
		));
	}

	// Strip the export_settings key from the filter_array \u2014 it will be stored at the top level.
	unset($filter_array['export_settings']);
	// Always mark as trusted (nonce verified above; this field is internal only).
	$filter_array['secure'] = true;

	// 5) Decode and validate the export_settings JSON sent by the JS.
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- This is a JSON string; applying a text sanitizer would corrupt it. The value is immediately passed to json_decode() and every resulting sub-field (cols, meta_keys, extra_meta, separator) is individually sanitized with sanitize_key() / sanitize_text_field() in the block below.
	$raw_export_settings = isset($_POST['export_settings']) ? wp_unslash($_POST['export_settings']) : '';
	$export_settings     = json_decode($raw_export_settings, true);
	if (!is_array($export_settings)) {
		$export_settings = array();
	}

	// Sanitise export_settings sub-fields defensively.
	$all_std_slugs = array('user_id', 'user_login', 'user_email', 'user_nicename', 'display_name', 'user_role', 'user_registered', 'first_name', 'last_name');

	$export_settings['cols'] = isset($export_settings['cols']) && is_array($export_settings['cols'])
		? array_values(array_intersect(array_map('sanitize_key', $export_settings['cols']), $all_std_slugs))
		: $all_std_slugs;

	$export_settings['include_meta'] = !empty($export_settings['include_meta']);

	$export_settings['meta_keys'] = isset($export_settings['meta_keys']) && is_array($export_settings['meta_keys'])
		? array_values(array_filter(array_map('sanitize_key', $export_settings['meta_keys'])))
		: array();

	$export_settings['extra_meta'] = isset($export_settings['extra_meta']) && is_array($export_settings['extra_meta'])
		? array_values(array_filter(array_map('sanitize_key', $export_settings['extra_meta'])))
		: array();

	$export_settings['separator'] = isset($export_settings['separator'])
		? sanitize_text_field($export_settings['separator'])
		: ',';
	if ('' === $export_settings['separator']) {
		$export_settings['separator'] = ',';
	}

	// 6) Load existing saved filters.
	$saved = (array) get_option('allusfi_saved_filters', array());

	// 7) Prevent duplicate names (case-insensitive).
	foreach ($saved as $existing) {
		if (isset($existing['name']) && strtolower($existing['name']) === strtolower($filter_name)) {
			wp_send_json_error(array(
				'status' => 'failed',
				'msg'    => esc_html__('A filter with that name already exists. Please choose a different name.', 'all-users-filter'),
			));
		}
	}

	// 8) Generate a unique 8-character hex ID for this saved filter.
	//    Used as the allu_filter_id token: saved_filter_<id>.
	$filter_unique_id = substr(md5(uniqid($filter_name . wp_rand(), true)), 0, 8);

	// 8a) Capture the WP search term the user had active when saving.
	$search_term = isset($_POST['search_term'])
		? sanitize_text_field(wp_unslash($_POST['search_term']))
		: '';

	// 9) Append and persist.
	$saved[] = array(
		'id'              => $filter_unique_id,
		'name'            => $filter_name,
		'filter_array'    => $filter_array,
		'export_settings' => $export_settings,
		'search_term'     => $search_term,
		'created_at'      => time(),
	);

	update_option('allusfi_saved_filters', $saved);

	// Return only new-format entries to JS (entries without 'id' are legacy and silently excluded).
	$new_format_saved = array_values(
		array_filter($saved, static function ($sf) {
			return isset($sf['id'], $sf['filter_array']);
		})
	);

	wp_send_json_success(array('saved_filters' => $new_format_saved));
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

	// 3) Validate filter ID (index into the re-indexed new-format list).
	$filter_id = isset($_POST['filter_id']) ? absint($_POST['filter_id']) : -1;

	// Work only with new-format entries so the JS index always matches.
	$all_saved   = (array) get_option('allusfi_saved_filters', array());
	$new_format  = array_values(
		array_filter($all_saved, static function ($sf) {
			return isset($sf['id'], $sf['filter_array']);
		})
	);

	if ($filter_id < 0 || !isset($new_format[$filter_id])) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Filter not found.', 'all-users-filter'),
		));
	}

	// 4) Remove from the full array (match by unique ID to be safe across indices).
	$unique_id_to_delete = $new_format[$filter_id]['id'];
	$all_saved = array_values(
		array_filter($all_saved, static function ($sf) use ($unique_id_to_delete) {
			return !(isset($sf['id']) && $sf['id'] === $unique_id_to_delete);
		})
	);

	update_option('allusfi_saved_filters', $all_saved);

	// Return only new-format entries.
	$remaining = array_values(
		array_filter($all_saved, static function ($sf) {
			return isset($sf['id'], $sf['filter_array']);
		})
	);

	wp_send_json_success(array('saved_filters' => $remaining));
}

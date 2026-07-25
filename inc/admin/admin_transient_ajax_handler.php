<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

add_action('wp_ajax_allusfi_save_filter_transient', 'allusfi_save_filter_transient_fun');

/**
 * AJAX handler: persist the modal form state in a per-user transient.
 *
 * The entire modal (filter params + export settings) is posted here before the
 * page redirects to ?allu_filter_id=allusifi_current_state.  On subsequent page
 * loads the transient is read instead of the long query-string.
 *
 * Transient key : allusfi_state_{user_id}   TTL : 6 hours
 */
function allusfi_save_filter_transient_fun()
{
	// 1) Nonce check.
	if (false === check_ajax_referer('allusfi_secure', 'allusfi_secure', false)) {
		wp_send_json_error(array(
			'msg' => esc_html__('Security check failed! May Be Session Expired!', 'all-users-filter'),
		));
	}

	// 2) Capability check.
	$allusfi_is_filter_allowed = apply_filters('allusfi_allowed_user_to_filter', false);
	if (!current_user_can('administrator') && !$allusfi_is_filter_allowed) {
		wp_send_json_error(array(
			'msg' => esc_html__('Insufficient permissions', 'all-users-filter'),
		));
	}

	// 3) Build sanitised payload arrays from $_POST.
	$payload         = allusfi_build_filter_payload_from_post();
	$export_settings = allusfi_build_export_settings_from_post();

	// 4) Merge and persist as a user-scoped transient (6-hour TTL).
	$state   = array_merge($payload, array('export_settings' => $export_settings));
	$user_id = get_current_user_id();
	set_transient('allusfi_state_' . $user_id, $state, 6 * HOUR_IN_SECONDS);

	wp_send_json_success(array('stored' => true));
}

/**
 * Parse and sanitise filter params from $_POST.
 *
 * Called exclusively from allusfi_save_filter_transient_fun() which has already
 * run check_ajax_referer('allusfi_secure', 'allusfi_secure') and a capability
 * check before reaching this point.  PHPCS cannot trace cross-function nonce
 * verification, so we suppress the NonceVerification.Missing sniff for this
 * helper function only.
 *
 * @return array
 */
function allusfi_build_filter_payload_from_post()
{
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	// Reason: nonce is verified by the calling function allusfi_save_filter_transient_fun()
	// via check_ajax_referer('allusfi_secure','allusfi_secure') before this helper is invoked.
	$out = array(
		'secure'           => true, // nonce already verified by caller
		'ordr_by'          => '',
		'usr_sort'         => '',
		'one_date'         => '',
		'cstm_dt'          => '',
		'relation'         => 'nd',
		'exclude_roles'    => array(),
		'excl_ids'         => array(),
		'multi_from_date'  => array(),
		'multi_to_date'    => array(),
		'meta_keys'        => array(),
		'meta_vals'        => array(),
		'meta_ops'         => array(),
		'meta_tp'          => array(),
		'rel_date_keys'    => array(),
		'rel_date_vals'    => array(),
		'rel_date_tp'      => array(),
		'wc_order_enabled' => false,
		'wc_order_count'   => 0,
		'wc_order_op'      => '>',
	);

	// Excluded user IDs (dash-separated string → integer array).
	if (isset($_POST['excl-ids'])) {
		$raw_ids         = wp_kses_post(wp_unslash($_POST['excl-ids']));
		$out['excl_ids'] = array_values(array_filter(array_map('absint', explode('-', $raw_ids))));
	}

	$out['usr_sort'] = isset($_POST['usr_srt'])  ? sanitize_text_field(wp_unslash($_POST['usr_srt']))  : '';
	$out['ordr_by']  = isset($_POST['ordr-by'])  ? sanitize_text_field(wp_unslash($_POST['ordr-by']))  : '';

	if (isset($_POST['rl-excld']) && is_array($_POST['rl-excld'])) {
		$out['exclude_roles'] = array_values(
			array_filter(array_map('sanitize_text_field', wp_unslash($_POST['rl-excld'])))
		);
	}

	$out['cstm_dt'] = isset($_POST['cstm-dt']) ? sanitize_text_field(wp_unslash($_POST['cstm-dt'])) : '';

	// Multi date ranges.
	if (
		isset($_POST['mlt-f-dt'], $_POST['mlt-t-dt']) &&
		is_array($_POST['mlt-f-dt']) &&
		is_array($_POST['mlt-t-dt'])
	) {
		$out['multi_from_date'] = array_filter(array_map('sanitize_text_field', wp_unslash($_POST['mlt-f-dt'])));
		$out['multi_to_date']   = array_filter(array_map('sanitize_text_field', wp_unslash($_POST['mlt-t-dt'])));
	}

	// Single date (only when multi-date is absent).
	$out['one_date'] = (
		isset($_POST['one-dt']) &&
		!empty($_POST['one-dt']) &&
		empty($out['multi_from_date'])
	)
		? sanitize_textarea_field(sanitize_text_field(wp_unslash($_POST['one-dt'])))
		: '';

	$out['relation'] = (isset($_POST['rltn']) && 'or' === $_POST['rltn']) ? 'or' : 'nd';

	// --- Meta arrays ---
	$allowed_types = array('CHAR', 'NUMERIC', 'BINARY', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'UNSIGNED', 'TIME');

	$keys = (isset($_POST['mta-ky']) && is_array($_POST['mta-ky']))
		? array_map('sanitize_key', wp_unslash($_POST['mta-ky']))
		: array();
	$vals = (isset($_POST['mta-vl']) && is_array($_POST['mta-vl']))
		? array_map('sanitize_textarea_field', wp_unslash($_POST['mta-vl']))
		: array();
	$tps  = (isset($_POST['mta-tp']) && is_array($_POST['mta-tp']))
		? array_map('sanitize_text_field', wp_unslash($_POST['mta-tp']))
		: array();
	$ops  = (isset($_POST['mta-op']) && is_array($_POST['mta-op']))
		? array_map('sanitize_text_field', wp_unslash($_POST['mta-op']))
		: array();

	$ops = array_map('allusfi_transient_resanitize_op', $ops);

	foreach ($tps as $i => $tp) {
		$tps[$i] = in_array($tp, $allowed_types, true) ? $tp : 'CHAR';
	}
	// Remove rows with empty meta key.
	foreach ($keys as $index => $key) {
		if (empty(trim($key))) {
			unset($keys[$index], $vals[$index], $ops[$index], $tps[$index]);
		}
	}

	$out['meta_keys'] = array_values($keys);
	$out['meta_vals'] = array_values($vals);
	$out['meta_ops']  = array_values($ops);
	$out['meta_tp']   = array_values($tps);

	// --- Relative-date meta params ---
	$rd_allowed = array('DATE', 'DATETIME', 'TIME', 'NUMERIC');

	$rd_keys = (isset($_POST['rdmq-ky']) && is_array($_POST['rdmq-ky']))
		? array_map('sanitize_key', wp_unslash($_POST['rdmq-ky']))
		: array();
	$rd_vals = (isset($_POST['rdmq-vl']) && is_array($_POST['rdmq-vl']))
		? array_map('sanitize_text_field', wp_unslash($_POST['rdmq-vl']))
		: array();
	$rd_tps  = (isset($_POST['rdmq-tp']) && is_array($_POST['rdmq-tp']))
		? array_map('sanitize_text_field', wp_unslash($_POST['rdmq-tp']))
		: array();

	foreach ($rd_tps as $i => $tp) {
		$rd_tps[$i] = in_array(strtoupper($tp), $rd_allowed, true) ? strtoupper($tp) : 'DATE';
	}

	$out['rel_date_keys'] = $rd_keys;
	$out['rel_date_vals'] = $rd_vals;
	$out['rel_date_tp']   = $rd_tps;

	// --- WooCommerce order count ---
	$out['wc_order_enabled'] = !empty($_POST['wc-ordr-enabled']);
	$out['wc_order_count']   = isset($_POST['wc-ordr-cnt']) ? absint(wp_unslash($_POST['wc-ordr-cnt'])) : 0;

	$allowed_wc_ops = array('>', '<', '=', '!=');
	$raw_wc_op      = isset($_POST['wc-ordr-op'])
		? allusfi_transient_resanitize_op(sanitize_text_field(wp_unslash($_POST['wc-ordr-op'])))
		: '>';
	$out['wc_order_op'] = in_array($raw_wc_op, $allowed_wc_ops, true) ? $raw_wc_op : '>';

	return $out;
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Build the export settings array from $_POST.
 *
 * Called exclusively from allusfi_save_filter_transient_fun() which has already
 * run check_ajax_referer('allusfi_secure', 'allusfi_secure') before this helper
 * is invoked.  PHPCS cannot trace cross-function nonce verification, so we
 * suppress the NonceVerification.Missing sniff for this helper only.
 *
 * @return array
 */
function allusfi_build_export_settings_from_post()
{
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	// Reason: nonce is verified by the calling function allusfi_save_filter_transient_fun()
	// via check_ajax_referer('allusfi_secure','allusfi_secure') before this helper is invoked.
	$all_std_slugs = array(
		'user_id', 'user_login', 'user_email', 'user_nicename',
		'display_name', 'user_role', 'user_registered', 'first_name', 'last_name',
	);

	$selected_cols = (isset($_POST['allusfi_export_cols']) && is_array($_POST['allusfi_export_cols']))
		? array_values(array_intersect(array_map('sanitize_key', wp_unslash($_POST['allusfi_export_cols'])), $all_std_slugs))
		: $all_std_slugs;

	$include_meta = !empty($_POST['allusfi_include_meta']);

	$export_meta_keys = (isset($_POST['allusfi_export_meta_keys']) && is_array($_POST['allusfi_export_meta_keys']))
		? array_values(array_filter(array_map('sanitize_key', wp_unslash($_POST['allusfi_export_meta_keys']))))
		: array();

	$extra_meta_keys = array();
	if (isset($_POST['allusfi_export_extra_meta']) && is_array($_POST['allusfi_export_extra_meta'])) {
		// Each element is sanitized immediately via sanitize_key() inside the loop.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_key() is applied to each item in the foreach below.
		foreach (wp_unslash($_POST['allusfi_export_extra_meta']) as $raw) {
			$s = sanitize_key($raw);
			if (!empty($s) && !in_array($s, $extra_meta_keys, true)) {
				$extra_meta_keys[] = $s;
			}
		}
	}

	$separator = isset($_POST['allusfi_csv_sep'])
		? sanitize_text_field(wp_unslash($_POST['allusfi_csv_sep']))
		: ',';
	$separator = ('' === $separator) ? ',' : $separator;

	return array(
		'cols'         => $selected_cols,
		'include_meta' => $include_meta,
		'meta_keys'    => $export_meta_keys,
		'extra_meta'   => $extra_meta_keys,
		'separator'    => $separator,
	);
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Re-sanitise comparison operators that may arrive HTML-entity-encoded.
 *
 * @param string $op
 * @return string
 */
function allusfi_transient_resanitize_op($op)
{
	$map = array(
		'&lt;'  => '<',
		'&lt;=' => '<=',
	);
	return isset($map[$op]) ? $map[$op] : $op;
}

<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// Ensure the date parser class is available (needed in AJAX context).
if (!class_exists('ALLUSFI_Date_Parser')) {
	$allusfi_parser_path = defined('ALLUSFI_DIR') ? ALLUSFI_DIR . '/inc/admin/class.allusfi_date_parser.php' : '';
	if ($allusfi_parser_path && file_exists($allusfi_parser_path)) {
		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		require_once $allusfi_parser_path;
	}
}

add_action('wp_ajax_allusfi_wp_usr_export_csv', 'allusfi_wp_usr_export_csv_fun');

function allusfi_wp_usr_export_csv_fun()
{
	// 1) PHPCS/WPCS-friendly nonce check (AJAX). Return JSON error
	if (false === check_ajax_referer('allusfi_secure', 'allusfi_secure', false)) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Security check failed! May Be Session Expired!', 'all-users-filter'),
		));
	}

	// 2) Capability check (standalone).
	$allusfi_is_filter_allowed = apply_filters('allusfi_allowed_user_to_filter', false);
	if (!current_user_can('administrator') && !$allusfi_is_filter_allowed) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Insufficient permissions', 'all-users-filter'),
		));
	}

	// 3) Ensure admin helper class is available
	if (!class_exists('ALLUSFI_Admin')) {
		$maybe = defined('ALLUSFI_DIR') ? ALLUSFI_DIR . '/inc/admin/class.allusfi_main.php' : '';
		if ($maybe && file_exists($maybe)) {
			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			require_once $maybe;
		}
	}
	if (!class_exists('ALLUSFI_Admin')) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Internal error: helper class missing', 'all-users-filter'),
		));
	}

	// 4) Resolve filter params — prefer transient/saved-filter over raw $_REQUEST parsing.
	$params          = null;
	$allu_filter_id  = isset($_POST['allu_filter_id'])
		? sanitize_text_field(wp_unslash($_POST['allu_filter_id']))
		: '';

	if ('allusifi_current_state' === $allu_filter_id) {
		// Read from the per-user transient saved by allusfi_save_filter_transient_fun.
		$user_id = get_current_user_id();
		$stored  = get_transient('allusfi_state_' . $user_id);
		if (is_array($stored) && !empty($stored)) {
			$params = $stored;
		}
	} elseif (0 === strpos($allu_filter_id, 'saved_filter_')) {
		// Read from the persisted saved-filters option.
		$sf_id = substr($allu_filter_id, strlen('saved_filter_'));
		$saved = (array) get_option('allusfi_saved_filters', array());
		foreach ($saved as $sf) {
			if (isset($sf['id'], $sf['filter_array']) && $sf['id'] === $sf_id) {
				$params = $sf['filter_array'];
				// export_settings is stored at the top level of the saved record,
				// not inside filter_array — merge it in so the export fallbacks work.
				if (isset($sf['export_settings']) && is_array($sf['export_settings'])) {
					$params['export_settings'] = $sf['export_settings'];
				}
				break;
			}
		}
	}

	// Fallback: use the class-based $_REQUEST parser (legacy / direct-URL mode).
	if (!is_array($params) || empty($params)) {
		$admin  = new ALLUSFI_Admin();
		$params = (array) $admin->allusfi_get_query_params();
	}

	$params = (array) $params;

	// Export settings stored in the transient (set when filter was applied via modal).
	$transient_export = isset($params['export_settings']) && is_array($params['export_settings'])
		? $params['export_settings']
		: array();

	// 5) Lightweight additional request values (sanitized)
	$paged  = isset($_REQUEST['paged']) ? absint(wp_unslash($_REQUEST['paged'])) : 1;
	$search = isset($_REQUEST['s'])     ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';

	// 5a) Export-specific params — POST values take priority; transient values are the fallback
	//     so that saved filters with stored export settings work out of the box.
	$csv_sep_raw = (isset($_POST['allusfi_csv_sep']) && '' !== $_POST['allusfi_csv_sep'])
		? sanitize_text_field(wp_unslash($_POST['allusfi_csv_sep']))
		: (isset($transient_export['separator']) ? $transient_export['separator'] : ',');
	$csv_sep = ('' === $csv_sep_raw) ? ',' : $csv_sep_raw;

	// Standard column slugs.
	$all_std_slugs = array('user_id', 'user_login', 'user_email', 'user_nicename', 'display_name', 'user_role', 'user_registered', 'first_name', 'last_name');
	$selected_cols = (isset($_POST['allusfi_export_cols']) && is_array($_POST['allusfi_export_cols']))
		? array_intersect(array_map('sanitize_key', wp_unslash($_POST['allusfi_export_cols'])), $all_std_slugs)
		: (isset($transient_export['cols']) && is_array($transient_export['cols']) ? $transient_export['cols'] : $all_std_slugs);

	// Meta keys from active filter rows (checkbox-selected or transient fallback).
	$export_meta_keys = (isset($_POST['allusfi_export_meta_keys']) && is_array($_POST['allusfi_export_meta_keys']))
		? array_values(array_filter(array_map('sanitize_key', wp_unslash($_POST['allusfi_export_meta_keys']))))
		: (isset($transient_export['meta_keys']) && is_array($transient_export['meta_keys']) ? $transient_export['meta_keys'] : array());

	// Additional freeform meta keys (repeater rows or transient fallback).
	$extra_meta_keys_raw = array();
	if (isset($_POST['allusfi_export_extra_meta']) && is_array($_POST['allusfi_export_extra_meta'])) {
		// sanitize_text_field applied at point of assignment; sanitize_key() is applied
		// again in the foreach below before the value is ever stored.
		$extra_meta_keys_raw = array_map('sanitize_text_field', wp_unslash($_POST['allusfi_export_extra_meta']));
	} elseif (isset($transient_export['extra_meta']) && is_array($transient_export['extra_meta'])) {
		$extra_meta_keys_raw = $transient_export['extra_meta'];
	}

	foreach ($extra_meta_keys_raw as $raw_extra_key) {
		$sanitized_extra_key = sanitize_key($raw_extra_key);
		if (!empty($sanitized_extra_key) && !in_array($sanitized_extra_key, $export_meta_keys, true)) {
			$export_meta_keys[] = $sanitized_extra_key;
		}
	}

	// Build combined unique meta key list for export (filter active keys + extras).
	$meta_keys_for_header = array_values(array_unique($export_meta_keys));

	// 6) Batch size (default 99) - keeps queries small and reduces DB pressure.
	$batch_size = (int) apply_filters('allusfi_export_batch_size', 99);
	$batch_size = $batch_size > 0 ? $batch_size : 99;

	// 7) Guard: refuse absurdly-large exclude lists.
	if (!empty($params['excl_ids']) && is_array($params['excl_ids'])) {
		if (count($params['excl_ids']) > 50) {
			wp_send_json_error(array(
				'status' => 'failed',
				'msg'    => esc_html__('Too many excluded IDs. Reduce the exclude list or export in smaller batches.', 'all-users-filter'),
			));
		}
	}

	$proto = new WP_User_Query();

	// Order.
	$proto->set('order', (isset($params['ordr_by']) && '1' === $params['ordr_by']) ? 'ASC' : 'DESC');

	// Sort mapping.
	if (!empty($params['usr_sort'])) {
		switch ($params['usr_sort']) {
			case 'f-nm':
				$proto->set('meta_key', 'first_name');
				$proto->set('orderby', 'meta_value');
				break;
			case 'l-nm':
				$proto->set('meta_key', 'last_name');
				$proto->set('orderby', 'meta_value');
				break;
			case 'usr-id':
				$proto->set('orderby', 'ID');
				break;
			case 'usr-lgn':
				$proto->set('orderby', 'user_login');
				break;
			case 'dis-nm':
				$proto->set('orderby', 'display_name');
				break;
			case 'reg-dt':
				$proto->set('orderby', 'registered');
				break;
			case 'pst-cnt':
				$proto->set('orderby', 'post_count');
				break;
		}
	}

	// Search.
	if (!empty($search)) {
		$proto->set('search', '*' . $search . '*');
	}

	// Role exclusion.
	if (!empty($params['exclude_roles']) && is_array($params['exclude_roles'])) {
		$proto->set('role__not_in', $params['exclude_roles']);
	}

	// Exclude IDs.
	if (!empty($params['excl_ids']) && is_array($params['excl_ids'])) {
		$proto->set('exclude', array_map('absint', $params['excl_ids']));
	}

	// Date args.
	$date_args = array('relation' => 'OR');
	if (!empty($params['one_date'])) {
		$dt = $params['one_date'];
		$date_args[] = array(
			'year'  => gmdate('Y', strtotime($dt)),
			'month' => gmdate('m', strtotime($dt)),
			'day'   => gmdate('d', strtotime($dt)),
		);
	}
	if (!empty($params['cstm_dt'])) {
		$date_args[] = array('after' => $params['cstm_dt'], 'inclusive' => true);
	}
	if (!empty($params['multi_from_date']) && !empty($params['multi_to_date'])) {
		foreach ($params['multi_from_date'] as $index => $from) {
			$to = isset($params['multi_to_date'][$index]) ? $params['multi_to_date'][$index] : '';
			if (empty($from) || empty($to)) {
				continue;
			}
			$date_args[] = array(
				'before' => array(
					'year'  => gmdate('Y', strtotime($to)),
					'month' => gmdate('m', strtotime($to)),
					'day'   => gmdate('d', strtotime($to)),
				),
				'after'  => array(
					'year'  => gmdate('Y', strtotime($from)),
					'month' => gmdate('m', strtotime($from)),
					'day'   => gmdate('d', strtotime($from)),
				),
				'inclusive' => true,
			);
		}
	}
	if (count($date_args) > 1) {
		$proto->set('date_query', $date_args);
	}

	// Meta query: combine standard advanced filter rows + relative-date rows.
	$meta_query = array('relation' => ('or' === $params['relation'] ? 'OR' : 'AND'));

	if (!empty($params['meta_keys']) && is_array($params['meta_keys'])) {
		$cnt_len = !empty($params['meta_ops']) && is_array($params['meta_ops']) ? count($params['meta_ops']) : 0;
		for ($i = 0; $i < $cnt_len; $i++) {
			$change_meta_vals = isset($params['meta_vals'][$i]) ? $params['meta_vals'][$i] : '';
			if (
				('BETWEEN' === $params['meta_ops'][$i] || 'IN' === $params['meta_ops'][$i]) &&
				is_string($change_meta_vals) &&
				false !== strpos($change_meta_vals, ',')
			) {
				$temp_array = array_map('trim', explode(',', $change_meta_vals));
				if (isset($temp_array[0], $temp_array[1])) {
					$change_meta_vals = array($temp_array[0], $temp_array[1]);
				}
			}
			$meta_query[] = array(
				'key'     => $params['meta_keys'][$i],
				'value'   => $change_meta_vals,
				'type'    => isset($params['meta_tp'][$i]) ? $params['meta_tp'][$i] : 'CHAR',
				'compare' => isset($params['meta_ops'][$i]) ? $params['meta_ops'][$i] : '=',
			);
		}
	}

	// Relative-date meta filter rows.
	if (
		!empty($params['rel_date_keys']) &&
		!empty($params['rel_date_vals']) &&
		!empty($params['rel_date_tp']) &&
		class_exists('ALLUSFI_Date_Parser')
	) {
		$rd_len = max(
			count($params['rel_date_keys']),
			count($params['rel_date_vals']),
			count($params['rel_date_tp'])
		);
		for ($i = 0; $i < $rd_len; $i++) {
			$rd_key = isset($params['rel_date_keys'][$i]) ? $params['rel_date_keys'][$i] : '';
			$rd_val = isset($params['rel_date_vals'][$i]) ? $params['rel_date_vals'][$i] : '';
			$rd_tp  = isset($params['rel_date_tp'][$i])   ? $params['rel_date_tp'][$i]   : 'DATE';
			if (empty($rd_key) || empty($rd_val)) {
				continue;
			}
			$clause = ALLUSFI_Date_Parser::parse($rd_key, $rd_val, $rd_tp);
			if (!empty($clause)) {
				$meta_query[] = $clause;
			}
		}
	}

	if (count($meta_query) > 1) {
		$proto->set('meta_query', $meta_query);
	}

	// Execute query in batches.
	$queried_variables             = (array) $proto->query_vars;
	$queried_variables['paged']    = $paged;
	$queried_variables['number']   = $batch_size;

	$user_query = new WP_User_Query($queried_variables);
	$users      = $user_query->get_results();
	$total      = $user_query->get_total();

	// Build the human-readable column label map.
	$col_label_map = array(
		'user_id'         => 'User ID',
		'user_login'      => 'User Login',
		'user_email'      => 'User Email',
		'user_nicename'   => 'User Nicename',
		'display_name'    => 'Display Name',
		'user_role'       => 'User Role',
		'user_registered' => 'Registration Date',
		'first_name'      => 'First Name',
		'last_name'       => 'Last Name',
	);

	$rows     = array();
	$wc_enabled = !empty($params['wc_order_enabled']) && class_exists('WooCommerce');

	// Header row (first page only).
	if (1 === (int) $paged) {
		$header = array();
		foreach ($selected_cols as $slug) {
			$header[] = isset($col_label_map[$slug]) ? $col_label_map[$slug] : $slug;
		}
		foreach ($meta_keys_for_header as $mk) {
			$header[] = $mk;
		}
		if ($wc_enabled) {
			$header[] = 'Total Orders';
		}
		$rows[] = $header;
	}

	// Data rows.
	foreach ($users as $u) {
		$row_data = array();

		foreach ($selected_cols as $slug) {
			switch ($slug) {
				case 'user_id':
					$row_data[] = $u->ID;
					break;
				case 'user_login':
					$row_data[] = $u->user_login;
					break;
				case 'user_email':
					$row_data[] = $u->user_email;
					break;
				case 'user_nicename':
					$row_data[] = isset($u->user_nicename) ? $u->user_nicename : '';
					break;
				case 'display_name':
					$row_data[] = isset($u->display_name) ? $u->display_name : '';
					break;
				case 'user_role':
					$row_data[] = !empty($u->roles) ? implode(',', $u->roles) : '';
					break;
				case 'user_registered':
					$row_data[] = isset($u->user_registered) ? $u->user_registered : '';
					break;
				case 'first_name':
					$row_data[] = get_user_meta($u->ID, 'first_name', true);
					break;
				case 'last_name':
					$row_data[] = get_user_meta($u->ID, 'last_name', true);
					break;
				default:
					$row_data[] = '';
					break;
			}
		}

		// Meta columns.
		if (!empty($meta_keys_for_header)) {
			foreach ($meta_keys_for_header as $single_key) {
				$m_val      = get_user_meta($u->ID, $single_key, true);
				$row_data[] = is_array($m_val) ? wp_json_encode($m_val) : $m_val;
			}
		}

		if ($wc_enabled) {
			$row_data[] = allusfi_get_user_order_count($u->ID);
		}

		$rows[] = $row_data;
	}

	wp_send_json_success(array(
		'rows'      => $rows,
		'total'     => $total,
		'paged'     => $paged,
		'count'     => count($rows),
		'separator' => $csv_sep,
	));
}

/**
 * Get the total WooCommerce order count for a specific user.
 *
 * Supports both HPOS and Legacy storage modes.
 *
 * @param int $user_id The user ID.
 * @return int The total number of completed/processing orders.
 */
function allusfi_get_user_order_count($user_id)
{
	global $wpdb;

	$user_id = absint($user_id);

	// Detect HPOS.
	$hpos_enabled = false;
	if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil')) {
		$hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	if ($hpos_enabled) {
		$orders_table = $wpdb->prefix . 'wc_orders';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Real-time per-user WC order count; caching would risk stale results on active order sites.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $orders_table equals $wpdb->prefix . 'wc_orders'; the table name is derived solely from the trusted $wpdb->prefix property.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				 FROM `{$orders_table}` 
				 WHERE customer_id = %d 
				 AND type = 'shop_order'",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	} else {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				 FROM {$wpdb->posts} AS p
				 INNER JOIN {$wpdb->postmeta} AS pm 
					ON p.ID = pm.post_id 
					AND pm.meta_key = '_customer_user'
				 WHERE pm.meta_value = %d 
				 AND p.post_type = 'shop_order'",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	return $count;
}

<?php
// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

add_action('wp_ajax_allusfi_wp_usr_export_csv', 'allusfi_wp_usr_export_csv_fun');

function allusfi_wp_usr_export_csv_fun()
{
	// 1) PHPCS/WPCS-friendly nonce check (AJAX). Return JSON error instead of die().
	if (false === check_ajax_referer('allusfi_secure', 'allusfi_secure', false)) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Security check failed! May Be Session Expired!', 'all-users-filter'),
		));
	}

	// 2) Capability check (standalone).
	$allusfi_is_filter_allowed = apply_filters('allusfi_allowed_user_to_filter', false);
	if (! current_user_can('administrator') && ! $allusfi_is_filter_allowed) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Insufficient permissions', 'all-users-filter'),
		));
	}

	// 3) Ensure admin helper class is available
	if (! class_exists('ALLUSFI_Admin')) {
		$maybe = defined('ALLUSFI_DIR') ? ALLUSFI_DIR . '/inc/admin/class.allusfi_main.php' : '';
		if ($maybe && file_exists($maybe)) {
			require_once $maybe;
		}
	}
	if (! class_exists('ALLUSFI_Admin')) {
		wp_send_json_error(array(
			'status' => 'failed',
			'msg'    => esc_html__('Internal error: helper class missing', 'all-users-filter'),
		));
	}

	// 4) Get sanitized params (the method should NOT perform nonce checks).
	$admin  = new ALLUSFI_Admin();
	$params = (array) $admin->allusfi_get_query_params();

	// 5) Lightweight additional request values (sanitized)
	$paged  = isset($_REQUEST['paged']) ? absint(wp_unslash($_REQUEST['paged'])) : 1;
	$search = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';

	// 6) Batch size (default 100) - keeps queries small and reduces DB pressure.
	$batch_size = (int) apply_filters('allusfi_export_batch_size', 999);
	$batch_size = $batch_size > 0 ? $batch_size : 999;

	// 7) Guard: refuse absurdly-large exclude lists (avoid extremely slow/exhaustive queries).
	if (! empty($params['excl_ids']) && is_array($params['excl_ids'])) {
		if (count($params['excl_ids']) > 500) {
			wp_send_json_error(array(
				'status' => 'failed',
				'msg'    => esc_html__('Too many excluded IDs. Reduce the exclude list or export in smaller batches.', 'all-users-filter'),
			));
		}
	}
	$proto = new WP_User_Query();

	// order
	$proto->set('order', (isset($params['ordr_by']) && '1' === $params['ordr_by']) ? 'ASC' : 'DESC');

	// sort mapping (only set meta_key when explicitly requested)
	if (! empty($params['usr_sort'])) {
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

	// search (if present)
	if (! empty($search)) {
		$proto->set('search', $search);
	}

	// role exclusion
	if (! empty($params['exlude_roles']) && is_array($params['exlude_roles'])) {
		$proto->set('role__not_in', $params['exlude_roles']);
	}

	// exclude ids
	if (! empty($params['excl_ids']) && is_array($params['excl_ids'])) {
		$proto->set('exclude', array_map('absint', $params['excl_ids']));
	}

	// date args (build only if necessary)
	$date_args = array('relation' => 'OR');
	if (! empty($params['one_date'])) {
		$dt = $params['one_date'];
		$date_args[] = array(
			'year'  => gmdate('Y', strtotime($dt)),
			'month' => gmdate('m', strtotime($dt)),
			'day'   => gmdate('d', strtotime($dt)),
		);
	}
	if (! empty($params['cstm_dt'])) {
		$date_args[] = array('after' => $params['cstm_dt'], 'inclusive' => true);
	}
	if (! empty($params['multi_from_date']) && ! empty($params['multi_to_date'])) {
		foreach ($params['multi_from_date'] as $index => $from) {
			$to = isset($params['multi_to_date'][$index]) ? $params['multi_to_date'][$index] : '';
			if (empty($from) || empty($to)) {
				continue;
			}
			$multi_dates = array(
				'before'    => array(
					'year'  => gmdate('Y', strtotime($to)),
					'month' => gmdate('m', strtotime($to)),
					'day'   => gmdate('d', strtotime($to)),
				),
				'after'     => array(
					'year'  => gmdate('Y', strtotime($from)),
					'month' => gmdate('m', strtotime($from)),
					'day'   => gmdate('d', strtotime($from)),
				),
				'inclusive' => true,
			);
			$date_args[] = $multi_dates;
		}
	}
	if (count($date_args) > 1) {
		$proto->set('date_query', $date_args);
	}

	// meta query (only if meta filters requested)
	if (! empty($params['meta_keys']) && is_array($params['meta_keys'])) {
		$meta_query = array('relation' => ('or' === $params['relation'] ? 'OR' : 'AND'));
		$cnt_len = ! empty($params['meta_ops']) && is_array($params['meta_ops']) ? count($params['meta_ops']) : 0;
		for ($i = 0; $i < $cnt_len; $i++) {
			$change_meta_vals = isset($params['meta_vals'][$i]) ? $params['meta_vals'][$i] : '';
			if (('BETWEEN' === $params['meta_ops'][$i] || 'IN' === $params['meta_ops'][$i]) && is_string($change_meta_vals) && false !== strpos($change_meta_vals, ',')) {
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
		if (count($meta_query) > 1) {
			$proto->set('meta_query', $meta_query);
		}
	}

	// 9) Extract the query vars and apply paged/number, then execute a fresh WP_User_Query
	$queried_variables = (array) $proto->query_vars;
	$queried_variables['paged']  = $paged;
	$queried_variables['number'] = $batch_size;

	$user_query = new WP_User_Query($queried_variables);
	$users      = $user_query->get_results();
	$total      = $user_query->get_total();

	// 10) Build the CSV rows (preserve original JSON shape)
	$meta_keys_for_header = ! empty($params['meta_keys']) && is_array($params['meta_keys']) ? array_map('sanitize_key', $params['meta_keys']) : array();

	$rows = array();
	if (1 === (int) $paged) {
		$base_cols = array('User ID', 'User Login', 'User Email', 'User Nicename', 'Display Name', 'User Role', 'Registration Date');
		$rows[] = ! empty($meta_keys_for_header) ? array_merge($base_cols, $meta_keys_for_header) : $base_cols;
	}

	foreach ($users as $u) {
		$main_data = array(
			$u->ID,
			$u->user_login,
			$u->user_email,
			isset($u->user_nicename) ? $u->user_nicename : '',
			isset($u->display_name) ? $u->display_name : '',
			! empty($u->roles) ? implode(',', $u->roles) : '',
			isset($u->user_registered) ? $u->user_registered : '',
		);

		if (! empty($meta_keys_for_header)) {
			$user_meta_vals = array();
			foreach ($meta_keys_for_header as $single_key) {
				$m_val = get_user_meta($u->ID, $single_key, true);
				$user_meta_vals[] = is_array($m_val) ? wp_json_encode($m_val) : $m_val;
			}
			$rows[] = array_merge($main_data, $user_meta_vals);
		} else {
			$rows[] = $main_data;
		}
	}

	wp_send_json_success(array(
		'rows'  => $rows,
		'total' => $total,
		'paged' => $paged,
		'count' => count($rows),
	));
}

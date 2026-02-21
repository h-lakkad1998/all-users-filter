<?php
/*Main class that is made for declaring the functions, actions, filters */
// Exit if accessed directly
if (!defined('ABSPATH'))
	exit;

if (!class_exists('ALLUSFI_Admin')) {

	class ALLUSFI_Admin
	{
		function __construct()
		{
			/********* list of action *********/
			add_action('admin_enqueue_scripts', array($this, 'allusfi_action_admin_init'));
			add_action('manage_users_extra_tablenav', array($this, 'allusfi_render_custom_html'));

			/********* list of filters *********/
			add_filter('pre_get_users', array($this, 'allusfi_filter_users_by_requests'));
			add_action('pre_user_query', array($this, 'allusfi_filter_users_by_wc_orders'));
		}

		function allusfi_action_admin_init()
		{
			wp_register_script(ALLUSFI_PREFIX . '_admin_js', ALLUSFI_URL . 'assets/js/admin.js', array('jquery'), ALLUSFI_VERSION, true);
			wp_register_style(ALLUSFI_PREFIX . '_admin_css', ALLUSFI_URL . 'assets/css/admin.css', array(), ALLUSFI_VERSION);
			$allusfi_local_array = array(
				'plugin_prefix' => ALLUSFI_PREFIX,
				'ajax_url' => admin_url('admin-ajax.php'),
				'btn_export_txt' => __('CLICK HERE TO EXPORT CSV', 'all-users-filter'),
				'btn_export_finish_txt' => __('Export complete', 'all-users-filter'),
				'get_req_txt' => __('GET REQUEST ENABLED!', 'all-users-filter'),
				'post_req_txt' => __('POST REQUEST ENABLED!', 'all-users-filter'),
				'start_export_process_txt' => __('Starting export...', 'all-users-filter'),
				'export_process_txt' => __('Exporting...', 'all-users-filter'),
				'export_ongoing_txt' => __('Currently processing your export... Please keep this browser window open until the process is complete to avoid interrupting it.', 'all-users-filter'),
			);
			wp_localize_script(ALLUSFI_PREFIX . '_admin_js', 'allusfi_obj', $allusfi_local_array);
			wp_enqueue_script(ALLUSFI_PREFIX . '_admin_js');
			wp_enqueue_style(ALLUSFI_PREFIX . '_admin_css');
		}


		function allusfi_render_custom_html()
		{
			include_once ALLUSFI_DIR . '/inc/admin/html_outs_' . ALLUSFI_PREFIX . '.php';
		}

		function allusfi_filter_users_by_requests($query)
		{
			if (!is_admin()) {
				return $query;
			}
			$admin = new ALLUSFI_Admin();
			$params = $admin->allusfi_get_query_params();

			if (!$params['secure']) {
				return $query;
			}

			// 1) Order.
			$query->set('order', ('1' === $params['ordr_by']) ? 'ASC' : 'DESC');

			switch ($params['usr_sort']) {
				case 'f-nm':
					$query->set('meta_key', 'first_name');
					$query->set('orderby', 'meta_value');
					break;
				case 'l-nm':
					$query->set('meta_key', 'last_name');
					$query->set('orderby', 'meta_value');
					break;
				case 'usr-id':
					$query->set('orderby', 'ID');
					break;
				case 'usr-lgn':
					$query->set('orderby', 'user_login');
					break;
				case 'dis-nm':
					$query->set('orderby', 'display_name');
					break;
				case 'reg-dt':
					$query->set('orderby', 'registered');
					break;
				case 'pst-cnt':
					$query->set('orderby', 'post_count');
					break;
			}

			// 2) Exclude roles.
			if (!empty($params['exclude_roles'])) {
				$query->set('role__not_in', $params['exclude_roles']);
			}

			// 3) Exclude IDs.
			if (!empty($params['excl_ids'])) {
				$query->set('exclude', $params['excl_ids']);
			}

			// 4) Date filters.
			$date_args = array('relation' => 'OR');

			if ($params['one_date']) {
				$date_args[] = array(
					'year' => (int) gmdate('Y', strtotime($params['one_date'])),
					'month' => (int) gmdate('m', strtotime($params['one_date'])),
					'day' => (int) gmdate('d', strtotime($params['one_date'])),
				);
			}

			if ($params['cstm_dt']) {
				$date_args[] = array('after' => $params['cstm_dt'], 'inclusive' => true);
			}

			if (!empty($params['multi_from_date']) && !empty($params['multi_to_date'])) {
				foreach ($params['multi_from_date'] as $i => $from) {
					if (empty($params['multi_to_date'][$i]) || empty($from)) {
						continue;
					}
					$date_args[] = array(
						'before' => array(
							'year' => (int) gmdate('Y', strtotime($params['multi_to_date'][$i])),
							'month' => (int) gmdate('m', strtotime($params['multi_to_date'][$i])),
							'day' => (int) gmdate('d', strtotime($params['multi_to_date'][$i])),
						),
						'after' => array(
							'year' => (int) gmdate('Y', strtotime($from)),
							'month' => (int) gmdate('m', strtotime($from)),
							'day' => (int) gmdate('d', strtotime($from)),
						),
						'inclusive' => true,
					);
				}
			}

			if (count($date_args) > 1) {
				$query->set('date_query', $date_args);
			}

			// 5) Meta query.
			if (
				!empty($params['meta_keys']) &&
				!empty($params['meta_ops']) &&
				!empty($params['meta_tp'])
			) {
				$meta_query = array('relation' => ('or' === $params['relation']) ? 'OR' : 'AND');
				$len = max(count($params['meta_keys']), count($params['meta_ops']), count($params['meta_tp']));

				for ($i = 0; $i < $len; $i++) {
					if (empty($params['meta_keys'][$i]) || empty($params['meta_ops'][$i]) || empty($params['meta_tp'][$i])) {
						continue;
					}

					$value = isset($params['meta_vals'][$i]) ? $params['meta_vals'][$i] : '';

					// Support "IN" / "BETWEEN" with comma-separated input.
					if (('BETWEEN' === $params['meta_ops'][$i] || 'IN' === $params['meta_ops'][$i]) && false !== strpos($value, ',')) {
						$tmp = array_map('trim', explode(',', $value));
						$value = array_slice($tmp, 0, 2); // ensure at most 2 for BETWEEN; IN can take many but this mirrors your original logic.
					}

					$meta_query[] = array(
						'key' => $params['meta_keys'][$i],
						'value' => $value,
						'type' => $params['meta_tp'][$i],
						'compare' => $params['meta_ops'][$i],
					);
				}

				if (count($meta_query) > 1) {
					$query->set('meta_query', $meta_query);
				}
			}
			return $query;
		}

		function allusfi_get_query_params()
		{
			$out = array(
				'secure' => false,
				'ordr_by' => '',
				'usr_sort' => '',
				'one_date' => '',
				'cstm_dt' => '',
				'relation' => 'nd',
				'exclude_roles' => array(),
				'excl_ids' => array(),
				'multi_from_date' => array(),
				'multi_to_date' => array(),
				'meta_keys' => array(),
				'meta_vals' => array(),
				'meta_ops' => array(),
				'meta_tp' => array(),
				'wc_order_enabled' => false,
				'wc_order_count' => 0,
				'wc_order_op' => '>',
			);

			// 1) Standalone nonce verification.
			$out['secure'] = empty($_REQUEST['allusfi_secure']) ? false : wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['allusfi_secure'])), 'allusfi_secure');
			if (!$out['secure']) {
				return $out;
			}

			if (isset($_REQUEST['excl-ids'])) {
				$raw_ids = wp_kses_post(wp_unslash($_REQUEST['excl-ids']));
				$ids = array_filter(array_map('absint', explode('-', $raw_ids)));
				$out['excl_ids'] = array_values($ids);
			}

			$out['usr_sort'] = isset($_REQUEST['usr_srt'])
				? sanitize_text_field(wp_unslash($_REQUEST['usr_srt']))
				: '';

			$out['ordr_by'] = isset($_REQUEST['ordr-by'])
				? sanitize_text_field(wp_unslash($_REQUEST['ordr-by']))
				: '';

			if (isset($_REQUEST['rl-excld']) && is_array($_REQUEST['rl-excld'])) {
				$out['exclude_roles'] = array_values(
					array_filter(
						array_map('sanitize_text_field', wp_unslash($_REQUEST['rl-excld']))
					)
				);
			}

			$out['cstm_dt'] = isset($_REQUEST['cstm-dt'])
				? sanitize_text_field(wp_unslash($_REQUEST['cstm-dt']))
				: '';

			// Multi date ranges.
			if (
				isset($_REQUEST['mlt-f-dt'], $_REQUEST['mlt-t-dt']) &&
				is_array($_REQUEST['mlt-f-dt']) &&
				is_array($_REQUEST['mlt-t-dt'])
			) {
				$from = array_map('sanitize_text_field', wp_unslash($_REQUEST['mlt-f-dt']));
				$to = array_map('sanitize_text_field', wp_unslash($_REQUEST['mlt-t-dt']));

				$out['multi_from_date'] = $from;
				$out['multi_to_date'] = $to;
			}

			$out['one_date'] = (
				isset($_REQUEST['one-dt']) &&
				!empty($_REQUEST['one-dt']) &&
				empty($out['multi_from_date'])
			)
				? sanitize_textarea_field(sanitize_text_field(wp_unslash($_REQUEST['one-dt'])))
				: '';

			$out['relation'] = (isset($_REQUEST['rltn']) && 'or' === $_REQUEST['rltn']) ? 'or' : 'nd';

			// Meta arrays.
			$keys = (isset($_REQUEST['mta-ky']) && is_array($_REQUEST['mta-ky']))
				? array_map('sanitize_key', wp_unslash($_REQUEST['mta-ky']))
				: array();

			$vals = (isset($_REQUEST['mta-vl']) && is_array($_REQUEST['mta-vl']))
				? array_map('sanitize_textarea_field', wp_unslash($_REQUEST['mta-vl']))
				: array();

			$tps = (isset($_REQUEST['mta-tp']) && is_array($_REQUEST['mta-tp']))
				? array_map('sanitize_text_field', wp_unslash($_REQUEST['mta-tp']))
				: array();

			$ops = (isset($_REQUEST['mta-op']) && is_array($_REQUEST['mta-op'])) ? array_map('sanitize_text_field', wp_unslash($_REQUEST['mta-op'])) : array();

			$ops = (!empty($ops) && is_array($ops)) ? array_map(array($this, 're_sanitize_operator'), $ops) : array();

			$allowed_types = array('CHAR', 'NUMERIC', 'BINARY', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'UNSIGNED', 'TIME');

			// Normalize types/operators to allowed sets.
			foreach ($tps as $i => $tp) {
				$tps[$i] = in_array($tp, $allowed_types, true) ? $tp : 'CHAR';
			}

			$out['meta_keys'] = array_values(array_filter($keys));
			$out['meta_vals'] = array_values($vals);
			$out['meta_ops'] = array_values($ops);
			$out['meta_tp'] = array_values($tps);

			// WooCommerce order count filter params.
			$out['wc_order_enabled'] = !empty($_REQUEST['wc-ordr-enabled']);

			$out['wc_order_count'] = isset($_REQUEST['wc-ordr-cnt'])
				? absint(wp_unslash($_REQUEST['wc-ordr-cnt']))
				: 0;

			$allowed_wc_ops = array('>', '<', '=', '!=');
			$raw_wc_op = isset($_REQUEST['wc-ordr-op'])
				? sanitize_text_field(wp_unslash($_REQUEST['wc-ordr-op']))
				: '>';
			$out['wc_order_op'] = in_array($raw_wc_op, $allowed_wc_ops, true) ? $raw_wc_op : '>';

			return $out;
		}

		/**
		 * Filter users by WooCommerce order count.
		 *
		 * Hooked to `pre_user_query`. Modifies the raw SQL to INNER JOIN
		 * order data grouped by customer ID with a HAVING clause.
		 *
		 * @param WP_User_Query $query The current user query object.
		 */
		function allusfi_filter_users_by_wc_orders($query)
		{
			if (!is_admin() || !class_exists('WooCommerce')) {
				return $query;
			}

			// Static guard: prevent the JOIN from being appended more than once
			// (the constructor re-registers this hook each time ALLUSFI_Admin is instantiated).
			static $wc_filter_applied = false;
			if ($wc_filter_applied) {
				return $query;
			}

			$params = $this->allusfi_get_query_params();

			if (!$params['secure'] || !$params['wc_order_enabled']) {
				return $query;
			}

			// Mark as applied so duplicate hook registrations are harmless.
			$wc_filter_applied = true;

			global $wpdb;

			$op = $params['wc_order_op'];    // Already whitelisted.
			$count = $params['wc_order_count'];  // Already absint.

			if (self::allusfi_is_hpos_enabled()) {
				// HPOS: orders stored in {prefix}wc_orders.
				$orders_table = $wpdb->prefix . 'wc_orders';
				$subquery = "LEFT JOIN (
						SELECT customer_id, COUNT(*) AS order_count
						FROM `{$orders_table}`
						GROUP BY customer_id
					) AS wc_order_counts ON {$wpdb->users}.ID = wc_order_counts.customer_id";
			} else {
				// Legacy: orders stored in {prefix}posts + {prefix}postmeta.
				$subquery = "LEFT JOIN (
						SELECT pm.meta_value AS customer_id, COUNT(*) AS order_count
						FROM {$wpdb->posts} AS p
						INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND pm.meta_key = '_customer_user'
						GROUP BY pm.meta_value
					) AS wc_order_counts ON {$wpdb->users}.ID = wc_order_counts.customer_id";
			}

			$query->query_from .= " {$subquery}";

			// Use COALESCE so users with no orders get count = 0 instead of NULL.
			$query->query_where .= $wpdb->prepare(
				" AND COALESCE(wc_order_counts.order_count, 0) {$op} %d",
				$count
			);

			// Prevent duplicate user rows from the JOIN.
			if (strpos($query->query_fields, 'DISTINCT') === false) {
				$query->query_fields = 'DISTINCT ' . $query->query_fields;
			}
		}

		/**
		 * Detect if WooCommerce High-Performance Order Storage (HPOS) is enabled.
		 *
		 * @return bool True if HPOS custom tables are in use.
		 */
		private static function allusfi_is_hpos_enabled()
		{
			if (!class_exists('Automattic\WooCommerce\Utilities\OrderUtil')) {
				return false;
			}
			return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		private function re_sanitize_operator(string $op)
		{
			$allowed = array(
				'&lt;' => '<',
				'&lt;=' => '<=',
			);
			return (isset($allowed[$op])) ? $allowed[$op] : $op;
		}
	}

	add_action('admin_init', function () {
		$allusfi_is_filter_allowed = apply_filters('allusfi_allowed_user_to_filter', false);
		if (current_user_can('administrator') || $allusfi_is_filter_allowed) {
			new ALLUSFI_Admin();
		}
	});
}

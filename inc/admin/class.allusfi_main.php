<?php
/*Main class that is made for declaring the functions, actions, filters */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (!class_exists('ALLUSFI_Admin')) {

	class  ALLUSFI_Admin
	{
		function __construct()
		{
			/********* list of action *********/
			add_action('admin_enqueue_scripts', array($this, 'allusfi_action_admin_init'));
			add_action('manage_users_extra_tablenav', array($this, 'allusfi_render_custom_html'));

			/********* list of filters *********/
			add_filter('pre_get_users', array($this, 'allusfi_filter_users_by_requests'));
		}

		function allusfi_action_admin_init()
		{
			wp_register_script(ALLUSFI_PREFIX . '_admin_js', ALLUSFI_URL . 'assets/js/admin.js', array('jquery'), ALLUSFI_VERSION, true);
			wp_register_style(ALLUSFI_PREFIX . '_admin_css', ALLUSFI_URL . 'assets/css/admin.css', array(), ALLUSFI_VERSION);
			$allusfi_local_array = array(
				'plugin_prefix' 			=> ALLUSFI_PREFIX,
				'ajax_url'      			=> admin_url('admin-ajax.php'),
				'btn_export_txt'			=> __( 'CLICK HERE TO EXPORT CSV', 'all-users-filter'),
				'btn_export_finish_txt'		=> __( 'Export complete', 'all-users-filter'),
				'get_req_txt'				=> __( 'GET REQUEST ENABLED!','all-users-filter'),
				'post_req_txt'				=> __( 'POST REQUEST ENABLED!','all-users-filter'),	
				'start_export_process_txt'	=> __( 'Starting export...', 'all-users-filter'),
				'export_process_txt'		=> __( 'Exporting...', 'all-users-filter'),
				'export_ongoing_txt'		=> __( 'Currently processing your export... Please keep this browser window open until the process is complete to avoid interrupting it.', 'all-users-filter'),
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
			if (! is_admin()) {
				return;
			}
			$admin  = new ALLUSFI_Admin();
			$params = $admin->allusfi_get_query_params();

			if (! $params['secure']) {
				return; // Do not modify the query for unverified/ordinary loads
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
			if (! empty($params['exclude_roles'])) {
				$query->set('role__not_in', $params['exclude_roles']);
			}

			// 3) Exclude IDs.
			if (! empty($params['excl_ids'])) {
				$query->set('exclude', $params['excl_ids']);
			}

			// 4) Date filters.
			$date_args = array('relation' => 'OR');

			if ($params['one_date']) {
				$date_args[] = array(
					'year'  => (int) gmdate('Y', strtotime($params['one_date'])),
					'month' => (int) gmdate('m', strtotime($params['one_date'])),
					'day'   => (int) gmdate('d', strtotime($params['one_date'])),
				);
			}

			if ($params['cstm_dt']) {
				$date_args[] = array('after' => $params['cstm_dt'], 'inclusive' => true);
			}

			if (! empty($params['multi_from_date']) && ! empty($params['multi_to_date'])) {
				foreach ($params['multi_from_date'] as $i => $from) {
					if (empty($params['multi_to_date'][$i]) || empty($from)) {
						continue;
					}
					$date_args[] = array(
						'before'    => array(
							'year'  => (int) gmdate('Y', strtotime($params['multi_to_date'][$i])),
							'month' => (int) gmdate('m', strtotime($params['multi_to_date'][$i])),
							'day'   => (int) gmdate('d', strtotime($params['multi_to_date'][$i])),
						),
						'after'     => array(
							'year'  => (int) gmdate('Y', strtotime($from)),
							'month' => (int) gmdate('m', strtotime($from)),
							'day'   => (int) gmdate('d', strtotime($from)),
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
				! empty($params['meta_keys']) &&
				! empty($params['meta_ops']) &&
				! empty($params['meta_tp'])
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
						'key'     => $params['meta_keys'][$i],
						'value'   => $value,
						'type'    => $params['meta_tp'][$i],
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
				'secure'          => false,
				'ordr_by'         => '',
				'usr_sort'        => '',
				'one_date'        => '',
				'cstm_dt'         => '',
				'relation'        => 'nd',
				'exclude_roles'    => array(),
				'excl_ids'        => array(),
				'multi_from_date' => array(),
				'multi_to_date'   => array(),
				'meta_keys'       => array(),
				'meta_vals'       => array(),
				'meta_ops'        => array(),
				'meta_tp'         => array(),
			);

			// 1) Standalone nonce verification.
			$out['secure'] = empty($_REQUEST['allusfi_secure']) ? false : wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['allusfi_secure'])), 'allusfi_secure');
			if (! $out['secure']) {
				return $out;
			}

			if (isset($_REQUEST['excl-ids'])) {
				$raw_ids = wp_kses_post(wp_unslash($_REQUEST['excl-ids']));
				$ids     = array_filter(array_map('absint', explode('-', $raw_ids)));
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
				$to   = array_map('sanitize_text_field', wp_unslash($_REQUEST['mlt-t-dt']));

				$out['multi_from_date'] = $from;
				$out['multi_to_date']   = $to;
			}

			$out['one_date'] = (
				isset($_REQUEST['one-dt']) &&
				! empty($_REQUEST['one-dt']) &&
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

			$ops = (isset($_REQUEST['mta-op']) && is_array($_REQUEST['mta-op'])) ? array_map( 'sanitize_text_field', wp_unslash($_REQUEST['mta-op'])) : array(); 

			$ops = ( ! empty($ops) && is_array($ops) ) ? array_map( array($this, 're_sanitize_operator'), $ops) : array(); 

			$allowed_types = array('CHAR', 'NUMERIC', 'BINARY', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'UNSIGNED', 'TIME');

			// Normalize types/operators to allowed sets.
			foreach ($tps as $i => $tp) {
				$tps[$i] = in_array($tp, $allowed_types, true) ? $tp : 'CHAR';
			}

			$out['meta_keys'] = array_values(array_filter($keys));
			$out['meta_vals'] = array_values($vals);
			$out['meta_ops']  = array_values($ops);
			$out['meta_tp']   = array_values($tps);

			return $out;
		}

		private function re_sanitize_operator(string $op)
		{
			$allowed = array(
				'&lt;'	=> '<',
				'&lt;='	=> '<=',
			);
			return ( isset( $allowed[$op] ) ) ? $allowed[$op] : $op;
		}
	}

	add_action('admin_init', function () {
		$allusfi_is_filter_allowed = apply_filters('allusfi_allowed_user_to_filter', false);
		if (current_user_can('administrator') || $allusfi_is_filter_allowed) {
			new ALLUSFI_Admin();
		}
	});
}

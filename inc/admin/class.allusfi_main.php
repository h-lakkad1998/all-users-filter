<?php
/*Main class that is made for declaring the functions, actions, filters */
// Exit if accessed directly
if (!defined('ABSPATH'))
	exit;

// Load the relative-date parser.
require_once ALLUSFI_DIR . '/inc/admin/class.allusfi_date_parser.php';

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
			wp_register_script(ALLUSFI_PREFIX . '_admin_js',  ALLUSFI_URL . 'assets/js/admin.js',       array('jquery'), (int) filemtime(ALLUSFI_DIR . '/assets/js/admin.js')       ?: 1, true);
			wp_register_style(ALLUSFI_PREFIX . '_admin_css',  ALLUSFI_URL . 'assets/css/admin.css',     array(),         (int) filemtime(ALLUSFI_DIR . '/assets/css/admin.css')     ?: 1);

			// WordPress 7.0+ ships a redesigned admin UI that breaks the base CSS.
			// Load the targeted override sheet only on WP 7.0 and above so that
			// WP 6.9 (and earlier) users keep the original, unchanged experience.
			if (version_compare(get_bloginfo('version'), '7.0', '>=')) {
				wp_register_style(
					ALLUSFI_PREFIX . '_admin_css_wp70',
					ALLUSFI_URL . 'assets/css/admin-wp70.css',
					array(ALLUSFI_PREFIX . '_admin_css'), // load after base CSS
					(int) filemtime(ALLUSFI_DIR . '/assets/css/admin-wp70.css') ?: 1
				);
				wp_enqueue_style(ALLUSFI_PREFIX . '_admin_css_wp70');
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- allu_filter_id is a plugin-internal routing token, not a nonce; it is only used to pass the current filter token into the JS context for AJAX calls. No capability action is taken based on this value here.
			$allu_filter_id_current = isset($_GET['allu_filter_id'])
				? sanitize_text_field(wp_unslash($_GET['allu_filter_id']))
				: '';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$allusfi_local_array = array(
				'plugin_prefix'            => ALLUSFI_PREFIX,
				'ajax_url'                 => admin_url('admin-ajax.php'),
				// Fresh nonce for JS-initiated requests (filter save, export, saved-filter apply).
				'nonce'                    => wp_create_nonce('allusfi_secure'),
				// Current filter state identifier so JS can include it in AJAX payloads.
				'allu_filter_id'           => $allu_filter_id_current,
				'users_page_url'           => admin_url('users.php'),
				'btn_export_txt'           => __('CLICK HERE TO EXPORT CSV', 'all-users-filter'),
				'btn_export_finish_txt'    => __('Export complete', 'all-users-filter'),
				'start_export_process_txt' => __('Starting export...', 'all-users-filter'),
				'export_process_txt'       => __('Exporting...', 'all-users-filter'),
				'export_ongoing_txt'       => __('Currently processing your export... Please keep this browser window open until the process is complete to avoid interrupting it.', 'all-users-filter'),
				// Saved filters — only expose new-format entries (those with an 'id' key).
				'saved_filters'            => array_values(
					array_filter(
						(array) get_option('allusfi_saved_filters', array()),
						static function ($sf) {
							return isset($sf['id'], $sf['filter_array']);
						}
					)
				),
				'sf_duplicate_name_txt'    => __('A filter with that name already exists. Please choose a different name.', 'all-users-filter'),
				'sf_enter_name_txt'        => __('Please enter a name for this filter.', 'all-users-filter'),
				'sf_save_error_txt'        => __('Error saving filter. Please try again.', 'all-users-filter'),
				'sf_no_active_state_txt'   => __('No active filter found. Please apply a filter before saving.', 'all-users-filter'),
				'sf_delete_confirm_txt'    => __('Are you sure you want to delete this saved filter?', 'all-users-filter'),
				'sf_delete_error_txt'      => __('Error deleting filter. Please try again.', 'all-users-filter'),
				'sf_no_filters_txt'        => __('No saved filters yet.', 'all-users-filter'),
				'sf_apply_txt'             => __('Apply', 'all-users-filter'),
				'sf_delete_txt'            => __('Delete', 'all-users-filter'),
				'sf_col_name_txt'          => __('Filter Name', 'all-users-filter'),
				'sf_col_actions_txt'       => __('Actions', 'all-users-filter'),
				// Export settings
				'export_meta_include_txt'  => __('Include Meta Fields', 'all-users-filter'),
				'export_no_meta_txt'       => __('No active meta filters. Use the Advanced tab to add meta filters, or enter an extra meta key below.', 'all-users-filter'),
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

			// 5) Meta query (standard advanced filters).
			$meta_query = array('relation' => ('or' === $params['relation']) ? 'OR' : 'AND');

			if (
				!empty($params['meta_keys']) &&
				!empty($params['meta_ops']) &&
				!empty($params['meta_tp'])
			) {
				$len = max(count($params['meta_keys']), count($params['meta_ops']), count($params['meta_tp']));

				for ($i = 0; $i < $len; $i++) {
					if (empty($params['meta_keys'][$i]) || empty($params['meta_ops'][$i]) || empty($params['meta_tp'][$i])) {
						continue;
					}

					$value = isset($params['meta_vals'][$i]) ? $params['meta_vals'][$i] : '';

					// Support "IN" / "BETWEEN" with comma-separated input.
					if (('BETWEEN' === $params['meta_ops'][$i] || 'IN' === $params['meta_ops'][$i]) && false !== strpos($value, ',')) {
						$tmp   = array_map('trim', explode(',', $value));
						$value = array_slice($tmp, 0, 2);
					}

					$meta_query[] = array(
						'key'     => $params['meta_keys'][$i],
						'value'   => $value,
						'type'    => $params['meta_tp'][$i],
						'compare' => $params['meta_ops'][$i],
					);
				}
			}

			// 6) Relative-date meta query (from the Date Filter tab).
			if (
				!empty($params['rel_date_keys']) &&
				!empty($params['rel_date_vals']) &&
				!empty($params['rel_date_tp'])
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
				$query->set('meta_query', $meta_query);
			}

			return $query;
		}

		function allusfi_get_query_params()
		{
			$out = array(
				'secure'           => false,
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
				// Relative-date meta filter rows (Date Filter tab).
				'rel_date_keys'    => array(),
				'rel_date_vals'    => array(),
				'rel_date_tp'      => array(),
				'wc_order_enabled' => false,
				'wc_order_count'   => 0,
				'wc_order_op'      => '>',
			);

			// ============================================================
			// NEW BRANCH: Transient / saved-filter lookup.
			// When allu_filter_id is present in the GET string we resolve
			// the stored state instead of parsing 20+ individual params.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is checked immediately below.
			if (isset($_GET['allu_filter_id'])) {
				// Verify nonce carried in the short URL.
				$nonce_ok = (
					!empty($_GET['allusfi_secure']) &&
					wp_verify_nonce(
						sanitize_text_field(wp_unslash($_GET['allusfi_secure'])),
						'allusfi_secure'
					)
				);

				if (!$nonce_ok) {
					return $out; // nonce invalid — returns with secure flag set to false
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- already verified above.
				$filter_id = sanitize_text_field(wp_unslash($_GET['allu_filter_id']));
				$stored    = null;

				if ('allusifi_current_state' === $filter_id) {
					// Per-user transient (set by allusfi_save_filter_transient_fun).
					$user_id = get_current_user_id();
					$stored  = get_transient('allusfi_state_' . $user_id);
				} elseif (0 === strpos($filter_id, 'saved_filter_')) {
					// Resolve from the saved-filters option by unique ID.
					$sf_id = substr($filter_id, strlen('saved_filter_'));
					$saved = (array) get_option('allusfi_saved_filters', array());
					foreach ($saved as $sf) {
						if (isset($sf['id'], $sf['filter_array']) && $sf['id'] === $sf_id) {
							$stored = $sf['filter_array'];
							// export_settings is stored at the top level of the saved record,
							// not inside filter_array — merge it in so $params['export_settings'] works.
							if (isset($sf['export_settings']) && is_array($sf['export_settings'])) {
								$stored['export_settings'] = $sf['export_settings'];
							}
							break;
						}
					}
				}

				if (is_array($stored) && !empty($stored)) {
					$stored['secure'] = true; // nonce already verified above
					return $stored;
				}

				// Transient expired / saved filter not found → no filter active.
				return $out;
			}
			// No allu_filter_id in GET — no filter is active.
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
				$subquery  = "LEFT JOIN (\n";
				$subquery .= "\t\t\t\t\t\tSELECT customer_id, COUNT(*) AS order_count\n";
				$subquery .= "\t\t\t\t\t\tFROM `{$orders_table}`\n";
				$subquery .= "\t\t\t\t\t\tGROUP BY customer_id\n";
				// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users -- JOIN on $wpdb->users is unavoidable in pre_user_query for WC order-count filtering; no WP API alternative exists for raw SQL JOIN hooks.
				$subquery .= "\t\t\t\t\t) AS wc_order_counts ON {$wpdb->users}.ID = wc_order_counts.customer_id";
			} else {
				// Legacy: orders stored in {prefix}posts + {prefix}postmeta.
				$subquery  = "LEFT JOIN (\n";
				$subquery .= "\t\t\t\t\t\tSELECT pm.meta_value AS customer_id, COUNT(*) AS order_count\n";
				$subquery .= "\t\t\t\t\t\tFROM {$wpdb->posts} AS p\n";
				$subquery .= "\t\t\t\t\t\tINNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND pm.meta_key = '_customer_user'\n";
				$subquery .= "\t\t\t\t\t\tGROUP BY pm.meta_value\n";
				// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users -- JOIN on $wpdb->users is unavoidable in pre_user_query for WC order-count filtering; no WP API alternative exists for raw SQL JOIN hooks.
				$subquery .= "\t\t\t\t\t) AS wc_order_counts ON {$wpdb->users}.ID = wc_order_counts.customer_id";
			}

			$query->query_from .= " {$subquery}";

			// $op is validated against an explicit whitelist above; SQL operators cannot be parameterised
			// via %s — each branch uses a fully-static SQL literal to satisfy WPCS.
			switch ($op) {
				case '<':
					$query->query_where .= $wpdb->prepare(' AND COALESCE(wc_order_counts.order_count, 0) < %d', $count);
					break;
				case '=':
					$query->query_where .= $wpdb->prepare(' AND COALESCE(wc_order_counts.order_count, 0) = %d', $count);
					break;
				case '!=':
					$query->query_where .= $wpdb->prepare(' AND COALESCE(wc_order_counts.order_count, 0) != %d', $count);
					break;
				default: // '>'
					$query->query_where .= $wpdb->prepare(' AND COALESCE(wc_order_counts.order_count, 0) > %d', $count);
					break;
			}

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

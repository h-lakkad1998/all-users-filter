<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;
/**
 * AJAX: Export users in batches of 20
 */
add_action('wp_ajax_lkd_wp_usr_export_csv', 'lkd_wp_usr_export_csv_fun');
function lkd_wp_usr_export_csv_fun()
{
    $lkd_usr_filter_secure = (isset($_REQUEST['lkd_usr_filter_secure'])) ? wp_verify_nonce(sanitize_textarea_field(wp_unslash($_REQUEST['lkd_usr_filter_secure'])), 'lkd_usr_filter_secure') : false;

    $ordr_by = $usr_sort = $one_date = $cstm_dt = $relation = '';
    $exlude_roles = $excl_ids = $multi_from_date = $multi_to_date = $meta_keys = $meta_vals = $meta_ops = $meta_tp  = array();
    $lkd_is_filter_allowed = apply_filters('lkd_wp_usr_filter_allowed', false);
    if ($lkd_usr_filter_secure && (current_user_can('administrator') || $lkd_is_filter_allowed)) {
        //  this is sanitization part
        $excl_ids = (isset($_REQUEST["excl-ids"])) ? wp_kses_post(wp_unslash($_REQUEST["excl-ids"])) : false;
        if ($excl_ids) {
            $excl_ids = explode("-", $excl_ids);
            $excl_ids = array_filter($excl_ids);
        }
        $excl_ids = (!empty($excl_ids)) ? $excl_ids : false;
        $usr_sort = (isset($_REQUEST["usr_srt"])) ? sanitize_textarea_field(wp_unslash($_REQUEST["usr_srt"]))  : "";
        $ordr_by = (isset($_REQUEST["ordr-by"])) ? sanitize_textarea_field(wp_unslash($_REQUEST["ordr-by"])) : "";
        $excl_ids = (false !== $excl_ids) ? array_map('sanitize_textarea_field', $excl_ids) : array();
        $exlude_roles = (isset($_REQUEST["rl-excld"]) && !empty($_REQUEST["rl-excld"])) ? array_map('sanitize_textarea_field', wp_unslash($_REQUEST["rl-excld"])) : array();
        $cstm_dt =  (isset($_REQUEST["cstm-dt"])) ? sanitize_textarea_field(wp_unslash($_REQUEST["cstm-dt"]))  : "";
        $multi_from_date = $multi_to_date = array();
        if (isset($_REQUEST["mlt-f-dt"]) && isset($_REQUEST["mlt-t-dt"])) {
            $multi_from_date = (!empty($_REQUEST["mlt-f-dt"])) ? array_map('sanitize_textarea_field', wp_unslash($_REQUEST["mlt-f-dt"])) : [];
            $multi_to_date = (!empty($_REQUEST["mlt-t-dt"])) ? array_map('sanitize_textarea_field', wp_unslash($_REQUEST["mlt-t-dt"])) : [];
        }
        $one_date =  (isset($_REQUEST["one-dt"]) && !empty($_REQUEST["one-dt"]) && empty($multi_from_date)) ? date_format(date_create(sanitize_textarea_field(wp_unslash($_REQUEST["one-dt"]))), "Y-m-d") : "";
        $relation = (isset($_REQUEST["rltn"]) && $_REQUEST["rltn"] == 'or') ? 'or' : 'nd';
        $meta_keys = $meta_vals = $meta_ops = $meta_tp = array();
        $meta_keys = (isset($_REQUEST["mta-ky"]) && is_array($_REQUEST["mta-ky"]) && !empty($_REQUEST["mta-ky"])) ?  array_map("sanitize_key", wp_unslash($_REQUEST["mta-ky"])) : $meta_keys;
        $meta_vals = (isset($_REQUEST["mta-vl"]) && is_array($_REQUEST["mta-vl"]) && !empty($_REQUEST["mta-vl"])) ? array_map("sanitize_textarea_field", wp_unslash($_REQUEST["mta-vl"])) : $meta_vals;
        $compatible_type = array("CHAR", "NUMERIC", "BINARY", "DATE", "DATETIME", "DECIMAL", "SIGNED", "UNSIGNED", "TIME");
        $meta_tp = (isset($_REQUEST["mta-tp"]) && is_array($_REQUEST["mta-tp"])  && !empty($_REQUEST["mta-tp"])) ? array_map("sanitize_textarea_field", wp_unslash($_REQUEST["mta-tp"])) : $meta_tp;
        $compatible_compares = array('=', "!=", 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=', 'NOT EXISTS', 'NOT REGEXP');
        if (isset($_REQUEST["mta-op"]) && !empty($_REQUEST["mta-op"])) {
            $meta_ops = (isset($_REQUEST["mta-op"]) && is_array($_REQUEST["mta-op"]) && !empty($_REQUEST["mta-op"])) ? array_map("lkd_sanitize_operator", wp_unslash($_REQUEST["mta-op"])) : $meta_ops;
            if (! empty($meta_ops) && is_array($meta_ops)) {
                foreach ($meta_ops as $index => $value) {
                    $meta_ops[$index] = (!in_array($value, $compatible_compares)) ? "=" : $value;
                    $meta_tp[$index] = (isset($meta_tp[$index]) && !in_array($meta_tp[$index], $compatible_type)) ? "CHAR" : $meta_tp[$index];
                }
            }
        }
        $paged = (isset($_REQUEST["paged"])) ? (int) sanitize_textarea_field(wp_unslash($_REQUEST["paged"]))  : "";
        $lkd_searched = (isset($_REQUEST["s"])) ? sanitize_textarea_field(wp_unslash($_REQUEST["s"]))  : "";
        // this is setting up query vars in ajax
        $lkd_ajx_query = new WP_User_Query();
        if ($ordr_by == "1") $lkd_ajx_query->set('order', 'ASC');
        else $lkd_ajx_query->set('order', 'DESC');
        if ($usr_sort !== "") {
            switch ($usr_sort) {
                case 'f-nm':
                    $lkd_ajx_query->set('meta_key', 'first_name');
                    $lkd_ajx_query->set('orderby', 'meta_value');
                    break;
                case 'l-nm':
                    $lkd_ajx_query->set('meta_key', 'last_name');
                    $lkd_ajx_query->set('orderby', 'meta_value');
                    break;
                case 'usr-id':
                    $lkd_ajx_query->set('orderby', 'ID');
                    break;
                case 'usr-lgn':
                    $lkd_ajx_query->set('orderby', 'user_login');
                    break;
                case 'dis-nm':
                    $lkd_ajx_query->set('orderby', 'display_name');
                    break;
                case 'reg-dt':
                    $lkd_ajx_query->set('orderby', 'registered');
                    break;
                case 'pst-cnt':
                    $lkd_ajx_query->set('orderby', 'post_count');
                    break;
            }
        }
        if (!empty($lkd_searched)) {
            $lkd_ajx_query->set('search', $lkd_searched);
        }
        // prevent particular roles
        if (! empty($exlude_roles)) {
            $lkd_ajx_query->set('role__not_in', $exlude_roles);
        }
        // prevent id to be displayed in the user listing - Number and "-" are allowed 
        if ($excl_ids && is_array($excl_ids)) $lkd_ajx_query->set('exclude', $excl_ids);
        $date_args = array(
            "relation"  => "OR",
        );
        if ($one_date) {
            $date_args[] =  array('year' =>  gmdate('Y', strtotime($one_date)), 'month' => gmdate('m', strtotime($one_date)), 'day' => gmdate('d', strtotime($one_date)));
            $lkd_ajx_query->set('date_query',  $date_args);
        }
        if ($cstm_dt) {
            $date_args[] =  array('after' =>  $cstm_dt, 'inclusive' => true);
            $lkd_ajx_query->set('date_query',  $date_args);
        }
        if (!empty($multi_from_date) && !empty($multi_to_date)) {
            foreach ($multi_from_date as $index => $single_val) {
                if (!empty($multi_to_date[$index]) &&   !empty($multi_from_date[$index])) {
                    $multi_dates = array(
                        "before" => array(
                            'year'  =>  gmdate('Y', strtotime($multi_to_date[$index])),
                            'month' => gmdate('m', strtotime($multi_to_date[$index])),
                            'day'   => gmdate('d', strtotime($multi_to_date[$index]))
                        ),
                        "after" =>  array(
                            'year'  =>  gmdate('Y', strtotime($multi_from_date[$index])),
                            'month' => gmdate('m', strtotime($multi_from_date[$index])),
                            'day'   => gmdate('d', strtotime($multi_from_date[$index]))
                        ),
                        'inclusive' => true
                    );
                    $date_args[] = $multi_dates;
                }
            }
            if (!empty($date_args)) {
                $lkd_ajx_query->set('date_query',  $date_args);
            }
        }
        if (!empty($meta_keys)  && !empty($meta_vals) && !empty($meta_ops) && ! empty($meta_tp)) {
            $meta_query_args = array(
                'relation' => ("or" === $relation) ? "OR" : "AND",
            );
            $cnt_len = (!empty($meta_ops) && is_array($meta_ops)) ? count($meta_ops)   :  false;
            if ($cnt_len !== false) {
                for ($i = 0; $i < $cnt_len; $i++) {
                    // Extending functionalities for between operator.
                    $change_meta_vals = (empty($meta_vals[$i])) ? '' : $meta_vals[$i];
                    if ('BETWEEN' === $meta_ops[$i] && false !== strpos($change_meta_vals, ',')) {
                        $temp_array = explode(',', $change_meta_vals);
                        if (isset($temp_array[0]) && isset($temp_array[1])) {
                            $change_meta_vals = array();
                            $change_meta_vals[] = $temp_array[0];
                            $change_meta_vals[] = $temp_array[1];
                        }
                    }
                    $meta_query_args[$i]['key']  = $meta_keys[$i];
                    $meta_query_args[$i]['value'] = $change_meta_vals;
                    $meta_query_args[$i]['type']    = $meta_tp[$i];
                    $meta_query_args[$i]['compare']  = $meta_ops[$i];
                }
            }
            $lkd_ajx_query->set('meta_query',  $meta_query_args);
        }
        $queried_variables = $lkd_ajx_query->query_vars;
        $queried_variables['paged'] = $paged;
        $queried_variables['number'] = 999;

        $user_query = new WP_User_Query($queried_variables);
        $users  = $user_query->get_results();
        $total  = $user_query->get_total();

        // Meta keys from request if needed
        $meta_keys = (isset($_REQUEST["mta-ky"]) && is_array($_REQUEST["mta-ky"]) && !empty($_REQUEST["mta-ky"]))
            ? array_map("sanitize_key", wp_unslash($_REQUEST["mta-ky"]))
            : [];

        $rows = [];
        if ($paged === 1) {
            $base_cols = ["User ID", "User Login", "User Email", "User Nicename", "Display Name", "User Role", "Registration Date"];
            $rows[] = (!empty($meta_keys)) ? array_merge($base_cols, $meta_keys) : $base_cols;
        }

        foreach ($users as $u) {
            $main_data = [
                $u->ID,
                $u->user_login,
                $u->user_email,
                $u->user_nicename,
                $u->display_name,
                (!empty($u->roles) ? implode(',', $u->roles) : ''),
                $u->user_registered,
            ];

            if (!empty($meta_keys)) {
                $user_meta_vals = [];
                foreach ($meta_keys as $single_key) {
                    $m_val = get_user_meta($u->ID, $single_key, true);
                    $m_val = (is_array($m_val)) ? serialize($m_val) : $m_val;
                    $user_meta_vals[] = $m_val;
                }
                $rows[] = array_merge($main_data, $user_meta_vals);
            } else {
                $rows[] = $main_data;
            }
        }

        wp_send_json_success([
            'rows'  => $rows,
            'total' => $total,
            'paged' => $paged,
            'count' => count($rows),
        ]);
    }else{
        wp_send_json_error( [
            'status'    => 'failed',
            'msg'       => esc_html__( 'Security check failed', 'all-users-filter' ) 
        ] );
    }
}

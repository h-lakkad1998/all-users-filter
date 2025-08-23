<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

include LKD_WP_USR_FLTR_DIR . '/inc/admin/query_get_paras.' . LKD_WP_USR_FLTR_PREFIX . '.php';

if ($lkd_usr_filter_secure) {
    if ($ordr_by == "1") $query->set('order', 'ASC');
    else $query->set('order', 'DESC');
    if ($usr_sort !== "") {
        switch ($usr_sort) {
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
    }
    // prevent particular roles
    if (!empty($exlude_roles)) {
        $query->set('role__not_in', $exlude_roles);
    }
    // prevent id to be displayed in the user listing - Number and "-" are allowed 
    if ($excl_ids && is_array($excl_ids)) $query->set('exclude', $excl_ids);
    $date_args = array(
        "relation"  => "OR",
    );
    if ($one_date) {
        $date_args[] =  array('year' =>  gmdate('Y', strtotime($one_date)), 'month' => gmdate('m', strtotime($one_date)), 'day' => gmdate('d', strtotime($one_date)));
        $query->set('date_query',  $date_args);
    }
    if ($cstm_dt) {
        $date_args[] =  array('after' =>  $cstm_dt, 'inclusive' => true);
        $query->set('date_query',  $date_args);
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
            $query->set('date_query',  $date_args);
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
        $query->set('meta_query',  $meta_query_args);
    }
}

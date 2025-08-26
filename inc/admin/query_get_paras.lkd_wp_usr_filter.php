<?php
/* 
This files is used for data sanitization and process GET/REQUEST variables from url query parameters for the user filtration
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Check security nonce
$lkd_usr_filter_secure = (isset($_REQUEST['lkd_usr_filter_secure'])) ? wp_verify_nonce(sanitize_textarea_field(wp_unslash($_REQUEST['lkd_usr_filter_secure'])), 'lkd_usr_filter_secure') : false;

$ordr_by = $usr_sort = $one_date = $cstm_dt = $relation = '';
$exlude_roles = $excl_ids = $multi_from_date = $multi_to_date = $meta_keys = $meta_vals = $meta_ops = $meta_tp  = array();
if ($lkd_usr_filter_secure) {
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
          $meta_ops = (isset($_REQUEST["mta-op"]) && is_array($_REQUEST["mta-op"]) && !empty($_REQUEST["mta-op"])) ? array_map("lkd_sanitize_operator", wp_unslash($_REQUEST["mta-op"])) : $meta_ops; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
          if (! empty($meta_ops) && is_array($meta_ops)) {
               foreach ($meta_ops as $index => $value) {
                    $meta_ops[$index] = (!in_array($value, $compatible_compares)) ? "=" : $value;
                    $meta_tp[$index] = (isset($meta_tp[$index]) && !in_array($meta_tp[$index], $compatible_type)) ? "CHAR" : $meta_tp[$index];
               }
          }
     }
}

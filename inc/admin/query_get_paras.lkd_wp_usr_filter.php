<?php
/* 
this files is used for data sanitization and process GET/REQUEST variables from url query parameters for the user filtration
*/

// Exit if accessed directly
if ( !defined('ABSPATH') ) exit;

// check security nonce
if( ! isset( $_REQUEST['lkd_usr_filter_secure'] ) && ( isset( $_REQUEST['lkd_usr_filter_secure'] ) && wp_verify_nonce($_REQUEST['lkd_usr_filter_secure'], 'lkd_usr_filter_secure') ) ){
     wp_die('Security check failed');
}

$excl_ids = ( isset( $_REQUEST["excl-ids"]) && !empty(trim($_REQUEST["excl-ids"]) ) ) ? sanitize_text_field($_REQUEST["excl-ids"]) : false;
if ( $excl_ids ) {
     $excl_ids = explode("-", $excl_ids);
     $excl_ids = array_map('sanitize_text_field', $excl_ids); // This sanitize bad values
     $excl_ids = array_filter($excl_ids); // This will remove empty value
}
$excl_ids = ( !empty($excl_ids) ) ? $excl_ids : false;
$filtr_sbmt = ( isset( $_REQUEST["fltr-sbmt"] ) && $_REQUEST["fltr-sbmt"] === "1" ) ? true : false;
$usr_sort = ( isset($_REQUEST["usr_srt"]) ) ? sanitize_text_field($_REQUEST["usr_srt"])  : "";
$ordr_by = ( isset($_REQUEST["ordr-by"]) ) ? sanitize_text_field($_REQUEST["ordr-by"]) : "";
$excl_ids = ( false !== $excl_ids ) ? array_map('sanitize_text_field', $excl_ids) : array();
$exlude_roles = (isset($_REQUEST["rl-excld"]) && !empty($_REQUEST["rl-excld"])) ? array_map('sanitize_text_field', $_REQUEST["rl-excld"]) : array();
$cstm_dt =  (isset($_REQUEST["cstm-dt"])) ? sanitize_text_field($_REQUEST["cstm-dt"])  : "";
$multi_from_date = $multi_to_date = array();
if ( isset($_REQUEST["mlt-f-dt"]) && isset($_REQUEST["mlt-t-dt"]) ) {
     $multi_from_date = ( !empty( $_REQUEST["mlt-f-dt"] ) ) ? array_map('sanitize_text_field', $_REQUEST["mlt-f-dt"] ) : [];
     $multi_to_date = ( !empty( $_REQUEST["mlt-t-dt"] )) ? array_map('sanitize_text_field', $_REQUEST["mlt-t-dt"] ) : [];
}
$one_date =  ( isset($_REQUEST["one-dt"]) && !empty($_REQUEST["one-dt"]) && empty( $multi_from_date ) ) ? sanitize_text_field(date_format(date_create($_REQUEST["one-dt"]), "Y-m-d")) : "";
$relation = (isset($_REQUEST["rltn"]) && $_REQUEST["rltn"] == 'or') ? 'or' : 'nd';
$meta_keys = $meta_vals = $meta_ops = $meta_tp = array();
$meta_keys = ( isset($_REQUEST["mta-ky"]) && is_array( $_REQUEST["mta-ky"] ) && !empty( $_REQUEST["mta-ky"] ) ) ?  array_map("sanitize_key", $_REQUEST["mta-ky"]) : $meta_keys;
$meta_vals = ( isset($_REQUEST["mta-vl"]) && is_array( $_REQUEST["mta-vl"] ) && !empty($_REQUEST["mta-vl"]) ) ? array_map("sanitize_text_field", $_REQUEST["mta-vl"]) : $meta_vals;
$compatible_type = array("CHAR", "NUMERIC", "BINARY", "DATE", "DATETIME", "DECIMAL", "SIGNED", "UNSIGNED", "TIME");
$meta_tp = ( isset($_REQUEST["mta-tp"]) && is_array( $_REQUEST["mta-tp"] )  && !empty($_REQUEST["mta-tp"]) ) ? array_map("sanitize_text_field", $_REQUEST["mta-tp"]) : $meta_tp;
$compatible_compares = array('=', "!=", 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=', 'NOT EXISTS', 'NOT REGEXP');
if ( isset($_REQUEST["mta-op"]) && !empty($_REQUEST["mta-op"]) ) {
     $meta_ops = $_REQUEST["mta-op"];
     foreach ($meta_ops as $index => $value){
          $meta_ops[$index] = ( !in_array($value, $compatible_compares) ) ? "=" : $value;
          $meta_tp[$index] = ( isset( $meta_tp[$index] ) && !in_array( $meta_tp[$index] , $compatible_type ) ) ? "CHAR" : $meta_tp[$index];
     }
}

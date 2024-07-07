<?php 
     /* 
      this files is used for data sanitization and GET variables from url query parameters for filtration
     */

     global $wp_roles;
     $roles = $wp_roles->get_names();
     $excl_ids = ( isset( $_REQUEST["excl-ids"] ) && ! empty( trim( $_REQUEST["excl-ids"] ) ) ) ? sanitize_text_field( $_REQUEST["excl-ids"] ) : false;
     if( $excl_ids ){
          $excl_ids = explode("-",$excl_ids);
          $excl_ids = array_map('sanitize_text_field', $excl_ids); // This sanitize bad values
          $excl_ids = array_filter($excl_ids); // This will remove empty value
     }
     $excl_ids = (  ! empty($excl_ids) ) ? $excl_ids : false;
     $filtr_sbmt = ( isset( $_REQUEST["fltr-sbmt"] ) && $_REQUEST["fltr-sbmt"] === "1" ) ? true : false;
     $usr_sort = ( isset( $_REQUEST["usr_srt"] ) ) ? sanitize_text_field( $_REQUEST["usr_srt"] )  : "";
     $ordr_by = ( isset( $_REQUEST["ordr-by"] ) ) ? sanitize_text_field( $_REQUEST["ordr-by"] ) : "";
     $excl_ids = ( false !== $excl_ids ) ? array_map('sanitize_text_field', $excl_ids) : array();
     $one_date =  ( isset( $_REQUEST["one-dt"] ) && ! empty( $_REQUEST["one-dt"] ) ) ? sanitize_text_field( date_format( date_create($_REQUEST["one-dt"]), "Y-m-d" ) ) : "";
     $exlude_roles = ( isset( $_REQUEST["rl-excld"] ) && !empty( $_REQUEST["rl-excld"] ) ) ? array_map( 'sanitize_text_field', $_REQUEST["rl-excld"]) : array();
     $cstm_dt =  ( isset( $_REQUEST["cstm-dt"] ) ) ? sanitize_text_field( $_REQUEST["cstm-dt"] )  :"";
     $from_date = ( isset( $_REQUEST["rg-f-dt"] ) ) ? date_format( date_create($_REQUEST["rg-f-dt"]), "Y-m-d" ):"";
     $to_date = ( isset( $_REQUEST["rg-t-dt"] ) ) ? date_format( date_create($_REQUEST["rg-t-dt"]), "Y-m-d" ):"";
     $multi_from_date = $multi_to_date = [];
     if( isset( $_REQUEST["mlt-f-dt"] ) && isset( $_REQUEST["mlt-t-dt"] ) ){
          $multi_from_date = ( !empty( $_REQUEST["mlt-f-dt"] ) ) ? $_REQUEST["mlt-f-dt"] : [];
          $multi_to_date = ( !empty( $_REQUEST["mlt-t-dt"] ) ) ? $_REQUEST["mlt-t-dt"] : [];
     }
     $relation = ( isset( $_REQUEST["rltn"]) && $_REQUEST["rltn"] == 'or' ) ? 'or': 'nd';
     $meta_keys = $meta_vals = $meta_ops = $meta_tp = false;
     if( isset( $_REQUEST["mta-ky"] ) && ! empty( $_REQUEST["mta-ky"] ) ) $meta_keys = array_map("sanitize_key", $_REQUEST["mta-ky"]);
     if( isset( $_REQUEST["mta-vl"] ) && ! empty( $_REQUEST["mta-vl"] ) ) $meta_vals = array_map("sanitize_text_field", $_REQUEST["mta-vl"]);
     if( isset( $_REQUEST["mta-tp"] ) && ! empty( $_REQUEST["mta-tp"] ) ) $meta_tp = array_map("sanitize_text_field", $_REQUEST["mta-tp"]);
     $compatible_compares = array( '=', "!=", 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=' ,'NOT EXISTS', 'NOT REGEXP' ); 
     $compatible_type = array( "CHAR","NUMERIC", "BINARY", "DATE", "DATETIME", "DECIMAL", "SIGNED", "UNSIGNED", "TIME" );
     if( isset( $_REQUEST["mta-op"] ) && ! empty( $_REQUEST["mta-op"] ) ) {
          $meta_ops = $_REQUEST["mta-op"];
          foreach( $meta_ops as $index => $value  ) if( ! in_array( $value, $compatible_compares ) ) $meta_ops[$index] = "=";
     }
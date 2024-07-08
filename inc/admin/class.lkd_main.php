<?php

/*Main class that is made for declaring the functions, actions, filters */
// Exit if accessed directly
if ( !defined('ABSPATH') ) exit;


class LKD_USERS_FILTER
{
    function __construct()
    {
        /********* list of action *********/
        add_action('admin_init', array($this, 'lkd_wp_usr_fltr_action__admin_init'));
        add_action('manage_users_extra_tablenav', array($this, 'lkd_wp_usr_filter_render_custom_filter_html'));

        /********* list of filters *********/
        add_filter('pre_get_users', array($this, 'filter_users_by_role'));
    }

    function lkd_wp_usr_fltr_action__admin_init()
    {
        wp_register_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js', LKD_WP_USR_FLTR_URL . 'assets/js/admin.js', array('jquery'), LKD_WP_USR_FLTR_VERSION, true);
        wp_register_style(LKD_WP_USR_FLTR_PREFIX . '_admin_css', LKD_WP_USR_FLTR_URL . 'assets/css/admin.css', array(), LKD_WP_USR_FLTR_VERSION);
        $lkd_local_array = array(
            'plugin_prefix' => LKD_WP_USR_FLTR_PREFIX
        );
        wp_localize_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js', 'lkd_usr_fltr_obj', $lkd_local_array);
        wp_enqueue_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js');
        wp_enqueue_style(LKD_WP_USR_FLTR_PREFIX . '_admin_css');
    }


    function lkd_wp_usr_filter_render_custom_filter_html()
    {
        include_once LKD_WP_USR_FLTR_DIR . '/inc/admin/html_outs_' . LKD_WP_USR_FLTR_PREFIX . '.php';
    }

    function filter_users_by_role($query)
    {
        include_once LKD_WP_USR_FLTR_DIR . '/inc/admin/query_filters.' . LKD_WP_USR_FLTR_PREFIX . '.php';
        return $query;
    }

    function object_to_array($data)
    {
        $result = [];
        if ((is_array($data) || is_object($data)) && !empty($data)) {
            foreach ($data as $key => $value)
                $result[$key] = (is_array($value) || is_object($value)) ? $this->object_to_array($value) : $value;
            return $result;
        } else {
            $result["empty_data"] = "empty_data";
        }
        return $data;
    }
    private function array_to_csv_download($query_array = array(array("maybe an issue!")), $filename = "export_users.csv", $delimiter = ",")
    {
        $f = fopen('php://memory', 'w');
        // loop over the input array
        foreach ($query_array as $line) {
            fputcsv($f, $line, $delimiter);
        }
        fseek($f, 0);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        fpassthru($f);
        fclose($f);
        exit;
    }
}
add_action('plugins_loaded', function () {
    if (current_user_can('administrator')) {
        $lkd_user_obj = new LKD_USERS_FILTER();
    }
});

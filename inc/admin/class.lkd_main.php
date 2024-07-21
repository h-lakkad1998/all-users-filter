<?php

/*Main class that is made for declaring the functions, actions, filters */
// Exit if accessed directly
if ( !defined('ABSPATH') ) exit;


class LKD_USERS_FILTER {
    function __construct(){
        /********* list of action *********/
        add_action('admin_init', array($this, 'lkd_wp_usr_fltr_action__admin_init'));
        add_action('manage_users_extra_tablenav', array($this, 'lkd_wp_usr_filter_render_custom_filter_html'));

        /********* list of filters *********/
        add_filter('pre_get_users', array($this, 'filter_users_by_lkd_requests'));
    }

    function lkd_wp_usr_fltr_action__admin_init(){
        wp_register_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js', LKD_WP_USR_FLTR_URL . 'assets/js/admin.js', array('jquery'), LKD_WP_USR_FLTR_VERSION, true);
        wp_register_style(LKD_WP_USR_FLTR_PREFIX . '_admin_css', LKD_WP_USR_FLTR_URL . 'assets/css/admin.css', array(), LKD_WP_USR_FLTR_VERSION);
        $lkd_local_array = array(
            'plugin_prefix' => LKD_WP_USR_FLTR_PREFIX
        );
        wp_localize_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js', 'lkd_usr_fltr_obj', $lkd_local_array);
        wp_enqueue_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js');
        wp_enqueue_style(LKD_WP_USR_FLTR_PREFIX . '_admin_css');
    }


    function lkd_wp_usr_filter_render_custom_filter_html(){
        include_once LKD_WP_USR_FLTR_DIR . '/inc/admin/html_outs_' . LKD_WP_USR_FLTR_PREFIX . '.php';
    }

    function filter_users_by_lkd_requests($query){
        include_once LKD_WP_USR_FLTR_DIR . '/inc/admin/query_filters.' . LKD_WP_USR_FLTR_PREFIX . '.php';
        return $query;
    }

    function object_to_array($data){
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
        if ( !function_exists('request_filesystem_credentials') ) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $creds = request_filesystem_credentials('', '', false, false, null);
        if ( !WP_Filesystem($creds) ) {
            $query_array = array( array("Could not access filesystem.") );
            return $query_array;
        }
        global $wp_filesystem;
        $temp_file = wp_tempnam();

        if (!$temp_file) {
            $query_array = array( array("Could not create temporary file.") );
            return $query_array;
        }

        // Prepare CSV data
        $csv_data = '';
        foreach ($query_array as $line) {
            $csv_data .= implode($delimiter, $line) . "\r\n";
        }

        // Write CSV data to the temporary file using WP_Filesystem
        if (!$wp_filesystem->put_contents($temp_file, $csv_data, FS_CHMOD_FILE)) {
            $query_array = array( array("Could not write to temporary file.") );
            return $query_array;
        }

        // Read the content of the temporary file using WP_Filesystem
        $csv_content = $wp_filesystem->get_contents($temp_file);

        if ($csv_content === false) {
            $query_array = array( array("Could not read temporary file.") );
            return $query_array;
        }

        // Clean up the temporary file
        $wp_filesystem->delete($temp_file);

        // Set headers and output the CSV content
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '";');
        header('Content-Length: ' . strlen($csv_content));

        // Output CSV content with proper escaping
        echo wp_kses_post($csv_content);

        exit;
    }
}
add_action('plugins_loaded', function () {
    if (current_user_can('administrator')) {
        $lkd_user_obj = new LKD_USERS_FILTER();
    }
});

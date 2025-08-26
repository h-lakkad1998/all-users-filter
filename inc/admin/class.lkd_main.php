<?php
/*Main class that is made for declaring the functions, actions, filters */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;


class LKD_USERS_FILTER
{
    function __construct()
    {
        /********* list of action *********/
        add_action('admin_enqueue_scripts', array($this, 'lkd_wp_usr_fltr_action__admin_init'));
        add_action('manage_users_extra_tablenav', array($this, 'lkd_wp_usr_filter_render_custom_filter_html'));

        /********* list of filters *********/
        add_filter('pre_get_users', array($this, 'filter_users_by_lkd_requests'));
    }

    function lkd_wp_usr_fltr_action__admin_init()
    {
        wp_register_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js', LKD_WP_USR_FLTR_URL . 'assets/js/admin.js', array('jquery'), LKD_WP_USR_FLTR_VERSION, true);
        wp_register_style(LKD_WP_USR_FLTR_PREFIX . '_admin_css', LKD_WP_USR_FLTR_URL . 'assets/css/admin.css', array(), LKD_WP_USR_FLTR_VERSION);
        $lkd_local_array = array(
            'plugin_prefix' => LKD_WP_USR_FLTR_PREFIX,
            'ajax_url'      => admin_url('admin-ajax.php'),
        );
        wp_localize_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js', 'lkd_usr_fltr_obj', $lkd_local_array);
        wp_enqueue_script(LKD_WP_USR_FLTR_PREFIX . '_admin_js');
        wp_enqueue_style(LKD_WP_USR_FLTR_PREFIX . '_admin_css');
    }


    function lkd_wp_usr_filter_render_custom_filter_html()
    {
        include_once LKD_WP_USR_FLTR_DIR . '/inc/admin/html_outs_' . LKD_WP_USR_FLTR_PREFIX . '.php';
    }

    function filter_users_by_lkd_requests($query)
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
}

add_action('admin_init', function () {
    $lkd_is_filter_allowed = apply_filters('lkd_wp_usr_filter_allowed', false);
    if (current_user_can('administrator') || $lkd_is_filter_allowed) {
        new LKD_USERS_FILTER();
    }
});

function lkd_sanitize_operator( $operator ) {
    $allowed_ops = [
        '=', '!=', '>', '>=', '<', '<=',
        'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
        'BETWEEN', 'NOT BETWEEN',
        'EXISTS', 'NOT EXISTS',
        'REGEXP', 'NOT REGEXP', 'RLIKE',
    ];
    return in_array( $operator, $allowed_ops, true ) ? $operator : '=';
}

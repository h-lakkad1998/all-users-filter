<?php

/**
 * Plugin Name: All Users Filter
 * Plugin URI: https://github.com/h-lakkad1998/all-users-filter
 * Description: This plugin helps the admin to filter the users with various ranges of filters.
 * Version: 1.0
 * Author: Hardik Lakkad/Patel
 * Author URI: https://www.linkedin.com/in/hardik-patel-lakkad-097b12147/
 * Text Domain: all-users-filter
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Developer: Hardik Patel
 * Developer E-Mail: hardiklakkad2@gmail.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Basic plugin definitions
 *
 */

if (!defined('LKD_WP_USR_FLTR_VERSION')) {
	define('LKD_WP_USR_FLTR_VERSION', '1.0');
}

if (!defined('LKD_WP_USR_FLTR_FILE')) {
	define('LKD_WP_USR_FLTR_FILE', __FILE__);
}

if (!defined('LKD_WP_USR_FLTR_DIR')) {
	define('LKD_WP_USR_FLTR_DIR', dirname(__FILE__));
}

if (!defined('LKD_WP_USR_FLTR_URL')) {
	define('LKD_WP_USR_FLTR_URL', plugin_dir_url(__FILE__));
}

if (!defined('LKD_WP_USR_FLTR_PREFIX')) {
	define('LKD_WP_USR_FLTR_PREFIX', 'lkd_wp_usr_filter');
}

/**
 * Initialize the main class
 * This class will be only available at user.php page 
 * 
 */
global $pagenow;
if (is_admin()) {
	if (!class_exists('LKD_USERS_FILTER') && $pagenow == "users.php") {
		require_once LKD_WP_USR_FLTR_DIR . '/inc/admin/class.lkd_main.php';
	}
	require_once LKD_WP_USR_FLTR_DIR . '/inc/admin/admin_ajax_handler.php';
}

<?php

/**
 * Plugin Name: WP Users Filter
 * Plugin URL: 
 * Description: This plugin helps the admin to filter the users with various ranges of filters.
 * Version: 1.0
 * Author: Hardik Lakkad/Hardik Patel
 * Author URI: https://in.linkedin.com/in/hardik-lakkad-097b12147
 * Developer: Hardik Patel
 * Developer E-Mail: hardiklakkad2@gmail.com
 * Text Domain: wp-users-filter
 * Domain Path: /languages
 *
 * Copyright: © 2009-2019 Hardik S Lakkad.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( !defined('ABSPATH') ) exit;

/**
 * Basic plugin definitions
 *
 * @package WP Users filter
 * @since 1.0
 *  LKD_WP_USR_FLTR will be prefix for every file, GLOBAL VARIABLE, Class
 */

if (!defined('LKD_WP_USR_FLTR_VERSION')) {
	define('LKD_WP_USR_FLTR_VERSION', '1.0'); // Version of plugin
}

if (!defined('LKD_WP_USR_TEXT_DOMAIN')) {
	define('LKD_WP_USR_TEXT_DOMAIN', 'wp-users-filter'); // Plugin text domain
}

if (!defined('LKD_WP_USR_FLTR_FILE')) {
	define('LKD_WP_USR_FLTR_FILE', __FILE__); // Plugin File
}

if (!defined('LKD_WP_USR_FLTR_DIR')) {
	define('LKD_WP_USR_FLTR_DIR', dirname(__FILE__)); // Plugin dir
}

if (!defined('LKD_WP_USR_FLTR_URL')) {
	define('LKD_WP_USR_FLTR_URL', plugin_dir_url(__FILE__)); // Plugin url
}

if (!defined('LKD_WP_USR_FLTR_PREFIX')) {
	define('LKD_WP_USR_FLTR_PREFIX', 'lkd_wp_usr_filter'); // Plugin prefix
}

/**
 * Initialize the main class
 * This class will be only available at user.php 
 * 
 */

global $pagenow;
if (is_admin() && $pagenow == "users.php") {
	if (!class_exists('LKD_USERS_FILTER')) {
		require_once LKD_WP_USR_FLTR_DIR . '/inc/admin/class.lkd_main.php';
	}
}

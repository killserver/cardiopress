<?php
/**
 * Plugin Name: Legion
 * Description: Логика из Cardinal Engine обмазанная Wordpress-ом :-)
 * Plugin URI:  https://github.com/killserver/cardinal/tree/trunk/
 * Author URI:  https://github.com/killserver/
 * Author:      killserver
 * Version:     0.5.1
*/

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die;
}

define('PLUGIN_NAME_VERSION', '0.5.1');

define("IS_CORE", true);
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php");

if(isset($_GET['debug'])) {
	ini_set("display_errors", "1");
	ini_set("error_reporting", "E_ALL");
	error_reporting(E_ALL);
	define("DEBUG_ACTIVATED", true);
} else {
	define("DEBUG_ACTIVATED", false);
}

if(!class_exists('CardinalUpdater')) {
	include_once(PATH_CORE.'CardinalUpdater.php');
}
$updater = new CardinalUpdater(__FILE__, "http://killer.pp.ua/wp/");

require_once(PATH_CORE."core.php");

add_action('init', 'initial_cardinal_engine');
function initial_cardinal_engine() {
	if(!defined("IS_CORE")) {
		define("IS_CORE", true);
	}
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php");
	require_once(PATH_CORE."wp-func.php");
}


add_action('admin_init', 'initial_plugins_cd');
function initial_plugins_cd() {
	global $updater;
	add_filter('pre_set_site_transient_update_plugins', array($updater, 'modify_transient'), 10, 3);
	add_filter('site_transient_update_plugins', array($updater, 'modify_transient'), 10, 3);
	add_filter('plugins_api', array($updater, 'plugin_popup'), 10, 3);
	add_filter('plugins_api_result', array($updater, 'plugin_add'), 10, 3);
	add_filter('upgrader_post_install', array($updater, 'after_install'), 10, 3);
	add_filter('all_plugins', array($updater, 'all_plugins'), 10, 3);
}
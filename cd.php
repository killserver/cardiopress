<?php
/**
 * Plugin Name: Cardinal Engine
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
$updater->initialize();

require_once(PATH_CORE."core.php");

add_action('init', 'initial_cardinal_engine');
function initial_cardinal_engine() {
	if(!defined("IS_CORE")) {
		define("IS_CORE", true);
	}
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php");
	require_once(PATH_CORE."wp-func.php");
}
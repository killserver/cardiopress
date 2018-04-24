<?php
/**
 * Plugin Name: Cardinal Engine
 * Description: Описание плагина желательно не очень длинное
 * Plugin URI:  https://github.com/killserver/cardinal/tree/trunk/
 * Author URI:  https://github.com/killserver/
 * Author:      killserver
 * Version:     1.0.0
*/

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die;
}

define('PLUGIN_NAME_VERSION', '1.0.0');


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

require_once(PATH_CORE."core.php");

add_action('init', 'initial_cardinal_engine');
function initial_cardinal_engine() {
	if(!defined("IS_CORE")) {
		define("IS_CORE", true);
	}
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php");
	require_once(PATH_CORE."wp-func.php");
}
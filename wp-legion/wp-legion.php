<?php
/**
 * Plugin Name: Legion
 * Description: Логика из Cardinal Engine обмазанная Wordpress-ом :-)
 * Plugin URI:  https://github.com/killserver/cardinal/tree/trunk/
 * Author URI:  https://github.com/killserver/
 * Author:      killserver
 * Version:     0.6.0
*/

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die;
}

define('LEGION_VERSION', '0.6.0');

define("IS_CORE", true);
if(file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php")) {
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php");
} else if(file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.default.php")) {
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.default.php");
}

$settings = get_option("legion");

function debugActivationLegion() {
	global $settings;
	if((isset($settings['legion_debug']) && $settings['legion_debug']=="1") || isset($_COOKIE['debug']) || isset($_GET['debug'])) {
		ini_set("display_errors", "1");
		ini_set("error_reporting", "E_ALL");
		error_reporting(E_ALL);
		if(!defined("DEBUG_ACTIVATED")) {
			define("DEBUG_ACTIVATED", true);
		}
	} else {
		if(!defined("DEBUG_ACTIVATED")) {
			define("DEBUG_ACTIVATED", false);
		}
	}
	if(defined("DEBUG")) {
		ini_set("display_errors", "1");
		ini_set("error_reporting", "E_ALL");
		error_reporting(E_ALL);
	}
}
debugActivationLegion();

if(!class_exists('CardinalUpdater')) {
	include_once(PATH_CORE.'CardinalUpdater.php');
}
$updater = new CardinalUpdater(__FILE__, "http://killer.pp.ua/wp/");

require_once(PATH_CORE."core.php");

add_action('init', 'initial_cardinal_engine', 20);
function initial_cardinal_engine() {
	if(!defined("IS_CORE")) {
		define("IS_CORE", true);
	}
	if(file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php")) {
		include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php");
	} else if(file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.default.php")) {
		include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.default.php");
	}
	require_once(PATH_CORE."wp-func.php");
	debugActivationLegion();
	add_filter('request', 'read_wp_request');
	add_action("admin_bar_menu", "wp_admin_bar_edit_menu2", 90);
}

function addAdminBarEdit($id, $type = "post") {
	global $wp_admin_bar;
	if(!empty($type) && ($post_type_object = get_post_type_object($type)) && current_user_can('edit_post', $id) && $post_type_object->show_in_admin_bar && $edit_post_link = get_edit_post_link($id)) {
		$wp_admin_bar->add_menu(array(
			'id' => 'edit',
			'title' => $post_type_object->labels->edit_item,
			'href' => $edit_post_link
		));
	}
}

function getArray(&$data) {
	foreach($data as $k => $v) {
		$data[$k] = getDatas($v);
	}
}

function getDatas($data) {
	if(is_numeric($data) || is_string($data)) {
		return $data;
	} else if(is_array($data)) {
		foreach($data as $k => $v) {
			$data[$k] = getDatas($v);
		}
		return $data;
	} else if(is_object($data)) {
		$data = (array) $data;
		foreach($data as $k => $v) {
			$data[$k] = getDatas($v);
		}
		return $data;
	}
	return $data;
}

function getById($id, $type = "post") {
	global $post, $wp_the_query;
	$my_posts = new WP_Query();
	$my_posts = $my_posts->query('post_type='.$type.'&post_id='.$id);
	$wp_the_query->queried_object = $post = $my_posts = current($my_posts);
	return (array) $my_posts;
}

function getByName($id, $type = "post") {
	global $post, $wp_the_query;
	$my_posts = new WP_Query();
	$my_posts = $my_posts->query('post_type='.$type.'&name='.$id);
	$wp_the_query->queried_object = $post = $my_posts = current($my_posts);
	return (array) $my_posts;
}

function getByTax($tax, $terms = array()) {
	global $post, $wp_the_query;
	if(!is_array($terms)) {
		$terms = array($terms);
	}
	$my_posts = new WP_Query();
	$my_posts = $my_posts->query(array(
		'post_type' => 'post',
		'numberposts' => -1,
		'tax_query' => array(
			array(
				'field' => 'slug',
				"taxonomy" => $tax,
				'terms' => $terms
			),
		),
	));
	$all = array();
	foreach($my_posts as $k => $v) {
		$all[$k] = (array) $v;
	}
	return $all;
}

function getAll($args) {
	$my_posts = new WP_Query();
	$all = array();
	$list = $my_posts->query($args);
	foreach($list as $k => $v) {
		$all[$k] = (array) $v;
	}
	return $all;
}

function getByCatName($name, $args = array(), $typeSearch = false) {
	if($typeSearch===false) {
		if(preg_match("#([0-9]+)#", $name)) {
			$typeSearch = "term_id";
		} else if(preg_match("#([a-zA-Z0-9]+)#", $name)) {
			$typeSearch = "slug";
		} else if(preg_match("#([a-zA-Z0-9а-яА-ЯёЁ]+)#", $name)) {
			$typeSearch = "name";
		}
	}
	$def = array(
		'post_type' => 'post',
		'tax_query' => array(
			array(
				'taxonomy' => 'category',
				'field' => $typeSearch,
				'terms' => $name,
			)
		)
	);
	$args = array_merge($def, $args);
	return getAll($args);
}

function getAllCategory(array $args = array()) {
global $catList;
	$def = array(
		'taxonomy' => 'category',
		"hide_empty" => 0,
	);
	$args = array_merge($def, $args);
	$key = md5(json_encode($array));
	if(is_null($catList)) {
		$catList = array();
	}
	if(!isset($catList[$key])) {
		$catList[$key] = get_categories($args);
	}
	return $catList[$key];
}

add_action('admin_init', 'initial_plugins_cd');
function initial_plugins_cd() {
	global $updater;
}

new CardinalSettings();
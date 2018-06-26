<?php
/**
 * Plugin Name: Menu Legion
 * Description: Данный плагин позволяет получить меню как массив, при помощи которого можно будет произвести свою настройку без помощи Walker-ов из WP. Внимание! Данный плагин может обновляться только при наличии плагина Legion!
 * Plugin URI:  https://github.com/killserver/cardinal/tree/trunk/
 * Author URI:  https://github.com/killserver/
 * Author:      killserver
 * Version:     0.0.1
*/

function loadTemplateMenu($data) {
	$data = (array) $data;
	$template = array(
		"id" => "object_id",
		"class" => "classes",
		"link" => "url",
		"target" => "target",
		"title" => "title",
		"type" => "type",
		"parent" => "menu_item_parent",
	);
	foreach($template as $k => $v) {
		if($k==="class") {
			$template[$k] = "menu-item menu-item-type-".$data['type']." menu-item-object-".$data['object']." menu-item-".$data['ID'].implode(" ", $data[$v]);
		} else if(isset($data[$v])) {
			$template[$k] = $data[$v];
		} else {
			$template[$k] = "";
		}
	}
	return $template;
}

function loadMenuLegion($name) {
	$menu = wp_get_nav_menu_object($name);
	$menu = wp_get_nav_menu_items($menu->term_id, array('update_post_term_cache' => false));
	$sorted_menu_items = $menu_items_with_children = array();
	foreach((array) $menu as $menu_item) {
		$sorted_menu_items[$menu_item->menu_order][$menu_item->ID] = $menu_item;
	}
	$arr = array_values($sorted_menu_items);
	$newArr = array();
	foreach($arr as $v) {
		$val = array_values($v);
		for($i=0;$i<sizeof($val);$i++) {
			$newArr[] = loadTemplateMenu($val[$i]);
		}
	}
	return $newArr;
}

function menuLegionSorted($newArr) {
	$arr = array();
	for($i=0;$i<sizeof($newArr);$i++) {
		if(isset($arr[$newArr[$i]['parent']])) {
			$arr[$newArr[$i]['parent']]['children'][] = $newArr[$i];
		} else {
			$arr[$newArr[$i]['id']] = $newArr[$i];
		}
	}
	$arr = array_values($arr);
	return $arr;
}
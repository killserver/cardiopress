<?php
if(!defined("IS_CORE")) {
	echo "403 ERROR";
	die();
}

class cardinal {
	
	function get_template_part($slug, $name = "", $type = "tpl") {
		do_action("get_template_part_".$slug, $slug, $name);
		$templates = array();
		$name = (string) $name;
		if($name!=="") {
			$templates[] = $slug."-".$name.".".$type;
		}
		$templates[] = $slug.".".$tpl;
		self::locate_template($templates, true, false);
	}

	function locate_template($template_names, $load = false, $require_once = true) {
		$located = '';
		foreach((array) $template_names as $template_name) {
			if(!$template_name) {
				continue;
			}
			$style = get_stylesheet_directory();
			if(file_exists($style.DS.$template_name)) {
				$located = $style.DS.$template_name;
				break;
			} elseif(file_exists(PATH_SKINS.$template_name)) {
				$located = PATH_SKINS.$template_name;
				break;
			} elseif(file_exists(ABSPATH.WPINC.DS.'theme-compat'.DS.$template_name)) {
				$located = ABSPATH.WPINC.DS.'theme-compat'.DS.$template_name;
				break;
			}
		}
		if($load && $located != '') {
			self::load_template($located, $require_once);
		}
		return $located;
	}
	
}
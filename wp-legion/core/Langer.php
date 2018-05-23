<?php

$currentLang = false;
$linkNowCardinal = $request_uri = "";
class Langer {

	private static $langSupport = array();
	private static $langHelper = array();
	private static $langSupportDelimer = "";
	private static $langSupportMain = "";
	private static $home_url = "";

	public static function setDelimer($delimer) {
		self::$langSupportDelimer = $delimer;
	}

	public static function setSupport(array $support) {
		self::$langSupport = $support;
	}

	public static function setMain($lang) {
		self::$langSupportMain = $lang;
	}

	public static function getTranslate($content) {
		global $q_config;
		return qtranxf_use($q_config['language'], $content, true, false);
	}

	public static function init() {
		global $q_config;
		if(isset($q_config) && is_array($q_config)) {
			return;
		}
		add_filter('init', 'Langer::parseLang');
		add_filter('wp', 'Langer::setQueryVar');
		add_action('query_vars', 'Langer::query_vars');
		add_action('parse_request', 'Langer::request');
		add_action("page_link", "Langer::addLink");
		add_action("attachment_link", "Langer::addLink");
		add_action("post_link", "Langer::addLink");
		add_action("post_type_link", "Langer::addLink");
		add_action("wp_nav_menu_objects", "Langer::menuItems");
	}

	public static function parseLang() {
		global $currentLang, $linkNowCardinal, $request_uri;
		self::$home_url = get_home_url();
		preg_match("#^/(".implode("|", self::$langSupport).")(.*?)$#", $_SERVER['REQUEST_URI'], $tt);
		$currentLang = (isset($tt[1]) ? $tt[1] : self::$langSupportMain);
		$request_uri = get_home_url().$_SERVER['REQUEST_URI'];
		(isset($tt[2]) ? $_SERVER['REQUEST_URI'] = "/".$tt[2] : false);
		$linkNowCardinal = $_SERVER['REQUEST_URI'];
	}

	public static function setQueryVar() {
		global $currentLang, $wp;
		$wp->set_query_var("lang", $currentLang);
	}

	public static function query_vars($arr) {
		$arr[] = "lang";
		return $arr;
	}

	public static function request($arr) {
		global $currentLang;
		//$GLOBALS['text_direction'] = $this->curlang->is_rtl ? 'rtl' : 'ltr';
		$arr->extra_query_vars['lang'] = $currentLang;
		$arr->query_vars['lang'] = $currentLang;
		return $arr;
	}

	public static function addLink($link) {
		$lang = self::getCurrentLang();
		$link = str_replace(self::$home_url, self::$home_url.($lang==self::$langSupportMain ? "" : "/".$lang), $link);
		return $link;
	}

	public static function menuItems($menu) {
		$menus = array_keys($menu);
		for($i=0;$i<sizeof($menus);$i++) {
			$menu[$menus[$i]]->url = self::addLink($menu[$menus[$i]]->url);
		}
		return $menu;
	}

	private static function mappingLangLink($elem) {
		return self::$home_url."/".($elem==self::$langSupportMain ? "" : $elem."/");
	}

	public static function getLinkWithoutLang($link) {
		$langs = array_map("self::mappingLangLink", self::$langSupport);
		$link = self::$home_url."/".str_replace($langs, "", $link);
		return $link;
	}

	public static function getRelLink($link) {
		$langs = array_map("self::mappingLangLink", self::$langSupport);
		return str_replace($langs, "", $link);
	}

	public static function getLangScreen($lang) {
		global $wp, $request_uri;
		return self::addLangLink($request_uri, $lang);
	}

	public static function addLangLink($link, $lang) {
		$langs = array_map("self::mappingLangLink", self::$langSupport);
		$link = self::$home_url.($lang==self::$langSupportMain ? "" : "/".$lang)."/".str_replace($langs, "", $link);
		return $link;
	}

	public static function getCurrentPage() {
		global $linkNowCardinal;
		return $linkNowCardinal;
	}

	public static function getCurrentLang() {
		global $currentLang;
		return $currentLang;
	}

	public static function getSupport() {
		return self::$langSupport;
	}

	public static function excludeMainLang() {
		$support = array();
		foreach(self::$langSupport as $v) {
			$support[$v] = $v;
		}
		if(isset($support[self::$langSupportMain])) {
			unset($support[self::$langSupportMain]);
		}
		return array_values($support);
	}

	public static function getLangData($arr, $langer = false) {
		$isMainPage = false;
		if($langer===false || $langer==self::$langSupportMain) {
			$isMainPage = true;
		}
		$support = self::excludeMainLang();
		$lang = array();
		foreach($arr as $k => $v) {
			if($isMainPage) {
				$ret = true;
				for($i=0;$i<sizeof($support);$i++) {
					if(substr($k, -strlen($support[$i]))==$support[$i]) {
						$ret = false;
						break;
					}
				}
				if($ret) {
					$lang[$k] = $v;
				}
			} else if(!$isMainPage) {
				$ret = true;
				for($i=0;$i<sizeof($support);$i++) {
					if(substr($k, -strlen($support[$i]))==$langer) {
						$ret = false;
						break;
					}
				}
				if($ret) {
					$lang[$k] = $v;
				}
			}
		}
		return $lang;
	}//$cuts = array_map("getLangHelper", $support);

	public static function getLangHelper($v) {
		return self::$langSupportDelimer.$v;
	}

	public static function getCleanLang($arr) {
		$newArr = array();
		$lang1 = self::excludeMainLang();
		$newArr = array();
		$lang2 = array_map("self::getLangHelper", $lang1);
		foreach($arr as $k => $v) {
			for($i=0;$i<sizeof($lang2);$i++) {
				if(substr($k, -strlen($lang2[$i]))==$lang2[$i]) {
					$k = substr($k, 0, strrpos($k, $lang2[$i]));
				} else if(substr($k, -strlen($lang1[$i]))==$lang1[$i]) {
					$k = substr($k, 0, strrpos($k, $lang1[$i]));
				}
			}
			if(is_array($v)) {
				$v = self::getCleanLang($v);
			}
			$newArr[$k] = $v;
		}
		return $newArr;
	}

	public static function loadLang($lang, array $arr) {
		if(!isset(self::$langHelper[$lang])) {
			self::$langHelper[$lang] = array();
		}
		self::$langHelper[$lang] = array_merge(self::$langHelper[$lang], $arr);
	}

	public static function getLang($str) {
		$lang = self::getCurrentLang();
		if(isset(self::$langHelper[$lang]) && isset(self::$langHelper[$lang][$str])) {
			return self::$langHelper[$lang][$str];
		}
		return $str;
	}

}
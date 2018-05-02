<?php
if(!defined("IS_CORE")) {
	echo "403 Error";
	die();
}

class templates {

	private static $template = "";
	private static $typeFile = ".tpl";
	public static $blocks = array();

	public static function output_buffer_contents($fn, $args = array()) {
		ob_start();
		call_user_func_array($fn, $args);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	private static function config($arr) {
		if($arr[1]=="default_http_host") {
			return home_url('/');
		} else if($arr[1]=="default_http_local") {
			$home = home_url('/');
			$host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : getenv("HTTP_HOST"));
			$home = str_replace(array('www.', 'http://', 'https://', $host), '', $home);
			return $home;
		} else {
			return get_option($arr[1], $arr[0]);
		}
	}

	private static function menu($arr) {
		$params = array();
		if(isset($arr[3]) && strpos($arr[3], ";")!==false) {
			$r = explode(";", $arr[3]);
			for($i=0;$i<sizeof($r);$i++) {
				$exp = explode("=-=", $r[$i]);
				if(isset($exp[1])) {
					$params[$exp[0]] = $exp[1];
				} else {
					$params[] = $exp[0];
				}
			}
		} else if(isset($arr[3])) {
			$params['class'] = $arr[3];
		} else {
			$params['class'] = "";
		}
		$walker = false;
		if(isset($params['walker'])) {
			$fn = $params['walker'];
			$walker = new $fn($params['class'], true);
			unset($params['walker']);
		}
		if($walker===false) {
			$default = array('menu' => $arr[1], 'menu_class' => $params['class'], 'echo' => false);
		} else {
			$default = array('menu' => $arr[1], 'menu_class' => $params['class'], 'echo' => false, "walker" => $walker);
		}
		$arr = array_merge($default, $params);
		return wp_nav_menu($arr);
	}

	private static function foreachs($arr) {
		$nameData = $arr[1];
		$start = 1;
		$step = 1;
		if(strpos($nameData, "step=")!==false) {
			preg_match("#step=([0-9\.,]+)#", $nameData, $exp);
			$nameData = str_replace($exp[0], "", $nameData);
			$step = $exp[1];
		}
		if(strpos($nameData, "start=")!==false) {
			preg_match("#start=([0-9\.,]+)#", $nameData, $exp);
			$nameData = str_replace($exp[0], "", $nameData);
			$start = $exp[1];
		}
		$nameData = trim($nameData);
		if(!isset(self::$blocks[$nameData])) {
			return "";
		}
		$tpl = $arr[2];
		$data = self::$blocks[$nameData];
		$ret = "";
		$keys = array_keys($data);
		for($i=0;$i<sizeof($keys);$i++) {
			$tmp = $tpl;
			foreach($data[$keys[$i]] as $k => $v) {
				$tmp = self::replaceForeach($k, $v, $tmp, $start, $nameData);
			}
			$tmp = preg_replace_callback('#\[foreachif (.*?)\]([\s\S]*?)\[/foreachif \\1\]#i', ("templates::is"), $tmp);
			$ret .= $tmp;
			$start += $step;
		}
		return $ret;
	}

	private static function replaceForeach($k, $v, $tmp, $start, $nameData) {
		if(is_array($v) || is_object($v)) {
			foreach($v as $k => $v) {
				$tmp = self::replaceForeach($k, $v, $tmp, $nameData.".".$k);
			}
		} else {
			$tmp = str_replace('{'.$nameData.'.$id}', $start, $tmp);
			$tmp = str_replace('{$id}', $start, $tmp);
			$tmp = str_replace("{".$nameData.".".$k."}", $v, $tmp);
			$tmp = str_replace("{".$k."}", $v, $tmp);
		}
		return $tmp;
	}

	public static function assign_var($name, $val, $block = "", $id = "") {
		self::assign_vars(array($name."" => $val), $block, $id);
	}

	public static function assign_vars($arr, $name = "", $id = "") {
		$gen = false;
		if($name!=="" && $id==="") {
			$id = wp_generate_uuid4().microtime(true);
			$gen = true;
		}
		if($id!=="" && (is_array($arr) || is_object($arr))) {
			if(!isset(self::$blocks[$name])) {
				self::$blocks[$name] = array();
			}
			if(!isset(self::$blocks[$name][$id])) {
				self::$blocks[$name][$id] = array();
			}
			foreach($arr as $k => $v) {
				if(empty($v)) {
					$v = "";
					self::$blocks[$name][$id]["IS_".$k] = "false";
				} else {
					self::$blocks[$name][$id]["IS_".$k] = "true";
				}
				self::$blocks[$name][$id][$k] = $v;
			}
		} else if(is_array($arr) || is_object($arr)) {
			foreach($arr as $k => $v) {
				if(empty($v)) {
					$v = "";
					self::$blocks["IS_".$k] = "false";
				} else {
					self::$blocks["IS_".$k] = "true";
				}
				self::$blocks[$k] = $v;
			}
		}
	}

	public static function completed_assign_vars($tpl = "") {
		if(empty($tpl)) {
			return "";
		}
		if(!file_exists(PATH_SKINS."tmp".DS.$tpl.self::$typeFile)) {
			return "";
		} else {
			$tpl = file_get_contents(PATH_SKINS."tmp".DS.$tpl.self::$typeFile);
		}
		$tpl = str_replace("{THEME}", esc_url(get_template_directory_uri())."/tmp", $tpl);
		$tpl = self::compile($tpl);
		return $tpl;
	}

	private static function includer($arr) {
		if($arr[1]=="content") {
			return self::includer_content($arr);
		} else if($arr[1]=="templates") {
			return self::includer_templates($arr);
		}
	}

	private static function includer_templates($arr) {
		if(self::exists($arr[2])) {
			return file_get_contents(self::path($arr[2]));
		} else {
			return $arr[0];
		}
	}

	private static function includer_content($arr) {
		$path = self::path();
		if(file_exists($path.$arr[2])) {
			return file_get_contents($path.$arr[2]);
		} else {
			return $arr[0];
		}
	}

	final private static function is($array, $elseif=false) {
		$else=false;
		$good = true;
		$ret = (isset($array[3]) ? (!$elseif ? $array[3] : false) : "");
		if(isset($array[1]) && strpos($array[1], "||") !== false) {
			$data = explode("||", $array[1]);
			$array[1] = $data[0];
			$else = self::is(array($array, $data[1], $array[2], $ret), true);
		}
		if(isset($array[1]) && strpos($array[1], "&&") !== false) {
			$data = explode("&&", $array[1]);
			$array[1] = $data[0];
			$good = self::is(array($array, $data[1], $array[2], $ret), true);
		}
		if(!$elseif) {
			$data = $array[2];
		} else {
			$data = true;
		}
		$e = array();
		if(strpos($array[1], "!ajax") !== false) {
			$type = "not_ajax";
		} elseif(strpos($array[1], "ajax") !== false) {
			$type = "ajax";
		} elseif(strpos($array[1], "<>") !== false) {
			$type = "not";
			$e = explode("<>", $array[1]);
		} elseif(strpos($array[1], ">=") !== false) {
			$type = "biga";
			$e = explode(">=", $array[1]);
		} elseif(strpos($array[1], "<=") !== false) {
			$type = "smalla";
			$e = explode("<=", $array[1]);
		} elseif(strpos($array[1], "<") !== false) {
			$type = "small";
			$e = explode("<", $array[1]);
		} elseif(strpos($array[1], ">") !== false) {
			$type = "big";
			$e = explode(">", $array[1]);
		} elseif(strpos($array[1], "!=") !== false) {
			$type = "not";
			$e = explode("!=", $array[1]);
		} elseif(strpos($array[1], "=") !== false) {
			$type = "yes";
			$t = str_replace("==", "=", $array[1]);
			$e = explode("=", $t);
		}
		if(!isset($type)) return false;
		if($type == "UL") {
			$e = str_replace("\"", "", $e);
			if(($e=="true" || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "nce") {
			$e = str_replace("\"", "", $e);
			if((!class_exists($e) || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "ce") {
			$e = str_replace("\"", "", $e);
			if((class_exists($e) || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "not") {
			$t = str_replace("\"", "", $e[1]);
			if(($e[0] != $e[1] || $e[0] != $t || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "smalla") {
			if(($e[0] <= $e[1] || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "biga") {
			if(($e[0] >= $e[1] || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "small") {
			if(($e[0] < $e[1] || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "big") {
			if(($e[0] > $e[1] || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "yes") {
			$t = str_replace("\"", "", $e[1]);
			if(($e[0] == $e[1] || $e[0] == $t || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "empty") {
			$e = str_replace("\"", "", $e);
			if((empty($e) || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "not_empty") {
			$e = str_replace("\"", "", $e);
			if((!empty($e) || isset(self::$blocks[$e]) || $else) && $good) {
				unset($e);
				unset($type);
				return $data;
			} else {
				unset($e);
				unset($type);
				return $ret;
			}
		} elseif($type == "ajax") {
			if(getenv('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' && !isset($_GET['jajax'])) {
				unset($type);
				return $data;
			} else {
				return "";
			}
		} elseif($type == "not_ajax") {
			if(getenv('HTTP_X_REQUESTED_WITH') != 'XMLHttpRequest') {
				unset($type);
				return $data;
			} else {
				return "";
			}
		}
		return "";
	}

	private static function system($arr) {
		if($arr[1]=="rand") {
			return rand();
		} else if($arr[1]=="time") {
			return time();
		}
	}

	private static function getParam($arr) {
		$g = Route::param($arr[1]);
		return (is_array($g) ? $arr[0] : $g);
	}

	private static function router($arr) {
		if(isset($arr[3])) {
			$params = array();
			$p = explode(";", $arr[3]);
			for($i=0;$i<sizeof($p);$i++) {
				$exp = explode("=", $p[$i]);
				if(isset($exp[1])) {
					$params[$exp[0]] = $exp[1];
				} else {
					$params[$exp[0]] = true;
				}
			}
			$g = Route::Find($arr[1], $params);
		} else {
			$g = Route::Find($arr[1]);
		}
		if($g!==false) {
			return $g;
		} else {
			return $arr[0];
		}
	}

	private static function compile($tpl) {
		$tpl = preg_replace_callback("#\{S_(.+?)\}#i", "self::system", $tpl);
		$tpl = preg_replace_callback("#\{C_(.+?)\}#i", "self::config", $tpl);
		$tpl = preg_replace_callback("#\[FIELD=\[(.+?)\]\[(.+?)\]\](.+?)\[/FIELD\]#is", "self::fields", $tpl);
		$tpl = preg_replace_callback("#\{field=[\"'](.+?)[\"'](|,([0-9]))\}#i", "self::getField", $tpl);
		$tpl = preg_replace_callback("#\{MENU_\[(.+?)\](|\[(.+?)\])\}#i", "self::menu", $tpl);
		$tpl = preg_replace_callback("#\[foreach block=(.+?)\](.+?)\[/foreach\]#is", "self::foreachs", $tpl);
		$tpl = preg_replace_callback("#\{permalink(|=\"(.+?)\")\}#i", "self::getPermalink", $tpl);
		$tpl = preg_replace_callback("#\{the_title(|=\"(.+?)\")\}#i", "self::getTitle", $tpl);
		$tpl = preg_replace_callback("#\{esc_html=\"(.*?)\"\}#i", "self::escHtml", $tpl);
		$tpl = preg_replace_callback("#\{kses_post=\"(.*?)\"\}#i", "self::ksesPost", $tpl);
		$tpl = preg_replace_callback("#\{title_attribute(|=\"(.+?)\")\}#i", "self::title_attribute", $tpl);
		$tpl = preg_replace_callback("#\{META_\[(.+?)\](|\[(.+?)\])\}#i", "self::getMeta", $tpl);
		$tpl = preg_replace_callback("#\{include (.+?)=[\"'](.+?)[\"']\}#i", "self::includer", $tpl);
		$tpl = preg_replace_callback("#\{FN=[\"'](.+?)[\"'](|\,[\"'](.+?)[\"'])\}#i", "self::userFN", $tpl);
		$tpl = preg_replace_callback("#\{R_\[(.+?)\](|\[(.+?)\])\}#", "self::router", $tpl);
		$tpl = preg_replace_callback("#\{RP\[(.+?)\]\}#", "self::getParam", $tpl);
		$blocks = self::$blocks;
		$tpl = self::replacer($tpl, $blocks);
		while(preg_match('~\[if (.+?)\]([^[]*)\[/if \\1\]~iU', $tpl)) {
			$tpl = preg_replace_callback('~\[if (.+?)\]([^[]*)\[/if \\1\]~iU', ("templates::is"), $tpl);
		}
		return $tpl;
	}

	private static function replacer($tpl, $blocks, $arr = false, $head = "") {
		foreach($blocks as $k => $v) {
			if(is_array($v) || is_object($v)) {
				$tpl = self::replacer($tpl, $v, true, $k.".");
			} else if($arr===false && !is_string($v) && !is_numeric($v)) {continue;}
			else
			{
				$tpl = str_replace("{".$head.$k."}", $v, $tpl);
			}
		}
		return $tpl;
	}

	private static function userFN($arr) {
		if(isset($arr[3])) {
			$params = array();
			$arr[3] = ltrim($arr[3], "=");
			$arr[3] = explode(" ", $arr[3]);
			for($i=0;$i<sizeof($arr[3]);$i++) {
				if(strpos($arr[3][$i], "=")!==false) {
					$exp = explode("=", $arr[3][$i]);
					$params[$exp[0]] = $exp[1];
				} else {
					$params[] = $arr[3][$i];
				}
			}
			return call_user_func_array($arr[1], $params);
		} else {
			return call_user_func_array($arr[1], array());
		}
	}

	private static function getField($arr) {
		if(isset($arr[3])) {
			$t = get_fields($arr[3]);
		} else {
			$t = get_fields();
		}
		return (isset($t[$arr[1]]) ? $t[$arr[1]] : $arr[0]);
	}

	private static function getMeta($arr) {
		if(isset($arr[3])) {
			return get_post_meta($arr[1], $arr[2], true);
		} else {
			global $post;
			if(!isset($post->ID)) {
				trigger_error("ИД новости не задано для ".$arr[0]);
				return $arr[0];
			}
			return get_post_meta($post->ID, $arr[1], true);
		}
	}

	private static function title_attribute($arr) {
		$params = array();
		$arr[2] = ltrim($arr[2], "=");
		$arr[2] = explode(" ", $arr[2]);
		for($i=0;$i<sizeof($arr[2]);$i++) {
			if(strpos($arr[2][$i], "=")!==false) {
				$exp = explode("=", $arr[2][$i]);
				$params[$exp[0]] = $exp[1];
			} else {
				$params[] = $arr[2][$i];
			}
		}
		return call_user_func_array("self::output_buffer_contents", array("the_title_attribute", $params));
	}

	private static function getPermalink($arr) {
		$params = array();
		$arr[2] = ltrim($arr[2], "=");
		$arr[2] = explode(" ", $arr[2]);
		for($i=0;$i<sizeof($arr[2]);$i++) {
			if(strpos($arr[2][$i], "=")!==false) {
				$exp = explode("=", $arr[2][$i]);
				$params[$exp[0]] = $exp[1];
			} else {
				$params[] = $arr[2][$i];
			}
		}
		return get_permalink($params);
	}

	private static function escHtml($arr) {
		return esc_html($arr[1]);
	}

	private static function ksesPost($arr) {
		return wp_kses_post($arr[1]);
	}

	private static function getTitle($arr) {
		$params = array();
		$arr[2] = ltrim($arr[2], "=");
		$arr[2] = explode(" ", $arr[2]);
		for($i=0;$i<sizeof($arr[2]);$i++) {
			if(strpos($arr[2][$i], "=")!==false) {
				$exp = explode("=", $arr[2][$i]);
				$params[$exp[0]] = $exp[1];
			} else {
				$params[] = $arr[2][$i];
			}
		}
		return call_user_func_array("get_the_title", $params);
	}

	private static function fields($arr) {
		$nameData = $arr[2];
		if(strpos($nameData, "step=")!==false) {
			preg_match("#step=([0-9\.,]+)#", $nameData, $exp);
			$nameData = str_replace($exp[0], "", $nameData);
			$step = $exp[1];
		}
		if(strpos($nameData, "start=")!==false) {
			preg_match("#start=([0-9\.,]+)#", $nameData, $exp);
			$nameData = str_replace($exp[0], "", $nameData);
			$start = $exp[1];
		}
		$nameData = trim($nameData);
		$t = get_field($nameData, $arr[1]);
		if(is_array($t)) {
			for($i=0;$i<sizeof($t);$i++) {
				templates::assign_vars($t[$i], $nameData, "id".$i);
			}
		}
		$tpl = preg_replace("#\{(.+?)\}#i", "{".$nameData.".$1}", $arr[3]);
		//$tpl = self::foreachs(array("", $nameData.(isset($step) ? ' step='.$step : "").(isset($start) ? ' start='.$start : ""), $tpl));
		$tpl = '[foreach block='.$nameData.(isset($step) ? ' step='.$step : "").(isset($start) ? ' start='.$start : "").']'.$tpl.'[/foreach]'.PHP_EOL.PHP_EOL;
		return $tpl;
	}

	private static function ErrorTemplate($tpl) {
		echo "Error: template: <b>".$tpl."</b> is not found";
		die();
	}

	public static function path($tpl = "") {
		return ($tpl==="" ? PATH_SKINS."tmp".DS : PATH_SKINS."tmp".DS.$tpl.self::$typeFile);
	}

	public static function exists($tpl) {
		return file_exists(PATH_SKINS."tmp".DS.$tpl.self::$typeFile);
	}

	public static function display($tmp = "") {
		//global $template;vdump(dirname($template));die(); /// ПУТЬ К ФАЙЛАМ АКТИВНОГО ШАБЛОНА
		if(!file_exists(PATH_SKINS."tmp".DS."main".self::$typeFile)) {
			return "";
		} else {
			$tpl = file_get_contents(PATH_SKINS."tmp".DS."main".self::$typeFile);
		}
		if($tmp==="" || $tmp==="index") {
			if(file_exists(PATH_SKINS."tmp".DS."index".self::$typeFile)) {
				$tmp = file_get_contents(PATH_SKINS."tmp".DS."index".self::$typeFile);
			}
		} else {
			if(!file_exists(PATH_SKINS."tmp".DS.$tmp.self::$typeFile)) {
				debug_print_backtrace();
				self::ErrorTemplate(PATH_SKINS."tmp".DS.$tmp.self::$typeFile);
			}
			$tmp = file_get_contents(PATH_SKINS."tmp".DS.$tmp.self::$typeFile);
		}
		$tpl = str_replace("{content}", $tmp, $tpl);
		if(!file_exists(PATH_SKINS."cache".DS."supportMenu.lock")) {
			preg_match_all("#\{MENU_\[(.+?)\](\[(.+?)\])\}#i", $tpl, $list);
			$php = "";
			for($i=0;$i<sizeof($list[1]);$i++) {
				$php .= "'menu".$i."' => __('".$list[1][$i]."'),\n";
			}
			if(!is_writeable(PATH_SKINS)) {
				@chmod(PATH_SKINS, 0777);
			}
			if(file_exists(PATH_SKINS."menuSupport.php")) {
				@unlink(PATH_SKINS."menuSupport.php");
			}
			file_put_contents(PATH_SKINS."menuSupport.php", '<?php'.PHP_EOL.'if(!defined("IS_CORE")) { echo "403 Error"; die(); }'.PHP_EOL.PHP_EOL.'register_nav_menus(array('.PHP_EOL.$php.PHP_EOL.'));');
			if(!file_exists(PATH_SKINS."cache".DS)) {
				@mkdir(PATH_SKINS."cache".DS, 0777);
			}
			if(!is_writeable(PATH_SKINS."cache".DS)) {
				@chmod(PATH_SKINS."cache".DS, 0777);
			}
			file_put_contents(PATH_SKINS."cache".DS."supportMenu.lock", "");
		}
		$tpl = self::compile($tpl);
		$tpl = str_replace("{THEME}", esc_url(get_template_directory_uri())."/tmp", $tpl);
		$tpl = str_replace("{headers}", call_user_func_array("self::output_buffer_contents", array("wp_head")), $tpl);
		$tpl = str_replace("{body_class}", call_user_func_array("self::output_buffer_contents", array("body_class")), $tpl);
		$tpl = str_replace("</body>", call_user_func_array("self::output_buffer_contents", array("wp_footer"))."<style type=\"text/css\">#wpadminbar{background:#ac1f1f;-webkit-box-shadow:0em 0.1em 0.7em -0.25em #000;-moz-box-shadow:0em 0.1em 0.7em -0.25em #000;box-shadow:0em 0.1em 0.7em -0.25em #000;}#wpadminbar .ab-top-menu>li.hover>.ab-item, #wpadminbar.nojq .quicklinks .ab-top-menu>li>.ab-item:focus, #wpadminbar:not(.mobile) .ab-top-menu>li:hover>.ab-item, #wpadminbar:not(.mobile) .ab-top-menu>li>.ab-item:focus{background:#7d1515;}#wpadminbar:not(.mobile)>#wp-toolbar a:focus span.ab-label, #wpadminbar:not(.mobile)>#wp-toolbar li:hover span.ab-label, #wpadminbar>#wp-toolbar li.hover span.ab-label,#wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover>a, #wpadminbar .quicklinks .menupop ul li a:focus, #wpadminbar .quicklinks .menupop ul li a:focus strong, #wpadminbar .quicklinks .menupop ul li a:hover, #wpadminbar .quicklinks .menupop ul li a:hover strong, #wpadminbar .quicklinks .menupop.hover ul li a:focus, #wpadminbar .quicklinks .menupop.hover ul li a:hover, #wpadminbar .quicklinks .menupop.hover ul li div[tabindex]:focus, #wpadminbar .quicklinks .menupop.hover ul li div[tabindex]:hover, #wpadminbar li #adminbarsearch.adminbar-focused:before, #wpadminbar li .ab-item:focus .ab-icon:before, #wpadminbar li .ab-item:focus:before, #wpadminbar li a:focus .ab-icon:before, #wpadminbar li.hover .ab-icon:before, #wpadminbar li.hover .ab-item:before, #wpadminbar li:hover #adminbarsearch:before, #wpadminbar li:hover .ab-icon:before, #wpadminbar li:hover .ab-item:before, #wpadminbar.nojs .quicklinks .menupop:hover ul li a:focus, #wpadminbar.nojs .quicklinks .menupop:hover ul li a:hover{color:#fff;}#wpadminbar .menupop .ab-sub-wrapper, #wpadminbar .shortlink-input{background:#ac1f1f;}#wpadminbar *:before {font:400 20px/1 dashicons;}</style></body>", $tpl);
		echo $tpl;
	}

}

?>
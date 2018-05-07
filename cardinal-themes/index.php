<?php
global $tplSite, $route, $call;
if($route!==false) {
	if(is_callable($call)) {
		call_user_func_array($call, array());
		die();
	}
}
function cardinalAutoload($class) {
    if(stripos(ini_get('include_path'), $class)!==false && class_exists($class, false)) {
        return false;
    }
    if(file_exists(PATH_SKINS."globals".DS.$class.".php")) {
        include_once(PATH_SKINS."autoloader".DS.$class.".php");
    }
}
if(version_compare(PHP_VERSION, '5.1.2', '>=')) {
	if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
		spl_autoload_register('cardinalAutoload', true, true);
	} else {
		spl_autoload_register('cardinalAutoload');
	}
} else {
	function __autoload($class) {
		cardinalAutoload($class);
	}
}
if($tplSite=="front_page" || $tplSite=="index") {
	$load = "main";
} else {
	$load = $tplSite;
}
if(file_exists(PATH_SKINS."pages".DS.$load.".php") || file_exists(PATH_SKINS."pages".DS.$load.".default.php")) {
	if(file_exists(PATH_SKINS."pages".DS.$load.".php")) {
		include_once(PATH_SKINS."pages".DS.$load.".php");
	} else if(file_exists(PATH_SKINS."pages".DS.$load.".default.php")) {
		include_once(PATH_SKINS."pages".DS.$load.".default.php");
	}
	if(class_exists($load)) {
		new $load();
	}
}
templates::display($tplSite);
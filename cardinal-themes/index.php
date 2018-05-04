<?php
global $tplSite, $route, $call;
if($route!==false) {
	if(is_callable($call)) {
		call_user_func_array($call, array());
		die();
	}
}
if(file_exists(PATH_SKINS."pages".DS.$tplSite.".php") || file_exists(PATH_SKINS."pages".DS.$tplSite.".default.php")) {
	if(file_exists(PATH_SKINS."pages".DS.$tplSite.".php")) {
		include_once(PATH_SKINS."pages".DS.$tplSite.".php");
	} else if(file_exists(PATH_SKINS."pages".DS.$tplSite.".default.php")) {
		include_once(PATH_SKINS."pages".DS.$tplSite.".default.php");
	}
	if(class_exists($tplSite)) {
		new $tplSite();
	}
}
templates::display($tplSite);
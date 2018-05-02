<?php
global $tplSite, $route, $call;
if($route!==false) {
	if(is_callable($call)) {
		call_user_func_array($call, array());
		die();
	}
}
templates::display($tplSite);
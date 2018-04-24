<?php
if(!defined("IS_CORE")) {
	echo "403 ERROR";
	die();
}
if(!defined("DS")) {
	define("DS", DIRECTORY_SEPARATOR);
}
if(!defined("ROOT_PATH")) {
	define("ROOT_PATH", dirname(__FILE__).DS);
}
if(!defined("PATH_CORE")) {
	define("PATH_CORE", ROOT_PATH."core".DS);
}
if(!defined("PATH_CACHE")) {
	define("PATH_CACHE", ROOT_PATH."cache".DS);
}
if(!defined("PATH_SKINS")) {
	define("PATH_SKINS", get_template_directory().DS);
}
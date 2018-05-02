<?php
/**
 * Plugin Name: Legion
 * Description: Логика из Cardinal Engine обмазанная Wordpress-ом :-)
 * Plugin URI:  https://github.com/killserver/cardinal/tree/trunk/
 * Author URI:  https://github.com/killserver/
 * Author:      killserver
 * Version:     0.5.5
*/

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die;
}

define('PLUGIN_NAME_VERSION', '0.5.5');

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

add_action('init', 'initial_cardinal_engine');
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
	add_action('parse_request', 'read_wp_request');
	//Route::Add("test", "(<lang>/)site(/<id>)", "calls");
}

function read_wp_request(&$wp) {
	global $pageNow;
	$pageNow = $wp->query_vars['pagename'];
	$t = Route::Get($pageNow);
	if($t!==false) {
		die();
	}
    return false;
}

function getById($id, $type = "page") {
	$my_posts = new WP_Query();
	$my_posts = $my_posts->get_posts('post_type='.$type.'&p='.$id);
	return (array) current($my_posts);
}

function getAll($args) {
	$my_posts = new WP_Query();
	$all = array();
	$list = $my_posts->get_posts($args);
	foreach($list as $k => $v) {
		$all[$k] = (array) $v;
	}
	return $all;
}

add_action('admin_init', 'initial_plugins_cd');
function initial_plugins_cd() {
	global $updater;
}

new CardinalSettings();

function legion_settings_adm() {
	global $wpdb, $my_plugin_hook;
	if(isset($_POST['edit'])) {
		update_option("wp_legion_debug", $_POST['debug'], "yes");
		var_dump($_POST);die();
	}
	?>
	<div class="wrap">
		<form method="post" action="<?php echo preg_replace( '/\\&.*/', '', $_SERVER['REQUEST_URI'] ); ?>">
			<input type="hidden" name="edit" value="1">
			<h3 id="new-custom-option"><?php _e("Управление легионом"); ?></h3>
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="value">Активация дебага:</label>
					</td>
					<td>
						<label><input id="should-clear-table" name="debug" type="checkbox" value="true"<?php echo(get_option("wp_legion_debug")=="true" ? " checked=\"checked\"" : ""); ?>></label><br/>
					</td>
				</tr>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
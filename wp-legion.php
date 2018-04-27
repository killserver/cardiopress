<?php
/**
 * Plugin Name: Legion
 * Description: Логика из Cardinal Engine обмазанная Wordpress-ом :-)
 * Plugin URI:  https://github.com/killserver/cardinal/tree/trunk/
 * Author URI:  https://github.com/killserver/
 * Author:      killserver
 * Version:     0.5.3
*/

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die();
}

define('PLUGIN_NAME_VERSION', '0.5.3');

define("IS_CORE", true);
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php");

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
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."paths.php");
	require_once(PATH_CORE."wp-func.php");
	debugActivationLegion();
}

add_action('admin_init', 'initial_plugins_cd');
function initial_plugins_cd() {
	global $updater;
	add_filter('pre_set_site_transient_update_plugins', array($updater, 'modify_transient'), 10, 3);
	add_filter('site_transient_update_plugins', array($updater, 'modify_transient'), 10, 3);
	add_filter('plugins_api', array($updater, 'plugin_popup'), 10, 3);
	add_filter('plugins_api_result', array($updater, 'plugin_add'), 10, 3);
	add_filter('upgrader_post_install', array($updater, 'after_install'), 10, 3);
	add_filter('all_plugins', array($updater, 'all_plugins'), 10, 3);
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
<?php
remove_action('wp_head', 'wp_generator');
function wpse_custom_generator_meta_tag() {
	if(!file_exists(PATH_CACHE."version.txt")) {
		if(!file_exists(PATH_CACHE)) {
			@mkdir(PATH_CACHE, 0777, true);
		}
		if(!is_writeable(PATH_CACHE)) {
			@chmod(PATH_CACHE, 0777);
		}
		$prs = new Parser("https://raw.githubusercontent.com/killserver/cardinal/trunk/version/version.txt");
		$prs = $prs->get();
		file_put_contents(PATH_CACHE."version.txt", $prs);
	} else {
		$prs = file_get_contents(PATH_CACHE."version.txt");
	}
	echo '<meta name="generator" content="Cardinal Engine '.$prs.'" />'."\n";
}
add_action('wp_head', 'wpse_custom_generator_meta_tag');
add_action('admin_bar_menu', 'remove_wp_logo', 999);
function remove_wp_logo($wp_admin_bar) {
	$wp_admin_bar->remove_node('wp-logo');
}


global $updater;
add_filter('pre_set_site_transient_update_plugins', array($updater, 'modify_transient'), 10, 3);
add_filter('site_transient_update_plugins', array($updater, 'modify_transient'), 10, 3);
add_filter('plugins_api', array($updater, 'plugin_popup'), 10, 3);
add_filter('plugins_api_result', array($updater, 'plugin_add'), 10, 3);
add_filter('upgrader_post_install', array($updater, 'after_install'), 10, 3);
add_filter('all_plugins', array($updater, 'all_plugins'), 10, 3);
add_filter('themes_api_result', array($updater, 'modify_transient_theme'), 10, 3);



if(file_exists(PATH_CORE."menuSupport.php")) {
	add_theme_support('menus');
	require_once(PATH_CORE."menuSupport.php");
}

remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
if(!defined('DISALLOW_FILE_EDIT')) {
	define('DISALLOW_FILE_EDIT', true);
}
add_theme_support('html5', array(
	'search-form', 'comment-form', 'comment-list', 'gallery', 'caption',
));

function custom_admin_footer() {
echo '<span id="footer-thankyou">Спасибо вам за творчество с <a href="https://ru.wordpress.org/">WordPress</a> и ядром <a href="https://github.com/killserver/cardinal/tree/trunk/">Cardinal Engine</a>.</span>';
}
add_filter('admin_footer_text', 'custom_admin_footer');


//add_action( 'admin_menu', 'remove_menus' );
function remove_menus(){
	// Remove Dashboard
	remove_menu_page('index.php');
	// Posts
	//remove_menu_page('edit.php');
	// Posts -> Categories
	remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category');
	// Posts -> Tags
	remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');
	// Media
	remove_menu_page('upload.php');
	// Media -> Library
	remove_submenu_page('upload.php', 'upload.php');
	// Media -> Add new media
	remove_submenu_page('upload.php', 'media-new.php');
	// Pages
	remove_menu_page('edit.php?post_type=page');
	// Pages -> All pages
	remove_submenu_page('edit.php?post_type=page', 'edit.php?post_type=page');
	// Pages -> Add new page
	remove_submenu_page('edit.php?post_type=page', 'post-new.php?post_type=page');
	// Comments
	remove_menu_page('edit-comments.php');
	// Appearance
	//remove_menu_page('themes.php');
	// Appearance -> Themes
	remove_submenu_page('themes.php', 'themes.php');
	// Appearance -> Customize
	remove_submenu_page('themes.php', 'customize.php?return=' . urlencode( $_SERVER['REQUEST_URI'] ));
	// Appearance -> Widgets
	remove_submenu_page('themes.php', 'widgets.php');
	// Appearance -> Menus
	//remove_submenu_page('themes.php', 'nav-menus.php');
	// Appearance -> Editor
	remove_submenu_page('themes.php', 'theme-editor.php');
	// Plugins
	remove_menu_page('plugins.php');
	// Plugins -> Installed plugins
	remove_submenu_page('plugins.php', 'plugins.php');
	// Plugins -> Add new plugins
	remove_submenu_page('plugins.php', 'plugin-install.php');
	// Plugins -> Plugin editor
	remove_submenu_page('plugins.php', 'plugin-editor.php');
	// Users
	remove_menu_page('users.php');
	// Users -> Users
	remove_submenu_page('users.php', 'users.php');
	// Users -> New user
	remove_submenu_page('users.php', 'user-new.php');
	// Users -> Your profile
	remove_submenu_page('users.php', 'profile.php');
	// Tools
	remove_menu_page('tools.php');
	// Tools -> Available Tools
	remove_submenu_page('tools.php', 'tools.php');
	// Tools -> Import
	remove_submenu_page('tools.php', 'import.php');
	// Tools -> Export
	remove_submenu_page('tools.php', 'export.php');
	// Settings
	remove_menu_page('options-general.php');

	// Settings -> Writing
	remove_submenu_page('options-general.php', 'options-writing.php');

	// Settings -> Reading
	remove_submenu_page('options-general.php', 'options-reading.php');

	// Settings -> Discussion
	remove_submenu_page('options-general.php', 'options-discussion.php');

	// Settings -> Media
	remove_submenu_page('options-general.php', 'options-media.php');

	// Settings -> Permalinks
	remove_submenu_page('options-general.php', 'options-permalink.php');

	// Кастом-филд
	remove_menu_page('edit.php?post_type=acf-field-group');
}
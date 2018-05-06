<?php
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
if(!defined('DISALLOW_FILE_EDIT')) {
	define('DISALLOW_FILE_EDIT', true);
}
add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
add_theme_support('title-tag');
add_action('wp_head', 'wpse_custom_generator_meta_tag');
add_action('admin_bar_menu', 'remove_wp_logo', 999);
// Обновления
global $updater;
add_filter('pre_set_site_transient_update_plugins', array($updater, 'modify_transient'), 10, 3);
add_filter('site_transient_update_plugins', array($updater, 'modify_transient'), 10, 3);
add_filter('plugins_api', array($updater, 'plugin_popup'), 10, 3);
add_filter('plugins_api_result', array($updater, 'plugin_add'), 10, 3);
add_filter('upgrader_post_install', array($updater, 'after_install'), 10, 3);
add_filter('all_plugins', array($updater, 'all_plugins'), 10, 3);
add_filter('themes_api_result', array($updater, 'modify_transient_theme'), 10, 3);
// Обновления
add_filter('admin_footer_text', 'custom_admin_footer');

$settings = get_option("legion");
if(isset($settings['legion_category'])) {
	add_filter('category_link', 'true_remove_category_from_category', 1, 1);
}

function debug_admin_menus() {
    global $submenu, $menu, $pagenow;
    $settings = get_option("legion");
    if(isset($settings['legion_submenu'])) {
		foreach($settings['legion_submenu'] as $k => $v) {
			$key = array_keys($v);
			for($i=0;$i<sizeof($key);$i++) {
				remove_submenu_page($k, $key[$i]);
			}
		}
	}
	if(isset($settings['legion_menu'])) {
		foreach($menu as $k => $v) {
			if(isset($settings['legion_menu'][$v[2]])) {
				remove_menu_page($v[2]);
			}
		}
	}
}
add_action('admin_menu', 'debug_admin_menus', 9999);

function remove_wp_logo($wp_admin_bar) {
	$wp_admin_bar->remove_node('wp-logo');
}
function true_remove_category_from_category($cat_url) {
	$cat_url = str_replace('/category', '', $cat_url);
	return $cat_url;
}

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

if(file_exists(PATH_CORE."menuSupport.php")) {
	add_theme_support('menus');
	require_once(PATH_CORE."menuSupport.php");
}

function custom_admin_footer() {
echo '<span id="footer-thankyou">Спасибо вам за творчество с <a href="https://ru.wordpress.org/">WordPress</a> и ядром <a href="https://github.com/killserver/cardinal/tree/trunk/">Cardinal Engine</a>.</span>';
}

function read_wp_request($wp) {
	global $pageNow, $route, $call;
	if(isset($wp['pagename'])) {
		$pageNow = $wp['pagename'];
		$routes = Route::Get($pageNow);
		$route = current($routes);
		$call = end($routes);
	}
    return $wp;
}

function wp_admin_bar_edit_menu2($wp_admin_bar) {
	global $tag, $wp_the_query, $user_id, $post;
	if(is_admin()) {
		$current_screen = get_current_screen();
		if('post' == $current_screen->base && 'add' != $current_screen->action && ($post_type_object = get_post_type_object($post->post_type)) && current_user_can('read_post', $post->ID) && ($post_type_object->public) && ( $post_type_object->show_in_admin_bar)) {
			if('draft' == $post->post_status) {
				$preview_link = get_preview_post_link($post);
				$wp_admin_bar->add_menu(array(
					'id' => 'preview',
					'title' => $post_type_object->labels->view_item,
					'href' => esc_url($preview_link),
					'meta' => array('target' => 'wp-preview-'.$post->ID),
				));
			} else {
				$wp_admin_bar->add_menu(array(
					'id' => 'view',
					'title' => $post_type_object->labels->view_item,
					'href' => get_permalink($post->ID)
				));
			}
		} elseif('edit' == $current_screen->base && ($post_type_object = get_post_type_object($current_screen->post_type)) && ($post_type_object->public) && ($post_type_object->show_in_admin_bar) && (get_post_type_archive_link($post_type_object->name)) && !('post' === $post_type_object->name && 'posts' === get_option('show_on_front'))) {
 			$wp_admin_bar->add_node(array(
 				'id' => 'archive',
 				'title' => $post_type_object->labels->view_items,
 				'href' => get_post_type_archive_link($current_screen->post_type)
 			));
		} elseif('term' == $current_screen->base && isset($tag) && is_object($tag) && !is_wp_error($tag) && ($tax = get_taxonomy($tag->taxonomy)) && $tax->public) {
			$wp_admin_bar->add_menu(array(
				'id' => 'view',
				'title' => $tax->labels->view_item,
				'href' => get_term_link($tag)
			));
		} elseif('user-edit' == $current_screen->base && isset($user_id) && ($user_object = get_userdata($user_id)) && $user_object->exists() && $view_link = get_author_posts_url($user_object->ID))
		{
			$wp_admin_bar->add_menu(array(
				'id'    => 'view',
				'title' => __('View User'),
				'href'  => $view_link,
			));
		}
	} else {
		$current_object = $wp_the_query->get_queried_object();
		if(empty($current_object)) {
			return;
		}
		if(!empty($current_object->post_type) && ($post_type_object = get_post_type_object($current_object->post_type)) && current_user_can('edit_post', $current_object->ID) && $post_type_object->show_in_admin_bar && $edit_post_link = get_edit_post_link($current_object->ID)) {
			$wp_admin_bar->add_menu(array(
				'id' => 'edit',
				'title' => $post_type_object->labels->edit_item,
				'href' => $edit_post_link
			));
		} elseif(!empty($current_object->taxonomy) && ($tax = get_taxonomy($current_object->taxonomy)) && current_user_can('edit_term', $current_object->term_id) && $edit_term_link = get_edit_term_link($current_object->term_id, $current_object->taxonomy)) {
			$wp_admin_bar->add_menu(array(
				'id' => 'edit',
				'title' => $tax->labels->edit_item,
				'href' => $edit_term_link
			));
		} elseif(is_a($current_object, 'WP_User') && current_user_can('edit_user', $current_object->ID) && $edit_user_link = get_edit_user_link($current_object->ID)) {
			$wp_admin_bar->add_menu(array(
				'id'    => 'edit',
				'title' => __('Edit User'),
				'href'  => $edit_user_link,
			));
		}
	}
}

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
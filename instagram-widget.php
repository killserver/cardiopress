<?php
/*
 * Plugin Name: Instagram Widget for site
 * Description: Добавляет возможность программистам разместить на сайте публикации из инстаграмма. Внимание! Данный плагин может обновляться только при наличии плагина Legion!
 * Plugin URI:  https://github.com/killserver/cardinal/tree/trunk/
 * Author URI:  https://github.com/killserver/
 * Author:      killserver
 * Version:     0.0.1
*/

function cutInstaWidget($f, $start, $end = "") {
    $p = strpos($f, $start);
    if($p===false) {
        return false;
    }
    $l = strlen($start);
    $f = substr($f, $p+$l);
    if($end!=="") {
        $find = strpos($f, $end);
        if($p!==false) {
            $f = substr($f, 0, $find);
        }
    }
    return $f;
}

function add_option_instawidget_admin_page1() {
	$option_name = 'instaWidget';
	register_setting( 'general', $option_name );
	add_settings_field( 'instaWidget_setting1', 'Ссылка на профиль в инстаграмме', 'instawidget_setting_callback1', 'general', 'default', array( 'id' => 'instaWidget_setting1', 'option_name' => $option_name ) );
}
add_action('admin_menu', 'add_option_instawidget_admin_page1');

function instawidget_setting_callback1($val) {
	$id = $val['id'];
	$option_name = $val['option_name'];
	echo '<input type="text" name="'.$option_name.'" id="'.$id.'" value="'.esc_attr(get_option($option_name)).'" style="width:100%;" />';
}

function add_option_instawidget_admin_page2() {
	$option_name = 'instaWidgetSize';
	register_setting( 'general', $option_name );
	add_settings_field( 'instaWidget_setting2', 'Размер по-умолчанию для картинок с инстаграмма', 'instawidget_setting_callback2', 'general', 'default', array( 'id' => 'instaWidget_setting2', 'option_name' => $option_name ) );
}
add_action('admin_menu', 'add_option_instawidget_admin_page2');

function instawidget_setting_callback2($val) {
	$id = $val['id'];
	$option_name = $val['option_name'];
	$val = esc_attr(get_option($option_name));
	echo '<select name="'.$option_name.'" id="'.$id.'" style="width:100%;">'.
		'<option value="150x150"'.($val=="150x150" ? " selected=\"selected\"" : "").'>150x150</option>'.
		'<option value="240x240"'.($val=="240x240" ? " selected=\"selected\"" : "").'>240x240</option>'.
		'<option value="320x320"'.($val=="320x320" ? " selected=\"selected\"" : "").'>320x320</option>'.
		'<option value="480x480"'.($val=="480x480" ? " selected=\"selected\"" : "").'>480x480</option>'.
		'<option value="640x640"'.($val=="640x640" ? " selected=\"selected\"" : "").'>640x640</option>'.
	'</select>';
}

function getInstaWidget($user = "", $size = "") {
	if($user==="") {
		$user = get_option("instaWidget", false);
	}
	if(strpos($user, "www.instagram.com/")===false) {
		$user = "https://www.instagram.com/".$user;
	}
	if(substr($user, -1, 1)!="/") {
		$user .= "/";
	}
	if($size==="") {
		$size = get_option("instaWidgetSize", "150x150");
	}
	$f = file_get_contents($user);
	$f = cutInstaWidget($f, '<script type="text/javascript">window._sharedData = ', ";</script>");
	$f = json_decode($f, true);
	switch($size) {
		case "640x640":
			$size = 3;
		break;
		case "480x480":
			$size = 3;
		break;
		case "320x320":
			$size = 2;
		break;
		case "240x240":
			$size = 1;
		break;
		case "150x150":
		default:
			$size = 0;
		break;
	}
	$arr = array();
	$d = $f['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];
	for($i=0;$i<sizeof($d);$i++) {
		$arr[] = array(
			"img" => $d[$i]['node']['thumbnail_resources'][$size]['src'],
			"link" => "https://www.instagram.com/p/".$d[$i]['node']['shortcode']."/",
		);
	}
	return $arr;
}


?>
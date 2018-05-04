<?php
if(!defined("IS_CORE")) {
	echo "403 ERROR";
	die();
}
require_once(PATH_CORE."Route.php");
require_once(PATH_CORE."Parser.php");
require_once(PATH_CORE."templates.php");
require_once(PATH_CORE."Settings.php");
require_once(PATH_CORE."cyr-to-lat.php");

function array_map_recursive($f, $xs) {
	$out = array();
	foreach($xs as $k => $x) {
		$out[$k] = (is_array($x) || is_object($x)) ? array_map_recursive($f, $x) : $f($x);
	}
	return $out;
}

function htmlspecialchars_cardinal($data) {
	if(is_numeric($data) || is_bool($data) || is_resource($data) || is_null($data)) {
		return $data;
	} else {
		return htmlspecialchars($data);
	}
}

$withoutPack = false;

function onlyEcho($val = true) {
	global $withoutPack;
	if($val==="") {
		return $withoutPack;
	} else {
		$withoutPack = $val;
	}
}

function vdump() {
	$d = debug_backtrace();
	echo "<pre class=\"debug_backtrace\" style=\"margin-left:".(is_admin() ? "160" : "0")."px; margin-right:0px; padding:10px; color:black; text-align:left; font-size: 12px;".(is_admin() ? "padding-bottom:60px;" : "background-color:ghostwhite; border:solid 1px black;")."\">";
	echo "<small style='font-size:12px'>".($d[0]['file']." [".$d[0]['line']."]")."</small><br><br>\n\n";
	$fn = func_get_args();
	$fn = array_map_recursive("htmlspecialchars_cardinal", $fn);
	for($i=0;$i<func_num_args();$i++) {
		if(!function_exists("onlyEcho") || onlyEcho("")===false) {
			call_user_func_array("var_dump", array($fn[$i]))."\n\n\n";
		} else {
			if(!is_string($fn[$i])) {
				call_user_func_array("var_dump", array($fn[$i]))."\n\n\n";
			} else {
				echo $fn[$i];
			}
		}
	}
	echo "</pre>";
	if(function_exists("onlyEcho")) {
		onlyEcho(false);
	}
}

function vecho() {
	$d = debug_backtrace();
	echo "<pre class=\"debug_backtrace\" style=\"margin-left:".(is_admin() ? "160" : "0")."px; margin-right:0px; padding:10px; color:black; text-align:left; font-size: 12px;".(is_admin() ? "padding-bottom:60px;" : "background-color:ghostwhite; border:solid 1px black;")."\">";
	echo "<small style='font-size:12px'>".($d[0]['file']." [".$d[0]['line']."]")."</small><br><br>\n\n";
	$fn = func_get_args();
	$fn = array_map_recursive("htmlspecialchars_cardinal", $fn);
	for($i=0;$i<func_num_args();$i++) {
		echo $fn[$i];
	}
	echo "</pre>";
	if(function_exists("onlyEcho")) {
		onlyEcho(false);
	}
}


function addDataPost($post = "", $block = "") {
	if($post==="") {
		global $post;
	}
	if(is_array($post) || is_object($post)) {
		$id = (is_array($post) && isset($post['ID']) ? $post['ID'] : (is_object($post) && isset($post->ID) ? $post->ID : uniqid()));
		foreach($post as $k => $v) {
			if($block==="") {
				templates::assign_var("post.".$k, $v);
			} else {
				templates::assign_var("post.".$k, $v, $block, $id);
			}
		}
	}
}

function more_posts() {
	global $wp_query;
	return $wp_query->current_post + 1 < $wp_query->post_count;
}

function addCustomField($data) {
	$arr = array(
		'id'         => '',       // динетификатор блока. Используется как префикс для названия метаполя.
								  // начните идент. с '_': '_foo', чтобы ID не был префиксом в названии метаполей.
		'title'      => '',       // заголовок блока
		'desc'       => '',       // описание для метабокса. Можно указать функцию/замыкание, она получит $post. С версии 1.9.1
		'post_type'  => '',       // строка/массив. Типы записей для которых добавляется блок: array('post','page'). По умолчанию: '' - для всех типов записей.
		'priority'   => 'high',   // Приоритет блока для показа выше или ниже остальных блоков ('high' или 'low').
		'context'    => 'normal', // Место где должен показываться блок ('normal', 'advanced' или 'side').

		'disable_func'  => '',    // функция отключения метабокса во время вызова самого метабокса. Если не false/null/0/array() - что-либо вернет,
								  // то метабокс будет отключен. Передает объект поста.

		'save_sanitize' => '',    // Функция очистки сохраняемых в БД полей. Получает 2 параметра: $metas - все поля для очистки и $post_id

		'theme' => 'table',       // тема оформления. Может быть: 'table', 'line' или массив паттернов полей/отдельного поля (см. параметр $theme_options).
								  // при указании массива за основу будут взяты паттерны темы 'line'.
								  // еще изменить тему можно через фильтр 'kp_metabox_theme' - удобен для общего изменения темы для всех метабосов.

		// Массив метаполей, которые будут выводиться
		'fields'     => array(

			// Каждое поле указывается в виде массива, где ключ - это название метаполя, а значение - это массив данных о поле
			// реальное название метаполя будет выглядеть как: {id}_{meta_key}
			'meta_key' => array(
				'type'          => '', // тип поля: 'text', 'textarea', 'select', 'checkbox', 'radio',
									   // 'wp_editor', 'hidden' или другое (email, number). По умолчанию 'text'.
				'title'         => '', // заголовок метаполя
				'desc'          => '', // описание для поля. Можно указать функцию/замыкание, она получит параметры: $post, $meta_key, $val. С версии 1.9.1
				'placeholder'   => '', // атрибут placeholder
				'id'            => '', // атрибут id. По умолчанию: $this->opt->id .'_'. $key
				'class'         => '', // атрибут class: добавляется в input, textarea, select. Для checkbox, radio в оборачивающий label
				'attr'          => '', // любая строка, будет расположена внутри тега. Для создания атрибутов. Пр: style="width:100%;"
				'val'           => '', // значение по умолчанию, если нет сохраненного.
				'options'       => '', // массив: array('значение'=>'название'). Варианты для select, radio, или аргументы для wp_editor
									   // Для 'checkbox' станет значением атрибута value.
				'callback'      => '', // название функции, которая отвечает за вывод поля.
									   // если указана, то ни один параметр не учитывается и за вывод полностью отвечает указанная функция.
									   // Все параметры передаются ей... Получит параметры:
									   // $args - все параметры указанные тут
									   // $post - объект WP_Post текущей записи
									   // $name - название атрибута 'name' (название полей собираются в массив)
									   // $val - атрибут 'value' текущего поля

				'sanitize_func' => '', // функция очистки данных при сохранении - название фукнции или Closure. Укажите 'none', чтобы не очищать данные...
									   // работает, только если не установлен глобальный параметр 'save_sanitize'...
									   // получит параметр $value - сохраняемое значение поля.
				'output_func'   => '', // функция обработки значения, перед выводом в поле.
									   // получит параметры: $post, $meta_key, $value - объект записи, ключ, значение метаполей.
				'update_func'   => '', // функция сохранения значения в метаполя.
									   // получит параметры: $post, $meta_key, $value - объект записи, ключ, значение метаполей.

				'disable_func'  => '', // функция отключения поля. Если не false/null/0/array() - что-либо вернет, то поле не будет выведено.
									   // Передает $post - объект поста.

			),
			'meta_key2' => array( /*...*/ ),
			'meta_key3' => array( /*...*/ ),
			// ...
		),
	);
	$arr = array_merge_recursive($arr, $data);
	new Kama_Post_Meta_Box($arr);
}


if(defined("DEBUG_ACTIVATED") && DEBUG_ACTIVATED===true){
	// инициализация
	add_action('init', function() { return add_stop('Load'); }, 10000000);
	add_action('template_redirect', function() { return add_stop('Query'); }, -10000000);
	add_action('wp_footer', function() { return add_stop('Display'); }, 10000000);
	add_action('admin_footer', function() { return add_stop('Display'); }, 10000000);

	// включим сохранение запросов, когда нужно
	if(!defined('SAVEQUERIES') && isset($_GET['debug_sql'])) {
		define('SAVEQUERIES', true);
	}

	add_action('wp_print_scripts', '__init_dump');
	function __init_dump() {
		global $hook_suffix;
		if(!is_admin() || empty($hook_suffix)) {
			add_action('wp_footer',    'dump_stops', 10000000);
			add_action('admin_footer', 'dump_stops', 10000000);
		} else {
			add_action('wp_footer', 'dump_stops', 10000000);
			add_action("admin_footer-$hook_suffix", 'dump_stops', 10000000);
		}
	}
	## Вспомогательная функция - показывает трасировку в удобном формате.
	function dump_trace() {
		$backtrace = debug_backtrace();
		foreach($backtrace as $trace) {
			vdump(
				(isset($trace['file']) && isset($trace['line']) ? 'File/Line: '.$trace['file'].' ['.$trace['line']."]" : "").PHP_EOL.
				'Function / Class: ' . (isset($trace['class']) ? $trace['class']."::" : "").$trace['function'].(isset($trace['args']) && is_array($trace['args']) && sizeof($trace['args'])>0 ? "(".var_export($trace['args'], true).")" : "")
			);
		}
	}

	## данные о запросах, времени выполнения, кэше...
	function add_stop($where = null) {
		global $sem_stops;
		global $wp_object_cache;
		$queries = get_num_queries();
		$milliseconds = timer_stop(0, 6);
		$out =  $queries." queries - ".$milliseconds." sec.";
		if(function_exists('memory_get_usage')){
			$memory = number_format(memory_get_usage() / ( 1024 * 1024 ), 1);
			$out .= " - ".$memory."MB";
		}

		$out .= " - ".$wp_object_cache->cache_hits." cache hits / ".($wp_object_cache->cache_hits + $wp_object_cache->cache_misses);

		if($where) {
			$sem_stops[$where] = $out;
		} else {
			vdump($out);
		}
	}

	## выводит разные данные и время генерации запросы в разные периоды (сохраняется в $sem_stops)
	## в параметр запроса можно указать: '?debug_sql', '?debug_cache', '?debug_cron'
	function dump_stops() {
		global $sem_stops;
		$stops = '';
		foreach($sem_stops as $where => $stop) {
			$stops .= $where.": ".$stop."\n";
		}
		onlyEcho(true);
		vdump("\n".trim($stops)."\n");

		// SQL запросы
		if(isset($_GET['debug_sql'])) {
			global $wpdb;
			foreach($wpdb->queries as $key => $data) {
				$query = rtrim($data[0]);
				$duration = number_format($data[1] * 1000, 1) . 'ms';
				$loc = trim($data[2]);
				$loc = preg_replace("/(require|include)(_once)?,\s*/ix", '', $loc);
				$loc = "\n".preg_replace("/,\s*/", ",\n", $loc)."\n";
				onlyEcho(true);
				vdump($query." (".$duration.")\n\n------".$loc."\n------");
			}
		}

		// объектный кэш
		if(isset($_GET['debug_cache'])) {
			onlyEcho(true);
			vdump($GLOBALS['wp_object_cache']->cache);
		}

		// данные о кроне
		if(isset($_GET['debug_cron'])) {
			$crons = get_option('cron');
			foreach($crons as $time => $_crons) {
				if(!is_array($_crons)) {
					continue;
				}
				foreach($_crons as $event => $_cron) {
					foreach($_cron as $details) {
						$date = date('Y-m-d H:m:i', $time);
						$schedule = isset($details['schedule']) ? "(".$details['schedule'].")" : '';
						if(isset($details['args'])) {
							onlyEcho(true);
							vdump($date.": ".$event." ".$schedule, $details['args']);
						} else {
							onlyEcho(true);
							vdump($date.": ".$event." ".$schedule);
						}
					}
				}
			}
		}
	}
}
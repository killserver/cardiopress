<?php

class CardinalUpdater {

	private $file;
	private $plugin;
	private $basename;
	private $active;
	private $responser;
	private $responserAll;
	private $responserAliases;
	private $responserMainAliases;
	private $server;

	public function __construct($file, $server) {
		$this->file = $file;
		$this->basename = plugin_basename( $this->file );
		add_action('admin_init', array($this, 'set_plugin_properties'));
		$this->server = $server;
		$this->get_repository_all();
		$this->loadAliases();
		return $this;
	}

	private function loadAliases($name = "") {
		if(is_null($this->responserAliases)) {
			$request_uri = sprintf($this->server."?loadAliases=1"); // Build URI
			$response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri)), true); // Get JSON and parse it
			$this->responserAliases = $response;
		}
		if($name!=="") {
			return (isset($this->responserAliases[$name]) ? $this->responserAliases[$name] : $name);
		}
	}

	private function replacer($data, $arr) {
		foreach($arr as $k => $v) {
			$keys = array_keys($data);
			$vals = array_values($data);
			$arr[$k] = str_replace($keys, $vals, $v);
		}
		return $arr;
	}

	public function set_plugin_properties() {
		$this->plugin	= get_plugin_data($this->file);
		$this->basename = plugin_basename($this->file);
		$this->active	= is_plugin_active($this->basename);
	}

	private function maping($file, $version) {
		$slug = $this->getClearName($file);
		return array($slug."" => $version);
	}

	private function get_repository_info($data) {
	    if(is_null($this->responser)) { // Do we have a response?
	    	if(is_array($data)) {
				$data = array_map(array($this, "maping"), array_keys($data), array_values($data));
				for($i=0;$i<sizeof($data);$i++) {
					$key = key($data[$i]);
					$request_uri = sprintf($this->server."?view=".$key); // Build URI
					$response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri)), true); // Get JSON and parse it
					$this->responser[$key] = $response; // Set it to our property
				}
	    	} else {
				$request_uri = sprintf($this->server."?view=".$data); // Build URI
				$response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri)), true); // Get JSON and parse it
				$this->responser[$data] = $response; // Set it to our property
	    	}
	    }
	}

	private function get_repository_all() {
	    if(is_null($this->responserAll)) { // Do we have a response?
	        $request_uri = sprintf($this->server."?all"); // Build URI
	        $response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri)), true); // Get JSON and parse it
	        $this->responserAll = $response; // Set it to our property
	    }
	}

	private function pluginsInfo($v, $data) {
		$arr = $v;
		foreach($v as $k => $v) {
			if(isset($data[$k])) {
				$arr[$k] = $data[$k];
			}
		}
		return $arr;
	}

	public function all_plugins($list) {
		foreach($list as $k => $v) {
			$clear = $this->getClearName($k);
			if(isset($this->responserAll[$clear])) {
				$info = $this->pluginsInfo($v, $this->responserAll[$clear]);
				$list[$k] = $info;
			}
		}
		return $list;
	}

	private function getClearName($file) {
		$exp = explode('/', $file);
		$slug = current($exp); // Create valid slug
		return $slug;
	}

	public function modify_transient_theme($resp, $type, $args) {
		$install = array(
			"name" => "Cardinal",
			"slug" => "cardinal-themes",
			"version" => "1.1",
			"preview_url" => get_site_url(),
			"author" => "killserver",
			"screenshot_url" => "http://kratko-news.com/wp-content/uploads/2013/04/%D0%BA%D0%B0%D1%80%D0%B4%D0%B8%D0%BD%D0%B0%D0%BB-%D0%BA%D0%BE%D1%85.jpg",
			"rating" => 100,
			"num_ratings" => "1",
			"downloaded" => "1",
			"last_updated" => "2018-04-03",
			"download_link" => "http://medik.local/update/cardinal-themes.zip",
			"homepage" => "https://wordpress.org/themes/twentyseventeen/",
			"description" => "Тема \"Cardinal Engine\" поможет Вам с быстрым началом работы с плагином <b>\"wp-legion\"</b>",
		);
		if($type=="query_themes") {
			array_unshift($resp->themes, (object) $install);
		} else if($type=="theme_information" && $args->slug=="cardinal-themes") {
			$resp = (object) $install;
		}
		return $resp;
	}

	public function site_allowed_themes() {
		var_dump(func_get_args());die();
	}

	public function modify_transient($transient) {
		if(isset($transient->checked)) {
			$all = $checked = $response = array();
			foreach($transient->checked as $k => $v) {
				$v = (object) array("version" => $v);
				$v->type = "checked";
				$all[$k] = $v;
				$checked[$k] = $v;
			}
			foreach($transient->response as $k => $v) {
				$v->type = "response";
				$all[$k] = $v;
				$response[$k] = $v;
			}
			foreach($all as $k => $v) {
				$clear = $this->getClearName($k);
				if(isset($this->responserAll[$clear])) {
					$version = version_compare($this->responserAll[$clear]['version_now'], $checked[$k]->version, 'gt');
					if($version && $v->type == "response" && isset($transient->response[$k])) {
						unset($transient->response[$k]);
						$transient->response[$k] = (object) $this->responserAll[$clear];
					} else if(!$version) {
						if(isset($transient->response[$k])) {
							unset($transient->response[$k]);
						}
						$transient->no_update[$k] = (object) $this->responserAll[$clear];
					}
				}
			}
		}
		return $transient; // Return filtered transient
	}

	public function plugin_add($result, $action, $args) {
		$this->get_repository_all();
		if(isset($args->browse) && $args->browse=="featured" && isset($result->plugins)) {
			foreach($this->responserAll as $v) {
				array_unshift($result->plugins, $v);
			}
		}
		return $result;
	}

	public function plugin_popup($result, $action, $args) {
		$this->get_repository_all();
		if(!empty($args->slug)) { // If there is a slug
			$args->slug = $this->loadAliases($args->slug);
			$this->get_repository_info($args->slug);
			if(isset($this->responser[$args->slug])) {
				$info = $this->responser[$args->slug];
				return (object) $info; // Return the data
			}
		}
		if(isset($args->per_page)) {
			$size = sizeof($this->responserAll);
			if($size>$args->per_page) {
				$args->per_page -= $size;
			} else {
				$args->per_page = 0;
			}
		}
		return $result; // Otherwise return default
	}

	public function after_install($response, $hook_extra, $result) {
		global $wp_filesystem; // Get global FS object
		$install_directory = plugin_dir_path($this->file); // Our plugin directory
		$wp_filesystem->move($result['destination'], $install_directory); // Move files to the plugin dir
		$result['destination'] = $install_directory; // Set the destination for the rest of the stack
		if($this->active) { // If it was active
			activate_plugin($this->basename); // Reactivate
		}
		return $result;
	}
}

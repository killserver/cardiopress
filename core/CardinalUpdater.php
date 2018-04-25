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

	private function loadMainAliases($name = "", $data = "") {
		if(is_null($this->responserMainAliases)) {
			$request_uri = sprintf($this->server."?loadMainAliases=1"); // Build URI
			$response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri)), true); // Get JSON and parse it
			$this->responserMainAliases = $response;
		}
		if($name!=="") {
			if(isset($this->responserMainAliases[$name])) {
				$ret = array_merge($data, $this->responserMainAliases[$name]);
				if(isset($this->responserMainAliases[$name]['replacer'])) {
					$ret = $this->replacer($this->responserMainAliases[$name]['replacer'], $ret);
				}
				return $ret;
			} else {
				return $data;
			}
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

	public function initialize() {
		add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
		add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
		add_filter('plugins_api_result', array($this, 'plugin_add'), 10, 3);
		add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
	}

	private function getClearName($file) {
		$exp = explode('/', $file);
		$slug = current($exp); // Create valid slug
		return $slug;
	}

	private function loadShort() {
		if(!isset($this->shortData)) {
	        $request_uri = sprintf($this->server."?short"); // Build URI
	        $response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri)), true); // Get JSON and parse it
	        $this->shortData = $response; // Set it to our property
		}
	}

	public function modify_transient($transient) {
		$myUpdates = array();
		if(property_exists($transient, 'checked')) { // Check if transient has a checked property
			if($checked = $transient->checked) { // Did Wordpress check for updates?
				foreach($transient->checked as $k => $v) {
					unset($transient->checked[$k]);
					$k = $this->getClearName($k);
					$k = $this->loadAliases($k);
					$transient->checked[$k] = $v;
				}
				$this->get_repository_info($transient->checked); // Get the repo info
				foreach($transient->checked as $name => $version) {
					$name = $this->getClearName($name);
					$out_of_date = version_compare($this->responser[$name]['version_now'], $version, 'gt'); // Check if we're out of date
					if($out_of_date) {
						$new_files = $this->responser[$name]['download_link']; // Get the ZIP
						$slug = $name; // Create valid slug
						$plugin = array( // setup our plugin info
							'url' => $this->plugin["PluginURI"],
							'slug' => $slug,
							'package' => $new_files,
							'new_version' => $this->responser[$name]['version_now']
						);
						$myUpdates[$name] = true;
						$transient->response[$name] = (object) $plugin; // Return it in response
					}
				}
			}
		}
		$this->loadShort();
		foreach($this->shortData as $k => $v) {
			$clear = $this->getClearName($k);
			if(!isset($myUpdates[$clear])) {
				if(isset($transient->response[$k])) {
					unset($transient->response[$k]);
				}
				$transient->no_update[$k] = (object) $v;
			}
		}
		return $transient; // Return filtered transient
	}

	public function plugin_add($result, $action, $args) {
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

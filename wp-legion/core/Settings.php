<?php

class CardinalSettings {
	/**
	 * Array of custom settings/options
	**/
	private $options;
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 998 );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}
	/**
	 * Add settings page
	 * The page will appear in Admin menu
	 */
	public function add_settings_page() {
		add_menu_page('Legion Settings', 'Legion', 'edit_pages', 'legion-settings', array($this, 'create_admin_page'));
	}
	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		$this->options = get_option('legion');
	?>
		<div class="wrap">
			<h2>Legion Settings</h2>           
			<form method="post" action="options.php">
			<?php
				settings_fields('legion_settings_group');   
				do_settings_sections('legion-settings-page');
				submit_button(); 
			?>
			</form>
		</div>
	<?php
	}

	public function page_init() {
		register_setting('legion_settings_group', 'legion', array($this, 'sanitize'));
		add_settings_section('legion_settings_section', 'Дебаг', array($this, 'legion_settings_section'), 'legion-settings-page');
		add_settings_field('legion_debug', 'Активация дебага', array($this, 'legion_setting1_html'), 'legion-settings-page', 'legion_settings_section');
		add_settings_field('legion_category', 'Отключение "/category/" для категорий', array($this, 'legion_setting2_html'), 'legion-settings-page', 'legion_settings_section');
		add_settings_field('legion_menu', 'Редактор меню', array($this, 'legion_setting3_html'), 'legion-settings-page', 'legion_settings_section');
	}

	public function sanitize($input) {
		$sanitized_input = array();
		if(isset($_POST['legion_debug'])) {
			$sanitized_input['legion_debug'] = sanitize_text_field($_POST['legion_debug']);
		}
		if(isset($_POST['legion_category'])) {
			$sanitized_input['legion_category'] = sanitize_text_field($_POST['legion_category']);
		}
		if(isset($_POST['legion_menu'])) {
			$sanitized_input['legion_menu'] = ($_POST['legion_menu']);
		}
		if(isset($_POST['legion_submenu'])) {
			$sanitized_input['legion_submenu'] = ($_POST['legion_submenu']);
		}
		return $sanitized_input;
	}

	public function legion_settings_section() {}
	/** 
	 * HTML for custom setting 1 input
	 */
	public function legion_setting1_html() {
		echo '<input type="checkbox" id="legion_debug" name="legion_debug" value="1"'.(isset($this->options['legion_debug']) ? " checked=\"checked\"" : '').' />';
	}
	public function legion_setting2_html() {
		echo '<input type="checkbox" id="legion_category" name="legion_category" value="1"'.(isset($this->options['legion_category']) ? " checked=\"checked\"" : '').' />';
	}

	private function showMenu($data) {
		$arr = array();
		foreach($data as $page => $true) {
			$arr[$page] = array($true, "", $page);
		}
		return $arr;
	}

	private function showSubMenu($data) {
		$arr = array();
		foreach($data as $page => $true) {
			$keys = array_keys($true);
			for($i=0;$i<sizeof($keys);$i++) {
				$arr[$page][$keys[$i]] = array($true[$keys[$i]], "", $keys[$i]);
			}
		}
		return $arr;
	}

	public function legion_setting3_html() {
		global $menu, $submenu;
		if(isset($this->options['legion_menu'])) {
			$menus = call_user_func_array(array($this, "showMenu"), array($this->options['legion_menu']));
		} else {
			$menus = array();
		}
		$menuShow = array_merge($menus, $menu);
		if(isset($this->options['legion_submenu'])) {
			$sub_menus = call_user_func_array(array($this, "showSubMenu"), array($this->options['legion_submenu']));
		} else {
			$sub_menus = array();
		}
		$subMenuShow = array_merge_recursive($sub_menus, $submenu);
		$keys = array_keys($menuShow);
		for($i=0;$i<sizeof($keys);$i++) {
			$page = (strpos($menuShow[$keys[$i]][2], "separator")===false ? $menuShow[$keys[$i]][2] : $menuShow[$keys[$i]][2]);
			echo '<div><input type="checkbox" id="legion_category" name="legion_menu['.$page.']" value="'.strip_tags($menuShow[$keys[$i]][0]).'"'.(isset($this->options['legion_menu'][$page]) ? " checked=\"checked\"" : '').' />'.(strpos($menuShow[$keys[$i]][2], "separator")!==false ? "Разделитель" : $menuShow[$keys[$i]][0]);
				if(isset($subMenuShow[$menuShow[$keys[$i]][2]])) {
					$skeys = array_keys($subMenuShow[$menuShow[$keys[$i]][2]]);
					for($z=0;$z<sizeof($subMenuShow[$menuShow[$keys[$i]][2]]);$z++) {
						$subpage = $subMenuShow[$menuShow[$keys[$i]][2]][$skeys[$z]][2];
						echo '<div><label for="'.$page."-".$subpage.'" class="submenu"><input type="checkbox" id="'.$page."-".$subpage.'" name="legion_submenu['.$page.']['.$subpage.']" value="'.strip_tags($subMenuShow[$menuShow[$keys[$i]][2]][$skeys[$z]][0]).'"'.(isset($this->options['legion_submenu'][$page][$subpage]) ? " checked=\"checked\"" : '').' />'.$subMenuShow[$menuShow[$keys[$i]][2]][$skeys[$z]][0]."</label></div>";
					}
				}
			echo "</div>";
			echo "<style>.submenu {margin-left: 2em;}</style>";
		}
	}
}
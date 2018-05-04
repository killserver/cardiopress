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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
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
	}

	public function sanitize($input) {
		$sanitized_input = array();
		if(isset($_POST['legion_debug'])) {
			$sanitized_input['legion_debug'] = sanitize_text_field($_POST['legion_debug']);
		}
		if(isset($_POST['legion_category'])) {
			$sanitized_input['legion_category'] = sanitize_text_field($_POST['legion_category']);
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
}
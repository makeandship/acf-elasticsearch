<?php

namespace makeandship\elasticsearch\wp;

class UIManager {

	const MENU_SPECIFICATION = array(
		'page_icon' => 'icon-themes',
		'page_title' => 'ACF Elasticsearch',
		'menu_title' => 'ACF Elasticsearch',
		'menu_icon' => 'dashicons-search',
		'page_slug' => 'wp/admin/index.php',
		'page_cap' => 'manage_options',
		'page_type' => 'menu',
		'page_parent' => '',
		'page_position' => 100
	);

	function __construct($version, $db_version) {
		$this->version = $version;
		$this->db_version = $db_version;
	}

	public function initialise_options() {
		error_log('initialise options');
		$multisite = is_multisite();
		$network_enabled = is_plugin_active_for_network(plugin_basename(__FILE__));
		error_log($multisite);
		error_log($network_enabled); 
		if ($multisite) { // && $network_enabled
			error_log('multisite');
			add_site_option('acf_elasticsearch_version', $this->version);
			add_site_option('acf_elasticsearch_db_version', $this->db_version);
			
			add_site_option('acf_elasticsearch_server', '');
			add_site_option('acf_elasticsearch_primary_index', '');
			add_site_option('acf_elasticsearch_secondary_index', '');

			add_site_option('acf_elasticsearch_read_timeout', 30);
			add_site_option('acf_elasticsearch_write_timeout', 30);
		}
		else {
			error_log('not multisite');
			add_option('acf_elasticsearch_version', $this->version);
			add_option('acf_elasticsearch_db_version', $this->db_version);

			add_option('acf_elasticsearch_server', '');
			add_option('acf_elasticsearch_primary_index', '');
			add_option('acf_elasticsearch_secondary_index', '');

			add_option('acf_elasticsearch_read_timeout', 30);
			add_option('acf_elasticsearch_write_timeout', 30);
		}
	}

	public function initialise_settings() {
		// add_settings_section( $id, $title, $callback, $page )
		add_settings_section(
			'acf_elasticsearch_settings',
			'Settings',
			array($this, 'render_section_settings'),
			'acf_elasticsearch_settings_page'
		);
		
		// add_settings_field( $id, $title, $callback, $page, $section, $args )
		add_settings_field(
			'acf_elasticsearch_server', 
			'Server', 
			array($this, 'render_option_server'), 
			'acf_elasticsearch_settings_page',
			'acf_elasticsearch_settings' 
			
		);
		add_settings_field(
			'acf_elasticsearch_primary_index', 
			'Primary Index', 
			array($this, 'render_option_primary_index'), 
			'acf_elasticsearch_settings_page',
			'acf_elasticsearch_settings'
		);
		add_settings_field(
			'acf_elasticsearch_secondary_index', 
			'Secondary Index', 
			array($this, 'render_option_secondary_index'), 
			'acf_elasticsearch_settings_page',
			'acf_elasticsearch_settings'
		);
		add_settings_field(
			'acf_elasticsearch_read_timeout', 
			'Read Timeout', 
			array($this, 'render_option_read_timeout'), 
			'acf_elasticsearch_settings_page',
			'acf_elasticsearch_settings' 
			
		);
		add_settings_field(
			'acf_elasticsearch_write_timeout', 
			'Write Timeout', 
			array($this, 'render_option_write_timeout'), 
			'acf_elasticsearch_settings_page',
			'acf_elasticsearch_settings'
		);
		
		// register_setting( $option_group, $option_name, $sanitize_callback )
		register_setting( 
			'acf_elasticsearch_settings', 
			'acf_elasticsearch_server', 
			array( $this, 'sanitize_server')
		);
		register_setting( 
			'acf_elasticsearch_settings', 
			'acf_elasticsearch_primary_index', 
			array( $this, 'sanitize_primary_index')
		);
		register_setting( 
			'acf_elasticsearch_settings', 
			'acf_elasticsearch_secondary_index', 
			array( $this, 'sanitize_secondary_index')
		);
		register_setting(
			'acf_elasticsearch_settings', 
			'acf_elasticsearch_read_timeout', 
			array( $this, 'sanitize_read_timeout')
		);
		register_setting( 
			'acf_elasticsearch_settings', 
			'acf_elasticsearch_write_timeout', 
			array( $this, 'sanitize_write_timeout')
		);

		add_settings_section(
			'acf_elasticsearch_mapping',
			'Mappings',
			array($this, 'render_section_mappings'),
			'acf_elasticsearch_mappings_page'
		);

		add_settings_section(
			'acf_elasticsearch_index',
			'Index',
			array($this, 'render_section_index'),
			'acf_elasticsearch_index_page'
		);

		/*
		// register_setting( $option_group, $option_name, $sanitize_callback )
		register_setting( 'multiple-sections-settings-group', 'test_multiple_sections_plugin_main_settings_arraykey', array($this, 'plugin_main_settings_validate') );
		
		// add_settings_section( $id, $title, $callback, $page )
		add_settings_section(
			'additional-settings-section',
			'Additional Settings',
			array($this, 'print_additional_settings_section_info'),
			'test-multiple-sections-plugin'
		);
		
		// add_settings_field( $id, $title, $callback, $page, $section, $args )
		add_settings_field(
			'another-setting', 
			'Another Setting', 
			array($this, 'create_input_another_setting'), 
			'test-multiple-sections-plugin', 
			'additional-settings-section'
		);
		
		// register_setting( $option_group, $option_name, $sanitize_callback )
		register_setting( 'multiple-sections-settings-group', 'test_multiple_sections_plugin_additonal_settings_arraykey', array($this, 'plugin_additional_settings_validate') );
		*/
	}

	public function initialise_menu() {
		
		$this->menu = add_options_page(
			UIManager::MENU_SPECIFICATION['page_title'], 
			UIManager::MENU_SPECIFICATION['menu_title'], 
			UIManager::MENU_SPECIFICATION['page_cap'], 
			UIManager::MENU_SPECIFICATION['page_slug'], 
			array(&$this, 'render_settings_page')
		);
	}

	public function render_settings_page() {
		/*
		$html = [
			'<div class="wrap">',
			'	<h1>ACF Elasticsearch</h1>',
			'	<div id="poststuff">',
			'		<form method="post" action="">',
			'			<div class="postbox">',
			'				<h2 class="hndle"><span>1. Configure your Elastic search server</span></h2>',
			'				<div class="inside acf-fields -left">',
			$this->render_settings_page_field( 
				'Server Address', 
				$this->render_field(
					'text', 
					'server_address',
					array( 'placeholder' => 'e.g. http://www.yourserver.com:9200/')
				)
			),
			$this->render_settings_page_field( 
				'Primary Index', 
				$this->render_field(
					'text', 
					'primary_index',
					array( 'placeholder' => '')
				)
			),
			$this->render_settings_page_field( 
				'Secondary Index', 
				$this->render_field(
					'text', 
					'secondary_index',
					array( 'placeholder' => '' )
				)
			),
			$this->render_settings_page_field( 
				'Read Timeout', 
				$this->render_field(
					'text', 
					'read_timeout',
					array( 'placeholder' => '30', 'class' => 'short' )
				)
			),
			$this->render_settings_page_field( 
				'Write Timeout', 
				$this->render_field(
					'text', 
					'write_timeout',
					array( 'placeholder' => '30', 'class' => 'short' )
				)
			),
			$this->render_settings_page_buttons([
				array('name' => 'Save', 'class' => 'button-primary')
			]),
			'				</div>',
			'			</div>',
			'			<div class="postbox">',
			'				<h2><span>2. Setup server mappings</span></h2>',
			'				<div class="inside acf-fields -left">',
			$this->render_settings_page_buttons([
				array('name' => 'Create mappings', 'class' => 'button')
			]),
			'				</div>',
			'			</div>',
			'			<div class="postbox">',
			'				<h2><span>3. Index the data</span></h2>',
			'				<div class="inside acf-fields -left">',
			$this->render_settings_page_buttons([
				array('name' => 'Index posts', 'class' => 'button'),
				array('name' => 'Clear index', 'class' => 'button')
			]),
			'				</div>',
			'			</div>',
			'	 	</form>',
			'	</div>',
			'</div>'
		];

		echo implode($html, PHP_EOL);*/
		include 'settings.php';
	}



	public function render_settings_page_field( $label, $field ) {
		$html = [
			'<div class="acf-elastic-row">',
			'	<div class="acf-elastic-label">',
			'		<label for="">'.$label.'</label>',
		    '	</div>',
			'	<div class="acf-elastic-field">',
			'		'.$field,
			'	</div>',
			'</div>'
		];

		return implode( $html, PHP_EOL );
	}

	public function render_settings_page_buttons( $buttons ) {
		$html = [];
		$html[] = '<div class="acf-elastic-row">';
		$html[] = '	<div class="acf-elastic-button-container">';

		foreach($buttons as $button) {
			$html[] = $this->render_settings_page_button( $button );
		}

		$html[] = '	</div>';
		$html[] = '</div>';

		return implode( $html, PHP_EOL );
	}

	public function render_settings_page_button( $button ) {
		$html = [
			'<input type="button" ',
			'	class="'.$button['class'].'"',
			'	name="'.$button['name'].'"',
			'	value="'.$button['name'].'"',
			'/>'
		];

		return implode( $html, PHP_EOL );
	}

	public function render_section_settings() {
		echo '<p>Something something</p>';
	}

	public function render_section_mappings() {
		echo '<p>Something something</p>';
	}

	public function render_section_index() {
		echo '<p>Something something</p>';
	}

	public function render_option_server() {
		$this->render_option( 'text', 'server', array(
			'placeholder' => 'e.g. http://www.yourserver.com:9200/',
			'class' => 'regular-text'
		));
	}

	public function render_option_primary_index() {
		$this->render_option( 'text', 'primary_index', array(
			'placeholder' => '',
			'class' => 'regular-text'
		));
	}

	public function render_option_secondary_index() {
		$this->render_option( 'text', 'secondary_index', array(
			'placeholder' => '',
			'class' => 'regular-text'
		));
	}

	public function render_option_read_timeout() {
		$this->render_option( 'text', 'read_timeout', array(
			'value' => 30
		));
	}

	public function render_option_write_timeout() {
		$this->render_option( 'text', 'write_timeout', array(
			'value' => 30
		));
	}

	public function render_option( $type, $name, $args ) {
		$value = null;
		$option = get_option( $name );

		if (isset($option) && empty($option)) {
			$value = $option;
		}
		else {
			if (array_key_exists('value', $args)) {
				$value = $args['value'];
			}
		}

		$clazz = isset($args) && array_key_exists('class', $args) ? $args['class'] : '';

		$html = [
			'<input type="text" ',
			'	class="'.$clazz.'"',
			'	name="'.$name.'"',
			'	value="'.$value.'"'
		];
		
		unset($args['value']);
		unset($args['class']);

		foreach($args as $key => $value) {
			$html[] = '	'.$key.'="'.$value.'"';
		}

		$html[] = '/>';
		

		echo implode( $html, PHP_EOL );
	}

	public function sanitize_server( $input ) {
		return esc_url( $input );
	}

	public function sanitize_primary_index( $input ) {
		return trim($input);
	}

	public function sanitize_secondary_index( $input ) {
		return trim($input);
	}

	public function sanitize_write_timeout( $input ) {
		return intval( $input );
	}

	public function sanitize_read_timeout( $input ) {
		return intval( $input );	
	}

	public function render_field( $type, $name, $attributes ) {
		$html = null;
		switch ($type) {
			case 'text': 
				$html = [
					'<input type="text" name="'.$name.'"',
				];

				foreach($attributes as $attr_name => $attr_value) {
					$html[] = ' '.$attr_name.'="'.$attr_value.'"';
				}

				$html[] = '>';

				$html = implode($html);
				break;
		}

		return $html;
	}

	public function enqueue_scripts() {
		
		$styles = plugins_url('acf-elasticsearch/css/style.css');
		$scripts = plugins_url('acf-elasticsearch/js/main.js');
		
		wp_register_style('acf-elasticsearch', $styles);
		wp_enqueue_style('acf-elasticsearch');
		wp_register_script('acf-elasticsearch', $scripts);
		wp_enqueue_script('acf-elasticsearch');
	}

}
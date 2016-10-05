<?php

namespace makeandship\elasticsearch;

require_once 'utils.php';
require_once 'wp/admin/ui-manager.php';
require_once 'makeandship/elasticsearch/indexer.php';
require_once 'makeandship/elasticsearch/mapper.php';
require_once __DIR__.'/../vendor/autoload.php';

class ACFElasticSearchPlugin {

	const VERSION = '0.0.1';
	const DB_VERSION = 1;

	const STATUS_PUBLISH = 'publish';
	const INDEX_POST_STATUSES = [ACFElasticSearchPlugin::STATUS_PUBLISH];

	const OPTION_SERVER = 'acf_elasticsearch_server';
	const OPTION_PRIMARY_INDEX = 'acf_elasticsearch_primary_index';
	const OPTION_SECONDARY_INDEX = 'acf_elasticsearch_secondary_index';
	const OPTION_READ_TIMEOUT = 'acf_elasticsearch_read_timeout';
	const OPTION_WRITE_TIMEOUT = 'acf_elasticsearch_write_timeout';

	public function __construct() {
		$this->indexer = new Indexer( $this->get_options() );

		$this->ui = new wp\UIManager(self::VERSION, self::DB_VERSION, $this);

		$this->multisite = (function_exists('is_multisite') && is_multisite());

		$this->plugin_dir = plugin_dir_path(__FILE__);
		$this->plugin_url = plugin_dir_url(__FILE__);

		$this->initialise_plugin_hooks();
		$this->initialise_index_hooks();
	}

	/**
	 * Get the current configuration.  Configuration values
	 * are cached.  Use the $fresh parameter to get an updated
	 * set 
	 *
	 * @param $fresh - true to get updated values
	 * @return array of options
	 */
	public function get_options( $fresh=false ) {

		if (!isset($this->options) || $fresh) {
			$this->options = array();

			$this->get_option( $this->options, ACFElasticSearchPlugin::OPTION_SERVER);
			$this->get_option( $this->options, ACFElasticSearchPlugin::OPTION_PRIMARY_INDEX);
			$this->get_option( $this->options, ACFElasticSearchPlugin::OPTION_SECONDARY_INDEX);
			$this->get_option( $this->options, ACFElasticSearchPlugin::OPTION_READ_TIMEOUT);
			$this->get_option( $this->options, ACFElasticSearchPlugin::OPTION_WRITE_TIMEOUT); 
		}
		
		return $this->options;
	}

	/**
	 * Add a single option to an options array.  Detects multisite
	 * and pulls from multisite options when it is 
	 *
	 * @param $options array (passed by reference)
	 * @param $name the option name
	 */ 
	private function get_option( &$options, $name ) {
		if (!isset($options)) {
			$options = array();
		}

		if (is_multisite()) {
			$options[$name] = get_site_option($name);
		}
		else {
			$options[$name] = get_option($name);
		}
	}

	public function initialise_plugin_hooks() {
		// wordpress initialisation
		add_action( 'admin_init', array( $this, 'initialise') );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts') );
		add_action( 'admin_menu', array($this, 'initialise_menu'));

		// activation
		register_activation_hook( __FILE__, array($this, 'activate' ));
		register_deactivation_hook( __FILE__, array($this, 'deactivate' ));
	}

	public function initialise_index_hooks() {
		// plugin
		add_action('wp_ajax_create_mappings', array(&$this, 'create_mappings'));
		add_action('wp_ajax_index_posts', array(&$this, 'index_posts'));
		add_action('wp_ajax_index_taxonomies', array(&$this, 'index_taxonomies'));
		add_action('wp_ajax_clear_index', array(&$this, 'clear_index'));

		// posts
		add_action('save_post', array(&$this, 'save_post'));
		add_action('delete_post', array(&$this, 'delete_post'));
		add_action('trash_post', array(&$this, 'delete_post'));
		add_action('transition_post_status', array(&$this, 'transition_post_status'), 10, 3);

		// taxonomies
		add_action('create_term', array(&$this, 'create_term'), 10, 3);
		add_action('edit_term', array(&$this, 'edit_term'), 10, 3);
		add_action('delete_term', array(&$this, 'delete_term'), 10, 3);
		add_action('registered_taxonomy', array(&$this, 'registered_taxonomy'), 10, 3);
	}

	/**
	 * -------------------
	 * Index Administration
	 * -------------------
	 */
	function create_mappings() {
		$options = $this->get_options();

		if (isset($options) && array_key_exists(ACFElasticSearchPlugin::OPTION_PRIMARY_INDEX, $options)) {
			$primary_index = $options[ACFElasticSearchPlugin::OPTION_PRIMARY_INDEX];

			// (re)create the index
			$indexer = new Indexer( $options );
			$indexer->create( $primary_index );

			// initialise the mapper with config
			$mapper = new Mapper( $options );
			$result = $mapper->map();
		}
		else {

		}

		$json = json_encode(array(
			'message' => 'Mappings were created successfully'
			));
		die($json);
	}

	function index_posts() {
		$json = json_encode(array(
			'message' => 'Posts were indexed successfully'
			));
		die($json);
	}

	function index_taxonomies() {
		$json = json_encode(array(
			'message' => 'Taxonomies were indexed successfully'
			));
		die($json);
	}

	function clear_index() {
		$json = json_encode(array(
			'message' => 'Index was cleared successfully'
			));
		die($json);
	}

	/**
	 * -------------------
	 * Index Lifecycle
	 * -------------------
	 */

	/**
	 *
	 */
	function save_post( $post_id ) {
		// get the post to index
		if (is_object($post_id)) {
			$post = $post_id;
		} 
		else {
			$post = get_post($post_id);
		}

		// can't index empty posts
		if ($post == null) {
			return;
		}

		// check post is a valid type to index
		if (!$this->should_index_post( $post )) {
			return;
		}

		// index valid statuses
		if (in_array($post->post_status, ACFElasticSearchPlugin::INDEX_POST_STATUSES)) {
			// index
			$this->indexer->add_or_update( $post );
		}
		else {
			// remove
			$this->indexer->remove( $post );
		}
	}

	/**
	 *
	 */
	function delete_post( $post_id ) {

	}

	/**
	 *
	 */
	function trash_post( $post_id ) {
		$this->delete_post ( $post_id );
	}

	/**
	 *
	 */
	function transition_post_status( $new_status, $old_status, $post ) {
		if ($new_status != ACFElasticSearchPlugin::STATUS_PUBLISH && $new_status != $old_status) {
			$this->indexer->add_or_update( $post );
		}
	}

	/**
	 * 
	 */
	function should_index_post( $post ) {
		return true;	
	}

	/**
	 * 
	 */
	function create_term( $term_id, $tt_id, $taxonomy ) {
		return true;	
	}

	/**
	 * 
	 */
	function edit_term( $term_id, $tt_id, $taxonomy ) {
		return true;	
	}

	/**
	 * 
	 */
	function delete_term( $term_id, $tt_id, $taxonomy ) {
		return true;	
	}

	/**
	 * 
	 */
	function registered_taxonomy( $taxonomy, $object_type, $args ) {
		return true;	
	}

	/**
	 * ---------------------
	 * Plugin Initialisation
	 * ---------------------
	 */
	public function initialise() {
		error_log('initialise');
		$this->ui->initialise_options();
		$this->ui->initialise_settings();
	}

	public function initialise_menu() {
		// show a menu
		$this->ui->initialise_menu();
	}

	public function admin_enqueue_scripts() {
		error_log('admin_enqueue_scripts');
		// add custom css and js
		$this->ui->enqueue_scripts();
	}

	public function activate() {
		$this->initialise_options();

		register_uninstall_hook( __FILE__, array($this, 'uninstall' ));
	}

	public function deactivate() {

	}

	public function uninstall() {

	}

}

new ACFElasticSearchPlugin();
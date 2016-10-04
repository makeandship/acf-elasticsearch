<?php

namespace makeandship\elasticsearch;

require 'utils.php';
require 'wp/admin/ui-manager.php';
require 'makeandship/elasticsearch/indexer.php';

class ACFElasticSearchPlugin {

	const VERSION = '0.0.1';
	const DB_VERSION = 1;

	const STATUS_PUBLISH = 'publish';
	const INDEX_POST_STATUSES = [ACFElasticSearchPlugin::STATUS_PUBLISH];

	public function __construct() {
		$this->indexer = new Indexer();

		$this->ui = new wp\UIManager(self::VERSION, self::DB_VERSION);

		$this->multisite = (function_exists('is_multisite') && is_multisite());

		$this->plugin_dir = plugin_dir_path(__FILE__);
		$this->pligin_url = plugin_dir_url(__FILE__);

		$this->initialise_plugin_hooks();
		$this->initialise_index_hooks();
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
		add_action('wp_ajax_esreindextaxonomies', array(&$this, 'reindex_taxonomies'));
		add_action('wp_ajax_esreindex', array(&$this, 'reindex'));
		add_action('wp_ajax_esswap', array(&$this, 'swap'));

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
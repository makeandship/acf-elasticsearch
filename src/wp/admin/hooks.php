<?php

namespace makeandship\elasticsearch;

class Hooks {
	const STATUS_PUBLISH = 'publish';
	const INDEX_POST_STATUSES = [STATUS_PUBLISH];

	function __construct() {
		$this->initialise_hooks();

		$this->indexer = new Indexer();
	}	

	function initialise_hooks() {
		// plugin
		add_action('wp_ajax_esreindextaxonomies', array(&$this, 'reindex_taxonomies'));
		add_action('wp_ajax_esreindex', array(&$this, 'reindex'));
		add_action('wp_ajax_esswap', array(&$this, 'swap'));

		// posts
		add_action('save_post', array(&$this, 'save_post'));
		add_action('delete_post', array(&$this, 'delete_post'));
		add_action('trash_post', array(&$this, 'delete_post'));
		add_action('transition_post_status', array(&$this, 'transition_post'), 10, 3);

		// taxonomies
		add_action('create_term', array(&$this, 'create_term'), 10, 3);
		add_action('edit_term', array(&$this, 'edit_term'), 10, 3);
		add_action('delete_term', array(&$this, 'delete_term'), 10, 3);
		add_action('registered_taxonomy', array(&$this, 'registered_taxonomy'), 10, 3);
	}

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
		if (in_array($post->post_status, INDEX_POST_STATUSES)) {
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
		if ($new_status != STATUS_PUBLISH && $new_status != $old_status) {
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


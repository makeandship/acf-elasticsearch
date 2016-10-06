<?php

namespace makeandship\elasticsearch;

require_once 'mapping_builder.php';

class PostMappingBuilder extends MappingBuilder {

	const EXCLUDE_POST_TYPES = array(
		'revision',
		'attachment',
		'json_consumer',
		'nav_menu',
		'nav_menu_item',
		'post_format',
		'link_category',
		'acf-field-group',
		'acf-field'
	);

	const CORE_FIELDS = array(
		'post_content' => array( 
			'type' => 'string', 
			'suggest' => true 
		),
		'post_title' => 'string',
		'post_type' => array( 
			'type' => 'string', 
			'index' => 'not_analyzed' 
		),
		'post_date' => 'date',
		'link' => array(
			'type' => 'string',
			'index' => 'not_analysed'
		)
	);

	const CORE_DATE_FIELDS = array(
	);

	/**
	 *
	 */
	public function build ( $post_type ) {
		error_log($post_type.' is '.$this->valid($post_type));
		if (!$this->valid( $post_type )) {
			return null;
		}

		$properties = array();

		// base post fields
		foreach( PostMappingBuilder::CORE_FIELDS as $field => $options) {
			if (isset( $field ) && isset($options)) {
				$properties = array_merge( 
					$properties, 
					$this->build_field( $field, $options ) 
				);	
			}
		}

		// acf fields
		if( class_exists('acf') ) {
			$field_groups = acf_get_field_groups();
		}

		return $properties;
	}

	public function valid( $post_type ) {
		if (in_array( $post_type, self::EXCLUDE_POST_TYPES)) {
			return false;
		}
		return true;
	}

	// TODO right place?
	public function get_valid_post_types() {
		$post_types = get_post_types(array(
			'public' => true
		));
		
		$valid_post_types = array();
		foreach($post_types as $post_type) {
			if ($this->valid($post_type)) {
				$valid_post_types[] = $post_type;
			}
		}

		return $valid_post_types;
	}

	private function build_field( $field, $options ) {
		$properties = array();

		if (isset( $field ) && isset( $options )) {
			// settings
			if (is_string($options)) {
				$type = $options;
				$index = 'analyzed';
				$suggest = null;
			}
			else {
				if (array_key_exists('type', $options)) {
					$type = $options['type'];
				}
				if (array_key_exists('index', $options)) {
					$index = $options['index'];
				}
				else{
					$index = 'analyzed';
				}
				if (array_key_exists('suggest', $options)) {
					$suggest = $options['suggest'];
				}
			}

			$properties[$field] = array(
				'type' => $type,
				'index' => $index
			);

			if (isset($suggest)) {
				$properties[$field.'_suggest'] = array(
					'type' => 'string',
					'analyzer' => 'ngram_analyzer',
	    			'search_analyzer' => 'whitespace_analyzer',
				);
			}
		}

		return $properties;
	}
}
<?php

namespace makeandship\elasticsearch;

require_once 'mapping_builder.php';

class PostMappingBuilder extends MappingBuilder {

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
		'post_date' => 'date'
	);

	const CORE_DATE_FIELDS = array(
	);

	/**
	 *
	 */
	public function build ( $post_type ) {

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
			
		}

		return $properties;
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
<?php

namespace makeandship\elasticsearch;

class PostMappingBuilder extends MappingBuilder {

	const CORE_FIELDS = array(
		'post_content' => { 'type' => 'string', 'suggest' => true },
		'post_title' => 'string',
		'post_type' => { 'type' => 'string', 'index' => 'not_analyzed' }
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
		foreach( POST_FIELDS as $field => $options) {
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
				$type = $options['type'];
				$index = $options['index'];
				$suggest = $options['suggest'];
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
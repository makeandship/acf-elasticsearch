<?php

namespace makeandship\elasticsearch;

require_once 'mapping_builder.php';

class TermMappingBuilder extends MappingBuilder {

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

	/**
	 *
	 */
	public function build ( $taxonomy ) {
		$properties = array();

		// TODO implement (this is post)

		// base post fields
		foreach( TermMappingBuilder::CORE_FIELDS as $field => $options) {
			if (isset( $field ) && isset($options)) {
				$properties = array_merge( 
					$properties, 
					$this->build_field( $field, $options ) 
				);	
			}
		}

		return $properties;
	}

	function build_field( $field, $options) {
		$properties = array();

		if (isset( $field ) && isset( $options )) {
			$properties = array( 
				'name_suggest' => array(
					'analyzer' => 'ngram_analyzer',
					'search_analyzer' => 'whitespace_analyzer',
					'type' => 'string'
				),
				'name' => array(
					'type' => 'string'
				),
				'slug' => array( 
					'type' => 'string',
					'index' => 'not_analyzed'
					)
			);
		}

		return $properties;
	}
}
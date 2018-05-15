<?php

namespace makeandship\elasticsearch;

class TermMappingBuilderV6 extends MappingBuilder {

	const EXCLUDE_TAXONOMIES = array(
		'nav_menu',
		'post_format',
		'link_category',
	);

	const CORE_FIELDS = array(
		'type' => array(
            'type' => 'keyword',
            'index' => true
        ),
		'name' => array(
			'type' => 'text', 
			'suggest' => true
		),
		'slug' => array(
			'type' => 'keyword',
			'index' => true
		),
	);

	/**
	 *
	 */
	public function build ( $taxonomy ) {
		$properties = array();
		
		if (!$this->valid( $taxonomy )) {
			return array();
		}

		// TODO implement (this is post)

		// base post fields
		foreach( TermMappingBuilderV6::CORE_FIELDS as $field => $options) {
			if (isset( $field ) && isset($options)) {
				$properties = array_merge( 
					$properties, 
					$this->build_field( $field, $options ) 
				);	
			}
		}

		return $properties;
	}

	public function valid( $taxonomy ) {
		if (in_array( $taxonomy, self::EXCLUDE_TAXONOMIES)) {
			return false;
		}
		return true;
	}

	function build_field( $field, $options) {
		$properties = array();

		if (isset( $field ) && isset( $options )) {
			$properties = array( 
				'name_suggest' => array(
					'analyzer' => 'ngram_analyzer',
					'search_analyzer' => 'whitespace_analyzer',
					'type' => 'text'
				),
				'name' => array(
					'type' => 'text'
				),
				'slug' => array( 
					'type' => 'keyword',
					'index' => true
				)
			);
		}

		return $properties;
	}
}
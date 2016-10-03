<?php

namespace makeandship\elasticsearch;

class TermMappingBuilder extends MappingBuilder {

	const CORE_FIELDS = array(
		'post_content' => { 'type' => 'string', 'suggest' => true },
		'post_title' => 'string',
		'post_type' => { 'type' => 'string', 'index' => 'not_analyzed' }
		'post_date' => 'date'
	);

	/**
	 *
	 */
	public function build ( $taxonomy ) {
		$properties = array();

		// base post fields
		foreach( CORE_FIELDS as $field => $options) {
			if (isset( $field ) && isset($options)) {
				$properties = array_merge( 
					$properties, 
					$this->build_field( $field, $options ) 
				);	
			}
		}

		$type = $index->getType( $type_name );
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

			$mapping = new \Elastica\Type\Mapping($type, $properties);
			$mapping->send();
		}
	}
}
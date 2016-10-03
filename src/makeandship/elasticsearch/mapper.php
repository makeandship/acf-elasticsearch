<?php

namespace makeandship\elasticsearch;

class Mapper {
	public function __construct() {
		$this->mapping_builder_factory = new MappingBuilderFactory();
	}

	public function map() {
		// create mappings for each post type
		$post_types = get_post_types();
		foreach( $post_types as $post_type ) {
			$this=>map_post_type( $post_type );
		}

		// create mappings for each taxonomy
		$taxonomies = get_taxonomies();
		foreach( $taxonomies as $taxonomy ) {
			$this->map_taxonomy( $taxonomy );
		}
	}

	public function map_post_type( $post_type ) {

		$builder = new PostMappingBuilder();
		$properties = $builder->build( $post_type );

		if (isset($properties)) {

			$mapping = new \Elastica\Type\Mapping( $post_type, $properties );
			$mapping->send();

		}
	}

	public function map_taxonomy( $taxonomy ) {
		$builder = new TermMappingBuilder();
		$properties = $builder->build( $taxonomy );

		if (isset($properties)) {

			$mapping = new \Elastica\Type\Mapping( $post_type, $properties );
			$mapping->send();

		}
	}
}
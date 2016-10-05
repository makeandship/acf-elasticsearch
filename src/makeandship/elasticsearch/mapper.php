<?php

namespace makeandship\elasticsearch;

require_once 'mapping_builder_factory.php';
require_once 'type_factory.php';

use \Elastica\Type\Mapping;

class Mapper {
	public function __construct( $config ) {
		$this->config = $config;

		// factory to manage individual mappers
		$this->mapping_builder_factory = new MappingBuilderFactory();

		// factory to manage types
		$this->type_factory = new TypeFactory( $this->config );
	}

	public function map() {
		// create mappings for each post type
		$post_types = get_post_types();
		foreach( $post_types as $post_type ) {
			$this->map_post_type( $post_type );
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

			$type = $this->type_factory->create($post_type);

			$mapping = new Mapping( $type, $properties );
			$mapping->send();

		}
	}

	public function map_taxonomy( $taxonomy ) {
		$builder = new TermMappingBuilder();
		$properties = $builder->build( $taxonomy );

		if (isset($properties)) {
			$type = $this->type_factory->create($taxonomy);

			$mapping = new Mapping( $type, $properties );
			$mapping->send();

		}
	}
}
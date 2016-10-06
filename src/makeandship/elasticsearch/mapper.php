<?php

namespace makeandship\elasticsearch;

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
		$post_types = get_post_types(array(
			'public' => true
		));
		foreach( $post_types as $post_type ) {
			$this->map_type( 
				new PostMappingBuilder(),
				$post_type
			);
		}

		// create mappings for each taxonomy
		$taxonomies = get_taxonomies();
		foreach( $taxonomies as $taxonomy ) {
			$this->map_type( 
				new TermMappingBuilder(),
				$taxonomy
			);
		}

		// create mappings for sites if this is a multisite
		if (is_multisite()) {
			$this->map_type( 
				new SiteMappingBuilder(),
				'sites'
			);
		}
	}

	private function map_type( $builder, $type_name=null ) {
		if (isset($builder)) {
	 		$properties = $builder->build( $type_name );

			if (isset($properties)) {
				$type = $this->type_factory->create($type_name);

				$mapping = new Mapping( $type, $properties );
				$mapping->send();
			}
		}
	}
}
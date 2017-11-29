<?php

namespace makeandship\elasticsearch;

use \Elastica\Type\Mapping;

class Mapper
{
    public function __construct()
    {
        // factory to manage individual mappers
        $this->mapping_builder_factory = new MappingBuilderFactory();

        // factory to manage types
        $this->type_factory = new TypeFactory();
    }

    public function map()
    {
        // create mappings for each post type
        $post_types = get_post_types(array(
            'public' => true
        ));
        foreach ($post_types as $post_type) {
            $this->map_type(
                new PostMappingBuilder(),
                $post_type
            );
        }

        // create mappings for each taxonomy
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $taxonomy) {
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

    private function map_type($builder, $type_name=null)
    {
        if (isset($builder)) {
            $properties = $builder->build($type_name);

            if (isset($properties)) {
                // create mappings for the public primary index if required
                $primary_type = $this->type_factory->create($type_name, false, false, true);
                if ($primary_type) {
                    $mapping_primary = new Mapping($primary_type, $properties);
                    $mapping_primary->send();
                }

                // create mappings for the private primary index if required
                $private_primary_type = $this->type_factory->create($type_name, false, true, true);
                if ($private_primary_type) {
                    $mapping_private_primary = new Mapping($private_primary_type, $properties);
                    $mapping_private_primary->send();
                }

                // create mappings for the public secondary index if required
                $secondary_type = $this->type_factory->create($type_name, false, false, false);
                if ($secondary_type) {
                    $mapping_secondary = new Mapping($secondary_type, $properties);
                    $mapping_secondary->send();
                }

                // create mappings for the private secondary index if required
                $private_secondary_type = $this->type_factory->create($type_name, false, true, false);
                if ($private_secondary_type) {
                    $mapping_private_secondary = new Mapping($private_secondary_type, $properties);
                    $mapping_private_secondary->send();
                }
            }
        }
    }
}

<?php

namespace makeandship\elasticsearch;

use \Elastica\Type\Mapping;
use makeandship\elasticsearch\settings\SettingsManager;

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
        $post_types = $this->post_types = SettingsManager::get_instance()->get_post_types();

        // mapping builder
        $post_mapping_builder = $this->mapping_builder_factory->create('WP_Post');
        $term_mapping_builder = $this->mapping_builder_factory->create('WP_Term');
        $site_mapping_builder = $this->mapping_builder_factory->create('WP_Site');

        $properties = array();

        foreach ($post_types as $post_type) {
            $properties = array_merge(
                $properties,
                $post_mapping_builder->build($post_type, true)
            );
        }

        //error_log(json_encode($mapping));

        // create mappings for each taxonomy
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            $properties = array_merge(
            $properties,
            $term_mapping_builder->build($taxonomy)
          );
        }

        // create mappings for sites if this is a multisite
        if (is_multisite()) {
            $properties = array_merge(
            $properties,
            $site_mapping_builder->build()
          );
        }

        $properties = apply_filters('acf_elasticsearch_pre_create_mappings', $properties);
        $this->create_mapping($properties);
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

    private function create_mapping($properties, $type_name=Constants::DEFAULT_MAPPING_TYPE)
    {
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

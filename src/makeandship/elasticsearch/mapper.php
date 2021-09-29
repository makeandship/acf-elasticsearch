<?php

namespace makeandship\elasticsearch;

use makeandship\elasticsearch\settings\SettingsManager;
use \Elastica\Mapping;

class Mapper
{
    public function __construct()
    {
        $this->properties = null;

        // factory to manage individual mappers
        $this->mapping_builder_factory = new MappingBuilderFactory();
    }

    public function map($index)
    {
        if ($index) {
            $properties = $this->create_properties();

            return $this->create_mapping($index, $properties);
        }

        return null;
    }

    private function create_properties()
    {
        if ($this->properties) {
            return $this->properties;
        }

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

        foreach ($post_types as $post_type) {
            $properties = array_merge(
                $properties,
                $post_mapping_builder->build_templates($post_type, true)
            );
        }

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

        $properties = Util::apply_filters('pre_create_mappings', $properties);

        $this->properties = $properties;

        return $properties;
    }

    private function create_mapping($index, $properties)
    {
        if (isset($properties)) {
            $mapping_timeout = intval(SettingsManager::get_instance()->get(Constants::OPTION_MAPPING_TIMEOUT));
            $params          = array('master_timeout' => $mapping_timeout . 's');
            // create mappings for the public primary index if required
            $mapping = new Mapping($properties);
            $mapping->send($index, $params);
        }
    }
}

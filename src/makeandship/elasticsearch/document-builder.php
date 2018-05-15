<?php

namespace makeandship\elasticsearch;

use \Elastica\Client;
use makeandship\elasticsearch\settings\SettingsManager;

abstract class DocumentBuilder
{
    abstract public function build($o, $include_private=false);
    abstract public function is_private($o);
    abstract public function has_private_fields();
    abstract public function is_indexable($o);
    
    public function get_core_fields($type) {
        // read elasticsearch version
		$version = $this->get_elasticseach_version();
		$use_v6 = version_compare($version,  "6.0");
		
		if ($type == 'WP_Post' && $use_v6 >= 0) {
			return PostMappingBuilderV6::CORE_FIELDS;
		}
		else if ($type == 'WP_Post' && $use_v6 < 0) {
			return PostMappingBuilderV5::CORE_FIELDS;
		}
		else if ($type == 'WP_Term' && $use_v6 >= 0) {
			return TermMappingBuilderV6::CORE_FIELDS;
		}
		else if ($type == 'WP_Term' && $use_v6 < 0) {
			return TermMappingBuilderV5::CORE_FIELDS;
		}
		else if ($type == 'WP_Site') {
			return SiteMappingBuilder::CORE_FIELDS;
		}

		return array();
    }

    private function get_elasticseach_version()
    {
        $client_settings = SettingsManager::get_instance()->get_client_settings();
        $client = new Client($client_settings);
        return $client->getVersion();
    }
}

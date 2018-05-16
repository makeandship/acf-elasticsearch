<?php

namespace makeandship\elasticsearch;

use \Elastica\Client;
use makeandship\elasticsearch\settings\SettingsManager;

class MappingBuilderFactory {
	
	public static final function create( $type ) {
		// read elasticsearch version
		$version = self::get_elasticseach_version();
		$use_v6 = version_compare($version,  "6.0") >= 0 ;
		
		if ($type == 'WP_Post' && $use_v6) {
			return new PostMappingBuilderV6();
		}
		else if ($type == 'WP_Post') {
			return new PostMappingBuilderV5();
		}
		else if ($type == 'WP_Term' && $use_v6) {
			return new TermMappingBuilderV6();
		}
		else if ($type == 'WP_Term') {
			return new TermMappingBuilderV5();
		}
		else if ($type == 'WP_Site') {
			return new SiteMappingBuilder();
		}

		return null;
	}

	private static function get_elasticseach_version()
    {
        return SettingsManager::get_instance()->get(Constants::OPTION_ELASTICSEARCH_VERSION);
    }
}
<?php

namespace makeandship\elasticsearch;

require_once 'post_mapping_builder.php';
require_once 'term_mapping_builder.php';
require_once 'site_mapping_builder.php';

class MappingBuilderFactory {
	
	public static final function create( $o ) {
		if (is_a( $o, 'WP_Post')) {
			return new PostMappingBuilder();
		}
		else if (is_a( $o, 'WP_Term')) {
			return new TermMappingBuilder();
		}
		else if (is_a( $o, 'WP_Site')) {
			return new SiteMappingBuilder();
		}

		return null;
	}
}
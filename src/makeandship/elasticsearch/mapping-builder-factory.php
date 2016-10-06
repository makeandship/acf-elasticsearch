<?php

namespace makeandship\elasticsearch;

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
<?php

namespace makeandship\elasticsearch;

class MappingBuilderFactory {
	
	public static final create( $o ) {
		if (is_a( $o, 'WP_Post')) {
			return new PostMappingBuilder();
		}
		else if (is_a( $o, 'WP_Term')) {
			return new TermMappingBuilder();
		}

		return null;
	}
}
<?php

namespace makeandship\elasticsearch;

abstract class TermMappingBuilder extends MappingBuilder
{

    const EXCLUDE_TAXONOMIES = array(
		'nav_menu',
		'post_format',
		'link_category',
    );
    
	public function valid( $taxonomy ) {
		if (in_array( $taxonomy, TermMappingBuilder::EXCLUDE_TAXONOMIES)) {
			return false;
		}
		return true;
	}

}
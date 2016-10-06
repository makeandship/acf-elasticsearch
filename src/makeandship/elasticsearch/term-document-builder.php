<?php

namespace makeandship\elasticsearch;

class TermDocumentBuilder extends DocumentBuilder {
	
	/**
	 * 
	 */
	public function build( $term ) {

	}

	/**
	 *
	 */
	public function get_id( $term ) {
		return $term->term_id;
	}
}
<?php

class TermDocumentBuilder extends Builder {
	
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
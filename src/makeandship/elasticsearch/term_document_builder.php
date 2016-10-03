<?php

namespace makeandship\elasticsearch;

check_and_require( 'DocumentBuilder', dirname( __FILE__ ) . '/document_builder.php' );

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
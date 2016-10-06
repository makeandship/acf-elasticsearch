<?php

namespace makeandship\elasticsearch;

check_and_require( 'DocumentBuilder', dirname( __FILE__ ) . '/document_builder.php' );

class PostDocumentBuilder extends DocumentBuilder {

	/**
	 *
	 */
	public function build ( $post ) {

	}

	/**
	 *
	 */
	public function get_id( $post ) {
		return $post->ID;
	}
}
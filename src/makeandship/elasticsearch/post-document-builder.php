<?php

namespace makeandship\elasticsearch;

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
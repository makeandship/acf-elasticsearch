<?php

class PostDocumentBuilder extends Builder {

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
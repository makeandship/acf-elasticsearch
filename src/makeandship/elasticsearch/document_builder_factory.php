<?php

namespace makeandship\elasticsearch;

check_and_require( 'PostDocumentBuilder', dirname( __FILE__ ) . '/post_document_builder.php' );
check_and_require( 'TermDocumentBuilder', dirname( __FILE__ ) . '/term_document_builder.php' );

class DocumentBuilderFactory {
	
	public static final function create( $o ) {
		if (is_a( $o, 'WP_Post')) {
			return new PostDocumentBuilder();
		}
		else if (is_a( $o, 'WP_Term')) {
			return new TermDocumentBuilder();
		}

		return null;
	}
}
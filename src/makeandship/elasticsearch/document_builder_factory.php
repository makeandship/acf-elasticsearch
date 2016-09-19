<?php

class DocumentBuilderFactory {
	
	public static final create( $o ) {
		if (is_a( $o, 'WP_Post')) {
			return new PostDocumentBuilder();
		}
		else if (is_a( $o, 'WP_Term')) {
			return new TermDocumentBuilder();
		}

		return null;
	}
}
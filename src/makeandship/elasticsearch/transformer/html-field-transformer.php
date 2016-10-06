<?php

namespace makeandship\elasticsearch\transformer;

class HtmlFieldTransformer  extends FieldTransformer {	
	public function transform( $value ) {
		return strip_tags( $value );
	}
}
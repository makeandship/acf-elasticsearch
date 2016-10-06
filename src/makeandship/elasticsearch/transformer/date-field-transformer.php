<?php

namespace makeandship\elasticsearch\transformer;

class DateFieldTransformer extends FieldTransformer {	
	public function transform( $value ) {
		return date('c', strtotime( $value ));
	}
}
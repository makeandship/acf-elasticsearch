<?php

namespace makeandship\elasticsearch\transformer;

abstract class DateFieldTransformer {
	
	public function transform( $value ) {
		return date('c', strtotime( $value ));
	}
}
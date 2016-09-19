<?php

abstract class DateFieldTransformer {
	
	public function transform( $value ) {
		return date('c', strtotime( $value ));
	}
}
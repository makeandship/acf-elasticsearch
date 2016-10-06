<?php

namespace makeandship\elasticsearch\transformer;

abstract class FieldTransformer {
	
	abstract function transform( $value );
	
}
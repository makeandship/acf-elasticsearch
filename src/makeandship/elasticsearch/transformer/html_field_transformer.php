<?php

abstract class HtmlFieldTransformer {
	
	public function transform( $value ) {
		return strip_tags( $value );
	}
}
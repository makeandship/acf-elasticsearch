<?php

function check_and_require( $clazz, $path ) {
	if ( !class_exists( $clazz ) ) {
	    require_once $path;
	}	
}
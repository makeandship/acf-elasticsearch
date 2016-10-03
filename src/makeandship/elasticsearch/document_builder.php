<?php

namespace makeandship\elasticsearch;

abstract class DocumentBuilder {
	abstract function build( $o );
}
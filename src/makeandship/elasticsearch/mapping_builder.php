<?php

namespace makeandship\elasticsearch;

abstract class MappingBuilder {
	abstract function build( $o );
}
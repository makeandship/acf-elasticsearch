<?php

namespace makeandship\elasticsearch;

abstract class DocumentBuilder
{
    abstract public function build($o, $include_private=false);
    abstract public function is_private($o);
    abstract public function has_private_fields();
}

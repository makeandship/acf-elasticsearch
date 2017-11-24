<?php

namespace makeandship\elasticsearch;

abstract class DocumentBuilder
{
    abstract public function build($o);
    abstract public function is_private($o);
}

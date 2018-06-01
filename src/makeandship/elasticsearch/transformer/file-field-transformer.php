<?php

namespace makeandship\elasticsearch\transformer;

use makeandship\elasticsearch\Util;

class FileFieldTransformer extends FieldTransformer
{
    public function transform($value)
    {
        if ($value && is_array($value)) {
            return Util::safely_get_attribute($value, 'title');
        }
        return null;
    }
}

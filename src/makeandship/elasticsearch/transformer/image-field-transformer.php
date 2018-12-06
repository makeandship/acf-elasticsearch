<?php

namespace makeandship\elasticsearch\transformer;

use makeandship\elasticsearch\Util;

class ImageFieldTransformer extends FieldTransformer
{
    public function transform($value)
    {
        $image = array(
          'id' => Util::safely_get_attribute($value, 'ID'),
          'filename' => Util::safely_get_attribute($value, 'filename'),
          'filesize' => Util::safely_get_attribute($value, 'filesize'),
          'alt' => Util::safely_get_attribute($value, 'alt'),
          'url' => Util::safely_get_attribute($value, 'url'),
          'description' => Util::safely_get_attribute($value, 'description'),
          'caption' => Util::safely_get_attribute($value, 'caption'),
          'mime' => Util::safely_get_attribute($value, 'mime_type'),
          'type' => Util::safely_get_attribute($value, 'type'),
          'subtype' => Util::safely_get_attribute($value, 'subtype'),
          'width' => Util::safely_get_attribute($value, 'width'),
          'height' => Util::safely_get_attribute($value, 'height')
        );

        $sizes = Util::safely_get_attribute($value, 'sizes');

        if ($sizes && is_array($sizes)) {
            $thumbnails = array();
            foreach ($sizes as $size_key => $size_value) {
                $name = $size_key;
                if (strpos($size_key, '-') !== false) {
                    $parts = explode("-", $size_key);

                    $name = $parts[0];
                    $key = $parts[1];

                    $thumbnails[$name][$key] = $size_value;
                } else {
                    $thumbnails[$name] = array(
                      'url' => $size_value
                    );
                }
            }

            $image['sizes'] = $thumbnails;
        }

        return $image;
    }
}

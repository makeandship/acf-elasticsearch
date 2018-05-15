<?php

namespace makeandship\elasticsearch;

abstract class PostMappingBuilder extends MappingBuilder
{

const EXCLUDE_TAXONOMIES = array(
  'post_tag',
  'post_format'
);

const EXCLUDE_POST_TYPES = array(
  'revision',
  'attachment',
  'json_consumer',
  'nav_menu',
  'nav_menu_item',
  'post_format',
  'link_category',
  'acf-field-group',
  'acf-field'
);

const CORE_DATE_FIELDS = array(
);

private static function valid($post_type)
{
    if (in_array($post_type, PostMappingBuilder::EXCLUDE_POST_TYPES)) {
        return false;
    }
    return true;
}

// TODO right place?
public static function get_valid_post_types()
{
    $post_types = get_post_types(array(
        'public' => true
    ));
    
    $valid_post_types = array();
    foreach ($post_types as $post_type) {
        if (self::valid($post_type)) {
            $valid_post_types[] = $post_type;
        }
    }

    return $valid_post_types;
}

}
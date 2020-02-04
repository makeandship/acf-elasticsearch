<?php

namespace makeandship\elasticsearch;

class MappingBuilderFactory
{

    final public static function create($type)
    {
        switch ($type) {
            case 'WP_Post':
                return new PostMappingBuilder();
                break;

            case 'WP_Term':
                return new TermMappingBuilderV6();
                break;

            case 'WP_Site':
                return new SiteMappingBuilder();
                break;

            default:
                # code...
                break;

        }

        return null;
    }
}

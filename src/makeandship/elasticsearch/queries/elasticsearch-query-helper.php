<?php

namespace makeandship\elasticsearch\queries;

class ElasticsearchQueryHelper {	

    public static function filtersToBoolean($filters)
    {
        $types = array();

        $bool = array();

        foreach ($filters as $filter) {
            // is this a safe assumption?
            $type = array_keys($filter[array_keys($filter)[0]])[0];

            if (!isset($types[$type])) {
                $types[$type] = array();
            }

            $types[$type][] = $filter;
        }

        foreach ($types as $slug => $type) {
            if (count($type) == 1) {
                $bool['should'][] = $type;
            } else {
                $bool['should'][] = array('bool' => array('should' => $type));
            }
        }

        $bool['minimum_should_match'] = count($bool['should']);

        return $bool;
    }
}
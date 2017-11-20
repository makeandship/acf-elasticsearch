<?php

namespace makeandship\elasticsearch\queries;

use makeandship\elasticsearch\Config;
use makeandship\elasticsearch\Util;

/*
 * A chainable builder to generate elastic search queries
 */
class ElasticsearchQueryBuilder {
    // the query object to be built
    private $query;
    
    // a constructor and query initializer
    public function __construct()
    {
        $this->query = array();
    }

    // creates a bool query that takes multiple musts 
    public function query($filters, $musts)
    {
        if (count($filters) > 0) {
            $this->query['filter']['bool'] = ElasticsearchQueryHelper::filtersToBoolean($filters);
        }

        if (count($musts) > 0) {
            $this->query['query']['bool']['must'] = $musts;
        }

        return $this;
    }

    // creates an aggregation query with the provided filters
    public function aggs($filters)
    {
        foreach (Config::facets() as $facet) {
            $this->query['aggs'][$facet] = array(
                'aggs' => array(
                    "facet" => array(
                        'terms' => array(
                            'field' => $facet,
                            'size' => Util::get_facet_size()
                        )
                    )
                )
            );

            if (count($filters) > 0) {
                $applicable = array();

                foreach ($filters as $filter) {
                    foreach ($filter as $type) {
                        $terms = array_keys($type);

                        if (!in_array($facet, $terms)) {
                            // do not filter on itself when using OR
                            $applicable[] = $filter;
                        }
                    }
                }

                if (count($applicable) > 0) {
                    $this->query['aggs'][$facet]['filter']['bool'] = self::_filtersToBoolean($applicable);
                }
            }
        }

        return $this;
    }

    // creates an aggregation query for a given field
    public function field_aggs($field_name, $fields) 
    {
        if (in_array($field_name, $fields)) {
            $this->query['aggs'][$field_name]['terms'] = array(
                'field' => $field_name,
                'size' => Util::get_facet_size()
            );
        }

        return $this;
    }

    // creates numeric aggregation with ranges
    public function numeric_aggs($numeric) 
    {
        if (is_array($numeric)) {
            foreach (array_keys($numeric) as $facet) {
                $ranges = Config::ranges($facet);

                if (count($ranges) > 0) {
                    $this->query['aggs'][$facet]['aggs'] = array(
                        "range" => array(
                            'range' => array(
                                'field' => $facet,
                                'ranges' => array()
                            )
                        )
                    );

                    foreach ($ranges as $key => $range) {
                        $params = array();

                        if (isset($range['to'])) {
                            $params['to'] = $range['to'];
                        }

                        if (isset($range['from'])) {
                            $params['from'] = $range['from'];
                        }

                        $this->query['aggs'][$facet]['aggs']['range']['range']['ranges'][] = $params;
                    }
                }
            }
        }

        return $this;
    }

    // adds the filter clause (needed for aggregations)
    public function filter() 
    {
        if (isset($this->query['aggs'])) {
            foreach ($this->query['aggs'] as $facet => &$config) {
                if (!isset($config['filter'])) {
                    $config['filter'] = array('bool' => array('must' => array()));
                }
            }
        }

        return $this;
    }

    // creates a match query against multiple fields
    public function match($fields, $field, $text)
    {
        $this->query['query']['bool']['must']['match'][$field]['query'] = strtolower($text); 
        $this->query['_source'] = $fields;
        return $this;
    }

    // add fuzziness for a match query
    public function fuzziness($field, $value)
    {
        $this->query['query']['bool']['must']['match'][$field]['fuzziness'] = $value;
        return $this;
    }

    // filter by taxonomies
    public function filter_categories($categories)
    {
        if (isset($categories) && is_array($categories) && count($categories) > 0) {
            $this->query['query']['bool']['filter'] = array();
                foreach ($categories as $taxonomy => $filters) {
                    foreach ($filters as $operation => $filter) {
                        if (is_string($operation)) {
                            $this->query['query']['bool']['filter']['bool'] = array();

                            $bool_operator = $operation === 'or' ? 'should' : 'must';
                            if (!array_key_exists($bool_operator, $this->query['query']['bool']['must']['match'])) {
                                $this->query['query']['bool']['filter']['bool'][$bool_operator] = array();
                            }

                            if (is_array($filter)) {
                                foreach ($filter as $value) {
                                    $this->query['query']['bool']['filter']['bool'][$bool_operator][] =
                                        array(
                                            'term' => array(
                                                $taxonomy => $value
                                                )
                                        );
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }

    // return the built query
    function getQuery()
    {
        return $this->query;
    }
}
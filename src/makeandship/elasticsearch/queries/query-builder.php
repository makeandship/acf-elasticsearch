<?php

namespace makeandship\elasticsearch\queries;

use makeandship\elasticsearch\PostMappingBuilder;
use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\Util;
use makeandship\elasticsearch\settings\SettingsManager;

class QueryBuilder
{
    public function __construct()
    {
        // default include all valid post types
        $settings_manager = SettingsManager::get_instance();
        $this->post_types = $settings_manager->get_valid_post_types();

        $this->freetext = null;
        $this->fuzziness = null;
        $this->weights = null;
        $this->post_types = null;
        $this->categories = null;
        $this->taxonomies = null;
        $this->counts = null;
        $this->from = null;
        $this->size = null;
        $this->sorts = null;
        $this->search_fields = null;
        $this->return_fields = null;
        $this->aggregate_fields = null;
        $this->zero_aggregates = false;

        $this->set_plugin_defaults();
    }

    private function set_plugin_defaults()
    {
        // set fuzziness, weights and post_types
        $this->fuzziness = intval(SettingsManager::get_instance()->get(Constants::OPTION_FUZZINESS));
        $this->post_types = SettingsManager::get_instance()->get_post_types();
        $this->weights = SettingsManager::get_instance()->get(Constants::OPTION_WEIGHTINGS);
        $this->search_fields = SettingsManager::get_instance()->get(Constants::OPTION_SEARCH_FIELDS);
    }

    /**
     * Captures a free text query
     * @param freetext a freetext search e.g. Ace
     */
    public function freetext($freetext)
    {
        $this->freetext = $freetext;

        return $this;
    }

    /**
     * Captures a fuzziness score.  By default this will use the admin
     * console setting
     *
     * @param fuzziness a level of fuzziness - letters allowed to swop
     */
    public function with_fuzziness($fuzziness=null)
    {
        if (isset($fuzziness)) {
            $this->fuzziness = $fuzziness;
        }

        return $this;
    }

    /**
     * Capture scoring weights to use in queries.  Weightings are captured as an array with
     * key the field name and the value the score.  These become elasticsearch weightings
     * e.g. post_title => 3 becomes post_title^3
     *
     * @param weights array of weights
     */
    public function weighted($weights=null)
    {
        if (isset($weights)) {
            $this->weights = $weights;
        }

        return $this;
    }

    /**
     * Post types to include in a search.  The default is to include post types set in the
     * plugin.
     *
     * @param post_types array of post types
     */
    public function for_post_types($post_types)
    {
        $this->post_types = $post_types;

        return $this;
    }

    /**
     * Taxonomies to include in a search.  The default is to include post types set in the
     * plugin.
     *
     * @param taxonomies array of post types
     */
    public function for_taxonomies($taxonomies)
    {
        $this->taxonomies = $taxonomies;

        return $this;
    }

    public function for_categories($categories)
    {
        $this->categories = $categories;

        return $this;
    }

    public function with_category_counts($counts)
    {
        $this->counts = $counts;

        return $this;
    }

    public function paged($from, $size)
    {
        $this->from = $from;
        $this->size = $size;

        return $this;
    }

    public function sort($sorts)
    {
        $this->sorts = $sorts;

        return $this;
    }

    public function searching($fields=null)
    {
        if (isset($fields)) {
            $this->search_fields = $fields;
        }

        return $this;
    }

    public function returning($fields)
    {
        $this->return_fields = $fields;

        return $this;
    }

    public function aggregate_by($fields)
    {
        $this->aggregate_fields = $fields;

        return $this;
    }

    public function including_zero_aggregations()
    {
        $this->zero_aggregates = true;
    }

    public function to_array()
    {
        $query = array(
            'query' => array(
                'bool' => array(
                    'must' => array()
                )
            )
        );

        // freetext, fuzziness and weightings are used together for the built query
        $query_text = $this->build_text_query();
        $query['query']['bool']['must'] = array_merge($query['query']['bool']['must'], $query_text);

        // post types and taxonomies filter the query results
        $query_filters = $this->build_filters();
        $query['query']['bool'] = array_merge($query['query']['bool'], $query_filters);

        // aggregations to count results by post types and taxonomy entries
        $aggregations = $this->build_aggregations();
        $query['aggs'] = $aggregations;

        // pagination
        $pagination = $this->build_pagination();
        $query = array_merge($query, $pagination);

        // sorting
        $sorts = $this->build_sorts();
        $query = array_merge($query, $sorts);

        // fields
        $fields = $this->build_fields();
        $query = array_merge($query, $fields);

        // highlights
        $highlights = $this->build_highlights();
        $query = array_merge($query, $highlights);

        return $query;
    }

    private function build_text_query()
    {
        $query_text = array();

        if ($this->freetext) {
            if ($this->search_fields) {
                // restrict the search to specific fields only and add weights for those fields only
                $fields = array();
                foreach ($this->search_fields as $search_field) {
                    $weight = Util::safely_get_attribute($this->weights, $search_field);
                    if ($weight) {
                        $fields[] = $search_field.'^'.$weight;
                    } else {
                        $fields[] = $search_field;
                    }
                }

                $query_text = array(
                    'multi_match' => array(
                        'fields' => $fields,
                        'query' => $this->freetext
                    )
                );
            } else {
                // search all fields
                $query_text = array(
                    'multi_match' => array(
                        'query' => $this->freetext
                    )
                );

                // weight fields in the search result
                if ($this->weights) {
                    foreach ($this->weights as $field => $weight) {
                        if (ctype_digit(strval($weight))) { // check numeric - see http://php.net/manual/en/function.is-int.php
                            if (!array_key_exists('fields', $query_text['multi_match'])) {
                                $query_text['multi_match']['fields'] = array();    
                            }
                            $query_text['multi_match']['fields'][] = $field.'^'.$weight;
                        }
                    }
                }
            }

            // add fuzziness for the free text query
            if ($this->fuzziness) {
                $query_text['multi_match']['fuzziness'] = $this->fuzziness;
            }
        } else {
            $query_text = array(
                'match_all' => (object) array()
            );
        }

        return $query_text;
    }

    private function build_filters()
    {
        $query_field_filters = array();

        // filter by taxonomy entries
        if ($this->categories) {
            $categories = $this->ensure_categories($this->categories);

            $query_field_filters['filter'] = array();

            if ($categories && count($categories) > 0) {
                $query_field_filters['filter']['bool'] = array();

                // e.g. 'category' => ['and' => ...]
                foreach ($categories as $field => $operations) {
                    // e.g. 'and' => ['acute-care']
                    foreach ($operations as $operation => $filters) {
                        $bool_operator = $operation === 'or' ? 'should' : 'must';

                        if (!array_key_exists($bool_operator, $query_field_filters['filter']['bool'])) {
                            $query_field_filters['filter']['bool'][$bool_operator] = array();
                        }

                        foreach ($filters as $filter) {
                            if (strpos($field, ".") !== false) {
                                $nested_filter = array();
                                $paths = explode(".", $field);

                                $keys = array_keys($paths);
                                $last_index = end($keys);
                                $current_filter = &$nested_filter;
                                foreach ($paths as $index => $path) {
                                    if ($index === $last_index) {
                                        $current_filter['term'] = array(
                                            $field => $filter
                                        );
                                    } else {
                                        $current_filter = array(
                                          "nested" => array(
                                            "path" => $path,
                                            "query" => array()
                                          )
                                        );
                                        $current_filter = &$current_filter['nested']['query'];
                                    }
                                }
                                $query_field_filters['filter']['bool'][$bool_operator][] = $nested_filter;
                            } else {
                                // category is held as a simple term so uses a term query
                                // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
                                $query_field_filters['filter']['bool'][$bool_operator][] = array(
                                    'term' => array(
                                        $field => $filter
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }

        // filter by post types (which are elastic search types)
        // use "should" as it can match any post_type (not all)
        if ($this->post_types && count($this->post_types) > 0) {
            if (!array_key_exists('filter', $query_field_filters)) {
                $query_field_filters['filter'] = array();
            }
            if (!array_key_exists('bool', $query_field_filters['filter'])) {
                $query_field_filters['filter']['bool'] = array();
            }
            if (!array_key_exists('must', $query_field_filters['filter']['bool'])) {
                $query_field_filters['filter']['bool']['should'] = array();
            }

            foreach ($this->post_types as $post_type) {
                // post type is used for the index type and therefore uses a type query
                // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-type-query.html
                $query_field_filters['filter']['bool']['should'][] = array(
                    'term' => array(
                        'post_type' => $post_type
                    )
                );
            }
        }

        // filter by taxonomies (which are elastic search types)
        // use "should" as it can match any post_type (not all)
        if ($this->taxonomies && count($this->taxonomies) > 0) {
            if (!array_key_exists('filter', $query_field_filters)) {
                $query_field_filters['filter'] = array();
            }
            if (!array_key_exists('bool', $query_field_filters['filter'])) {
                $query_field_filters['filter']['bool'] = array();
            }
            if (!array_key_exists('must', $query_field_filters['filter']['bool'])) {
                $query_field_filters['filter']['bool']['must'] = array();
            }
            if (!array_key_exists('must', $query_field_filters['filter']['bool'])) {
                $query_field_filters['filter']['bool']['must'] = array();
            }
            if (!array_key_exists('bool', $query_field_filters['filter']['bool']['must'])) {
                $query_field_filters['filter']['bool']['must']['bool'] = array();
            }
            if (!array_key_exists('should', $query_field_filters['filter']['bool'])) {
                $query_field_filters['filter']['bool']['must']['bool']['should'] = array(
                  'minimum_should_match' => 1
                );
            }

            foreach ($this->taxonomies as $taxonomy) {
                // post type is used for the index type and therefore uses a type query
                // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-type-query.html
                $query_field_filters['filter']['bool']['must']['bool']['should'][] = array(
                    'term' => array(
                        'type' => $taxonomy
                    )
                );
            }
        }

        return $query_field_filters;
    }

    /**
     * Ensure categories are int he correct shape
     *
     * Translate from an array of WP_Term into a taxonomy array
     *
     * Final state should be
     * taxonomy_name => []
     *   operator => [
     *      taxonomy_slug,
     *      taxonomy_slug
     *   ]
     * ]
     *
     * e.g.
     * categories => [
     *   and => [
     *     acute-care
     *   ]
     * ]
     */
    private function ensure_categories($categories)
    {
        return $categories;
    }

    /**
     * Creates aggregations for post types and taxonomies
     *
     * The final result will look similar to
     * "aggs": {
     *     "post_type": {
     *         "aggs": {
     *             "facet": {
     *                 "terms": {
     *                     "field": "post_type",
     *                     "size": 100
     *                 }
     *             }
     *         },
     *         "filter": {
     *             "bool": {
     *                 "must": []
     *             }
     *         }
     *     }
     * }
     */
    private function build_aggregations()
    {
        $aggregations = array();

        // aggregations for taxonomies
        if ($this->counts && count($this->counts) > 0) {
            foreach ($this->counts as $taxonomy => $count) {
                $count = is_string($count) ? intval($count) : $count;

                $agg = $this->build_aggregation($taxonomy, $count, $this->zero_aggregates);
                $aggregations = array_merge($aggregations, $agg);
            }
        }

        // post type aggregation
        $post_type_count = $this->post_types ? count($this->post_types) : 100;
        $agg = $this->build_aggregation('post_type', $post_type_count, $this->zero_aggregates);
        $aggregations = array_merge($aggregations, $agg);

        // custom aggregations
        if ($this->aggregate_fields && is_array($this->aggregate_fields) && count($this->aggregate_fields)) {
            if (Util::is_array_sequential($this->aggregate_fields)) {
                foreach ($this->aggregate_fields as $aggregate_field) {
                    $agg = $this->build_aggregation($aggregate_field, null, $this->zero_aggregates);
                    $aggregations = array_merge($aggregations, $agg);
                }
            } else {
                foreach ($this->aggregate_fields as $aggregate_field => $aggregate_field_count) {
                    $agg = $this->build_aggregation($aggregate_field, $aggregate_field_count, $this->zero_aggregates);
                    $aggregations = array_merge($aggregations, $agg);
                }
            }
        }

        return $aggregations;
    }

    /**
     * Build a single aggregation
     *
     * @param $key is the field name
     * @param $count the maximum terms to return
     * @param $min_count boolean whether to use a 0 min count
     */
    private function build_aggregation($key, $count, $min_count)
    {
        if ($key) {
            $aggregations = array();

            // detect dot notation and convert into nested aggregation format
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-nested-aggregation.html
            if (strpos($key, ".") !== false) {
                $parts = explode(".", $key);

                $paths = array();
                foreach ($parts as $part) {
                    // work through earlier layers
                    $target = &$aggregations;
                    foreach ($paths as $path) {
                        $target = &$aggregations[$path]['aggs'];
                    }

                    $target[$part] = array(
                        'aggs' => array()
                    );

                    // detect last and add aggregation
                    if (array($part) === array_slice($parts, -1)) {
                        $target[$part] = array(
                            'terms' => array(
                                'field' => $key
                            )
                        );
                        if ($count) {
                            $target[$part]['terms']['size'] = $count;
                        }

                        if ($min_count) {
                            $target[$part]['terms']['min_doc_count'] = 0;
                        }
                    } else {
                        $target[$part]['nested'] = array('path' => $part);
                    }

                    $paths[]= $part;
                }
            } else {
                $aggregations[$key] = array(
                    'terms' => array(
                        'field' => $key
                    )
                );

                if ($count) {
                    $aggregations[$key]['terms']['size'] = $count;
                }

                if ($min_count) {
                    $aggregations[$key]['terms']['min_doc_count'] = 0;
                }
            }

            return $aggregations;
        }
        return array();
    }

    private function build_pagination()
    {
        $pagination = array();

        if ($this->from !== null && $this->size !== null) {
            // from is the record number hence page size * page number
            $from = $this->from * $this->size;
            $pagination['from'] = $from;

            $pagination['size'] = $this->size;
        }

        return $pagination;
    }

    /**
     * Creates sorts and ordering within the query
     *
     * Target example
     * {
     *     "sort": [
     *         { "post_date": "desc" }
     *     ],
     *     ...
     * }
     *
     * _score is used for no sort
     */
    private function build_sorts()
    {
        $sorts = array();

        if ($this->sorts && count($this->sorts) > 0) {
            $sorts['sort'] = array();
            foreach ($this->sorts as $sort => $order) {
                $sorts['sort'][$sort] = $order;
            }
        } else {
            if (!$this->freetext) {
                $sorts['sort'] = array(
                    'post_date' => 'desc'
                );
            } else {
                $sorts['sort'] = array(
                    '_score'
                );
            }
        }

        return $sorts;
    }

    /**
     * Build query body to control which fields are returned
     *
     * @return array for the source attribute
     */
    private function build_fields()
    {
        $fields = array();

        if ($this->return_fields) {
            $fields['_source'] = $this->return_fields;
        }

        return $fields;
    }

    /**
     * Build query body to control which fields are checked for highlights
     *
     * @return array for the highlight attribute
     */
    private function build_highlights()
    {
        $highlights = array();

        if ($this->weights) {
            $highlights['highlight'] = array(
                'fields' => array()
            );

            foreach ($this->weights as $field => $weight) {
                if ($field) {
                    $highlights['highlight']['fields'][$field] = new \stdClass();
                }
            }
        }

        return $highlights;
    }
}
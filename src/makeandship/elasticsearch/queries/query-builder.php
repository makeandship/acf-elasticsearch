<?php
class QueryBuilder
{
    public function __construct()
    {
    }

    public function freetext($freetext)
    {
        $this->freetext = $freetext;

        return $this;
    }

    public function with_fuzziness($fuzziness)
    {
        $this->fuzziness = $fuzziness;

        return $this;
    }

    public function weighted($weights)
    {
        $this->weights = $weights;

        return $this;
    }

    public function for_post_types($post_types)
    {
        $this->post_types = $post_types;

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

    public function to_query()
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
        $query['query']['bool']['must'] = array_merge($query['bool']['must'], $query_text);

        // post types and taxonomies filter the query results
        $query_filters = $this->build_filters();
        $query['query']['must'] = array_merge($query['bool']['must'], $query_filters);

        // aggregations to count results by post types and taxonomy entries
        $aggregations = $this->build_aggregations();
        $query['aggregations'] = $aggregations;

        // pagination
        $pagination = $this->build_pagination();
        $query = array_merge($query, $pagination);
    }

    private function build_text_query()
    {
        $query_text = array();

        if ($this->freetext) {
            $query_text = array(
                'multi_match' => array(
                    'fields' => array(
                        '_all'
                    ),
                    'query' => $this->freetext
                )
            );

            // weight fields in the search result
            if ($this->weights) {
                foreach ($this->weights as $field => $weight) {
                    if (ctype_digit(strval($weight))) { // check numeric - see http://php.net/manual/en/function.is-int.php
                        $query_text['multi_match']['fields'][] = $field.'^'.$weight;
                    }
                }
            }

            // add fuzziness for the free text query
            if ($this->fuzziness) {
                $query_text['multi_match']['fuzziness'] = $this->fuzziness;
            }
        } else {
            $query_text = array(
                'match_all' => array()
            );
        }

        return $query_text;
    }

    private function build_filters()
    {
        $query_taxonomy_filters = array();

        // filter by taxonomy entries
        if ($this->categories) {
            $categories = $this->ensure_categories($this->categories);

            $query_taxonomy_filters['filter'] = array();

            if ($categories && count($categories) > 0) {
                $query['filter']['bool'] = array();

                // e.g. 'category' => ['and' => ...]
                foreach ($categories as $taxonomy => $operations) {
                    // e.g. 'and' => ['acute-care']
                    foreach ($operations as $operation => $filters) {
                        $bool_operator = $operation === 'or' ? 'should' : 'must';

                        if (!array_key_exists($bool_operator, $query['filter']['bool'])) {
                            $query['filter']['bool'][$bool_operator] = array();
                        }

                        foreach ($filters as $filter) {
                            // category is held as a simple term so uses a term query
                            // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
                            $query['filter']['bool'][$bool_operator][] = array(
                                'term' => array(
                                    $taxonomy => $filter
                                )
                            );
                        }
                    }
                }
            }
        }

        // filter by post types (which are elastic search types)
        if ($this->post_types && count($this->post_types) > 0) {
            if (!array_key_exists('must', $query['filter']['bool'])) {
                $query['filter']['bool']['must'] = array();
            }

            foreach ($post_types as $post_type) {
                // post type is used for the index type and therefore uses a type query
                // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-type-query.html
                $query['filter']['bool'][$bool_operator][] = array(
                    'term' => array(
                        $taxonomy => $filter
                    )
                );
            }
        }

        return $query_taxonomy_filters;
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
                $aggregations[$taxonomy] = array(
                    'aggs' => array(
                        'facet' => array(
                            'terms' => array(
                                'field' => $taxonomy,
                                'size' => $count
                            )
                        )
                    ),
                    'filter' => array(
                        'bool' => array(
                            'must' => array()
                        )
                    )
                );
            }
        }

        // post type aggregation
        $post_type_count = $this->post_types ? count($this->post_types) : 100;
        $aggregations['post_type'] = array(
            'aggs' => array(
                'facet' => array(
                    'terms' => array(
                        'field' => 'post_type',
                        'size' => $post_type_count
                    )
                )
            ),
            'filter' => array(
                'bool' => array(
                    'must' => array()
                )
            )
        );

        return $aggregations;
    }

    private function build_pagination()
    {
        $pagination = array();

        if ($this->from && $this->size) {
            // from is the record number hence page size * page number
            $from = $this->from * $this->size;
            $pagination['from'] = $from;

            $pagination['size'] = $this->size;
        }

        return $pagination();
    }
}

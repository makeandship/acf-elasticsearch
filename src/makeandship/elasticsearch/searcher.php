<?php
namespace makeandship\elasticsearch;

use makeandship\elasticsearch\SettingsManager;
use makeandship\elasticsearch\queries\ElasticsearchQueryBuilder;

use \Elastica\Client;

/**
 * The searcher class provides all you need to query your ElasticSearch server.
 *
 * @license http://opensource.org/licenses/MIT
 * @author Paris Holley <mail@parisholley.com>
 * @version 4.0.1
 **/
class Searcher
{
    /**
     * Initiate a search with the ElasticSearch server and return the results. Use Faceting to manipulate URLs.
     * @param string $search A space delimited list of terms to search for
     * @param integer $pageIndex The index that represents the current page
     * @param integer $size The number of results to return per page
     * @param array $facets An object that contains selected facets (typically the query string, ie: $_GET)
     * @param boolean $sortByDate If false, results will be sorted by score (relevancy)
     * @see Faceting
     *
     * @return array The results of the search
     **/
    public static function search($search = '', $pageIndex = 0, $size = 10, $facets = array(), $sortByDate = false)
    {
        $args = self::_buildQuery($search, $facets);

        if (empty($args) || (empty($args['query']) && empty($args['aggs']))) {
            return array(
                'total' => 0,
                'ids' => array(),
                'facets' => array()
            );
        }

        // need to do rethink the signature of the search() method, arg list can't just keep growing
        return self::_query($args, $pageIndex, $size, $sortByDate);
    }

    /**
     * @internal
     **/
    public static function _query($args, $pageIndex, $size, $sortByDate = false)
    {
        $query = new \Elastica\Query($args);
        $query->setFrom($pageIndex * $size);
        $query->setSize($size);

        $query = Config::apply_filters('searcher_query', $query);

        try {
            $settings_manager = new SettingsManager();
            $settings = $settings_manager->get_settings();
            $client_settings = Util::get_client_settings($settings);

            $client = new Client($settings);
            $name = get_option(Constants::OPTION_PRIMARY_INDEX);
            $index = $client->getIndex($name);

            $search = new \Elastica\Search($client);
            $search->addIndex($index);

            if (!$query->hasParam('sort')) {
                if ($sortByDate) {
                    $query->addSort(array('post_date' => 'desc'));
                } else {
                    $query->addSort('_score');
                }
            }

            $search = Config::apply_filters('searcher_search', $search, $query);

            $results = $search->search($query);

            return self::_parseResults($results);
        } catch (\Exception $ex) {
            error_log($ex);

            Config::do_action('searcher_exception', $ex);

            return null;
        }
    }

    /**
     * @internal
     **/
    public static function _parseResults($response)
    {
        $val = array(
            'total' => $response->getTotalHits(),
            'facets' => array(),
            'ids' => array()
        );

        foreach ($response->getAggregations() as $name => $agg) {
            if (isset($agg['facet']['buckets'])) {
                foreach ($agg['facet']['buckets'] as $bucket) {
                    $val['facets'][$name][$bucket['key']] = $bucket['doc_count'];
                }
            }

            if (isset($agg['range']['buckets'])) {
                foreach ($agg['range']['buckets'] as $bucket) {
                    $from = isset($bucket['from']) ? $bucket['from'] : '';
                    $to = isset($bucket['to']) && $bucket['to'] != '*' ? $bucket['to'] : '';

                    $val['facets'][$name][$from . '-' . $to] = $bucket['doc_count'];
                }
            }
        }

        foreach ($response->getResults() as $result) {
            $val['ids'][] = $result->getId();
        }

        return Config::apply_filters('searcher_results', $val, $response);
    }

    /**
     * @internal
     **/
    public static function _buildQuery($search, $facets = array())
    {
        global $blog_id;

        $search = str_ireplace(array(' and ', ' or '), array(' AND ', ' OR '), $search);

        $fields = array();
        $musts = array();
        $filters = array();
        $scored = array();

        foreach (Config::taxonomies() as $tax) {
            if ($search) {
                $score = Config::score('tax', $tax);

                if ($score > 0) {
                    $scored[] = "{$tax}_name^$score";
                }
            }

            self::_filterBySelectedFacets($tax, $facets, 'term', $musts, $filters);
        }

        $args = new ElasticsearchQueryBuilder();

        $numeric = Config::option('numeric');

        $exclude = Config::apply_filters('searcher_query_exclude_fields', array('post_date'));

        $fields = Config::fields();

        self::_searchField($fields, 'field', $exclude, $search, $facets, $musts, $filters, $scored, $numeric);
        self::_searchField(Config::meta_fields(), 'meta', $exclude, $search, $facets, $musts, $filters, $scored, $numeric);

        if (count($scored) > 0 && $search) {
            $qs = array(
                'fields' => $scored,
                'query' => $search
            );

            $fuzzy = Config::option('fuzzy');

            if ($fuzzy && strpos($search, "~") > -1) {
                $qs['fuzzy_min_sim'] = $fuzzy;
            }

            $qs = Config::apply_filters('searcher_query_string', $qs);

            $musts[] = array('query_string' => $qs);
        }

        if (in_array('post_type', $fields)) {
            self::_filterBySelectedFacets('post_type', $facets, 'term', $musts, $filters);
        }

        self::_searchField(Config::customFacets(), 'custom', $exclude, $search, $facets, $musts, $filters, $scored, $numeric);

        $args = $args->query($filters, $musts)
                     ->field_aggs('post_type', $fields)
                     ->aggs($filters)
                     ->numeric_aggs($numeric)
                     ->filter();

        return Config::apply_filters('searcher_query_post_facet_filter', $args->getQuery());
    }

    public static function _searchField($fields, $type, $exclude, $search, $facets, &$musts, &$filters, &$scored, $numeric)
    {
        foreach ($fields as $field) {
            if (in_array($field, $exclude)) {
                continue;
            }

            if ($search) {
                $score = Config::score($type, $field);
                $notanalyzed = Config::option('not_analyzed');

                if ($score > 0) {
                    if (strpos($search, "~") > -1 || isset($notanalyzed[$field])) {
                        // TODO: fuzzy doesn't work with english analyzer
                        $scored[] = "$field^$score";
                    } else {
                        $scored[] = sprintf(
                            "$field^$score"
                        );
                    }
                }
            }

            if (isset($numeric[$field]) && $numeric[$field]) {
                $ranges = Config::ranges($field);

                if (count($ranges) > 0) {
                    $transformed = array();

                    foreach ($ranges as $key => $range) {
                        $transformed[$key] = array();

                        if (isset($range['to'])) {
                            $transformed[$key]['lt'] = $range['to'];
                        }

                        if (isset($range['from'])) {
                            $transformed[$key]['gte'] = $range['from'];
                        }
                    }

                    self::_filterBySelectedFacets($field, $facets, 'range', $musts, $filters, $transformed);
                }
            } elseif ($type == 'custom') {
                self::_filterBySelectedFacets($field, $facets, 'term', $musts, $filters);
            }
        }
    }

    // remove
    public static function _filtersToBoolean($filters)
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

    /**
     * @internal
     **/
    public static function _filterBySelectedFacets($name, $facets, $type, &$musts, &$filters, $translate = array())
    {
        if (isset($facets[$name])) {
            $facets = $facets[$name];

            if (!is_array($facets)) {
                $facets = array($facets);
            }

            foreach ($facets as $operation => $facet) {
                if (is_string($operation) && $operation == 'or') {
                    // use filters so faceting isn't affecting, allowing the user to select more "or" options
                    $output = &$filters;
                } else {
                    $output = &$musts;
                }

                if (is_array($facet)) {
                    foreach ($facet as $value) {
                        $output[] = array($type => array($name => isset($translate[$value]) ? $translate[$value] : $value));
                    }

                    continue;
                }

                $output[] = array($type => array($name => isset($translate[$facet]) ? $translate[$facet] : $facet));
            }
        }
    }
}

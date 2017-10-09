<?php
namespace makeandship\elasticsearch;

/**
 * The searcher class provides all you need to query your ElasticSearch server.
 *
 * @license http://opensource.org/licenses/MIT
 * @author Paris Holley <mail@parisholley.com>
 * @author Mark Thomsit <mark@makeandship.com>
 * @version 4.0.1
 **/
use \Elastica\Client;

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
	public static function search($search = '', $page_index = 0, $size = 10, $facets = array())
	{
		$query = self::_generate_query( $search, $facets );
		error_log( json_encode( $query ) );

		$sort_by_date = (!isset($search) || empty($search)) ? true : false;

		// need to do rethink the signature of the search() method, arg list can't just keep growing
		return self::_execute($query, $page_index, $size, $sort_by_date);
	}

	public static function _generate_query($search, $facets) {
		$query = array();

		// query config
		$config = self::_get_config();

		// free text search
		// - no text 
		// - normal text 
		// - fuzzy
		// - against fields
		$query_freetext = self::_generate_query_freetext( $config, $search, $facets );

		// facets (taxonomies)
		$query_facets = self::_generate_query_facets( $config, $search, $facets );

		// types (using post_types)
		$query_types = self::_generate_query_types( $config, $search, $facets );

		// ranges
		$query_ranges = self::_generate_query_ranges( $config, $search, $facets );

		// scoring
		$query_scores = self::_generate_query_scores( $config, $search, $facets );

		// taxonomy counts
		$query_aggregations = self::_generate_query_aggregations( $config, $search, $facets );

		// compose into a valid es query
		$query = self::_generate_complete_query(
			$config,
			$query_freetext,
			$query_facets,
			$query_types,
			$query_ranges,
			$query_scores,
			$query_aggregations
			);
		return $query;
	}

	/**
	 * Get wrapped plugin configuration to support query building
	 *
	 * @return array
	 * - taxonomies
	 * - facets
	 * - numeric
	 * - exclude
	 * - fields
	 * - meta_fields
	 * - fuzzy
	 */
	public static function _get_config() {
		return array(
			'taxonomies' => Config::taxonomies(),
			'facets' => Config::facets(),
			'numeric' => Config::option('numeric'),
			'exclude' => Config::apply_filters('searcher_query_exclude_fields', array('post_date')),
			'fields' => Config::fields(),
			'meta_fields' => Config::meta_fields(),
			'fuzzy' => Config::option('fuzzy'),
			'types' => Config::types()
		);
	}

	/**
	 * Generate query structures for a free-text search 
	 * 
	 * Includes:
	 * - free text search
	 * - fuzzy search
	 * - no free text 
	 *
	 * @param $config - plugin configuration
	 * @param $search - free-text query 
	 * @param $config - facets - taxonomy query values
	 *
	 * @return $query - free-text structures of a query - query['match_all'], query['multi_match']
	 */
	public static function _generate_query_freetext( $config, $search, $facets ) {
		$query = array(
			'query' => array()
		);

		if (isset($search) && $search) {
			
			// - normal text
			if (!array_key_exists('multi_match', $query['query'])) {
				$query['query']['multi_match'] = array();
			}

			// free text search
			$query['query']['multi_match']['fields'] = ["_all"];
			$query['query']['multi_match']['query'] = $search;
			
			// - fuzzy
			$fuzzy = $config['fuzzy'];
			if ($fuzzy) {
				$query['query']['multi_match']['fuzziness'] = $fuzzy;
			}
		}
		else {
			// no text search
			if (!array_key_exists('match_all', $query['query'])) {
				$query['query']['match_all'] = array();
			}
		}

		return $query;
	}

	/**
	 * Generate filter structures based on taxonomy queries
	 * 
	 * Uses boolean filters - must (and) and should (or)
	 *
	 * @param $config - plugin configuration
	 * @param $search - free-text query 
	 * @param $config - facets - taxonomy query values
	 *
	 * @return $query - filter structures for query filter['bool']
	 */
	public static function _generate_query_facets( $config, $search, $facets ) {
		$query = array();

		if (isset($facets) && count($facets) > 0) {
			$query = array(
				'filter' => array()
			);

			foreach($facets as $taxonomy => $filters) {
				foreach ($filters as $operation => $filter) {
					if (is_string($operation)) {
						$query['filter']['bool'] = array();

						$bool_operator = $operation === 'or' ? 'should' : 'must';
						if (!array_key_exists($bool_operator, $query['filter']['bool'])) {
							$query['filter']['bool'][$bool_operator] = array();
						}

						if (is_array($filter)) {
							foreach ($filter as $value) {
								$query['filter']['bool'][$bool_operator][] = 
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

		return $query;
	}

	/**
	 * Generate filter structures based on post types.  This excludes taxonomy types
	 *
	 * @param $config - plugin configuration
	 * @param $search - free-text query 
	 * @param $config - facets - taxonomy query values
	 *
	 * @return $query - filter structures for query filter['bool']
	 */
	public static function _generate_query_types( $config, $search, $facets ) {
		$query = array(
			'filter' => array(
				'bool' => array(
					'should' => array(

					)
				)
			)
		);

		foreach($config['types'] as $post_type) {
			$query['filter']['bool']['should'][] = array(
				'type' => array(
					'value' => $post_type
				)
			);
		}

		return $query;
	}

	/**
	 * Generate filter structures based on taxonomy queries
	 * 
	 * @param $config - plugin configuration
	 * @param $search - free-text query 
	 * @param $config - facets - taxonomy query values
	 *
	 * @todo consider adding back in range queries with adjusted plugin UI
	 */
	public static function _generate_query_ranges( $config, $search, $facets ) {
		// ranges need re-think in implementation
	}

	/**
	 * Generate fields and scorings based on plugin config.  
	 * 
	 * Overlays fields with scores into query blocks
	 *
	 * @param $config - plugin configuration
	 * @param $search - free-text query 
	 * @param $config - facets - taxonomy query values
	 *
	 * @return $query - filter structures for query multi_match['fields']
	 */
	public static function _generate_query_scores( $config, $search, $facets ) {
		$query = array();

		$scores = self::_get_field_scores( $config );

		if ( array_key_exists( 'scored' , $scores) && count($scores['scored']) > 0) {
			$query['query'] = array(
				'multi_match' => array(
					'fields' => array()
				)
			);

			foreach($scores['scored'] as $field => $score) {
				$query['query']['multi_match']['fields'][] = $field.'^'.$score;
			}
		}

		return $query;
	}

	/**
	 * Generate aggregations based on selected taxonomie
	 * 
	 * Uses aggs to count against taxonomies.  Use filters to support sizes 
	 * if you need complete counts e.g. to return all terms
	 *
	 * @param $config - plugin configuration
	 * @param $search - free-text query 
	 * @param $config - facets - taxonomy query values
	 *
	 * @return $query - filter structures for query filter['bool']
	 */
	public static function _generate_query_aggregations( $config, $search, $facets ) {
		global $blog_id;

		$aggs = array(
			'aggs' => array()
			);

		$config_facets = $config['facets'];

		foreach ($config_facets as $facet) {
			$aggs['aggs'][$facet] = array(
				'aggregations' => array(
					'facet' => array(
						'terms' => array(
							'field' => $facet,
							'size' => Config::apply_filters('searcher_query_facet_size', 100, $facet)  // see https://github.com/elasticsearch/elasticsearch/issues/1832
						)
					)
				),
				'filter' => array(
					'bool' => array(
						'must' => [
							array(
								'term' => array(
									'blog_id'=> $blog_id
								)
							)
						]
					)
				)
			);
			
		}

		return $aggs;
	}

	/**
	 * Compose individual sections of a query into a single valid one 
	 * for submission to elastic search
	 * 
	 * @param $config - plugin configuration
	 * @param $query_freetext - free-text query 
	 * @param $query_facets - facets
	 * @param $query_ranges - range aggregations
	 * @param $query_scores - individual field scorings
	 * @param $query_aggregations - aggregations
	 *
	 * @return $query - a complete query
	 */
	public static function _generate_complete_query( 
		$config,
		$query_freetext,
		$query_facets,
		$query_types,
		$query_ranges,
		$query_scores,
		$query_aggregations ) {

		if (is_array($query_scores) && !empty($query_scores)) {
			// query scores merges with a multi-match element when 
			// the user has specified a search query 
			if (!array_key_exists('match_all', $query_freetext['query'])) {
				$query_freetext = array_merge_recursive($query_freetext, $query_scores);
			}
		}

		$query_filter = $query_facets;
		if (is_array($query_filter) && !empty($query_filter)) {
			if (is_array($query_types) && !empty($query_types)) {
				// merge with other filters as a nested must (could be more robust here)
				$query_filter['filter']['bool']['must'][] = $query_types['filter'];
			}
		}
		else {
			$query_filter = $query_types;
		}

		if (is_array($query_freetext) && !empty($query_freetext) && 
			is_array($query_filter) && !empty($query_filter) ) {
			
			$query = array(
				'query' => array( 
					'filtered' => array_merge($query_freetext, $query_filter)
				)
			);

			$query = array_merge(
				$query, 
				$query_aggregations
			);
			
		}
		else {
			$query = array_merge(
				array(),
				$query_freetext,
				$query_facets,
				$query_aggregations
			);
		}

		return $query;
	}

	public static function _get_field_scores( $config ) {
		$scores = array(
			'scored' => array(),
			'unscored' => array()
		);

		// 
		foreach($config['fields'] as $field) {
			$score = Config::score( 'field', $field);
			
			if ($score) {
				$scores['scored'][$field] = $score;
			}
			else {
				$scores['unscored'][] = $field;
			}
		}
		foreach($config['meta_fields'] as $meta_field) {
			$score = Config::score( 'meta', $field );
			
			if ($score) {
				$scores['scored'][$field] = $score;
			}
			else {
				$scores['unscored'][] = $field;
			}
		}
		foreach($config['taxonomies'] as $taxonomy) {
			$score = Config::score( 'tax', $field);
			
			if ($score) {
				$scores['scored'][$field.'_name'] = $score;
			}
			else {
				$scores['unscored'][] = $field.'_name';
			}
		}

		return $scores;
	}

	/**
	 * @internal
	 **/
	public static function _execute($args, $page_index, $size, $sort_by_date = false) {
		// simple validation of the query - return empty result if invalid
		if (empty($args) || (empty($args['query']) && empty($args['aggs']))) {
			return self::_empty_result();
		}

		// wrap query array in an elastica query
		$query = new \Elastica\Query($args);
		// enhance elastica query - pagination and sort
		$query->setFrom($page_index * $size);
		$query->setSize($size);
		if (!$query->hasParam('sort')) {
			if ($sort_by_date) {
				$query->addSort(array('post_date' => 'desc'));
			} else {
				$query->addSort('_score');
			}
		}

		// dev override
		$query = Config::apply_filters('searcher_query', $query);

		try {
			// elastica objects for querying
			$plugin = new AcfElasticsearchPlugin();
			$settings = array();
			$client = new Client($settings);

			$indexer = $plugin->indexer;
			$index = $indexer->index_posts(false);
			$search = new \Elastica\Search($client);
			$search->addIndex($index);

			$search = Config::apply_filters('searcher_search', $search, $query);

			// search and transform results
			$results = $search->search($query);
			$transformed = self::_parse_results($results);

			return $transformed;
		} 
		catch (\Exception $ex) {
			error_log($ex);

			Config::do_action('searcher_exception', $ex);

			return self::_empty_result();
		}
	}

	/**
	 * @internal
	 **/
	public static function _parse_results($response)
	{
		$val = array(
			'total' => $response->getTotalHits(),
			'facets' => array(),
			'results' => array(),
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
			$source = $result->getSource();
			$source['id'] = $result->getId();

			$val['results'][] = $source;
			$val['ids'][] = $source['id'];
		}

		return Config::apply_filters('searcher_results', $val, $response);
	}

	public static function _empty_result() {
		return array(
			'total' => 0,
			'ids' => array(),
			'facets' => array(),
			'results' => array()
		);
	}
}

?>

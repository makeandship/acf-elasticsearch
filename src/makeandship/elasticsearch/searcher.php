<?php
namespace makeandship\elasticsearch;

use makeandship\elasticsearch\settings\SettingsManager;
use makeandship\elasticsearch\transformer\SearchTransformer;
use \Elastica\Client;

/**
 * Run searches against the backing Elasticsearch server configured in the plugin settings
 **/
class Searcher
{
    private $client;

    public function __construct()
    {
        $this->settings_manager = SettingsManager::get_instance();
        $client_settings        = $this->settings_manager->get_client_settings();

        $this->client = new Client($client_settings);
    }

    /**
     * Execute an elastic search query.  Use a <code>QueryBuilder</code> to generate valid queries
     *
     * @param array an elastic search query
     * @return results object
     *
     * @see QueryBuilder
     */
    public function search($args)
    {
        $args = Util::apply_filters('prepare_query', $args);

        $query = new \Elastica\Query($args);

        try {
            $search = new \Elastica\Search($this->client);
            $search->addIndex($this->get_index());

            $response = $search->search($query);

            $transformer = new SearchTransformer();
            $results     = $transformer->transform($response);

            return $results;
        } catch (\Exception $ex) {
            Util::debug('Searcher#search', $ex);

            Util::do_action('search_exception', $ex);

            return null;
        }
    }
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
    /*public function search($query, $pageIndex = 0, $size = 10, $facets = array(), $sortByDate = false)
    {
    if (empty($query) || (empty($query['query']) && empty($query['aggs']))) {
    return array(
    'total' => 0,
    'ids' => array(),
    'facets' => array()
    );
    }

    return $this->query($query);
    }*/

    /**
     * @internal
     **/
    private function query($args)
    {
        $query = new \Elastica\Query($args);

        $query = Util::apply_filters('searcher_query', $query);

        try {
            $search = new \Elastica\Search($this->client);
            $search->addIndex($this->get_index());

            $search = Util::apply_filters('searcher_search', $search, $query);

            return $search->search($query);
        } catch (\Exception $ex) {
            Util::debug('Searcher#query', $ex);

            Util::do_action('searcher_exception', $ex);

            return null;
        }
    }

    private function get_index()
    {
        $status        = $this->settings_manager->get(Constants::OPTION_INDEX_STATUS);
        $capability    = $this->settings_manager->get(Constants::OPTION_CAPABILITY);
        $secondary     = $this->settings_manager->get(Constants::OPTION_PRIVATE_SECONDARY_INDEX);
        $use_secondary = isset($secondary) && !empty($secondary);

        if ($status['index'] == "primary" && $use_secondary) {
            $private_index = $this->settings_manager->get(Constants::OPTION_PRIVATE_SECONDARY_INDEX);
            $public_index  = $this->settings_manager->get(Constants::OPTION_SECONDARY_INDEX);
        } else {
            $private_index = $this->settings_manager->get(Constants::OPTION_PRIVATE_PRIMARY_INDEX);
            $public_index  = $this->settings_manager->get(Constants::OPTION_PRIMARY_INDEX);
        }

        if (isset($private_index) && !empty($private_index) && current_user_can($capability)) {
            return $this->client->getIndex($private_index);
        }

        return $this->client->getIndex($public_index);
    }
}

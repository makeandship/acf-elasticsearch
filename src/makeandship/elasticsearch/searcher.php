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
    private $client;
    private $index;

    public function __construct()
    {
      $settings_manager = new SettingsManager();
      $settings = $settings_manager->get_settings();
      $client_settings = Util::get_client_settings($settings);

      $this->client = new Client($settings);
      $name = get_option(Constants::OPTION_PRIMARY_INDEX);
      $this->index = $this->client->getIndex($name);
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
    public function search($query, $pageIndex = 0, $size = 10, $facets = array(), $sortByDate = false)
    {
        if (empty($query) || (empty($query['query']) && empty($query['aggs']))) {
            return array(
                'total' => 0,
                'ids' => array(),
                'facets' => array()
            );
        }

        return $this->query($query);
    }

    /**
     * @internal
     **/
    private function query($args)
    {
        $query = new \Elastica\Query($args);

        $query = Config::apply_filters('searcher_query', $query);

        try {
            $search = new \Elastica\Search($this->client);
            $search->addIndex($this->index);

            $search = Config::apply_filters('searcher_search', $search, $query);

            return $search->search($query);
        } catch (\Exception $ex) {
            error_log($ex);

            Config::do_action('searcher_exception', $ex);

            return null;
        }
    }

}

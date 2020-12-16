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
        $status     = $this->settings_manager->get(Constants::OPTION_INDEX_STATUS);
        $capability = $this->settings_manager->get(Constants::OPTION_CAPABILITY);

        $private_index = $this->settings_manager->get(Constants::OPTION_PRIVATE_INDEX);
        $public_index  = $this->settings_manager->get(Constants::OPTION_INDEX);

        if (isset($private_index) && !empty($private_index) && current_user_can($capability)) {
            return $this->client->getIndex($private_index);
        }

        return $this->client->getIndex($public_index);
    }
}

<?php
namespace makeandship\elasticsearch;

use makeandship\elasticsearch\settings\SettingsManager;
use makeandship\elasticsearch\transformer\SearchTransformer;
use makeandship\logging\Log;
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

        $use_cache = Util::apply_filters('use_search_cache', true);
        Log::debug('Searcher#search: Use search cache: ' . $use_cache);

        if ($use_cache) {
            $serialized = serialize($args);
            $cache_key  = 'search_' . md5($serialized);
            Log::debug('Searcher#search: Cache key: ' . $cache_key);

            $results = get_transient($cache_key);
        } else {
            $results = null;
        }

        if ($results) {
            Log::debug('Searcher#search: Using cached results');
            return $results;
        } else {
            Log::debug('Searcher#search: Generating results ...');
            $query = new \Elastica\Query($args);

            try {
                $search = new \Elastica\Search($this->client);
                $search->addIndex($this->get_index());

                $response = $search->search($query);

                $transformer = new SearchTransformer();
                $results     = $transformer->transform($response);

                if ($use_cache && $results) {
                    $cache_expiry = Util::apply_filters('search_cache_expiry', 3600); // 1 hr
                    Log::debug('Searcher#search: Cache expiry: ' . $cache_expiry);

                    set_transient($cache_key, $results, $cache_expiry);
                }

                return $results;
            } catch (\Exception $ex) {
                Log::debug('Searcher#search: ' . $ex);

                Util::do_action('search_exception', $ex);

                return null;
            }
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
            Log::debug('Searcher#query: ' . $ex);

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

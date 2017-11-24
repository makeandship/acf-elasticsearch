<?php

namespace makeandship\elasticsearch;

use makeandship\elasticsearch\Constants;

use makeandship\elasticsearch\settings\SettingsManager;

use makeandship\elasticsearch\domain\SitesManager;
use makeandship\elasticsearch\domain\PostsManager;
use makeandship\elasticsearch\domain\TaxonomiesManager;

use makeandship\elasticsearch\Util;

use \Elastica\Client;
use \Elastica\Exception\ResponseException;
use \Elastica\Response;

class Indexer
{
    public function __construct()
    {
        // factories
        $this->document_builder_factory = new DocumentBuilderFactory();
        $this->type_factory = new TypeFactory();
    }

    /**
     * Create a new index
     */
    public function create($name)
    {
        $errors = array();

        $shards = Constants::DEFAULT_SHARDS;
        $replicas = Constants::DEFAULT_REPLICAS;

        // elastic client to the cluster/server
        $client_settings = SettingsManager::get_instance()->get_client_settings();
        $client = new Client($client_settings);

        // remove the current index
        $index = $client->getIndex($name);
        if ($index->exists()) {
            try {
                $index->delete();
            } catch (ResponseException $ex) {
                $response = $ex->getResponse();
                $error = $response->getFullError();
                
                $errors[]= $error;
            }
        }

        $analysis = array(
            'filter' => array(
                'ngram_filter' => array(
                    'type' => 'edge_ngram',
                    'min_gram' => 1,
                    'max_gram' => 20,
                    'token_chars' => array(
                        'letter',
                        'digit',
                        'punctuation',
                        'symbol'
                    )
                )
            ),
            'analyzer' => array(
                'analyzer_startswith' => array(
                    'tokenizer' => 'keyword',
                    'filter'=> 'lowercase'
                ),
                'ngram_analyzer' => array(
                    'type' => 'custom',
                    'tokenizer' => 'whitespace',
                    'filter' => array(
                        'lowercase',
                        'asciifolding',
                        'ngram_filter'
                    )
                ),
                'whitespace_analyzer' => array(
                    'type' => 'custom',
                    'tokenizer' => 'whitespace',
                    'filter' => array(
                        'lowercase',
                        'asciifolding'
                    )
                )
            )
        );

        $settings = array(
            'number_of_shards' => $shards,
            'number_of_replicas' => $replicas,
            'analysis' => $analysis
        );

        // create the index
        try {
            $response = $index->create($settings);
        } catch (\Exception $ex) {
            // likely index doesn't exist
            $errors[] = $ex;
        }
        
        if (isset($errors) && !empty($errors)) {
            return $errors;
        } else {
            return $response;
        }
    }

    /**
     * Clear the index
     */
    public function clear($name)
    {
        $errors = array();

        // elastic client to the cluster/server
        $client_settings = SettingsManager::get_instance()->get_client_settings();
        $client = new Client($client_settings);

        // remove the current index
        $index = $client->getIndex($name);
        try {
            $index->delete();
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $error = $response->getFullError();

            // ignore if there's no index as that's the state we want
            $is_index_error = strpos($error, 'IndexMissingException');
            if ($is_index_error === false) {
                $errors = $ex;
            }
        }
    }

    public function index_posts($fresh)
    {
        if (is_multisite()) {
            $status = $this->index_posts_multisite($fresh);
        } else {
            $status = $this->index_posts_singlesite($fresh);
        }

        return $status;
    }

    public function index_posts_multisite($fresh)
    {
        $status = SettingsManager::get_instance()->get(Constants::OPTION_INDEX_STATUS);

        $posts_manager = new PostsManager();
        
        if ($fresh || (!isset($status) || empty($status))) {
            $status = $posts_manager->initialise_status($include_private);

            // store initial state
            SettingsManager::get_instance()->set(Constants::OPTION_INDEX_STATUS, $status);
        }

        // find the next site to index (or next page in a site to index)
        $target_site = null;
        foreach ($status as $site_status) {
            if ($site_status['count'] < $site_status['total']) {
                $target_site = $site_status;
                break;
            }
        }

        $blog_id = $target_site['blog_id'];
        $page = $target_site['page'];
        $per = Constants::DEFAULT_POSTS_PER_PAGE;

        // get and update posts
        $posts = $posts_manager->get_posts($blog_id, $page, $per);
        $count = $this->add_or_update_documents($posts);

        // update status
        $target_site['count'] = $target_site['count'] || 0;
        $target_site['page'] = $page + 1;
        $target_site['count'] = $target_site['count'] + $count;
        $status[$blog_id] = $target_site;
        SettingsManager::get_instance()->set(Constants::OPTION_INDEX_STATUS, $status);

        return $status;
    }

    public function index_posts_singlesite($fresh)
    {
        $status = SettingsManager::get_instance()->get(Constants::OPTION_INDEX_STATUS);

        $posts_manager = new PostsManager();
        
        if ($fresh || (!isset($status) || empty($status))) {
            $status = $posts_manager->initialise_status();

            // store initial state
            SettingsManager::get_instance()->set(Constants::OPTION_INDEX_STATUS, $status);
        }

        // find the next site to index (or next page in a site to index)
        $page = $status['page'];
        $per = Constants::DEFAULT_POSTS_PER_PAGE;

        // get and update posts
        $posts = $posts_manager->get_posts(null, $page, $per);
        $count = $this->add_or_update_documents($posts);

        // update status
        $status['page'] = $page + 1;
        $status['count'] = $status['count'] + $count;
        
        SettingsManager::get_instance()->set(Constants::OPTION_INDEX_STATUS, $status);

        error_log(print_r($status, true));

        return $status;
    }

    public function index_taxonomies($name)
    {
        $taxonomies_manager = new TaxonomiesManager();
        $terms = $taxonomies_manager->get_taxonomies();
        $count = $this->add_or_update_documents($terms);

        error_log('Indexed '.strval($count).' terms');

        return $count;
    }

    public function index_sites($page, $per)
    {
    }

    /**
     * Add a set of wordpress objects to an index
     *
     * Supported objects are
     * - WP_Post
     * - WP_Term
     * - WP_Site
     *
     * @param $o the wordpress object to add
     */
    public function add_or_update_documents($o)
    {
        $count = 0;

        // TODO for now go one by one - later switch to bulk
        foreach ($o as $item) {
            $this->add_or_update_document($item);

            $count++;
        }

        return $count;
    }

    /**
     * Add a wordpress object to an index
     *
     * Supported objects are
     * - WP_Post
     * - WP_Term
     * - WP_Site
     *
     * @param $o the wordpress object to add
     */
    public function add_or_update_document($o)
    {
        $builder = $this->document_builder_factory->create($o);

        $private = $builder->is_private($o);
        
        $document = $builder->build($o);
        $id = $builder->get_id($o);
        $doc_type = $builder->get_type($o);

        // ensure the document and id are valid before indexing
        if (isset($document) && !empty($document) &&
            isset($id) && !empty($id)) {
            if ($private) {
                // only index private documents in the private repository
                $type = $this->type_factory->create($doc_type, false, true);
                $type->addDocument(new \Elastica\Document($id, $document));
            } else {
                // index public documents in the public repository
                $type = $this->type_factory->create($doc_type, false, false);
                $type->addDocument(new \Elastica\Document($id, $document));
                // index public documents in the private repository
                $type = $this->type_factory->create($doc_type, false, true);
                $type->addDocument(new \Elastica\Document($id, $document));
            }
        }
    }

    /**
     * Remove a wordpress object from an index
     *
     * Supported objects are
     * - WP_Post
     * - WP_Term
     *
     * @param $o the wordpress object to remove
     */
    public function remove_document($o)
    {
        $builder = $this->document_builder_factory->create($o);
        $id = $builder->get_id($o);

        // ensure the document and id are valid before indexing
        if (isset($document) && !empty($document) &&
            isset($id) && !empty($id)) {
            $type = $this->type_factory->create($o);
            if ($type) {
                try {
                    $type->deleteById($id);
                } catch (\Elastica\Exception\NotFoundException $ex) {
                    // ignore
                }
            }
        }
    }
}

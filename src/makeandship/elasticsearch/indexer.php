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
    public function __construct($bulk=false)
    {
        // factories
        $this->document_builder_factory = new DocumentBuilderFactory();
        $this->type_factory = new TypeFactory();
        $this->bulk = $bulk;

        // bulk indexing
        $this->queues = array();
        $this->types = array();
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
            $status = $posts_manager->initialise_status();

            // store initial state
            SettingsManager::get_instance()->set(Constants::OPTION_INDEX_STATUS, $status);
        }

        // find the next site to index (or next page in a site to index)
        $target_site = null;
        $completed = false;
        $secondary = get_option(Constants::OPTION_SECONDARY_INDEX);
        $use_secondary = isset($secondary) && !empty($secondary);
        foreach ($status as $site_status) {
            $completed = true;
            if ($site_status['count'] < $site_status['total']) {
                $target_site = $site_status;
                $completed = false;
                break;
            } elseif ($site_status['index'] == 'primary' && $use_secondary) {
                $target_site = array(
                    'page' => 1,
                    'count' => 0,
                    'total' => $site_status['total'],
                    'blog_id' => $site_status['blog_id'],
                    'index' => 'secondary'
                );
                $completed = false;
                break;
            }
        }

        $blog_id = $target_site['blog_id'];
        $page = $target_site['page'];
        $per = Constants::DEFAULT_POSTS_PER_PAGE;

        // get and update posts
        $posts = $posts_manager->get_posts($blog_id, $page, $per);
        $count = $this->add_or_update_documents($posts);

        // flush bulk indexing
        if ($this->bulk) {
            $this->flush();
        }

        // update status
        $target_site['count'] = $target_site['count'] || 0;
        $target_site['page'] = $page + 1;
        $target_site['count'] = $target_site['count'] + $count;
        $status[$blog_id] = $target_site;
        $status['completed'] = $completed;
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

        // gather posts and time
        $before = microtime(true);

        $posts = $posts_manager->get_posts(null, $page, $per);

        $after = microtime(true);
        $search_time = ($after-$before) . " sec";
        error_log("Gathering posts: ".$search_time);

        // index documents and time
        $before = microtime(true);

        $count = $this->add_or_update_documents($posts);

        // flush bulk indexing
        if ($this->bulk) {
            $this->flush();
        }

        $after = microtime(true);
        $search_time = ($after-$before) . " sec";
        error_log("Indexing: ".$search_time);

        // update count
        $status['count'] = $status['count'] + $count;

        if ($status['count'] >= $status['total']) {
            $secondary = get_option(Constants::OPTION_SECONDARY_INDEX);
            $use_secondary = isset($secondary) && !empty($secondary);

            if ($status['index'] == "primary" && $use_secondary) {
                $status = array(
                    'page' => 1,
                    'count' => 0,
                    'total' => $status['total'],
                    'index' => 'secondary',
                    'completed' => false
                );
            } else {
                $status['completed'] = true;
            }
        } else {
            // only update page if we're not complete
            $status['page'] = $page + 1;
        }

        SettingsManager::get_instance()->set(Constants::OPTION_INDEX_STATUS, $status);

        return $status;
    }

    public function index_taxonomies()
    {
        $taxonomies_manager = new TaxonomiesManager();
        $terms = $taxonomies_manager->get_taxonomies();
        $count = $this->add_or_update_documents($terms);

        // flush bulk indexing
        if ($this->bulk) {
            $this->flush();
        }

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
    public function add_or_update_document($o, $new=false)
    {
        $status = SettingsManager::get_instance()->get(Constants::OPTION_INDEX_STATUS);
        $builder = $this->document_builder_factory->create($o);

        $indexable = $builder->is_indexable($o);

        if ($indexable) {
            $private = $builder->is_private($o);

            if (is_multisite()) {
                $blog_id = get_current_blog_id();
                $primary = $status[$blog_id]['index'] == "primary";
            } else {
                $primary = $status['index'] == "primary";
            }

            $private_fields = $builder->has_private_fields();

            $document = $builder->build($o, false, true);

            if ($private_fields) {
                $private_document = $builder->build($o, true, true);
            } else {
                $private_document = $document;
            }

            $id = $builder->get_id($o);
            $doc_type = $builder->get_type($o);
            $mapping_type = $builder->get_mapping_type($o);

            // ensure the document and id are valid before indexing
            if (isset($document) && !empty($document) &&
                isset($id) && !empty($id)) {
                if (!$private) {
                    $document = apply_filters('acf_elasticsearch_pre_add_document', $document);
                    // index public documents in the public repository
                    $public_type = $this->type_factory->create($mapping_type, false, false, $primary);
                    if ($public_type) {
                        if ($this->bulk) {
                            $this->queue($public_type, new \Elastica\Document($id, $document));
                        } else {
                            $public_type->addDocument(new \Elastica\Document($id, $document));
                        }
                    }
                    if ($new) {
                        $public_type = $this->type_factory->create($mapping_type, false, false, !$primary);
                        if ($public_type) {
                            if ($this->bulk) {
                                $this->queue($public_type, new \Elastica\Document($id, $document));
                            } else {
                                $public_type->addDocument(new \Elastica\Document($id, $document));
                            }
                        }
                    }
                }
                // index everything to private index
                $private_type = $this->type_factory->create($mapping_type, false, true, $primary);
                if ($private_type) {
                    $private_document = apply_filters('acf_elasticsearch_pre_add_private_document', $private_document);
                    if ($this->bulk) {
                        $this->queue($private_type, new \Elastica\Document($id, $private_document));
                    } else {
                        $private_type->addDocument(new \Elastica\Document($id, $private_document));
                    }
                }
                if ($new) {
                    $private_type = $this->type_factory->create($mapping_type, false, true, !$primary);
                    if ($private_type) {
                        $private_document = apply_filters('acf_elasticsearch_pre_add_private_document', $private_document);
                        if ($this->bulk) {
                            $this->queue($private_type, new \Elastica\Document($id, $private_document));
                        } else {
                            $private_type->addDocument(new \Elastica\Document($id, $private_document));
                        }
                    }
                }
            }
        }
    }

    /**
     * For bulk indexing add a document to a queue
     */
    private function queue($type, $document)
    {
        if ($type) {
            $type_name = $type->getName();
            $index = $type->getIndex();
            if ($index) {
                $index_name = $index->getName();

                $key = $index_name . $type_name;

                if ($key) {
                    if (!$this->queues) {
                        $this->queues = array();
                    }
                    if (!array_key_exists($key, $this->queues)) {
                        $this->queues[$key] = array();
                    }
                    $this->queues[$key][] = $document;

                    if (!$this->types) {
                        $this->types = array();
                    }

                    if (!array_key_exists($key, $this->types)) {
                        $this->types[$key] = $type;
                    }
                }
            }
        }
    }

    /**
     * Flush all queues
     *
     */
    public function flush()
    {
        if ($this->types && $this->queues) {
            foreach ($this->queues as $key => $documents) {
                $type = $this->types[$key];

                if ($type && $documents && count($documents) > 0) {
                    // add the documents
                    $type->addDocuments($documents);
                    $type->getIndex()->refresh();

                    unset($this->queues[$key]);
                }
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
        $private = $builder->is_private($o);
        $id = $builder->get_id($o);

        $doc_type = $builder->get_type($o);
        $mapping_type = $builder->get_mapping_type($o);

        // ensure the document and id are valid before indexing
        if (isset($o) && !empty($o) &&
                isset($id) && !empty($id)) {
            // attempt to clear from all types - post_status = private won't be available
            $primary_public_type = $this->type_factory->create($mapping_type, false, false, true);
            if ($primary_public_type) {
                try {
                    $primary_public_type->deleteById($id);
                } catch (\Elastica\Exception\NotFoundException $ex) {
                    // ignore
                    error_log('Unable to delete from primary public index');
                }
            }

            $secondary_public_type = $this->type_factory->create($mapping_type, false, false, false);
            if ($secondary_public_type) {
                try {
                    $secondary_public_type->deleteById($id);
                } catch (\Elastica\Exception\NotFoundException $ex) {
                    // ignore
                    error_log('Unable to delete from secondary public index');
                }
            }

            $primary_private_type = $this->type_factory->create($mapping_type, false, true, true);
            if ($primary_private_type) {
                try {
                    $primary_private_type->deleteById($id);
                } catch (\Elastica\Exception\NotFoundException $ex) {
                    // ignore
                    error_log('Unable to delete from primary private index');
                }
            }

            $secondary_private_type = $this->type_factory->create($mapping_type, false, true, false);
            if ($secondary_private_type) {
                try {
                    $secondary_private_type->deleteById($id);
                } catch (\Elastica\Exception\NotFoundException $ex) {
                    // ignore
                    error_log('Unable to delete from secondary private index');
                }
            }
        }
    }
}

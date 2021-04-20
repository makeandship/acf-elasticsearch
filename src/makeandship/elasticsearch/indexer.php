<?php

namespace makeandship\elasticsearch;

use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\domain\PostsManager;
use makeandship\elasticsearch\domain\TaxonomiesManager;
use makeandship\elasticsearch\settings\SettingsManager;
use \Elastica\Client;
use \Elastica\Exception\ResponseException;

class Indexer
{
    public function __construct($bulk = false)
    {
        // factories
        $this->document_builder_factory = new DocumentBuilderFactory();
        $this->index_factory            = new IndexFactory();
        $this->bulk                     = $bulk;

        // bulk indexing
        $this->queues  = array();
        $this->indexes = array();
    }

    /**
     * Create a new index
     */
    public function create($name)
    {
        $errors = array();

        $shards   = Util::apply_filters('index_shards', Constants::DEFAULT_SHARDS);
        $replicas = Util::apply_filters('index_replicas', Constants::DEFAULT_REPLICAS);

        // elastic client to the cluster/server
        $client_settings = SettingsManager::get_instance()->get_client_settings();
        $client          = new Client($client_settings);

        // remove the current index
        $index = $client->getIndex($name);
        if ($index->exists()) {
            try {
                $index->delete();
            } catch (ResponseException $ex) {
                $response = $ex->getResponse();
                $error    = $response->getFullError();

                $errors[] = $error;
            }
        }

        $analysis = array(
            'filter'   => array(
                'ngram_filter' => array(
                    'type'        => 'edge_ngram',
                    'min_gram'    => 1,
                    'max_gram'    => 20,
                    'token_chars' => array(
                        'letter',
                        'digit',
                        'punctuation',
                        'symbol',
                    ),
                ),
            ),
            'analyzer' => array(
                'analyzer_startswith' => array(
                    'tokenizer' => 'keyword',
                    'filter'    => 'lowercase',
                ),
                'ngram_analyzer'      => array(
                    'type'      => 'custom',
                    'tokenizer' => 'whitespace',
                    'filter'    => array(
                        'lowercase',
                        'asciifolding',
                        'ngram_filter',
                    ),
                ),
                'whitespace_analyzer' => array(
                    'type'      => 'custom',
                    'tokenizer' => 'whitespace',
                    'filter'    => array(
                        'lowercase',
                        'asciifolding',
                    ),
                ),
            ),
        );

        $settings = array(
            'settings' => array(
                'index'    => array(
                    'number_of_shards'   => $shards,
                    'number_of_replicas' => $replicas,
                ),
                'analysis' => $analysis,
            ),
        );

        $settings = Util::apply_filters('pre_create_index', $settings);

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
            return $client->getIndex($name);
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
        $client          = new Client($client_settings);

        // remove the current index
        $index = $client->getIndex($name);
        try {
            $index->delete();
        } catch (ResponseException $ex) {
            $response = $ex->getResponse();
            $error    = $response->getFullError();

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
        $completed   = false;

        foreach ($status as $site_status) {
            $completed = true;
            if ($site_status['count'] < $site_status['total']) {
                $target_site = $site_status;
                $completed   = false;
                break;
            }
        }

        $blog_id = $target_site['blog_id'];
        $page    = $target_site['page'];
        $per     = Util::apply_filters('bulk_posts_per_page', Constants::DEFAULT_POSTS_PER_PAGE);

        // get and update posts
        $posts = $posts_manager->get_posts($blog_id, $page, $per);
        $count = $this->add_or_update_documents($posts);

        // flush bulk indexing
        if ($this->bulk) {
            $response = $this->flush();
            $this->log_flush_response($response);
        }

        // update status
        $target_site['count'] = $target_site['count'] || 0;
        $target_site['page']  = $page + 1;
        $target_site['count'] = $target_site['count'] + $count;
        $status[$blog_id]     = $target_site;
        $status['completed']  = $completed;

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
        $per  = Util::apply_filters('bulk_posts_per_page', Constants::DEFAULT_POSTS_PER_PAGE);

        // gather posts and time
        $before = microtime(true);

        $posts = $posts_manager->get_posts(null, $page, $per);

        $after       = microtime(true);
        $search_time = ($after - $before) . " sec";
        Util::debug("Indexer#index_posts_singlesite", "Gathering posts: " . $search_time);

        // index documents and time
        $before = microtime(true);

        Util::debug("Indexer#index_posts_singlesite", "Count of posts: " . count($posts));
        $ids = array_map(function ($post) {
            return Util::safely_get_attribute($post, 'ID');
        }, $posts);
        Util::debug("Indexer#index_posts_singlesite", "IDs of posts: " . implode($ids, ", "));
        $count = $this->add_or_update_documents($posts);

        // flush bulk indexing
        if ($this->bulk) {
            $response = $this->flush();
            $this->log_flush_response($response);
        }

        $after       = microtime(true);
        $search_time = ($after - $before) . " sec";
        Util::debug("Indexer#index_posts_singlesite", "Indexing: " . $search_time);

        // update count
        $status['count'] = $status['count'] + $count;

        // counts
        $private_index = $this->index_factory->create(true);
        if ($private_index) {
            Util::debug("Indexer#index_posts_singlesite", "Current count: " . $private_index->count());
        }

        if ($status['count'] >= $status['total']) {
            $status['count']     = $status['total'];
            $status['completed'] = true;
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
        $terms              = $taxonomies_manager->get_taxonomies();
        $count              = $this->add_or_update_documents($terms);

        // flush bulk indexing
        if ($this->bulk) {
            $response = $this->flush();
            $this->log_flush_response($response);
        }

        Util::debug("Indexer#index_taxonomies", "Indexed " . strval($count) . " terms");

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
     * Add a set of wordpress objects for a given term and taxonomy
     *
     * @param $term_id a term_id or array of term_ids that have changed
     * @param $taxonomy of the changed term
     */
    public function add_or_update_documents_by_term($term_id, $taxonomy)
    {
        $count = 0;

        if ($term_id && $taxonomy) {
            $posts_manager = new PostsManager();
            $posts         = $posts_manager->get_posts_by_term(null, $term_id, $taxonomy);

            if ($posts && is_array($posts) && count($posts) > 0) {
                foreach ($posts as $item) {
                    $this->add_or_update_document($item);

                    $count++;
                }
            }
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
        $status  = SettingsManager::get_instance()->get(Constants::OPTION_INDEX_STATUS);
        $builder = $this->document_builder_factory->create($o);

        $indexable = $builder->is_indexable($o);
        if (!$indexable) {
            Util::debug("Indexer#add_or_update_document", Util::safely_get_attribute($o, 'ID') . " is not indexable");
        }

        if ($indexable) {
            $private = $builder->is_private($o);
            if ($private) {
                Util::debug("Indexer#add_or_update_document", "Document is private");
            }

            if (is_multisite()) {
                $blog_id = get_current_blog_id();
            }

            $private_fields = $builder->has_private_fields();

            $document = $builder->build($o, false, true);

            if ($private_fields) {
                $private_document = $builder->build($o, true, true);
            } else {
                $private_document = $document;
            }

            $id           = $builder->get_id($o);
            $doc_type     = $builder->get_type($o);
            $mapping_type = $builder->get_mapping_type($o);

            // ensure the document and id are valid before indexing
            if (isset($document) && !empty($document) &&
                isset($id) && !empty($id)) {
                if (!$private) {
                    $document = Util::apply_filters('pre_add_document', $document, $id);
                    // index public documents in the public repository
                    $index = $this->index_factory->create(false);
                    if ($index) {
                        if ($this->bulk) {
                            $this->queue($index, new \Elastica\Document($id, $document));
                        } else {
                            $index->addDocument(new \Elastica\Document($id, $document));
                        }
                    }
                }
                // index everything to private index
                $index = $this->index_factory->create(true);
                if ($index) {
                    $private_document = Util::apply_filters('pre_add_private_document', $private_document, $id);
                    if ($this->bulk) {
                        $this->queue($index, new \Elastica\Document($id, $private_document));
                    } else {
                        $index->addDocument(new \Elastica\Document($id, $private_document));
                    }
                }
            }
        }
    }

    /**
     * For bulk indexing add a document to a queue
     */
    private function queue($index, $document)
    {
        if ($index) {
            $index_name = $index->getName();
            $key        = $index_name;

            if ($key) {
                if (!$this->queues) {
                    $this->queues = array();
                }
                if (!array_key_exists($key, $this->queues)) {
                    $this->queues[$key] = array();
                }
                $this->queues[$key][] = $document;
                Util::debug("Indexer#queue", "Add " . $document->getId() . " to " . $key);

                if (!$this->indexes) {
                    $this->indexes = array();
                }

                if (!array_key_exists($key, $this->indexes)) {
                    $this->indexes[$key] = $index;
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
        $response = array();

        if ($this->indexes && $this->queues) {
            foreach ($this->queues as $key => $documents) {
                $index = $this->indexes[$key];

                if ($index && $documents && count($documents) > 0) {
                    // add the documents
                    $add_documents_response = $index->addDocuments($documents);
                    $index_response         = $index->refresh();
                    Util::debug("Indexer#flush", "Added " . count($documents) . " to " . $key);

                    unset($this->queues[$key]);

                    $response[$key] = array(
                        'add_documents' => $this->get_status_from_response($add_documents_response),
                        'index'         => $this->get_status_from_response($index_response),
                    );
                }
            }
        }

        return $response;
    }

    private function get_status_from_response($response)
    {
        if ($response) {
            $status  = $response->getStatus();
            $message = $response->getErrorMessage();

            if ($status) {
                return $status . ($message ? ": " . $message : "");
            } else {
                return $message ? $message : "";
            }

        }

        return null;
    }

    private function log_flush_response($response)
    {
        if ($response) {
            $messages = array();

            foreach ($response as $name => $message) {
                $methods = array();
                foreach ($message as $method => $method_message) {
                    $methods[] = $method . ': ' . $method_message;
                }
                $messages[] = $name . ': ' . implode($methods, ", ");
            }

            Util::debug("Indexer#flush", implode($messages, ", "));
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
        $id      = $builder->get_id($o);

        $doc_type     = $builder->get_type($o);
        $mapping_type = $builder->get_mapping_type($o);

        // ensure the document and id are valid before indexing
        if (isset($o) && !empty($o) &&
            isset($id) && !empty($id)) {
            // attempt to clear from all types - post_status = private won't be available
            $public_index = $this->index_factory->create(false);
            if ($public_index) {
                try {
                    $public_index->deleteById($id);
                } catch (\Elastica\Exception\NotFoundException $ex) {
                    // ignore
                    Util::debug('Indexer#remove_document', 'Unable to delete from public index');
                }
            }

            $private_index = $this->index_factory->create(true);
            if ($private_index) {
                try {
                    $private_index->deleteById($id);
                } catch (\Elastica\Exception\NotFoundException $ex) {
                    // ignore
                    Util::debug('Indexer#remove_document', 'Unable to delete from private index');
                }
            }
        }
    }
}

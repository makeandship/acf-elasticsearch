<?php

namespace makeandship\elasticsearch;

use makeandship\elasticsearch\admin\UserInterfaceManager;
use makeandship\elasticsearch\domain\PostsManager;
use makeandship\elasticsearch\settings\SettingsManager;

class AcfElasticsearchPlugin
{
    public function __construct()
    {
        $this->indexer = new Indexer();

        $this->ui = new UserInterfaceManager(Constants::VERSION, Constants::DB_VERSION, $this);

        $this->multisite = (function_exists('is_multisite') && is_multisite());

        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        $this->initialise_plugin_hooks();
        $this->initialise_index_hooks();
    }

    public function get_indexer()
    {
        return $this->indexer;
    }

    public function initialise_plugin_hooks()
    {
        // wordpress initialisation
        add_action('admin_init', array($this, 'initialise'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'initialise_menu'));

        // activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function initialise_index_hooks()
    {
        // plugin
        add_action('wp_ajax_create_mappings', array(&$this, 'create_mappings'));
        add_action('wp_ajax_index_posts', array(&$this, 'index_posts'));
        add_action('wp_ajax_index_taxonomies', array(&$this, 'index_taxonomies'));
        add_action('wp_ajax_clear_index', array(&$this, 'clear_index'));

        // posts
        add_action('save_post', array(&$this, 'save_post'), 20, 1); // after acf fields save - 15
        add_action('delete_post', array(&$this, 'delete_post'));
        add_action('trash_post', array(&$this, 'delete_post'));
        add_action('transition_post_status', array(&$this, 'transition_post_status'), 10, 3);

        // taxonomies
        add_action('create_term', array(&$this, 'create_term'), 10, 3);
        add_action('edited_term', array(&$this, 'edit_term'), 10, 3);
        add_action('delete_term', array(&$this, 'delete_term'), 10, 5);
        add_action('registered_taxonomy', array(&$this, 'registered_taxonomy'), 10, 3);
    }

    /**
     * -------------------
     * Index Administration
     * -------------------
     */
    public function create_mappings()
    {
        $indexes = SettingsManager::get_instance()->get_indexes();
        $indexer = new Indexer();

        foreach ($indexes as $index) {
            $name          = $index['name'];
            $indexer->create($name);
        }

        // extract message from result
        $message = 'Mappings were created successfully';

        // json response
        $json = json_encode(array(
            'message' => $message,
        ));
        die($json);
    }

    public function index_posts()
    {
        $fresh = isset($_POST['fresh']) ? ($_POST['fresh'] === 'true') : false;

        $indexer = new Indexer(true); // use bulk indexing
        $status  = 0;

        $status = $indexer->index_posts($fresh);

        $response = array(
            'message' => 'Posts were indexed successfully',
            'status'  => $status,
        );

        $json = json_encode($response);
        die($json);
    }

    public function index_taxonomies()
    {
        Util::debug('AcfElasticsearchPlugin#index_taxonomies', 'enter');

        // instantiate the index and current status
        $indexer = new Indexer(true);
        $status  = SettingsManager::get_instance()->get(Constants::OPTION_INDEX_STATUS);

        $count = $indexer->index_taxonomies();

        $json = json_encode(array(
            'message' => $count . ' taxonomies were indexed successfully',
        ));
        die($json);
    }

    public function clear_index()
    {
        $this->create_mappings();

        $json = json_encode(array(
            'message' => 'Index was cleared successfully',
        ));
        die($json);
    }

    /**
     * -------------------
     * Index Lifecycle
     * -------------------
     */

    /**
     *
     */
    public function save_post($post_id)
    {
        Util::debug('AcfElasticsearchPlugin#save_post', $post_id);
        // get the post to index
        if (is_object($post_id)) {
            $post = $post_id;
        } else {
            $post = get_post($post_id);
        }

        // can't index empty posts
        if ($post == null) {
            return;
        }

        // check post is a valid type to index
        if (!$this->should_index_post($post)) {
            return;
        }

        // index valid statuses
        if (in_array($post->post_status, Constants::INDEX_POST_STATUSES)) {
            Util::debug('AcfElasticsearchPlugin#save_post', 'Add/update document: ' . $post_id);
            // index
            $this->indexer->add_or_update_document($post);
        } else {
            Util::debug('AcfElasticsearchPlugin#save_post', 'Remove document: ' . $post_id);
            // remove
            $this->indexer->remove_document($post);
        }
    }

    /**
     *
     */
    public function delete_post($post_id)
    {
        Util::debug('AcfElasticsearchPlugin#delete_post', $post_id);
        $post = get_post($post_id);
        $this->indexer->remove_document($post);
    }

    /**
     *
     */
    public function trash_post($post_id)
    {
        Util::debug('AcfElasticsearchPlugin#trash_post', $post_id);
        $this->delete_post($post_id);
    }

    /**
     *
     */
    public function transition_post_status($new_status, $old_status, $post)
    {
        $post_id = Util::safely_get_attribute($post, 'ID');
        Util::debug('AcfElasticsearchPlugin#transition_post_status', ($post_id ? $post_id : "Unknown post id"));
        if (!$this->should_index_post($post)) {
            return;
        }
        if (in_array($new_status, Constants::INDEX_POST_STATUSES) && $new_status != $old_status) {
            Util::debug('AcfElasticsearchPlugin#transition_post_status', 'Add/update document: ' . ($post_id ? $post_id : "Unknown post id"));
            $this->indexer->add_or_update_document($post);
        } else {
            if ($new_status != "publish" && $old_status != "publish") {
                Util::debug('AcfElasticsearchPlugin#transition_post_status', 'Remove document: ' . ($post_id ? $post_id : "Unknown post id"));
                $this->indexer->remove_document($post);
            }
        }
    }

    /**
     *
     */
    public function should_index_post($post)
    {
        $manager = new PostsManager();
        return $manager->valid($post->post_type);
    }

    /**
     *
     */
    public function create_term($term_id, $tt_id, $taxonomy)
    {
        // get the term to index
        $term = get_term($term_id, $taxonomy);

        // can't index empty terms
        if ($term == null) {
            return;
        }

        $this->indexer->add_or_update_document($term);
    }

    /**
     *
     */
    public function edit_term($term_id, $tt_id, $taxonomy)
    {
        // get the term to index
        $term = get_term($term_id, $taxonomy);

        // can't index empty terms
        if ($term == null) {
            return;
        }

        $this->indexer->add_or_update_document($term);

        // re-index any impacted posts
        $this->indexer->add_or_update_documents_by_term($term_id, $taxonomy);
    }

    /**
     *
     */
    public function delete_term($term_id, $tt_id, $taxonomy, $deleted_term, $object_ids)
    {
        $term = get_term($term_id, $taxonomy);
        $this->indexer->remove_document($deleted_term);

        // re-index impacted posts
        if ($object_ids && is_array($object_ids) && count($object_ids)) {
            foreach ($object_ids as $object_id) {
                Util::debug('AcfElasticsearchPlugin#delete_term', 'Reindex ' . $object_id . ' after term delete');
                $this->save_post($object_id);
            }
        }
    }

    /**
     *
     */
    public function registered_taxonomy($taxonomy, $object_type, $args)
    {
        return true;
    }

    /**
     * ---------------------
     * Plugin Initialisation
     * ---------------------
     */
    public function initialise()
    {
        $this->ui->initialise_settings();
        $this->ui->initialise_settings();
    }

    public function initialise_menu()
    {
        // show a menu
        $this->ui->initialise_menu();
    }

    public function admin_enqueue_scripts()
    {
        // add custom css and js
        $this->ui->enqueue_scripts();
    }

    public function activate()
    {
        $this->initialise_settings();

        register_uninstall_hook(__FILE__, array($this, 'uninstall'));
    }

    public function deactivate()
    {
    }

    public function uninstall()
    {
    }
}

<?php

namespace makeandship\elasticsearch;

use \Elastica\Client;

class Indexer {

	public function __construct( $config ) {
		$this->config = $config;

		// factories
		$this->document_builder_factory = new DocumentBuilderFactory();
		$this->type_factory = new TypeFactory( $this->config);
	}

	/**
	 * Create a new index
	 */
	public function create( $name ) {
		$errors = array();

		$shards = Constants::DEFAULT_SHARDS;
		$replicas = Constants::DEFAULT_REPLICAS;

		// elastic client to the cluster/server
		$settings = array(
			'url' => $this->config[Constants::OPTION_SERVER]
		);
		$client = new Client($settings);

		// remove the current index
		$index = $client->getIndex( $name );
		try {
			$index->delete();
		} catch (\Exception $ex) {
			// likely index doesn't exist
			$errors[] = $ex->getActionExceptionsAsString();
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
		return $index->create( $settings );
	}

	public function index_posts( $page, $per, $count=0 ) {
		if (is_multisite()) {
			$this->index_posts_multisite( );
		}
		else {
			$this->index_posts_singlesite( );
		}
	}

	public function index_posts_multisite() {
		$status = $this->config[Constants::OPTION_INDEX_STATUS];

		if (!isset($status) || empty($status)) {
			$posts_manager = new PostsManager();
			$status = $posts_manager->initialise_status();

			// store initial state
			$options_manager = new OptionsManager();
			$options_manager->set(Constants::OPTION_INDEX_STATUS, $status);
		}
	}

	public function index_posts_singlesite() {
		/*
		$post_mapping_builder = new PostMappingBuilder();
		$post_types = $post_mapping_builder->get_valid_post_types();

		// args for count only 
		$args = array(
			'post_type' => $post_types,
			'post_status' => 'publish',
			'fields'=> 'count'
		);

		$total = get_posts( $args);

		// adjust arguments to retrieve full posts
		unset($args['fields']);
		$args['paged'] = $page;
		$args['posts_per_page'] = $per;

		$posts = get_posts( $args);

		// bulk update on a group of posts
		$indexed_total = $indexer->add_or_update_documents( $posts );

		$updated_count = $indexed_total + $total;

		return array(
			'total' => $total,
			'count' => $updated_count
		);
		*/
	}

	public function index_taxonomies( $page, $per ) {

	}

	public function index_sites( $page, $per ) {

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
	public function add_or_update_documents( $o ) {
		$count = 0;

		// TODO for now go one by one - later switch to bulk
		foreach( $o as $item ) {
			$this->add_or_update_document( $item );

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
	public function add_or_update_document( $o ) {
		$builder = $this->document_builder_factory->create( $o );
		$document = $builder->build( $o );
		$id = $builder->get_id( $o );

		// ensure the document and id are valid before indexing
		if (isset($document) && !empty($document) &&
			isset($id) && !empty($id)) {

			$type = $this->type_factory->create( $o );
			$type->addDocument(new \Elastica\Document($o->ID, $data));

			// response ?
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
	public function remove_document( $o ) {
		$builder = $this->document_builder_factory->create( $o );
		$id = $builder->get_id( $o );

		// ensure the document and id are valid before indexing
		if (isset($document) && !empty($document) &&
			isset($id) && !empty($id)) {

			$type = $this->type_factory->create( $o );
			if ($type) {
				try {
					$type->deleteById( $id );
				} 
				catch (\Elastica\Exception\NotFoundException $ex) {
					// ignore
				}
			}
		}
	}
}
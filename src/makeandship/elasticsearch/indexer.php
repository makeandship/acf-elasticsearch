<?php

namespace makeandship\elasticsearch;

check_and_require( 'DocumentBuilderFactory', dirname( __FILE__ ) . '/document_builder_factory.php' );
check_and_require( 'TypeFactory', dirname( __FILE__ ) . '/type_factory.php' );

use \Elastica\Client;

class Indexer {
	
	const DEFAULT_SHARDS = 1;
	const DEFAULT_REPLICAS = 1;

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

		$shards = Indexer::DEFAULT_SHARDS;
		$replicas = Indexer::DEFAULT_REPLICAS;

		// elastic client to the cluster/server
		$settings = array(
			'url' => $this->config[ACFElasticSearchPlugin::OPTION_SERVER]
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


	/**
	 * Add a wordpress object to an index
	 * 
	 * Supported objects are
	 * - WP_Post
	 * - WP_Term
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
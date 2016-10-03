<?php

namespace makeandship\elasticsearch;

check_and_require( 'DocumentBuilderFactory', dirname( __FILE__ ) . '/document_builder_factory.php' );
check_and_require( 'TypeFactory', dirname( __FILE__ ) . '/type_factory.php' );

class Indexer {
	
	public function __construct() {
		// factories
		$this->document_builder_factory = new DocumentBuilderFactory();
		$this->type_factory = new TypeFactory();
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
	public function add_or_update( $o ) {
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
	public function remove( $o ) {
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
<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\Indexer;
use makeandship\elasticsearch\Constants;

class IndexerTest extends WP_UnitTestCase
{
    public function testCreateIndexer()
    {
        $config = array(
            Constants::OPTION_SERVER => "127.0.0.1",
            Constants::DEFAULT_SHARDS => "1",
            Constants::DEFAULT_REPLICAS => "1"
        );
        $factory = new Indexer($config);
        $indexer = $factory->create('test_indexer');
    }
}
<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\Indexer;
use makeandship\elasticsearch\Constants;

class IndexerTest extends WP_UnitTestCase
{
    const CONFIG = array(
        Constants::OPTION_SERVER => "http://127.0.0.1:9200/"
    );

    public function testCreateIndexer()
    {
        $factory = new Indexer(self::CONFIG);
        $indexer = $factory->create('elastictest');

        $this->assertNotNull($indexer);
    }
}
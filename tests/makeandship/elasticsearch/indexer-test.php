<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\Indexer;
use makeandship\elasticsearch\Constants;

class IndexerTest extends WP_UnitTestCase
{
    const CONFIG = array(
        Constants::OPTION_SERVER => "http://127.0.0.1:9200/",
        Constants::OPTION_INDEX_STATUS => "acf_elasticsearch_index_status"
    );

    public function testCreateIndex()
    {
        $indexer = new Indexer();
        $index = $indexer->create('elastictest');

        $this->assertNotNull($index);
    }

    public function testClearIndex()
    {
        $indexer = new Indexer();
        $index = $indexer->create('elastictest');
        $indexer->clear('elastictest');

        $this->assertNotNull($index);
    }

    public function testIndexPosts()
    {
        $indexer = new Indexer();
        $posts = $indexer->index_posts(true);

        $this->assertEquals($posts['page'], 1);
        $this->assertEquals($posts['count'], 0);
        $this->assertEquals($posts['total'], 0);
    }

    public function testIndexPostsMultiSite()
    {
        define('WP_ALLOW_MULTISITE', true);
        $indexer = new Indexer();
        $mulisite = $indexer->index_posts(true);

        $this->assertEquals($mulisite['page'], 1);
        $this->assertEquals($mulisite['count'], 0);
        $this->assertEquals($mulisite['total'], 0);
    }

    public function testIndexPostsSingleSite()
    {
        $indexer = new Indexer();
        $singlesite = $indexer->index_posts_singlesite(true);

        $this->assertEquals($singlesite['page'], 1);
        $this->assertEquals($singlesite['count'], 0);
        $this->assertEquals($singlesite['total'], 0);
    }
}
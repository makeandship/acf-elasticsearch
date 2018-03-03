<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\Indexer;
use makeandship\elasticsearch\Constants;

class IndexerTest extends WP_UnitTestCase
{

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

        if (is_multisite()) {
            $this->assertEquals($posts[1]['page'], 2);
            $this->assertEquals($posts[1]['count'], 0);
            $this->assertEquals($posts[1]['total'], 0);
            $this->assertEquals($posts[1]['blog_id'], 1);
        }
        else {
            $this->assertEquals($posts['page'], 1);
            $this->assertEquals($posts['count'], 0);
            $this->assertEquals($posts['total'], 0);
        }
    }

    public function testIndexPostsMultiSite()
    {
        $indexer = new Indexer();
        $posts = $indexer->index_posts(true);

        if (is_multisite()) {
            $this->assertEquals($posts[1]['page'], 2);
            $this->assertEquals($posts[1]['count'], 0);
            $this->assertEquals($posts[1]['total'], 0);
            $this->assertEquals($posts[1]['blog_id'], 1);
        }
        else {
            $this->assertEquals($posts['page'], 1);
            $this->assertEquals($posts['count'], 0);
            $this->assertEquals($posts['total'], 0);
        }
    }

    public function testIndexTaxonomies()
    {
        $id = $this->factory->term->create( array( 
                'taxonomy' => 'tax',
                'name' => 'Term 1' 
            ) 
        ); 
        $indexer = new Indexer();
        $count = $indexer->index_taxonomies();

        $this->assertEquals($count, 1);
    }
}
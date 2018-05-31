<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\Searcher;
use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\queries\QueryBuilder;
use makeandship\elasticsearch\AcfElasticsearchPlugin;

class SearcherTest extends WP_UnitTestCase
{
    public function testSearchByKeyword()
    {
        $id = $this->factory->post->create( 
            array( 
                'post_title' => 'Just for testing',
                'post_type' => 'people'
            ) 
        );
        $searcher = new Searcher();
        $query = new QueryBuilder();
        $query = $query->freetext("Just")
            ->with_fuzziness()
            ->weighted();

        $result = $searcher->search($query->to_array());

        $this->assertNotNull($result);
    }
}
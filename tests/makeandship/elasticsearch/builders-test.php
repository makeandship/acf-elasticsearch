<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\PostDocumentBuilder;

class BuildersTest extends WP_UnitTestCase
{
    public function testPostDocumentBuilder()
    {
        $p = $this->factory->post->create( array( 'post_title' => 'Test Post' ) );        
    	$builder = new PostDocumentBuilder();
        $document = $builder->build($p);
        error_log(json_encode($document));
    }
}
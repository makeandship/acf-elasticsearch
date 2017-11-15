<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\PostDocumentBuilder;
use makeandship\elasticsearch\PostMappingBuilder;
use makeandship\elasticsearch\SiteMappingBuilder;
use makeandship\elasticsearch\TermDocumentBuilder;
use makeandship\elasticsearch\TermMappingBuilder;

class BuildersTest extends WP_UnitTestCase
{
    public function testPostDocumentBuilder()
    {
        $id = $this->factory->post->create( array( 'post_title' => 'Test Post' ) );        
    	$builder = new PostDocumentBuilder();
        $document = $builder->build(get_post($id));
        
        $this->assertEquals($document['post_title'], 'Test Post');
        $this->assertEquals($document['post_type'], 'post');
    }

    public function testPostMappingBuilder()
    {
        $id = $this->factory->post->create( array( 'post_title' => 'Test Post' ) );        
        $builder = new PostMappingBuilder();
        $post = get_post($id);
        $mapping = $builder->build($post->post_type);
        
        $this->assertEquals($mapping['post_content']['type'], 'string');
        $this->assertEquals($mapping['post_content']['index'], 'analyzed');
        $this->assertEquals($mapping['post_content_suggest']['analyzer'], 'ngram_analyzer');
        $this->assertEquals($mapping['post_content_suggest']['search_analyzer'], 'whitespace_analyzer');
        $this->assertEquals($mapping['post_type']['index'], 'not_analyzed');
    }

    public function testSiteMappingBuilder()
    {
        $site = get_bloginfo();        
        $builder = new SiteMappingBuilder();
        $mapping = $builder->build($site);

        $this->assertEquals($mapping['blog_id']['type'], 'integer');
        $this->assertEquals($mapping['blog_id']['index'], 'analyzed');
    }

    public function testTermDocumentBuilder()
    {
        $id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );        
        $builder = new TermDocumentBuilder();
        $document = $builder->build(get_term($id));

        $this->assertEquals($document['name_suggest'], 'Term 20');
    }

    public function testTermMappingBuilder()
    {
        $id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );         
        $builder = new TermMappingBuilder();
        $mapping = $builder->build($id);

        $this->assertEquals($mapping['name_suggest']['analyzer'], 'ngram_analyzer');
        $this->assertEquals($mapping['name_suggest']['search_analyzer'], 'whitespace_analyzer');
        $this->assertEquals($mapping['slug']['index'], 'not_analyzed');
    }
}
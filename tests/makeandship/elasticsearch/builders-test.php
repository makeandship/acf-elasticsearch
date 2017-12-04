<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\PostDocumentBuilder;
use makeandship\elasticsearch\PostMappingBuilder;
use makeandship\elasticsearch\SiteMappingBuilder;
use makeandship\elasticsearch\TermDocumentBuilder;
use makeandship\elasticsearch\TermMappingBuilder;
use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\settings\SettingsManager;

class BuildersTest extends WP_UnitTestCase
{
    public function testPostDocumentBuilder()
    {
        SettingsManager::get_instance()->set(Constants::OPTION_POST_TYPES, array(
            array(
                'type' => 'post',
                'exclude' => [],
                'private' => []
            )
        ));
        $id = $this->factory->post->create( array( 'post_title' => 'Test Post' ) );        
    	$builder = new PostDocumentBuilder();
        $document = $builder->build(get_post($id));
        
        $this->assertEquals($document['post_title'], 'Test Post');
        $this->assertEquals($document['post_type'], 'post');
    }

    public function testIsPrivate()
    {
        SettingsManager::get_instance()->set(Constants::OPTION_POST_TYPES, array(
            array(
                'type' => 'post',
                'exclude' => [],
                'private' => []
            )
        ));
        $id = $this->factory->post->create( array( 
                'post_title' => 'Test Post',
                'post_status' => 'private' 
            ) 
        );        

        $post = get_post($id);
        $builder = new PostDocumentBuilder();
        $is_private = $builder->is_private($post);
        
        $this->assertEquals($is_private, true);
    }

    public function testIsPrivate_2()
    {
        SettingsManager::get_instance()->set(Constants::OPTION_POST_TYPES, array(
            array(
                'type' => 'post',
                'exclude' => [],
                'private' => []
            )
        ));
        $id = $this->factory->post->create( array( 
                'post_title' => 'Test Post'
            ) 
        );        

        $post = get_post($id);
        $builder = new PostDocumentBuilder();
        $is_private = $builder->is_private($post);
        
        $this->assertEquals($is_private, false);
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
        $id = $this->factory->term->create( array( 
                'taxonomy' => 'category',
                'name' => 'Term 1' 
            ) 
        );        
        $builder = new TermDocumentBuilder();
        $document = $builder->build(get_term($id));

        $this->assertEquals($document['name_suggest'], 'Term 1');
        $this->assertEquals($document['name'], 'Term 1');
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
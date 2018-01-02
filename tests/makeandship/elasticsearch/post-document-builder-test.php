<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\PostDocumentBuilder;
use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\settings\SettingsManager;

// mocks
function get_field($field, $post_id)
{
    $post = get_post($post_id);
    if ($field === 'search_exclude_post') {
        if ($post->post_name === 'exclude-post') {
            return true;
        }
    }

    return null;
}

class PostDocumentBuilderTest extends WP_UnitTestCase
{
    public function testIsIndexable()
    {
        $id = $this->factory->post->create(
            array(
                'post_title' => 'Simple post',
                'post_type' => 'meetings'
            )
        );
        $post = get_post($id);
        $builder = new PostDocumentBuilder();
        $this->assertEquals($builder->is_indexable($post), true);
    }

    public function testIsIndexableWithExclusionFlag()
    {
        SettingsManager::get_instance()->set(Constants::OPTION_EXCLUSION_FIELD, 'search_exclude_post');

        $id = $this->factory->post->create(
            array(
                'post_title' => 'Simple post',
                'post_name' => 'exclude-post',
                'post_type' => 'meetings'
            )
        );
        $post = get_post($id);
        $builder = new PostDocumentBuilder();
        $this->assertEquals($builder->is_indexable($post), false);
    }

    public function testIsIndexableWithExclusionSlug()
    {
        SettingsManager::get_instance()->set(Constants::OPTION_SLUGS_TO_EXCLUDE, array('exclude-post'));
        
        $id = $this->factory->post->create(
                    array(
                        'post_title' => 'Simple post',
                        'post_name' => 'exclude-post',
                        'post_type' => 'meetings'
                    )
                );
        $post = get_post($id);
        $builder = new PostDocumentBuilder();
        $this->assertEquals($builder->is_indexable($post), false);
    }
}

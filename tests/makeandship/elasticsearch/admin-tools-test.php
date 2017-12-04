<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\admin\HtmlUtils;
use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\settings\SettingsManager;

class AdminToolsTest extends WP_UnitTestCase
{
    public function testRenderField()
    {
        $rendered = HtmlUtils::render_field('Home', 'home', array());

        $this->assertContains('<div class="acf-elasticsearch-row">', $rendered);
        $this->assertContains('<label for="">Home</label>', $rendered);
    }

    public function testRenderTextField()
    {
        $rendered = HtmlUtils::render_text_field('Username', array(
            "value" => "username",
            "class" => "simple"
        ));

        $this->assertContains('<input type="text"', $rendered);
        $this->assertContains('class="simple"', $rendered);
        $this->assertContains('name="Username"', $rendered);
    }

    public function testRenderButtons()
    {
        $rendered = HtmlUtils::render_buttons(array(
            "save button" => array(
                "id" => "save"
            )
        ));

        $this->assertContains('<div class="acf-elasticsearch-row">', $rendered);
        $this->assertContains('<div class="twelvecol last acf-elasticsearch-button-container">', $rendered);
        $this->assertContains('<input type="submit"', $rendered);
        $this->assertContains('id="save"', $rendered);
    }

    public function testRenderButton()
    {
        $rendered = HtmlUtils::render_button(array(
            "id" => "save",
            "name" => "Save",
        ));

        $this->assertContains('<input type="submit"', $rendered);
        $this->assertContains('id="save"', $rendered);
        $this->assertContains('name="Save"', $rendered);
    }

    public function testRenderPostTypeChoices()
    {
        SettingsManager::get_instance()->set(Constants::OPTION_POST_TYPES, array(
            array(
                'type' => 'post',
                'exclude' => [],
                'private' => []
            )
        ));

        $rendered = HtmlUtils::render_post_type_choices('Post Types');

        $this->assertContains('<label for="">Post Types</label>', $rendered);
        $this->assertContains('<input type="checkbox" value="post" name="acf_elasticsearch_post_types[]" id="post"', $rendered);
        $this->assertContains('<label class="textarea-label" for="">Exclude fields from indexing</label>', $rendered);
        $this->assertContains('<textarea name="post_exclude"></textarea>', $rendered);
        $this->assertContains('<textarea name="page_exclude"></textarea>', $rendered);
        $this->assertContains('<textarea name="page_private"></textarea>', $rendered);
    }

    public function testRenderCheckbox()
    {
        $rendered = HtmlUtils::render_checkbox(array(
            'id' => 'checkbox_id',
            'name' => 'Post',
            'value' => 'post',
            'checked' => true
        ));

        $this->assertContains('<input type="checkbox" value="post" name="Post" id="checkbox_id"', $rendered);
        $this->assertContains('checked="checked">', $rendered);
    }

    public function testCreatePostTypes()
    {
        $_POST['acf_elasticsearch_post_types'] = ["post", "article"];
        $_POST['article_exclude'] = "acf_title\nurl";
        $_POST['post_private'] = "acf_content";
        $post_types = HtmlUtils::create_post_types();

        $this->assertEquals('post', $post_types[0]['type']);
        $this->assertEquals(array(), $post_types[0]['exclude']);
        $this->assertEquals(array("acf_content"), $post_types[0]['private']);
        $this->assertEquals('article', $post_types[1]['type']);
        $this->assertEquals(array("acf_title","url"), $post_types[1]['exclude']);
        $this->assertEquals(array(), $post_types[1]['private']);
    }

    public function testRenderTextareaField()
    {
        $rendered = HtmlUtils::render_textarea_field('search_fields', array(
            "value" => "Search Fields",
            "class" => "medium"
        ));

        $this->assertEquals('<textarea name="search_fields" class="medium">Search Fields</textarea>', $rendered);
    }
}
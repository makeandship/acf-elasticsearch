<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\admin\HtmlUtils;

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
        $this->assertContains('<div class="acf-elasticsearch-button-container">', $rendered);
        $this->assertContains('<input type="submit"', $rendered);
        $this->assertContains('id="save"', $rendered);
    }
}
<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\admin\HtmlUtils;

class AdminToolsTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderTextField()
    {
        $rendered = HtmlUtils::render_field('Home', 'home', array());

        $this->assertContains('<div class="acf-elasticsearch-row">', $rendered);
        $this->assertContains('<label for="">Home</label>', $rendered);
    }
}
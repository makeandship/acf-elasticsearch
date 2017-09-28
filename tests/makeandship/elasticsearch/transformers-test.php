<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\transformer\DateFieldTransformer;
use makeandship\elasticsearch\transformer\HtmlFieldTransformer;

class TransformersTest extends WP_UnitTestCase
{
    public function testDateFieldTransformer()
    {
    	$transformer = new DateFieldTransformer();
        $transformed = $transformer->transform('27-09-2017');
        $this->assertEquals($transformed, '2017-09-27T00:00:00+00:00');
    }

    public function testHtmlFieldTransformer()
    {
    	$transformer = new HtmlFieldTransformer();
        $transformed = $transformer->transform('<p>text here</p>');
        $this->assertEquals($transformed, 'text here');
    }
}
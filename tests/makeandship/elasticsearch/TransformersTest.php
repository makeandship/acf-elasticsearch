<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\transformer\DateFieldTransformer;

class TransformersTest extends \PHPUnit\Framework\TestCase
{
    public function testTrueIsTrue()
    {
    	$transformer = new DateFieldTransformer();
        $foo = true;
        $this->assertTrue($foo);
    }
}
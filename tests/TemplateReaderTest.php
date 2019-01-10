<?php

require_once(dirname(__FILE__) . '/../lib/php/TemplateReader.php');

use PHPUnit\Framework\TestCase;
use WikivoyageApi\TemplateReader;

class TemplateReaderTest extends TestCase
{
    public function testSimple()
    {
        $templateReader = new TemplateReader();
        $data = $templateReader->read(['mytemplate'], '{{mytemplate|param1=val1|param2=val2}}');
        $expectedData = [
            ['param1' => 'val1', 'param2' => 'val2']
        ];
        $this->assertSame($expectedData, $data);
    }
}
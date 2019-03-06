<?php
namespace Digraph\DataObject\Tests;

use PHPUnit\Framework\TestCase;
use Digraph\DataObject\JSON\JSONLayer;

class JSONLayerTest extends TestCase
{
    public function testJSONLayer()
    {
        $layer = new JSONLayer(array(
            'foo','bar','baz'
        ));
        $this->assertEquals(
            'foo',
            $layer[0]
        );
        $this->assertEquals(
            'bar',
            $layer[1]
        );
        $this->assertEquals(
            'baz',
            $layer[2]
        );
    }

    public function testNestingAndAltering()
    {
        $test = array(
            'foo' => 'bar',
            'baz' => array(
                'good' => 'nice',
                'bad' => 'mean'
            )
        );
        $layer = new JSONLayer($test);
        $this->assertEquals(
            'nice',
            $layer['baz']['good']
        );
        $this->assertTrue($layer['baz'] instanceof JSONLayer);

        //test altering both first and second layer
        $layer['foo'] = 'rab';
        $this->assertEquals(
            'rab',
            $layer['foo']
        );

        $layer['baz']['bad'] = 'evil';
        $this->assertEquals(
            'evil',
            $layer['baz']['bad']
        );

        //test adding an array value
        $layer['bar'] = array(
            'a' => 'b',
            'c' => 'd'
        );
        
        $this->assertTrue($layer['bar'] instanceof JSONLayer);
        $this->assertEquals(
            'b',
            $layer['bar']['a']
        );
        $this->assertEquals(
            'd',
            $layer['bar']['c']
        );
    }
}

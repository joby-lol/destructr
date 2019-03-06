<?php
namespace Digraph\DataObject\Tests;

use PHPUnit\Framework\TestCase;
use Digraph\DataObject\Tests\AbstractArrayHarnessObject;

class AbstractArrayDataObjectTest extends TestCase
{
    public function testFundamentalGetters()
    {
        $testObj = new AAOT();
        $this->assertEquals(
            'testPropDefault-gotten',
            $testObj["testProp1"],
            "Getter set in map isn't altering output"
        );
        $this->assertEquals(
            'testPropDefault',
            $testObj["testProp1Raw"],
            "Getter set via function name (_get_testProp1Raw) isn't working"
        );
        $this->assertEquals(
            null,
            $testObj["doesNotExist"],
            "getting must return null for nonexistent properties"
        );
        $this->assertEquals(
            null,
            $testObj["testProp4"],
            "getting must return  null for masked properties"
        );
    }

    public function testFundamentalIssetters()
    {
        $testObj = new AAOT();
        $this->assertFalse(
            isset($testObj["doesNotExist"]),
            "isset() must return false for items that don't exist"
        );
        $this->assertTrue(
            isset($testObj["testProp1"]),
            "isset() must return true for items with custom getters in the map"
        );
        $this->assertTrue(
            isset($testObj["testProp1Raw"]),
            "isset() must return true for custom get functions like _get_testProp1Raw()"
        );
        $this->assertTrue(
            isset($testObj["testProp2"]),
            "isset() must return true for items with no custom getter"
        );
        $this->assertFalse(
            isset($testObj["testProp4"]),
            "isset() must return false for masked properties"
        );
    }

    public function testFundamentalSetters()
    {
        $testObj = new AAOT();
        $testObj["testProp2"] = 'testPropSet';
        $this->assertEquals(
            'testPropSet',
            $testObj["testProp2"],
            "Setting an item with no custom setter isn't working"
        );
        $testData = array(
            "test",
            "data",
            array("nested")
        );
        $testObj["testProp3"] = $testData;
        $this->assertEquals(
            $testData[0],
            $testObj["testProp3"][0],
            "JSON setter assigned in map isn't working"
        );
    }
}

class AAOT extends AbstractArrayHarnessObject
{
    static $TESTPROP1 = array(
        'name' => 'testprop1',
        'default' => 'testPropDefault',
        'transform' => 'testGetter'
    );

    static $TESTPROP2 = array(
        'name' => 'testprop2',
        'default' => 'testProp2Default'
    );

    static $TESTPROP3 = array(
        'name' => 'testProp3',
        'transform' => 'JSON'
    );

    static $TESTPROP4 = array(
        'name' => 'testProp4',
        'masked' => true,
        'default' => 'testProp4Default'
    );

    static function getMap()
    {
        $map = parent::getMap();
        $map['testProp1'] = static::$TESTPROP1;
        $map['testProp2'] = static::$TESTPROP2;
        $map['testProp3'] = static::$TESTPROP3;
        $map['testProp4'] = static::$TESTPROP4;
        return $map;
    }

    public function _getter_test($name)
    {
        return $this->getRaw($name) . '-gotten';
    }

    public function _get_testProp1Raw($name)
    {
        return $this->getRaw('testProp1');
    }
}

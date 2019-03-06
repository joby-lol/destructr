<?php
namespace Digraph\DataObject\Tests;

use PHPUnit\Framework\TestCase;
use Digraph\DataObject\Tests\AbstractHarnessObject;

class AbstractDataObjectTest extends TestCase
{
    public function testMap()
    {

        $baseMap = AbstractHarnessObject::map();
        $testMap = ADOT::map();

        $this->assertEquals(
            4,
            count($testMap)-count($baseMap),
            "The test harness object doesn't have the right number of map items"
        );

        $this->assertEquals(
            ADOT::$TESTPROP1,
            ADOT::mapEntry('testProp1')
        );
    }

    /**
     * @expectedException Digraph\DataObject\Exceptions\IDSpaceException
     */
    function testIDSpaceExhaustion()
    {
        new ADOT_BrokenIDExists();
    }

    public function testGeneration()
    {
        $testObj = new ADOT(array(
            'testProp2' => 'manuallySet'
        ));
        //cdate and mdate should be very close to now
        $this->assertLessThan(
            1,
            time()-$testObj->do_cdate,
            "Newly created object's do_cdate is more than 1 second from now"
        );
        $this->assertLessThan(
            1,
            time()-$testObj->do_mdate,
            "Newly created object's do_mdate is more than 1 second from now"
        );
        $this->assertEquals(
            'manuallySet',
            $testObj->testProp2,
            "Newly created object's property not correctly set from array"
        );
    }

    public function testGetters()
    {
        $testObj = new ADOT();
        $this->assertEquals(
            'testPropDefault-gotten',
            $testObj->testProp1,
            "Getter set in map isn't altering output"
        );
        $this->assertEquals(
            'testPropDefault',
            $testObj->testProp1Raw,
            "Getter set via function name (_get_testProp1Raw) isn't working"
        );
        $this->assertEquals(
            null,
            $testObj->doesNotExist,
            "__get() must return null for nonexistent properties"
        );
        $this->assertEquals(
            null,
            $testObj->testProp4,
            "__get() must return  null for masked properties"
        );
    }

    public function testIssetters()
    {
        $testObj = new ADOT();
        $this->assertFalse(
            isset($testObj->doesNotExist),
            "__isset() must return false for items that don't exist"
        );
        $this->assertTrue(
            isset($testObj->testProp1),
            "__isset() must return true for items with custom getters in the map"
        );
        $this->assertTrue(
            isset($testObj->testProp1Raw),
            "__isset() must return true for custom get functions like _get_testProp1Raw()"
        );
        $this->assertTrue(
            isset($testObj->testProp2),
            "__isset() must return true for items with no custom getter"
        );
        $this->assertFalse(
            isset($testObj->testProp4),
            "__isset() must return false for masked properties"
        );
    }

    public function testSetters()
    {
        $testObj = new ADOT();
        $testObj->testProp2 = 'testPropSet';
        $this->assertEquals(
            'testPropSet',
            $testObj->testProp2,
            "Setting an item with no custom setter isn't working"
        );
    }
}

class ADOT extends AbstractHarnessObject
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

    public function _get_testProp1Raw($name)
    {
        return $this->getRaw('testProp1');
    }
}

class ADOT_BrokenIDExists extends ADOT
{
    static function idExists($id)
    {
        return true;
    }
}

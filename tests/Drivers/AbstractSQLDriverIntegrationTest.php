<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare (strict_types = 1);
namespace Destructr\Drivers;

use Destructr\Factory;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

abstract class AbstractSQLDriverIntegrationTest extends TestCase
{
    use TestCaseTrait;
    const TEST_TABLE = 'integrationtest';

    public static function setUpBeforeClass()
    {
        $pdo = static::createPDO();
        $pdo->exec('DROP TABLE ' . static::TEST_TABLE);
    }

    public function testPrepareEnvironment()
    {
        $factory = $this->createFactory();
        $factory->prepareEnvironment();
        //table should exist and have zero rows
        $this->assertEquals(0, $this->getConnection()->getRowCount(static::TEST_TABLE));
    }

    public function testInsert()
    {
        $startRowCount = $this->getConnection()->getRowCount(static::TEST_TABLE);
        $factory = $this->createFactory();
        //inserting a freshly created object should return true
        $o = $factory->create(['dso.id' => 'object-one']);
        $this->assertTrue($o->insert());
        //inserting it a second time should not
        $this->assertFalse($o->insert());
        //there should now be one more row
        $this->assertEquals($startRowCount + 1, $this->getConnection()->getRowCount(static::TEST_TABLE));
    }

    public function testReadAndUpdate()
    {
        $startRowCount = $this->getConnection()->getRowCount(static::TEST_TABLE);
        $factory = $this->createFactory();
        //insert some new objects
        $a1 = $factory->create(['foo' => 'bar']);
        $a1->insert();
        $b1 = $factory->create(['foo.bar' => 'baz']);
        $b1->insert();
        //read objects back out
        $a2 = $factory->read($a1['dso.id']);
        $b2 = $factory->read($b1['dso.id']);
        //objects should be the same
        $this->assertEquals($a1->get(), $a2->get());
        $this->assertEquals($b1->get(), $b2->get());
        //alter things in the objects and update them
        $a2['foo'] = 'baz';
        $b2['foo.bar'] = 'bar';
        $a2->update();
        $b2->update();
        //read objects back out a third time
        $a3 = $factory->read($a1['dso.id']);
        $b3 = $factory->read($b1['dso.id']);
        //objects should be the same
        $this->assertEquals($a2->get(), $a3->get());
        $this->assertEquals($b2->get(), $b3->get());
        //they should not be the same as the originals from the beginning
        $this->assertNotEquals($a1->get(), $a3->get());
        $this->assertNotEquals($b1->get(), $b3->get());
        //there should now be two more rows
        $this->assertEquals($startRowCount + 2, $this->getConnection()->getRowCount(static::TEST_TABLE));
    }

    public function testDelete()
    {
        $startRowCount = $this->getConnection()->getRowCount(static::TEST_TABLE);
        $factory = $this->createFactory();
        //insert some new objects
        $a1 = $factory->create(['testDelete' => 'undelete me']);
        $a1->insert();
        $b1 = $factory->create(['testDelete' => 'should be permanently deleted']);
        $b1->insert();
        //there should now be two more rows
        $this->assertEquals($startRowCount + 2, $this->getConnection()->getRowCount(static::TEST_TABLE));
        //delete one permanently and the other not, both shoudl take effect immediately
        $a1->delete();
        $b1->delete(true);
        //there should now be only one more row
        $this->assertEquals($startRowCount + 1, $this->getConnection()->getRowCount(static::TEST_TABLE));
        //a should be possible to read a back out with the right flags
        $this->assertNull($factory->read($a1['dso.id']));
        $this->assertNotNull($factory->read($a1['dso.id'], 'dso.id', true));
        $this->assertNotNull($factory->read($a1['dso.id'], 'dso.id', null));
        //undelete a, should have update() inside it
        $a1->undelete();
        //it should be possible to read a back out with different flags
        $this->assertNotNull($factory->read($a1['dso.id']));
        $this->assertNull($factory->read($a1['dso.id'], 'dso.id', true));
        $this->assertNotNull($factory->read($a1['dso.id'], 'dso.id', null));
    }

    public function testSearch()
    {
        $startRowCount = $this->getConnection()->getRowCount(static::TEST_TABLE);
        $factory = $this->createFactory();
        //insert some dummy data
        $factory->create([
            'testSearch' => 'a',
            'a' => '1',
            'b' => '2',
        ])->insert();
        $factory->create([
            'testSearch' => 'b',
            'a' => '2',
            'b' => '1',
        ])->insert();
        $factory->create([
            'testSearch' => 'c',
            'a' => '3',
            'b' => '4',
        ])->insert();
        $factory->create([
            'testSearch' => 'a',
            'a' => '4',
            'b' => '3',
        ])->insert();
        //there should now be four more rows
        $this->assertEquals($startRowCount + 4, $this->getConnection()->getRowCount(static::TEST_TABLE));
        //TODO: test some searches
    }

    /**
     * Creates a Driver from class constants, so extending classes can test
     * different databases.
     */
    public function createDriver()
    {
        $class = static::DRIVER_CLASS;
        return new $class(
            static::DRIVER_DSN(),
            static::DRIVER_USERNAME(),
            static::DRIVER_PASSWORD(),
            static::DRIVER_OPTIONS()
        );
    }

    public function createFactory()
    {
        $driver = $this->createDriver();
        return new Factory(
            $driver,
            static::TEST_TABLE
        );
    }

    protected static function createPDO()
    {
        return new \PDO(
            static::DRIVER_DSN(),
            static::DRIVER_USERNAME(),
            static::DRIVER_PASSWORD(),
            static::DRIVER_OPTIONS()
        );
    }

    public function getConnection()
    {
        return $this->createDefaultDBConnection($this->createPDO(), 'phpunit');
    }

    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }
}

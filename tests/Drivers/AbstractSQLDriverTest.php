<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare (strict_types = 1);
namespace Destructr\Drivers;

use Destructr\DSO;
use Destructr\Factory;
use Destructr\Search;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * This class tests a factory in isolation. In the name of simplicity it's a bit
 * simplistic, because it doesn't get the help of the Factory.
 *
 * There is also a class called AbstractSQLDriverIntegrationTest that tests drivers
 * through a Factory. The results of that are harder to interpret, but more
 * properly and thoroughly test the Drivers in a real environment.
 */
abstract class AbstractSQLDriverTest extends TestCase
{
    use TestCaseTrait;

    /*
    In actual practice, these would come from a Factory
     */
    protected $schema = [
        'dso.id' => [
            'name' => 'dso_id',
            'type' => 'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
        ],
        'dso.type' => [
            'name' => 'dso_type',
            'type' => 'VARCHAR(30)',
            'index' => 'BTREE',
        ],
        'dso.deleted' => [
            'name' => 'dso_deleted',
            'type' => 'BIGINT',
            'index' => 'BTREE',
        ],
    ];

    public function testPrepareEnvironment()
    {
        $driver = $this->createDriver();
        $this->assertFalse($driver->tableExists('testPrepareEnvironment'));
        $this->assertFalse($driver->tableExists('destructr_schema'));
        $driver->prepareEnvironment('testPrepareEnvironment', $this->schema);
        $this->assertTrue($driver->tableExists('destructr_schema'));
        $this->assertTrue($driver->tableExists('testPrepareEnvironment'));
        $this->assertEquals(1, $this->getConnection()->getRowCount('destructr_schema'));
        $this->assertEquals(0, $this->getConnection()->getRowCount('testPrepareEnvironment'));
    }

    public function testInsert()
    {
        $driver = $this->createDriver();
        $driver->prepareEnvironment('testInsert', $this->schema);
        //test inserting an object
        $o = new DSO(['dso.id' => 'first-inserted'],new Factory($driver,'no_table'));
        $this->assertTrue($driver->insert('testInsert', $o));
        $this->assertEquals(1, $this->getConnection()->getRowCount('testInsert'));
        //test inserting a second object
        $o = new DSO(['dso.id' => 'second-inserted'],new Factory($driver,'no_table'));
        $this->assertTrue($driver->insert('testInsert', $o));
        $this->assertEquals(2, $this->getConnection()->getRowCount('testInsert'));
    }

    public function testSelect()
    {
        $driver = $this->createDriver();
        $driver->prepareEnvironment('testSelect', $this->schema);
        //set up dummy data
        $this->setup_testSelect();
        //empty search
        $search = new Search();
        $results = $driver->select('testSelect', $search, []);
        $this->assertSame(4, count($results));
        //sorting by json value sort
        $search = new Search();
        $search->order('${sort} asc');
        $results = $driver->select('testSelect', $search, []);
        $this->assertSame(4, count($results));
        $results = array_map(
            function ($a) {
                return json_decode($a['json_data'], true);
            },
            $results
        );
        $this->assertSame('item-a-1', $results[0]['dso']['id']);
        $this->assertSame('item-b-1', $results[1]['dso']['id']);
        $this->assertSame('item-a-2', $results[2]['dso']['id']);
        $this->assertSame('item-b-2', $results[3]['dso']['id']);
        // search with no results, searching by virtual column
        $search = new Search();
        $search->where('`dso_type` = :param');
        $results = $driver->select('testSelect', $search, [':param' => 'type-none']);
        $this->assertSame(0, count($results));
        // search with no results, searching by json field
        $search = new Search();
        $search->where('${foo} = :param');
        $results = $driver->select('testSelect', $search, [':param' => 'nonexistent foo value']);
        $this->assertSame(0, count($results));
    }

    protected function setup_testSelect()
    {
        $driver = $this->createDriver();
        $driver->insert('testSelect', new DSO([
            'dso' => ['id' => 'item-a-1', 'type' => 'type-a'],
            'foo' => 'bar',
            'sort' => 'a',
        ],new Factory($driver,'no_table')));
        $driver->insert('testSelect', new DSO([
            'dso' => ['id' => 'item-a-2', 'type' => 'type-a'],
            'foo' => 'baz',
            'sort' => 'c',
        ],new Factory($driver,'no_table')));
        $driver->insert('testSelect', new DSO([
            'dso' => ['id' => 'item-b-1', 'type' => 'type-b'],
            'foo' => 'buz',
            'sort' => 'b',
        ],new Factory($driver,'no_table')));
        $driver->insert('testSelect', new DSO([
            'dso' => ['id' => 'item-b-2', 'type' => 'type-b', 'deleted' => 100],
            'foo' => 'quz',
            'sort' => 'd',
        ],new Factory($driver,'no_table')));
    }

    /**
     * Creates a Driver from class constants, so extending classes can test
     * different databases.
     */
    public function createDriver()
    {
        $class = static::DRIVER_CLASS;
        return new $class(
            static::DRIVER_DSN,
            static::DRIVER_USERNAME,
            static::DRIVER_PASSWORD,
            static::DRIVER_OPTIONS
        );
    }

    public static function setUpBeforeClass()
    {
        $pdo = static::createPDO();
        $pdo->exec('DROP TABLE testPrepareEnvironment');
        $pdo->exec('DROP TABLE testInsert');
        $pdo->exec('DROP TABLE testSelect');
        $pdo->exec('DROP TABLE destructr_schema');
    }

    protected static function createPDO()
    {
        return new \PDO(
            static::DRIVER_DSN,
            static::DRIVER_USERNAME,
            static::DRIVER_PASSWORD,
            static::DRIVER_OPTIONS
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

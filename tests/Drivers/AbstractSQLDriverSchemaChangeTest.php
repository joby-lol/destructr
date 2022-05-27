<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare (strict_types = 1);
namespace Destructr\Drivers;

use PDO;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * This class tests a Driver's ability to correctly change schemas.
 */
abstract class AbstractSQLDriverSchemaChangeTest extends TestCase
{
    use TestCaseTrait;
    const TEST_TABLE = 'schematest';

    public function testSchemaChanges()
    {
        // set up using schema A
        $factory = $this->createFactoryA();
        $this->assertFalse($factory->checkEnvironment());
        $factory->prepareEnvironment();
        $this->assertTrue($factory->checkEnvironment());
        $factory->updateEnvironment();
        // verify schema in database
        $this->assertEquals(
            $factory->schema,
            $factory->driver()->getSchema('schematest')
        );
        // add some content
        $new = $factory->create([
            'dso.id' => 'dso1',
            'test.a' => 'value a1',
            'test.b' => 'value b1',
            'test.c' => 'value c1',
        ]);
        $new->insert();
        $new = $factory->create([
            'dso.id' => 'dso2',
            'test.a' => 'value a2',
            'test.b' => 'value b2',
            'test.c' => 'value c2',
        ]);
        $new->insert();
        $new = $factory->create([
            'dso.id' => 'dso3',
            'test.a' => 'value a3',
            'test.b' => 'value b3',
            'test.c' => 'value c3',
        ]);
        $new->insert();
        // verify data in table matches
        $pdo = $this->createPDO();
        $this->assertEquals(3, $this->getConnection()->getRowCount('schematest'));
        for ($i = 1; $i <= 3; $i++) {
            $row = $pdo->query('select dso_id, test_a, test_b from schematest where dso_id = "dso' . $i . '"')->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(['dso_id' => "dso$i", 'test_a' => "value a$i", 'test_b' => "value b$i"], $row);
        }
        // change to schema B
        sleep(1); //a table can't have its schema updated faster than once per second
        $factory = $this->createFactoryB();
        $this->assertFalse($factory->checkEnvironment());
        $factory->prepareEnvironment();
        $this->assertFalse($factory->checkEnvironment());
        $factory->updateEnvironment();
        $this->assertTrue($factory->checkEnvironment());
        // verify schema in database
        $this->assertEquals(
            $factory->schema,
            $factory->driver()->getSchema('schematest')
        );
        // verify data in table matches
        $pdo = $this->createPDO();
        $this->assertEquals(3, $this->getConnection()->getRowCount('schematest'));
        for ($i = 1; $i <= 3; $i++) {
            $row = $pdo->query('select dso_id, test_a_2, test_c from schematest where dso_id = "dso' . $i . '"')->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals(['dso_id' => "dso$i", 'test_a_2' => "value a$i", 'test_c' => "value c$i"], $row);
        }
    }

    protected static function createFactoryA()
    {
        $driver = static::createDriver();
        $factory = new FactorySchemaA(
            $driver,
            static::TEST_TABLE
        );
        return $factory;
    }

    protected static function createFactoryB()
    {
        $driver = static::createDriver();
        $factory = new FactorySchemaB(
            $driver,
            static::TEST_TABLE
        );
        return $factory;
    }

    protected static function createDriver()
    {
        $class = static::DRIVER_CLASS;
        return new $class(
            static::DRIVER_DSN(),
            static::DRIVER_USERNAME(),
            static::DRIVER_PASSWORD(),
            static::DRIVER_OPTIONS()
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

    public static function setUpBeforeClass()
    {
        $pdo = static::createPDO();
        $pdo->exec('DROP TABLE schematest');
        $pdo->exec('DROP TABLE destructr_schema');
    }
}

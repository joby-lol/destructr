<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare (strict_types = 1);
namespace Destructr\Drivers;

use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * This class tests a Driver's ability to correctly change schemas.
 */
abstract class AbstractSQLDriverSchemaChangeTest extends TestCase
{
    use TestCaseTrait;
    const TEST_TABLE = 'schematest';

    public static function createFactory()
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
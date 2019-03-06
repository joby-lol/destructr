<?php
namespace Digraph\DataObject\Tests\SQL;

use \PDO;

use Digraph\DataObject\SQL\AbstractSQLDataObject;

abstract class Generic_Tests_DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    private static $pdo = null;

    /**
     * Note that the tests copy test.sqlite to test.sqlite.tmp to avoid
     * mucking up the repository
     * @return
     */
    public function getConnection()
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:test.sqlite.tmp');
            copy('test.sqlite', 'test.sqlite.tmp');
        }
        return $this->createDefaultDBConnection(self::$pdo, 'test.sqlite.tmp');
    }

    public function getDataSet()
    {
        return $this->createXMLDataSet(dirname(__FILE__).'/_files/db-seed.xml');
    }
}

<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare (strict_types = 1);
namespace Destructr\Drivers\MySQL;

use Destructr\Drivers\AbstractSQLDriverIntegrationTest;
use Destructr\Drivers\MySQLDriver;

class MySQLDriverIntegrationTest extends AbstractSQLDriverIntegrationTest
{
    const DRIVER_CLASS = MySQLDriver::class;
    const DRIVER_DSN = 'mysql:host=127.0.0.1;dbname=test';
    const DRIVER_USERNAME = 'root';
    const DRIVER_PASSWORD = null;
    const DRIVER_OPTIONS = null;
    const TEST_TABLE = 'integrationtest';
}

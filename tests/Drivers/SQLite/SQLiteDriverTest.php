<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare (strict_types = 1);
namespace Destructr\Drivers\SQLite;

use Destructr\Drivers\AbstractSQLDriverTest;
use Destructr\Drivers\SQLiteDriver;

class SQLiteDriverTest extends AbstractSQLDriverTest
{
    const DRIVER_CLASS = SQLiteDriver::class;
    const DRIVER_DSN = 'sqlite:'.__DIR__.'/driver.test.sqlite';
    const DRIVER_USERNAME = 'root';
    const DRIVER_PASSWORD = '';
    const DRIVER_OPTIONS = null;
}

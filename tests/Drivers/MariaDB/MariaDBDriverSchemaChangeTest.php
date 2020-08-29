<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare (strict_types = 1);
namespace Destructr\Drivers\MariaDB;

use Destructr\Drivers\AbstractSQLDriverSchemaChangeTest;
use Destructr\Drivers\MariaDBDriver;

class MariaDBDriverSchemaChangeTest extends AbstractSQLDriverSchemaChangeTest
{
    const DRIVER_CLASS = MariaDBDriver::class;
    const DRIVER_DSN = 'mysql:host=127.0.0.1;port=3307;dbname=destructrtest';
    const DRIVER_USERNAME = 'destructrtest';
    const DRIVER_PASSWORD = 'destructrtest';
    const DRIVER_OPTIONS = null;
}

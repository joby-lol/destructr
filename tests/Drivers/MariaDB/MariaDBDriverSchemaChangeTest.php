<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */

declare(strict_types=1);

namespace Destructr\Drivers\MariaDB;

use Destructr\Drivers\AbstractSQLDriverSchemaChangeTest;
use Destructr\Drivers\MariaDBDriver;

class MariaDBDriverSchemaChangeTest extends AbstractSQLDriverSchemaChangeTest
{
    const DRIVER_CLASS = MariaDBDriver::class;
    const DRIVER_OPTIONS = null;

    protected static function DRIVER_DSN()
    {
        return sprintf(
            'mysql:host=%s:%s;dbname=destructr_test',
            $_ENV['TEST_MARIADB_SERVER'],
            $_ENV['TEST_MARIADB_PORT']
        );
    }
}

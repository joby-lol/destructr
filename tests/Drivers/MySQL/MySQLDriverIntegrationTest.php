<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */

declare(strict_types=1);

namespace Destructr\Drivers\MySQL;

use Destructr\Drivers\AbstractSQLDriverIntegrationTest;
use Destructr\Drivers\MySQLDriver;

class MySQLDriverIntegrationTest extends AbstractSQLDriverIntegrationTest
{
    const DRIVER_CLASS = MySQLDriver::class;
    const DRIVER_OPTIONS = null;

    protected static function DRIVER_DSN()
    {
        return sprintf(
            'mysql:host=%s:%s;dbname=destructr_test',
            $_ENV['TEST_MYSQL_SERVER'],
            $_ENV['TEST_MYSQL_PORT']
        );
    }
}

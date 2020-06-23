<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
declare(strict_types=1);
namespace Destructr\Drivers\MySQL;

use PHPUnit\Framework\TestCase;
use Destructr\Drivers\AbstractDriverTest;
use Destructr\Drivers\MySQLDriver;

class MySQLDriverTest extends AbstractDriverTest
{
    const DRIVER_CLASS = MySQLDriver::class;
    const DRIVER_DSN = 'mysql:host=127.0.0.1;dbname=test';
    const DRIVER_USERNAME = 'root';
    const DRIVER_PASSWORD = '';
    const DRIVER_OPTIONS = null;
}

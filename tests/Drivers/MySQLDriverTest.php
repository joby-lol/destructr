<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
declare(strict_types=1);
namespace Destructr\Drivers;

use PHPUnit\Framework\TestCase;

class MySQLDriverTest extends AbstractDriverTest
{
    const DRIVER_CLASS = MySQLDriver::class;
    const DRIVER_DSN = 'mysql:host=mysql;dbname=ci';
    const DRIVER_USERNAME = 'ci_password';
    const DRIVER_PASSWORD = null;
    const DRIVER_OPTIONS = null;
}

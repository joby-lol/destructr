<?php
namespace Digraph\DataObject\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Digraph\DataObject\Utils\BadWords;

class BadWordsTest extends TestCase
{
    public function testProfanity()
    {
        $this->assertTrue(BadWords::profane('fuck'));
        $this->assertTrue(BadWords::profane('phuxx'));
        $this->assertFalse(BadWords::profane('ABCD'));
        $this->assertTrue(BadWords::profane('secks'));
    }
}

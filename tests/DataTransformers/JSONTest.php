<?php
namespace Digraph\DataObject\Tests\DataTransformers;

use PHPUnit\Framework\TestCase;
use Digraph\DataObject\DataTransformers\JSON;

class JSONTest extends TestCase
{
    public function testStorageValues()
    {
        $o = 'foo';
        $h = new JSON($o);
        $pre = $h->getStorageValue();
        $this->assertEquals('[]', $pre);
        $h['foo'] = 'bar';
        $post = $h->getStorageValue();
        $this->assertEquals(1, preg_match('/\{["\']foo["\']:["\']bar["\']\}/', $post));
        unset($h['foo']);
        $unset = $h->getStorageValue();
        $this->assertEquals('[]', $unset);
    }

    public function testNullValues()
    {
        $o = 'foo';
        $h = new JSON($o);
        $pre = $h->getStorageValue();
        $this->assertEquals('[]', $pre);
        $h['foo'] = array(
            'bar' => null,
            'baz' => 'buzz'
        );
        $post = $h->getStorageValue();
        $this->assertEquals(1, preg_match('/\{["\']foo["\']:{["\']baz["\']:["\']buzz["\']}\}/', $post));
    }

    public function testNullValuesOverwriting()
    {
        $o = 'foo';
        $h = new JSON($o);
        $pre = $h->getStorageValue();
        $this->assertEquals('[]', $pre);
        $h['foo'] = array(
            'bar' => 'baz',
            'baz' => 'buzz'
        );
        $h['foo'] = array(
            'bar' => null,
            'baz' => 'buzz'
        );
        $post = $h->getStorageValue();
        $this->assertEquals(1, preg_match('/\{["\']foo["\']:{["\']baz["\']:["\']buzz["\']}\}/', $post));
    }
}

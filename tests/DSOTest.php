<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
declare(strict_types=1);
namespace Destructr;

use PHPUnit\Framework\TestCase;

class DSOTest extends TestCase
{
    public function testChangeTracking()
    {
        $dso = new DSO([
            'a' => 'b',
            'c' => 'd',
            'e' => [1,2,3]
        ]);
        $this->assertEquals([], $dso->changes());
        $this->assertEquals([], $dso->removals());
        //not actually a change, shouldn't trigger
        $dso['a'] = 'b';
        $this->assertEquals([], $dso->changes());
        $this->assertEquals([], $dso->removals());
        //not actually a change, shouldn't trigger
        $dso['e'] = [1,2,3];
        $this->assertEquals([], $dso->changes());
        $this->assertEquals([], $dso->removals());
        //changing a should trigger changes but not removals
        $dso['a'] = 'B';
        $this->assertEquals(['a'=>'B'], $dso->changes());
        $this->assertEquals([], $dso->removals());
        //removing c should trigger removals but not change changes
        unset($dso['c']);
        $this->assertEquals(['a'=>'B'], $dso->changes());
        $this->assertEquals(['c'=>'d'], $dso->removals());
        //setting c back should remove it from removals, but add it to changes
        $dso['c'] = 'd';
        $this->assertEquals(['a'=>'B','c'=>'d'], $dso->changes());
        $this->assertEquals([], $dso->removals());
        //unsetting c again should remove it from changes, but add it back to removals
        unset($dso['c']);
        $this->assertEquals(['a'=>'B'], $dso->changes());
        $this->assertEquals(['c'=>'d'], $dso->removals());
        //resetting changes
        $dso->resetChanges();
        $this->assertEquals([], $dso->changes());
        $this->assertEquals([], $dso->removals());
    }

    public function testGetting()
    {
        $data = [
            'a' => 'A',
            'b' => ['c'=>'C']
        ];
        $a = new DSO($data);
        //first level
        $this->assertEquals('A', $a['a']);
        $this->assertEquals('A', $a->get('a'));
        //nested
        $this->assertEquals('C', $a['b.c']);
        $this->assertEquals('C', $a->get('b.c'));
        //returning array
        $this->assertEquals(['c'=>'C'], $a['b']);
        $this->assertEquals(['c'=>'C'], $a->get('b'));
        //returning entire array by requesting null or empty string
        $this->assertEquals($data, $a[null]);
        $this->assertEquals($data, $a->get());
        $this->assertEquals($data, $a['']);
        $this->assertEquals($data, $a->get(''));
        //requesting invalid keys should return null
        $this->assertNull($a->get('nonexistent'));
        $this->assertNull($a->get('b.nonexistent'));
        $this->assertNull($a->get('..'));
        $this->assertNull($a->get('.'));
        //double dots
        $this->assertNull($a->get('..a'));
        $this->assertNull($a->get('a..'));
        $this->assertNull($a->get('..a..'));
        $this->assertNull($a->get('..a..'));
        $this->assertNull($a->get('b..c'));
        $this->assertNull($a->get('b..c..'));
        $this->assertNull($a->get('..b..c'));
        $this->assertNull($a->get('..b..c..'));
        $this->assertNull($a->get('b.c..'));
        $this->assertNull($a->get('..b.c'));
        $this->assertNull($a->get('..b.c..'));
        //single dots
        $this->assertNull($a->get('.a'));
        $this->assertNull($a->get('a.'));
        $this->assertNull($a->get('.a.'));
        $this->assertNull($a->get('.a.'));
        $this->assertNull($a->get('b.c.'));
        $this->assertNull($a->get('.b.c'));
        $this->assertNull($a->get('.b.c.'));
        $this->assertNull($a->get('b.c.'));
        $this->assertNull($a->get('.b.c'));
        $this->assertNull($a->get('.b.c.'));
    }

    public function testSetting()
    {
        $data = [
            'a' => 'A',
            'b' => ['c'=>'C']
        ];
        $a = new DSO($data);
        //setting on first layer
        $a['a'] = 'B';
        $this->assertEquals('B', $a['a']);
        $a['new'] = 'NEW';
        $this->assertEquals('NEW', $a['new']);
        //setting nested
        $a['b.c'] = 'D';
        $this->assertEquals('D', $a['b.c']);
        $a['b.new'] = 'NEW';
        $this->assertEquals('NEW', $a['b.new']);
        //final state
        $this->assertEquals(
            [
                'a' => 'B',
                'b' => [
                    'c' => 'D',
                    'new' => 'NEW'
                ],
                'new' => 'NEW'
            ],
            $a->get()
        );
    }

    public function testSettingFalseyValues()
    {
        $a = new DSO(['foo'=>['bar'=>'baz']]);
        $a['foo.bar'] = false;
        $this->assertFalse($a['foo.bar']);
        $a = new DSO(['foo'=>['bar'=>'baz']]);
        $a['foo.bar'] = 0;
        $this->assertSame(0, $a['foo.bar']);
        $a = new DSO(['foo'=>['bar'=>'baz']]);
        $a['foo.bar'] = '';
        $this->assertSame('', $a['foo.bar']);
        $a = new DSO(['foo'=>['bar'=>'baz']]);
        $a['foo.bar'] = [];
        $this->assertIsArray($a['foo.bar']);
    }

    public function testMergingFalseyValues()
    {
        $a = new DSO(['foo'=>['bar'=>'baz']]);
        $a->merge(['foo'=>['bar'=>false]], null, true);
        $this->assertFalse($a['foo.bar']);
        $a = new DSO(['foo'=>['bar'=>'baz']]);
        $a->merge(['foo'=>['bar'=>0]], null, true);
        $this->assertSame(0, $a['foo.bar']);
        $a = new DSO(['foo'=>['bar'=>'baz']]);
        $a->merge(['foo'=>['bar'=>'']], null, true);
        $this->assertSame('', $a['foo.bar']);
        $a = new DSO(['foo'=>['bar'=>'baz']]);
        $a->merge(['foo'=>['bar'=>[]]], null, true);
        $this->assertIsArray($a['foo.bar']);
    }

    public function testMerge()
    {
        $data = [
            'a' => 'b',
            'c' => [
                'd' => 'e'
            ]
        ];
        //overwrite false, original values should be preserved
        $c = new DSO($data);
        $c->merge([
            'a' => 'B',
            'c' => [
                'd' => 'E',
                'f' => 'g'
            ],
            'h' => 'i'
        ]);
        $this->assertEquals('b', $c['a']);
        $this->assertEquals('e', $c['c.d']);
        $this->assertEquals('i', $c['h']);
        $this->assertEquals('g', $c['c.f']);
        //overwrite true, original values should be overwritten
        $c = new DSO($data);
        $c->merge([
            'a' => 'B',
            'c' => [
                'd' => 'E',
                'f' => 'g'
            ],
            'h' => 'i'
        ], null, true);
        $this->assertEquals('B', $c['a']);
        $this->assertEquals('E', $c['c.d']);
        $this->assertEquals('i', $c['h']);
        $this->assertEquals('g', $c['c.f']);
        //overwrite false with mismatched array-ness
        $c = new DSO($data);
        $c->merge([
            'a' => ['b'=>'c'],
            'c' => 'd'
        ]);
        $this->assertEquals('b', $c['a']);
        $this->assertEquals('e', $c['c.d']);
        //overwrite true with mismatched array-ness
        $c = new DSO($data);
        $c->merge([
            'a' => ['b'=>'c'],
            'c' => 'd'
        ], null, true);
        $this->assertEquals('c', $c['a.b']);
        $this->assertEquals('d', $c['c']);
    }

    public function testConstructionUnflattening()
    {
        $arr = new DSO([
            'foo.bar' => 'baz'
        ]);
        $this->assertEquals(
            ['foo'=>['bar'=>'baz']],
            $arr->get()
        );
    }
}

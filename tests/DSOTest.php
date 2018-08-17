<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
declare(strict_types=1);
namespace Digraph\Destructr;

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
}

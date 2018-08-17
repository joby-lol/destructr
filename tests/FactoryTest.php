<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
declare(strict_types=1);
namespace Digraph\Destructr;

use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testSearch()
    {
        $d = new HarnessDriver('testSearch');
        $f = new Factory($d, 'table_name');

        $s = $f->search();
        $s->where('foo = bar');

        //default execute, should be adding dso_deleted is null to where
        $s->execute(['p'=>'q']);
        $this->assertEquals('table_name', $d->last_select['table']);
        $this->assertEquals('(foo = bar) AND `dso_deleted` is null', $d->last_select['search']->where());
        $this->assertEquals(['p'=>'q'], $d->last_select['params']);

        //execute with deleted=true, should be adding dso_deleted is not null to where
        $s->execute(['p'=>'q'], true);
        $this->assertEquals('table_name', $d->last_select['table']);
        $this->assertEquals('(foo = bar) AND `dso_deleted` is not null', $d->last_select['search']->where());
        $this->assertEquals(['p'=>'q'], $d->last_select['params']);

        //execute with deleted=null, shouldn't touch where
        $s->execute(['p'=>'q'], null);
        $this->assertEquals('table_name', $d->last_select['table']);
        $this->assertEquals('foo = bar', $d->last_select['search']->where());
        $this->assertEquals(['p'=>'q'], $d->last_select['params']);
    }

    public function testInsert()
    {
        $d = new HarnessDriver('testInsert');
        $f = new Factory($d, 'table_name');
        //creating a new object
        $o = $f->create();
        $o->insert();
        $this->assertEquals('table_name', $d->last_insert['table']);
        $this->assertEquals($o->get('dso.id'), $d->last_insert['dso']->get('dso.id'));

        //creating a second object to verify
        $o = $f->create();
        $o->insert();
        $this->assertEquals('table_name', $d->last_insert['table']);
        $this->assertEquals($o->get('dso.id'), $d->last_insert['dso']->get('dso.id'));
    }

    public function testUpdate()
    {
        $d = new HarnessDriver('testUpdate');
        $f = new Factory($d, 'table_name');

        //creatingtwo new objects
        $o1 = $f->create(['dso.id'=>'object1id']);
        $o2 = $f->create(['dso.id'=>'object2id']);
        //initially, updating shouldn't do anything because there are no changes
        $o1->update();
        $this->assertNull($d->last_update);
        //after making changes updating should do something
        $o1['foo'] = 'bar';
        $o1->update();
        $this->assertEquals('table_name', $d->last_update['table']);
        $this->assertEquals($o1->get('dso.id'), $d->last_update['dso']->get('dso.id'));
        //updating second object to verify
        $o2['foo'] = 'bar';
        $o2->update();
        $this->assertEquals('table_name', $d->last_update['table']);
        $this->assertEquals($o2->get('dso.id'), $d->last_update['dso']->get('dso.id'));
        //calling update on first object shouldn't do anything, because it hasn't changed
        $o1->update();
        $this->assertEquals('table_name', $d->last_update['table']);
        $this->assertEquals($o2->get('dso.id'), $d->last_update['dso']->get('dso.id'));
        //after making changes updating should do something
        $o1['foo'] = 'baz';
        $o1->update();
        $this->assertEquals('table_name', $d->last_update['table']);
        $this->assertEquals($o1->get('dso.id'), $d->last_update['dso']->get('dso.id'));
    }

    public function testDelete()
    {
        $d = new HarnessDriver('testInsert');
        $f = new Factory($d, 'table_name');

        //non-permanent delete shouldn't do anything with deletion, but should call update
        $o = $f->create();
        $o->delete();
        $this->assertNull($d->last_delete);
        $this->assertEquals('table_name', $d->last_update['table']);
        $this->assertEquals($o->get('dso.id'), $d->last_update['dso']->get('dso.id'));
        //undelete should also not engage delete, but should call update
        $d->last_update = null;
        $o->undelete();
        $this->assertNull($d->last_delete);
        $this->assertEquals('table_name', $d->last_update['table']);
        $this->assertEquals($o->get('dso.id'), $d->last_update['dso']->get('dso.id'));

        //non-permanent delete shouldn't do anything with update, but should call delete
        $d->last_update = null;
        $o = $f->create();
        $o->delete(true);
        $this->assertNull($d->last_update);
        $this->assertEquals('table_name', $d->last_delete['table']);
        $this->assertEquals($o->get('dso.id'), $d->last_delete['dso']->get('dso.id'));
    }
}

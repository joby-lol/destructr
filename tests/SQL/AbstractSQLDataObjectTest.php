<?php
namespace Digraph\DataObject\Tests\SQL;

use \Digraph\DataObject\SQL\AbstractSQLDataObject;
use \Digraph\DataObject\DataTransformers\DataTransformerInterface;

class AbstractSQLDataObjectTest extends Generic_Tests_DatabaseTestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(
            '\\Digraph\\DataObject\\Tests\\SQL\\HarnessObject',
            new HarnessObject(),
            "Failed to instantiate a HarnessObject"
        );
    }

    public function testCreateAndRead()
    {
        //test creation
        $new = new HarnessObject(array(
            'test_col' => 'test data'
        ));
        $this->newID = $new->do_id;
        $new->create();
        $this->assertEquals(
            1,
            $this->getConnection()->getRowCount('HarnessObject'),
            "HarnessObject table should have a row in it after create() is called"
        );

        //test reading that object back out
        $read = HarnessObject::read($new->do_id);
        $this->assertNotNull($read);
        foreach (HarnessObject::map() as $key => $value) {
            if ($new->$key instanceof DataTransformerInterface) {
                $this->assertEquals(
                    $new->$key->getUserValue(),
                    $read->$key->getUserValue(),
                    "Item $key doesn't match after reading back out of DB"
                );
            } else {
                $this->assertEquals(
                    $new->$key,
                    $read->$key,
                    "Item $key doesn't match after reading back out of DB"
                );
            }
        }
    }

    /**
     * @expectedException Digraph\DataObject\Exceptions\IDExistsException
     */
    function testCreationIDExists()
    {
        $new = new HarnessObject();
        $new->create();
        $new->create();
    }

    public function testSearchSortLimit()
    {
        $d = new HarnessObject(array(
            'search_sort' => 3,
            'search_like' => 'xax'
        ));
        $c = new HarnessObject(array(
            'search_sort' => 3,
            'search_like' => 'xzx'
        ));
        $b = new HarnessObject(array(
            'search_sort' => 2,
            'search_like' => 'xbx'
        ));
        $a = new HarnessObject(array(
            'search_sort' => 1,
            'search_like' => 'xax'
        ));
        $a->create();
        $b->create();
        $c->create();
        $d->create();
        //check
        $sorted = HarnessObject::search(
            array(),
            array(
                'search_sort' => 'desc',
                'search_like' => 'asc'
            ),
            array()
        );
        //assert proper sorting
        $this->assertEquals(3, $sorted[0]->search_sort);
        $this->assertEquals('xax', $sorted[0]->search_like);
        $this->assertEquals(3, $sorted[1]->search_sort);
        $this->assertEquals('xzx', $sorted[1]->search_like);
        $this->assertEquals(2, $sorted[2]->search_sort);
        $this->assertEquals(1, $sorted[3]->search_sort);
        //re-query with limit
        $limited = HarnessObject::search(
            array(),
            array(
                'search_sort' => 'desc',
                'search_like' => 'asc'
            ),
            array(
                'limit' => 2
            )
        );
        $this->assertEquals(2, count($limited));
        $this->assertEquals(3, $limited[0]->search_sort);
        $this->assertEquals('xax', $limited[0]->search_like);
        $this->assertEquals(3, $limited[1]->search_sort);
        $this->assertEquals('xzx', $limited[1]->search_like);
        //re-query with limit, offset
        $limited = HarnessObject::search(
            array(),
            array(
                'search_sort' => 'desc',
                'search_like' => 'asc'
            ),
            array(
                'limit' => 3,
                'offset' => 1
            )
        );
        $this->assertEquals(3, count($limited));
        $this->assertEquals(3, $limited[0]->search_sort);
        $this->assertEquals('xzx', $limited[0]->search_like);
        $this->assertEquals(2, $limited[1]->search_sort);
        $this->assertEquals(1, $limited[2]->search_sort);
    }

    public function testSearchLike()
    {
        $a = new HarnessObject(array(
            'search_sort' => 1,
            'search_like' => 'xax'
        ));
        $b = new HarnessObject(array(
            'search_sort' => 2,
            'search_like' => 'xbx'
        ));
        $c = new HarnessObject(array(
            'search_sort' => 3,
            'search_like' => 'xzx'
        ));
        $d = new HarnessObject(array(
            'search_sort' => 3,
            'search_like' => 'xax'
        ));
        $a->create();
        $b->create();
        $c->create();
        $d->create();
        //check
        $sorted = HarnessObject::search(
            array(
                ':search_like LIKE ?' => '%a%'
            ),
            array(),
            array()
        );
        $this->assertEquals(2, count($sorted));
    }

    public function testUpdate()
    {
        //test basic update
        $rowCount = $this->getConnection()->getRowCount('HarnessObject');
        $new = new HarnessObject();
        $new->create();
        $read = HarnessObject::read($new->do_id);
        $read->test_col = 'updated';
        $read->update();
        $read2 = HarnessObject::read($new->do_id);
        $this->assertEquals(
            'updated',
            $read2->test_col,
            "After update, test_col value should be saved and have value \"updated\" in freshly loaded object"
        );
    }

    public function testUpdateJSON()
    {
        //test basic update
        $rowCount = $this->getConnection()->getRowCount('HarnessObject');
        $new = new HarnessObject(array(
            'test_json' => array('array'=>'not updated')
        ));
        $this->assertEquals(
            array('array'=>'not updated'),
            $new->test_json->getUserValue(),
            "Before update, test_col value should be saved and have value \"not updated\" in freshly created object"
        );
        $new->create();
        $read = HarnessObject::read($new->do_id);
        $read->test_json = array('array'=>'updated');
        $read->update();
        $read2 = HarnessObject::read($new->do_id);
        $this->assertEquals(
            array('array'=>'updated'),
            $read2->test_json->getUserValue(),
            "After update, test_col value should be saved and have value \"updated\" in freshly loaded object"
        );
    }

    public function testDelete()
    {
        $rowCount = $this->getConnection()->getRowCount('HarnessObject');
        $new = new HarnessObject();
        $new->create();
        $new->delete();
        $this->assertEquals(
            $rowCount+1,
            $this->getConnection()->getRowCount('HarnessObject'),
            "HarnessObject table should have one more entry even after a delete()"
        );
        $this->assertNull(
            HarnessObject::read($new->do_id),
            "A deleted object must not be returned by read()"
        );
        $read = HarnessObject::read($new->do_id, true);
        $this->assertNotNull(
            $read,
            "A deleted object should be returned by read() if the \$deleted flag is set"
        );
        //test permanent deletion
        $rowCount = $this->getConnection()->getRowCount('HarnessObject');
        $new = new HarnessObject();
        $new->create();
        $new->delete(true);
        $this->assertEquals(
            $rowCount,
            $this->getConnection()->getRowCount('HarnessObject'),
            'Row count should be the same as at the start after delete(true), as row is actually removed from DB'
        );
    }
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

class HarnessObject extends AbstractSQLDataObject
{
    protected static $_table = 'HarnessObject';

    static function getMap()
    {
        $map = parent::getMap();
        $map['test_col'] = array(
            'name' => 'test_col_col'
        );
        $map['test_json'] = array(
            'name' => 'test_json_col',
            'transform' => 'JSON'
        );
        $map['search_sort'] = array(
            'name'=>'search_sort'
        );
        $map['search_like'] = array(
            'name'=>'search_like'
        );
        return $map;
    }

    protected static function buildConn()
    {
        return new \PDO('sqlite:test.sqlite.tmp');
    }

    public function getStorageFolder()
    {
        return '';
    }
}

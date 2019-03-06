<?php
namespace Digraph\DataObject\Tests;

use PHPUnit\Framework\TestCase;
use Digraph\DataObject\AbstractArrayDataObject;

class AbstractArrayHarnessObject extends AbstractArrayDataObject
{
    protected static $_classTransforms = array(
        'testGetter' => array(
            'get' => 'testGetter'
        ),
        'testSetter' => array(
            'set' => 'testSetter'
        )
    );

    public function testGetter($name)
    {
        return $this->getRaw($name) . '-gotten';
    }

    function create()
    {
    }
    static function read($id)
    {
    }
    static function search($parameters = array(), $sort = array(), $options = array())
    {
    }
    function update($action = null)
    {
    }
    function delete($permanent = false)
    {
    }
    static function idExists($id)
    {
        return false;
    }
}

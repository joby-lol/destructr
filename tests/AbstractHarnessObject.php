<?php
namespace Digraph\DataObject\Tests;

use PHPUnit\Framework\TestCase;
use Digraph\DataObject\AbstractArrayDataObject;

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

class AbstractHarnessObject extends AbstractArrayDataObject
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

    public function create()
    {
    }
    public static function read($id)
    {
    }
    public static function search($parameters = array(), $sort = array(), $options = array())
    {
    }
    public function update($action = null)
    {
    }
    public function delete($permanent = false)
    {
    }
    public static function idExists($id)
    {
        return false;
    }
}

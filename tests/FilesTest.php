<?php
namespace Digraph\DataObject\Tests;

use Digraph\DataObject\Files\SingleFile;
use Digraph\DataObject\Files\FilesContainer;

class FilesTest extends \PHPUnit_Framework_TestCase
{
    public function testContainer()
    {
        //test proper instantiation and getter/setter in AbstractDataObject
        $obj = new SFTO();
        $this->assertTrue($obj->files instanceof FilesContainer);

        //try adding a file from an info array
        $tmpFile = tempnam(sys_get_temp_dir(), 'SFF');
        file_put_contents($tmpFile, 'test content');
        $newFile = array(
            'name' => 'test',
            'ext' => 'md',
            'size' => 12,
            'type' => 'text/markdown',
            'tmp_name' => $tmpFile
        );
        $files = $obj->files;
        $files->addFile('test', $newFile);
        $this->assertTrue($obj->files['test'] instanceof SingleFile);
        $this->assertEquals('test content', file_get_contents($files['test']->fullPath()));

        //test stashing file
        //it should refuse to stash this file, because tmp_name isn't an actual
        //uploaded file
        $this->assertFalse($files['test']->stash());
        $this->assertTrue(isset($files['test']['tmp_name']));
        $this->assertFalse(isset($files['test']['stash_name']));
        $this->assertEquals('test content', file_get_contents($files['test']->fullPath()));

        //test stashing file, skipping uploaded check
        $this->assertTrue($files['test']->stash(true));
        $this->assertFalse(isset($files['test']['tmp_name']));
        $this->assertTrue(isset($files['test']['stash_name']));
        $this->assertTrue(is_file($files['test']['stash_name']));
        $this->assertEquals('test content', file_get_contents($files['test']['stash_name']));
        $this->assertEquals('test content', file_get_contents($files['test']->fullPath()));

        //test storing file
        $this->assertTrue($files['test']->store());
        $this->assertFalse(isset($files['test']['stash_name']));
        $this->assertTrue(is_file($files['test']->fullPath()));
        $this->assertEquals('test content', file_get_contents($files['test']->fullPath()));
    }

    public function testSingleFile()
    {
        $obj = new SFTO();
        $tmpFile = tempnam(sys_get_temp_dir(), 'SFF');
        file_put_contents($tmpFile, 'test content');
        $obj->files->addFile('test', array(
            'name' => 'test',
            'ext' => 'md',
            'size' => 12,
            'type' => 'text/markdown',
            'tmp_name' => $tmpFile
        ));

        //test that automatic mtime updates are happening
        $this->assertEquals(time(), $obj->files['test']['mtime']);
        sleep(1);
        $this->assertNotEquals(time(), $obj->files['test']['mtime']);
        $obj->files['test']['name'] = 'new-name';
        $this->assertEquals(time(), $obj->files['test']['mtime']);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testWriteProtection()
    {
        $obj = new SFTO();
        $obj->files->addFile('test', array(
            'name' => 'test',
            'ext' => 'md',
            'size' => 12,
            'type' => 'text/markdown'
        ));
        $obj->files['test']['ctime'] = time();
    }

    public function testStringification()
    {
        $obj = new SFTO();
        $obj->files->addFile('test1', array(
            'name' => 'test1',
            'ext' => 'md',
            'size' => 12,
            'type' => 'text/markdown',
            'store_name' => 'imaginaryname'
        ));
        $obj->files->addFile('test2', array(
            'name' => 'test2',
            'ext' => 'txt',
            'size' => 24,
            'type' => 'text/plain'
        ));
        $storageString = $obj->files->getStorageString();
        //now make a new object and verify
        $new = new FilesContainer($obj, $storageString);
        $this->assertEquals('test1', $new['test1']['name']);
        $this->assertEquals('md', $new['test1']['ext']);
        $this->assertEquals('test2', $new['test2']['name']);
        $this->assertEquals('txt', $new['test2']['ext']);
        //verify same-ness of fullPath results
        $this->assertEquals(
            $obj->files['test1']->fullPath(),
            $new['test1']->fullPath()
        );
    }

    public function testFilesArrayAccess()
    {
        $obj = new SFTO();
        $obj['files']->addFile('test1', array(
            'name' => 'test1',
            'ext' => 'md',
            'size' => 12,
            'type' => 'text/markdown',
            'store_name' => 'imaginaryname'
        ));
        $obj['files']['test1']['name'] = 'altered';
        $this->assertEquals(
            'altered',
            $obj->files['test1']['name']
        );
    }
}

class SFTO extends AbstractArrayHarnessObject
{
    static function getMap()
    {
        $map = parent::getMap();
        $map['files'] = array(
            'name' => 'files',
            'getter' => 'Files',
            'setter' => 'Files',
            'default' => '{}'
        );
        return $map;
    }

    public function getFileFolder()
    {
        $tmpDir = tempnam(sys_get_temp_dir(), 'SFD').'.dir';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir);
        }
        return $tmpDir;
    }
}

<?php
/**
 * Digraph CMS: DataObject
 * https://github.com/digraphcms/digraph-dataobject

 * Copyright (c) 2017 Joby Elliott <joby@byjoby.com>

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */
namespace Digraph\DataObject\Files;

use \Digraph\DataObject\Files\SingleFile;

class FilesContainer implements FilesContainerInterface, \ArrayAccess, \Iterator
{
    protected $files = array();
    protected $iterMap = array();
    protected $iterPos = 0;

    protected $_obj = null;

    public function __construct($obj, $storageString)
    {
        $this->setParent($obj);
        $files = @json_decode($storageString, true);
        if (!is_array($files)) {
            $files = array();
        }
        foreach ($files as $key => $value) {
            $this->addFile($key, $value);
        }
    }

    public function setParent(&$obj)
    {
        $this->_obj = $obj;
    }

    public function getStashFolder()
    {
        return $this->_obj->getStashFolder();
    }

    public function getStorageFolder()
    {
        return $this->_obj->getStorageFolder();
    }

    public function addFile($id, $info)
    {
        $this[$id] = new SingleFile($this, $info);
    }

    public function deleteFile($id)
    {
        unlink($this->files[$id]->fullPath());
        unset($this->files[$id]);
        $this->buildIterMap();
    }

    public function getStorageString()
    {
        $out = array();
        foreach ($this as $name => $file) {
            $out[$name] = $file->getProperties();
        }
        return json_encode($out);
    }

    public function generateTimestampNow()
    {
        return $this->_obj->generateTimestampNow('FilesContainer', array());
    }
    public function generateCurrentUser()
    {
        return $this->_obj->generateCurrentUser('FilesContainer', array());
    }

    public function fieldCount()
    {
        return count($this->files);
    }

    protected function buildIterMap()
    {
        $this->iterMap = array();
        foreach ($this->files as $key => $value) {
            $this->iterMap[] = $key;
        }
    }
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->files[] = $value;
            $offset = count($this->files);
        } else {
            $this->files[$offset] = $value;
        }
        $value->setParent($this);
        $this->buildIterMap();
    }
    public function offsetExists($offset)
    {
        return isset($this->files[$offset]);
    }
    public function offsetUnset($offset)
    {
        $this->deleteFile($offset);
    }

    protected function &getRef($offset)
    {
        return $this->files[$offset];
    }

    public function offsetGet($offset)
    {
        if (isset($this->files[$offset])) {
            return $this->getRef($offset);
        }
        return null;
    }

    public function rewind()
    {
        $this->iterPos = 0;
    }
    public function &current()
    {
        if (isset($this->files[$this->key()])) {
            return $this->files[$this->key()];
        }
        $return = null;
        return $return;
    }
    public function key()
    {
        return isset($this->iterMap[$this->iterPos]) ? $this->iterMap[$this->iterPos] : null;
    }
    public function next()
    {
        $this->iterPos++;
    }
    public function valid()
    {
        return isset($this->files[$this->key()]);
    }
}

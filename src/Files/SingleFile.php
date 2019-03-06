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

class SingleFile implements SingleFileInterface, \ArrayAccess, \Iterator
{
    protected $properties = array(
        'name' => 'empty',
        'ext' => 'txt',
        'type' => 'text/plain',
        'size' => 0,
        'ctime' => null,
        'cuser' => null,
        'mtime' => null,
        'muser' => null,
        'store_name' => null
    );
    protected $iterMap = array();
    protected $iterPos = 0;
    protected $writeProtect = array(
        'ctime','cuser','mtime','muser','size','type','ext'
    );

    protected $_container = false;

    public function __construct($container, $info)
    {
        $this->setParent($container);
        if (!isset($info['ctime'])) {
            $info['ctime'] = $this->generateTimestampNow();
            $info['cuser'] = $this->generateCurrentUser();
        }
        if (!isset($info['mtime'])) {
            $info['mtime'] = $this->generateTimestampNow();
            $info['muser'] = $this->generateCurrentUser();
        }
        foreach ($this->properties as $key => $value) {
            $this->properties[$key] = isset($info[$key])?$info[$key]:null;
        }
        if (isset($info['tmp_name'])) {
            $this->properties['tmp_name'] = $info['tmp_name'];
        }
        if (isset($info['stash_name'])) {
            $this->properties['stash_name'] = $info['stash_name'];
        }
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function fullPath()
    {
        if (isset($this['store_name'])) {
            return $this->_container->getStorageFolder() . '/' . $this['store_name'];
        }
        if (isset($this['stash_name'])) {
            return $this['stash_name'];
        }
        if (isset($this['tmp_name'])) {
            return $this['tmp_name'];
        }
        return false;
    }

    public function store($skipCheck = false)
    {
        $storeFolder = $this->_container->getStorageFolder();
        if (isset($this['tmp_name'])) {
            $result = $this->stash($skipCheck);
            if ($result == false) {
                return false;
            }
        }
        if (isset($this['stash_name'])) {
            $storageName = strtolower(preg_replace('/[^a-z0-9]/i', '', $this['name'])).'_'.md5(file_get_contents($this['stash_name'])).'.'.$this['ext'];
            $moved = rename($this['stash_name'], $storeFolder . '/' . $storageName);
            if ($moved) {
                unset($this['stash_name']);
                $this['store_name'] = $storageName;
            }
            return $moved;
        }
        //return true because nothing needed doing
        return true;
    }

    public function stash($skipCheck = false)
    {
        $stashFolder = $this->_container->getStashFolder();
        if (isset($this['tmp_name'])) {
            $stashFile = $stashFolder . '/' . md5(rand());
            if ($skipCheck) {
                $moved = rename($this['tmp_name'], $stashFile);
            } else {
                $moved = move_uploaded_file($this['tmp_name'], $stashFile);
            }
            if ($moved) {
                unset($this['tmp_name']);
                $this['stash_name'] = $stashFile;
            }
            return $moved;
        }
        //return true becuase nothing needed doing
        return true;
    }

    public function setParent($container)
    {
        $this->_container = $container;
    }

    public function updateModified()
    {
        $this->properties['mtime'] = $this->generateTimestampNow();
        $this->properties['muser'] = $this->generateCurrentUser();
    }

    public function generateTimestampNow()
    {
        return $this->_container->generateTimestampNow();
    }

    public function generateCurrentUser()
    {
        return $this->_container->generateCurrentUser();
    }

    public function fieldCount()
    {
        return count($this->properties);
    }

    protected function buildIterMap()
    {
        $this->iterMap = array();
        foreach ($this->properties as $key => $value) {
            $this->iterMap[] = $key;
        }
    }
    public function offsetSet($offset, $value)
    {
        if (in_array($offset, $this->writeProtect)) {
            trigger_error("Property \"$offset\" is write-protected", E_USER_WARNING);
            return false;
        }
        if (is_null($offset)) {
            $this->properties[] = $value;
            $offset = count($this->properties);
        } else {
            $this->properties[$offset] = $value;
        }
        $this->updateModified();
        $this->buildIterMap();
    }
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
        $this->updateModified();
        $this->buildIterMap();
    }
    public function &offsetGet($offset)
    {
        if (isset($this->properties[$offset])) {
            return $this->properties[$offset];
        }
        $ref = null;
        return $ref;
    }

    public function rewind()
    {
        $this->iterPos = 0;
    }
    public function &current()
    {
        if (isset($this->properties[$this->key()])) {
            return $this->properties[$this->key()];
        }
        return null;
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
        return isset($this->properties[$this->key()]);
    }
}

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
namespace Digraph\DataObject;

abstract class AbstractArrayDataObject extends AbstractDataObject implements \ArrayAccess, \Iterator
{
    protected $iterMap = array();
    protected $iterPos = 0;

    protected function buildIterMap()
    {
        $this->iterMap = array();
        foreach ($this->map() as $key => $value) {
            $this->iterMap[] = $key;
        }
    }
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
        $this->buildIterMap();
    }
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }
    public function offsetUnset($offset)
    {
        throw new Exceptions\Exception("Can't unset a DataObject field", 1);
    }
    public function offsetGet($offset)
    {
        $return = $this->$offset;
        return $return;
    }

    public function rewind()
    {
        $this->iterPos = 0;
    }
    public function &current()
    {
        $key = $this->key();
        if (isset($this->$key)) {
            $return = $this->$key;
            return $return;
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
        $key = $this->key();
        return isset($this->$key);
    }
}

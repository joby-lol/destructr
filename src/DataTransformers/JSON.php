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
namespace Digraph\DataObject\DataTransformers;

use \Digraph\DataObject\DataTransformers\AbstractDataTransformer;

class JSON extends AbstractDataTransformer implements \ArrayAccess, \Iterator
{
    private $arrayAccessData = array();
    private $iteratorArrayMap = array();
    private $jsonParent = null;

    public function __construct(&$parent, &$jsonParent=null)
    {
        $this->setParent($parent, $jsonParent);
    }

    public function changed($changed = true)
    {
        parent::changed($changed);
        if ($this->jsonParent) {
            $this->jsonParent->changed($changed);
        }
    }

    public function setParent(&$parent, &$jsonParent=null)
    {
        parent::setParent($parent);
        $this->jsonParent = $jsonParent;
    }

    public function fieldCount()
    {
        return count($this->arrayAccessData);
    }

    public function setUserValue($userValue)
    {
        if (is_array($userValue)) {
            foreach ($userValue as $key => $value) {
                $this[$key] = $value;
            }
        }
    }

    public function setStorageValue($storageValue)
    {
        $this->setUserValue(json_decode($storageValue, true));
    }

    public function getStorageValue()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        return $this->getUserValue();
    }

    public function getUserValue()
    {
        $array = array();
        foreach ($this as $key => $value) {
            if ($value instanceof JSON) {
                $value = $value->toArray();
            }
            $array[$key] = $value;
        }
        return $array;
    }

    private function buildIterMap()
    {
        $this->iteratorArrayMap = array();
        foreach ($this->arrayAccessData as $key => $value) {
            $this->iteratorArrayMap[] = $key;
        }
    }

    public function offsetSet($offset, $value)
    {
        //set offset
        if (is_null($offset)) {
            $this->arrayAccessData[] = $value;
            $offset = count($this->arrayAccessData);
        }
        //array values convert to JSON
        if (is_array($value)) {
            $array = $value;
            $value = new JSON($this->getParent(), $this);
            $value->setUserValue($array);
        }
        //JSON values need a parent set
        if ($value instanceof JSON) {
            $value->setParent($this->getParent(), $this);
        }
        //set in arrayAccessData
        if (isset($this->arrayAccessData[$offset])) {
            if ($this->arrayAccessData[$offset] != $value) {
                $this->changed();
            }
        }
        $this->arrayAccessData[$offset] = $value;
        $this->buildIterMap();
    }

    public function offsetExists($offset)
    {
        return isset($this->arrayAccessData[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->arrayAccessData[$offset]);
        $this->buildIterMap();
    }

    protected function &getRef($offset)
    {
        return $this->arrayAccessData[$offset];
    }

    public function offsetGet($offset)
    {
        if (isset($this->arrayAccessData[$offset])) {
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
        if (isset($this->arrayAccessData[$this->key()])) {
            return $this->arrayAccessData[$this->key()];
        }
        $return = null;
        return $return;
    }

    public function key()
    {
        return isset($this->iteratorArrayMap[$this->iterPos]) ? $this->iteratorArrayMap[$this->iterPos] : null;
    }

    public function next()
    {
        $this->iterPos++;
    }

    public function valid()
    {
        return isset($this->arrayAccessData[$this->key()]);
    }
}

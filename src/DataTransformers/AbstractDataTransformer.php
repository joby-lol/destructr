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

abstract class AbstractDataTransformer implements DataTransformerInterface
{
    protected $parent = null;
    protected $changed = false;

    public function __construct(&$parent)
    {
        $this->setParent($parent);
    }

    public function setParent(&$parent)
    {
        $this->parent = $parent;
    }

    public function &getParent()
    {
        return $this->parent;
    }

    public function changed($changed=true)
    {
        $this->changed = $changed;
    }

    public function isChanged()
    {
        return $this->changed;
    }
}

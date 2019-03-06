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

interface DataTransformerInterface
{
    public function __construct(&$parent);
    public function setParent(&$parent);
    public function &getParent();

    public function setStorageValue($storageValue);
    public function getStorageValue();

    public function setUserValue($userValue);
    public function getUserValue();

    public function changed($changed=true);
    public function isChanged();
}

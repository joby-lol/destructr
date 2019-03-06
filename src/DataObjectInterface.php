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

interface DataObjectInterface
{
    public function create();
    public static function read($id);
    public static function search($parameters = array(), $sort = array(), $options = array());
    public function update($action = null);
    public function delete($permanent = false);

    public static function idExists($id);

    public function __construct($data = array(), $fromRaw = false);

    public static function map();
    public static function getMap();
    public static function mapEntry($name);
    public static function storageName($name);

    public function __get($name);
    public function __set($name, $value);
    public function __isset($name);
}

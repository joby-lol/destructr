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
namespace Digraph\DataObject\SQL;

use \Digraph\DataObject\Exceptions\IDExistsException;
use \Digraph\DataObject\Exceptions\UnmappedFieldException;
use \Digraph\DataObject\SQL\Exceptions\QueryException;

abstract class AbstractSQLDataObject extends \Digraph\DataObject\AbstractArrayDataObject implements SQLDataObjectInterface
{
    protected static $_table;

    protected static $_conn = null;
    protected static $_fpdo = null;

    protected static function getConn()
    {
        if (static::$_conn === null) {
            static::$_conn = static::buildConn();
        }
        return static::$_conn;
    }

    protected static function getFPDO()
    {
        if (static::$_fpdo === null) {
            static::$_fpdo = new \FluentPDO(static::getConn());
        }
        return static::$_fpdo;
    }

    protected static function getInsert()
    {
        return static::getFPDO()->insertInto(static::$_table);
    }

    public static function getSelect($deleted = false)
    {
        $query = static::getFPDO()->from(static::$_table);
        if (!$deleted) {
            $deletedCol = static::storageName('do_deleted');
            $query->where("$deletedCol is null");
        }
        return $query;
    }

    protected function getUpdate()
    {
        return static::getFPDO()->update(static::$_table)->where(
            static::storageName('do_id'),
            $this->do_id
        );
    }

    public static function runSelect($query)
    {
        $class = get_called_class();
        $results = array();
        foreach ($query as $row) {
            $data = array();
            foreach (static::map() as $key => $mapEntry) {
                $data[$key] = $row[$mapEntry['name']];
            }
            $results[] = new $class($data, true);
        }
        return $results;
    }

    public function create($dump = false)
    {
        if (static::idExists($this->do_id)) {
            throw new IDExistsException($this->do_id);
        }
        $values = array();
        foreach ($this->dataRaw as $name => $value) {
            $map = $this->mapEntry($name);
            $values[$map['name']] = $value;
        }
        $query = $this->getInsert();
        $query->values($values);
        if ($dump) {
            var_dump($query->getQuery());
            var_dump($values);
        }
        $result = $query->execute();
        if ($result === false) {
            throw new QueryException();
        }
        return $result;
    }

    public static function read($id, $deleted = false)
    {
        $query = static::getSelect($deleted);
        $query->where(static::storageName('do_id'), $id);
        $results = static::runSelect($query);
        return array_pop($results);
    }

    protected static function isValidOrder($order)
    {
        switch (strtolower($order)) {
            case 'asc':
                return true;
            case 'desc':
                return true;
            default:
                return false;
        }
    }

    protected static function colParameterSearch($str)
    {
        $class = get_called_class();
        $str = preg_replace_callback('/(:([a-zA-Z0-9_]+))/', function ($matches) use ($class) {
            $col = $class::storageName($matches[2]);
            if (!$col) {
                throw new UnmappedFieldException($matches[2]);
            }
            return $col;
        }, $str);
        return $str;
    }

    public static function count($parameters = array())
    {
        $query = static::getSelect();
        //parse $parameters
        foreach ($parameters as $search => $value) {
            $search = static::colParameterSearch($search);
            if ($value === null) {
                $query->where($search);
            } else {
                $query->where($search, $value);
            }
        }
        return count($query);
    }

    public static function search($parameters = array(), $sort = array(), $options = array())
    {
        $deleted = (isset($options['deleted']) && $options['deleted']);
        $query = static::getSelect($deleted);
        //parse $parameters
        foreach ($parameters as $search => $value) {
            $search = static::colParameterSearch($search);
            if ($value === null) {
                $query->where($search);
            } else {
                $query->where($search, $value);
            }
        }
        //parse $sort
        foreach ($sort as $name => $order) {
            if (($col = static::colParameterSearch($name)) && static::isValidOrder($order)) {
                $query->orderBy("$col $order");
            } else {
                throw new UnmappedFieldException($name);
            }
        }
        //limit
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }
        //offset
        if (isset($options['offset'])) {
            $query->offset($options['offset']);
        }
        return static::runSelect($query);
    }

    public function update($action = null, $dump = false)
    {
        // var_dump($this->dataChanged);
        // exit();
        if ($action !== null) {
            $action = array('action'=>$action);
        } else {
            $action = array();
        }
        if (!$this->dataChanged) {
            return null;
        }
        $this->set('do_mdate', strval(time()));
        $this->set('do_muser', $this->generateUser('do_muser'));
        $query = static::getUpdate();
        $values = array();
        foreach ($this->dataChanged as $key) {
            $values[static::storageName($key)] = $this->getRaw($key);
        }
        $query->set($values);
        if ($dump) {
            var_dump($query->getQuery());
            var_dump($values);
        }
        return $query->execute();
    }

    public function delete($permanent = false, $dump = false)
    {
        if ($permanent) {
            $query = $this->getFPDO()->deleteFrom(static::$_table);
            $query->where(static::storageName('do_id'), $this->do_id);
            return $query->execute();
        }
        $this->set('do_deleted', time());
        return $this->update(null, $dump);
    }

    public static function idExists($id)
    {
        if (static::read($id)) {
            return true;
        }
        return false;
    }
}

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

use \Digraph\DataObject\Files\FilesContainer;
use \Digraph\DataObject\JSON\JSONContainer;

abstract class AbstractDataObject implements DataObjectInterface
{
    /**
     * Characters that are allowed in ID generation
     * @var string
     */
    protected static $_idChars = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Length of random portion of ID -- with the default 36 character set, 12
     * characters gives you about 62 bits of info, which is probably good for
     * most applications, even after profanity filtering
     * @var int
     */
    protected static $_idLength = 12;

    /**
     * Prefix for this class's objects
     * @var string
     */
    protected static $_idPrefix = 'do-';

    /**
     * Prefix for this class's storage names
     * @var string
     */
    protected static $_storagePrefix = '';

    /**
     * Stores the current data for this object
     * @var array
     */
    protected $_data = array();

    /**
     * Stores a list of what data has been changed in this object
     * @var array
     */
    protected $_dataChanged = array();

    /**
     * Stores the rules for transforming properties as they are get/set
     * @var array
     */
    protected $_transforms = array();

    /**
     * Holds static transforms that are loaded for each class at construction
     * @var array
     */
    protected static $_classTransforms = array(
        'JSON' => array(
            'class' => '\\Digraph\\DataObject\\DataTransformers\\JSON'
        ),
        'bool' => array(
            'set' => '\\Digraph\\DataObject\\DataTransformers\\SimpleTransforms::bool_set',
            'get' => '\\Digraph\\DataObject\\DataTransformers\\SimpleTransforms::bool_get'
        )
    );

    /**
     * Holds transformation objects for properties that have them defined via
     * class transforms and the map
     * @var array
     */
    protected $_transformObjects = array();

    /**
     * Construct a new copy of a DataObject -- data values can be specified,
     * and those with defaults will be populated and those with generators will
     * be generated (overriding anything the user requests)
     * @param array $data
     */
    public function __construct($data = array(), $fromRaw = false)
    {
        $this->registerClassTransforms();
        //set up class transformers
        foreach ($this->map() as $name => $map) {
            if (isset($map['transform'])) {
                if (isset($this->_transforms[$map['transform']]['class'])) {
                    $class = $this->_transforms[$map['transform']]['class'];
                    $this->_transformObjects[$name] = new $class($this);
                }
            }
        }
        //set values
        if (!$fromRaw) {
            $data = $this->dataGenerate($data, true);
            foreach ($data as $name => $value) {
                $this->set($name, $value);
            }
        } else {
            foreach ($data as $name => $value) {
                $this->setRaw($name, $value);
            }
        }
        //build itermap
        $this->buildIterMap();
    }

    /**
     * getter for retrieving all changed data
     * @return array
     */
    public function _get_dataChanged()
    {
        foreach ($this->_transformObjects as $name => $object) {
            $this->_dataChanged[$name] = true;
        }
        $changed = array();
        foreach ($this->_dataChanged as $name => $value) {
            $changed[] = $name;
        }
        return $changed;
    }

    /**
     * getter for the raw storage values of all data
     * @return array
     */
    public function _get_dataRaw()
    {
        $this->dataChanged;
        return $this->_data;
    }

    /**
     * walk up the inheritance list, registering all class transforms
     * @return void
     */
    protected function registerClassTransforms($class = false)
    {
        if (!$class) {
            $class = get_called_class();
        }
        $parent = false;
        if ($parent = get_parent_class($class)) {
            $parent::registerClassTransforms($parent);
        }
        foreach ($class::$_classTransforms as $key => $value) {
            $this->_transforms[$key] = $value;
        }
    }

    /**
     * Given an array of name/value pairs, fill it out with any defaults or
     * anything that must be generated per the map
     * @param  array  $data
     * @return array
     */
    public function dataGenerate($data = array())
    {
        $dataOut = array();
        //set values of $dataOut from $data or defaults and queue things that need generation
        foreach (static::map() as $name => $conf) {
            if (isset($data[$name])) {
                //simple value being set in data
                $dataOut[$name] = $data[$name];
            } elseif (isset($conf['generated'])) {
                //generated value
                $dataOut[$name] = $this->generateValue($name);
            } elseif (isset($conf['default'])) {
                //default value
                $dataOut[$name] = $conf['default'];
            } else {
                //null is the default of defaults
                $dataOut[$name] = null;
            }
        }
        //return
        return $dataOut;
    }

    /**
     * generate a value for a particular parameter name
     * @param  string $name
     * @return mixed
     */
    public function generateValue($name)
    {
        $map = $this->mapEntry($name);
        $generator = $map['generated'];
        if (method_exists($this, $generator)) {
            return $this->$generator($name);
        }
        if (is_callable($generator)) {
            return $generator($name);
        }
        throw new Exceptions\Exception("No valid generator found for $name", 1);
    }

    /**
     * Get a value using its custom getter
     * @param  string $name
     * @return mixed
     */
    protected function get($name)
    {
        $map = $this->mapEntry($name);
        //getters by property name
        $getter = '_get_'.$name;
        if (method_exists($this, $getter)) {
            return $this->$getter($name);
        }
        //transformed
        if (isset($map['transform']) && isset($this->_transforms[$map['transform']])) {
            $transform = $this->_transforms[$map['transform']];
            //transformation object
            if (isset($transform['class'])) {
                return $this->ref($this->_transformObjects[$name]);
            }
            //transformation function
            if (isset($transform['get'])) {
                $getter = $transform['get'];
                if (method_exists($this, $getter)) {
                    return $this->$getter($name);
                }
            }
        }
        //just regular return
        if (array_key_exists($name, $this->_data)) {
            $return = $this->getRaw($name);
            return $return;
        }
        $return = null;
        return $return;
    }

    protected function &ref(&$var)
    {
        return $var;
    }

    /**
     * Set the value of a field using its custom setters
     * @param string $name
     * @param mixed $value
     */
    protected function set($name, $value)
    {
        $map = $this->mapEntry($name);
        //setters by property name
        $setter = '_set_'.$name;
        if (method_exists($this, $setter)) {
            $this->$setter($name, $value);
            return $this->get($name);
        }
        //transformed
        if (isset($map['transform']) && isset($this->_transforms[$map['transform']])) {
            $transform = $this->_transforms[$map['transform']];
            //transformation object
            if (isset($transform['class'])) {
                if ($this->_transformObjects[$name]->getUserValue() != $value) {
                    $this->_dataChanged[$name] = true;
                }
                $this->_transformObjects[$name]->setUserValue($value);
                return $this->get($name);
            }
            //transformation function
            $transform = $this->_transforms[$map['transform']];
            if (isset($transform['set'])) {
                $setter = $transform['set'];
                if (method_exists($this, $setter)) {
                    if ($value != $this->get($name)) {
                        $this->_dataChanged[$name] = true;
                    }
                    $this->$setter($name, $value);
                    return $this->get($name);
                }
            }
        }
        //just regular value
        if (array_key_exists($name, $this->map())) {
            if ($value != $this->get($name)) {
                $this->_dataChanged[$name] = true;
            }
            $this->setRaw($name, $value);
            return $this->get($name);
        }
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $mapEntry = $this->mapEntry($name);
        //hide masked values
        if (isset($mapEntry['masked']) && $mapEntry['masked']) {
            $return = null;
            return $return;
        }
        //use internal getter
        return $this->get($name);
    }

    /**
     * @param  string $name
     * @param  mixed $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        //load map entry
        $mapEntry = $this->mapEntry($name);
        //check for not setting masked values
        if (isset($mapEntry['masked']) && $mapEntry['masked']) {
            return null;
        }
        //check for not setting system values
        if (isset($mapEntry['system']) && $mapEntry['system']) {
            throw new Exceptions\Exception("Public interface doesn't allow setting system values (tried to set $name)", 1);
        }
        //do the actual setting
        return $this->set($name, $value);
    }

    /**
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        //load map entry
        $mapEntry = $this->mapEntry($name);
        //hide masked values
        if (isset($mapEntry['masked']) && $mapEntry['masked']) {
            return false;
        }
        //function name getters
        $getter = '_get_' . $name;
        if (method_exists($this, $getter)) {
            return true;
        }
        //map names
        if (array_key_exists($name, $this->map())) {
            return true;
        }
        return false;
    }

    /**
     * get the raw value of an item
     * @param  string $name
     * @return mixed
     */
    protected function getRaw($name)
    {
        if (!$this->mapEntry($name)) {
            throw new Exceptions\Exception("\"$name\" not mapped for \"".get_called_class()."\"", 1);
        }
        if (isset($this->_transformObjects[$name])) {
            $this->_data[$name] = $this->_transformObjects[$name]->getStorageValue();
        }
        if (!isset($this->_data[$name])) {
            return null;
        }
        return $this->_data[$name];
    }

    /**
     * set the raw value of an item
     * @param string $name
     * @param mixed $value
     */
    protected function setRaw($name, $value)
    {
        if (!array_key_exists($name, $this->map())) {
            throw new Exceptions\Exception("\"$name\" not mapped for \"".get_called_class()."\"", 1);
        }
        //transform object
        if (isset($this->_transformObjects[$name])) {
            $this->_transformObjects[$name]->setStorageValue($value);
            $value = $this->_transformObjects[$name]->getStorageValue();
            if (!isset($this->_data[$name])) {
                $this->_data[$name] = $value;
            }
            return $value;
        }
        //regular value
        $this->_data[$name] = $value;
        return $this->_data[$name] = $value;
    }

    /**
     * Return the same data as getMap, but with $_storagePrefix applied to each
     * entry's name data.
     * @return Array
     */
    protected static $_maps = array();
    public static function map()
    {
        $class = get_called_class();
        if (!isset(static::$_maps[$class])) {
            $map = static::getMap();
            if (static::$_storagePrefix) {
                foreach ($map as $key => $value) {
                    if (strpos($map[$key]['name'], 'do_') === 0) {
                        $map[$key]['name'] = preg_replace('/^do_/', static::$_storagePrefix, $value['name']);
                    } else {
                        $map[$key]['name'] = static::$_storagePrefix.$map[$key]['name'];
                    }
                }
            }
            static::$_maps[$class] = $map;
        }
        return static::$_maps[$class];
    }

    /**
     * Return a map of this class's data names and how they are named in the
     * underlying storage layer. Abstract classes provide the bare minimum
     * mapping to create a DataObject, so child classes should extend what is
     * returned by parent::getMap()
     * @return Array
     */
    public static function getMap()
    {
        return array(
            'do_id' => array(
                'name' => 'do_id',
                'system' => true,
                'generated' => 'generateID'
            ),
            'do_cdate' => array(
                'name' => 'do_cdate',
                'system' => true,
                'generated' => 'time'
            ),
            'do_cuser' => array(
                'name' => 'do_cuser',
                'system' => true,
                'generated' => 'generateUser',
                'transform' => 'JSON'
            ),
            'do_mdate' => array(
                'name' => 'do_mdate',
                'system' => true,
                'generated' => 'time'
            ),
            'do_muser' => array(
                'name' => 'do_muser',
                'system' => true,
                'generated' => 'generateUser',
                'transform' => 'JSON'
            ),
            'do_deleted' => array(
                'name' => 'do_deleted',
                'system' => true
            )
        );
    }

    /**
     * Retrieve a map entry by name
     * @param  string $name
     * @return array
     */
    public static function mapEntry($name)
    {
        $class = get_called_class();
        if (!isset(static::$_maps[$class])) {
            static::map();
        }
        if (isset(static::$_maps[$class][$name])) {
            return static::$_maps[$class][$name];
        }
        return false;
    }

    /**
     * Retrieve the storage name of a map entry
     * @param  string $name
     * @return string
     */
    public static function storageName($name)
    {
        $mapEntry = static::mapEntry($name);
        if (!$mapEntry) {
            return false;
        }
        return $mapEntry['name'];
    }

    /**
     * generate a new ID
     * @param  string $name
     * @param  array  $data
     * @return string
     */
    public static function generateID($name, $depth = 0)
    {
        if ($depth >= 10) {
            throw new Exceptions\IDSpaceException();
        }
        $id = '';
        while (strlen($id) < static::$_idLength) {
            $id .= substr(
                static::$_idChars,
                rand(0, strlen(static::$_idChars)-1),
                1
            );
        }
        $id = static::$_idPrefix.$id;
        if (Utils\BadWords::profane($id) || static::idExists($id)) {
            return static::generateID($name, $depth+1);
        }
        return $id;
    }

    /**
     * Generate an array holding information about the current user
     * @param  string $name
     * @param  array  $data
     * @return array
     */
    public static function generateUser($name)
    {
        $out = array();
        $out['ip'] = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $out['fw'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $out;
    }
}

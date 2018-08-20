<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr;

use \Flatrr\FlatArray;

/**
 * Interface for DeStructure Objects (DSOs). These are the class that is
 * actually used for storing and retrieving partially-structured data from the
 * database.
 */
class DSO extends FlatArray implements DSOInterface
{
    protected $factory;
    protected $changes;
    protected $removals;

    public function __construct(array $data = null, DSOFactoryInterface &$factory = null)
    {
        $this->resetChanges();
        parent::__construct($data);
        $this->factory($factory);
        $this->resetChanges();
    }

    public function hook_create()
    {
        //does nothing
    }
    public function hook_update()
    {
        //does nothing
    }

    public function delete(bool $permanent = false) : bool
    {
        return $this->factory->delete($this, $permanent);
    }

    public function undelete() : bool
    {
        return $this->factory->undelete($this);
    }

    public function insert() : bool
    {
        return $this->factory()->insert($this);
    }

    public function update() : bool
    {
        return $this->factory()->update($this);
    }

    public function resetChanges()
    {
        $this->changes = new FlatArray();
        $this->removals = new FlatArray();
    }

    public function changes() : array
    {
        return $this->changes->get();
    }

    public function removals() : array
    {
        return $this->removals->get();
    }

    public function set(string $name = null, $value, $force=false)
    {
        $name = strtolower($name);
        if ($this->get($name) == $value) {
            return;
        }
        if (is_array($value)) {
            //check for what's being removed
            if (is_array($this->get($name))) {
                foreach ($this->get($name) as $k => $v) {
                    if (!isset($value[$k])) {
                        if ($name) {
                            $k = $name.'.'.$k;
                        }
                        $this->unset($k);
                    }
                }
            }
            //recursively set individual values so we can track them
            foreach ($value as $k => $v) {
                if ($name) {
                    $k = $name.'.'.$k;
                }
                $this->set($k, $v, $force);
            }
        } else {
            $this->changes->set($name, $value);
            unset($this->removals[$name]);
            parent::set($name, $value);
        }
    }

    public function unset(?string $name)
    {
        if (isset($this[$name])) {
            $this->removals->set($name, $this->get($name));
            unset($this->changes[$name]);
            parent::unset($name);
        }
    }

    public function factory(DSOFactoryInterface &$factory = null) : ?DSOFactoryInterface
    {
        if ($factory) {
            $this->factory = $factory;
        }
        return $this->factory;
    }
}

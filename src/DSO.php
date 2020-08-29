<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr;

use \Flatrr\FlatArray;

/**
 * Interface for DeStructured Objects (DSOs). These are the class that is
 * actually used for storing and retrieving partially-structured data from the
 * database.
 */
class DSO extends FlatArray implements DSOInterface
{
    protected $factory;
    protected $changes;
    protected $removals;

    public function __construct(array $data = null, Factory $factory = null)
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

    public function delete(bool $permanent = false): bool
    {
        return $this->factory->delete($this, $permanent);
    }

    public function undelete(): bool
    {
        return $this->factory->undelete($this);
    }

    public function insert(): bool
    {
        return $this->factory()->insert($this);
    }

    public function update(bool $sneaky = false): bool
    {
        return $this->factory()->update($this);
    }

    public function resetChanges()
    {
        $this->changes = new FlatArray();
        $this->removals = new FlatArray();
    }

    public function changes(): array
    {
        return $this->changes->get();
    }

    public function removals(): array
    {
        return $this->removals->get();
    }

    public function set(?string $name, $value, $force = false)
    {
        $name = strtolower($name);
        if ($this->get($name) === $value) {
            return;
        }
        if (is_array($value)) {
            //check for what's being removed
            if (is_array($this->get($name))) {
                foreach ($this->get($name) as $k => $v) {
                    if (!isset($value[$k])) {
                        if ($name) {
                            $k = $name . '.' . $k;
                        }
                        $this->unset($k);
                    }
                }
            } else {
                parent::set($name, []);
            }
            //recursively set individual values so we can track them
            foreach ($value as $k => $v) {
                if ($name) {
                    $k = $name . '.' . $k;
                }
                $this->set($k, $v, $force);
            }
        } else {
            $this->changes->set($name, $value);
            unset($this->removals[$name]);
            parent::set($name, $value);
        }
    }

    function unset(?string $name) {
        if (isset($this[$name])) {
            $this->removals->set($name, $this->get($name));
            unset($this->changes[$name]);
            parent::unset($name);
        }
    }

    public function factory(Factory $factory = null): ?Factory
    {
        if ($factory) {
            $this->factory = $factory;
        }
        return $this->factory;
    }
}

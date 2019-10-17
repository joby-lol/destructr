<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr;

use Flatrr\FlatArrayInterface;

/**
 * Interface for DeStructure Objects (DSOs). These are the class that is
 * actually used for storing and retrieving partially-structured data from the
 * database.
 */
interface DSOInterface extends FlatArrayInterface
{
    public function __construct(array $data = null, DSOFactoryInterface &$factory = null);
    public function factory(DSOFactoryInterface &$factory = null) : ?DSOFactoryInterface;

    public function set(string $name = null, $value, $force=false);

    public function resetChanges();
    public function changes() : array;
    public function removals() : array;

    public function insert() : bool;
    public function update(bool $sneaky = false) : bool;
    public function delete(bool $permanent = false) : bool;
    public function undelete() : bool;
}
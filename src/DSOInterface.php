<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr;

use Flatrr\FlatArrayInterface;

/**
 * Interface for DeStructure Objects (DSOs). These are the class that is
 * actually used for storing and retrieving partially-structured data from the
 * database.
 */
interface DSOInterface extends FlatArrayInterface
{
    public function __construct(array $data = null, Factory $factory = null);
    public function factory(Factory $factory = null): ?Factory;

    public function set(?string $name, $value, $force = false);

    public function resetChanges();
    public function changes(): array;
    public function removals(): array;

    public function insert(): bool;
    public function update(bool $sneaky = false): bool;
    public function delete(bool $permanent = false): bool;
    public function undelete(): bool;
}

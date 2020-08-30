<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;
use Destructr\Search;

abstract class AbstractDriver
{
    const SCHEMA_TABLE = 'destructr_schema';

    abstract public function errorInfo();
    abstract public function update(string $table, DSOInterface $dso): bool;
    abstract public function delete(string $table, DSOInterface $dso): bool;
    abstract public function count(string $table, Search $search, array $params): int;
    abstract public function select(string $table, Search $search, array $params);
    abstract public function insert(string $table, DSOInterface $dso): bool;
    abstract public function beginTransaction(): bool;
    abstract public function commit(): bool;
    abstract public function rollBack(): bool;
    abstract public function prepareEnvironment(string $table, array $schema): bool;
    abstract public function updateEnvironment(string $table, array $schema): bool;
    abstract public function checkEnvironment(string $table, array $schema): bool;
}

<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;
use Destructr\Search;
use PDO;

abstract class AbstractDriver
{
    abstract public function errorInfo();
    abstract public function update(string $table, DSOInterface $dso): bool;
    abstract public function delete(string $table, DSOInterface $dso): bool;
    abstract public function count(string $table, Search $search, array $params): int;
    abstract public function select(string $table, Search $search, array $params);
    abstract public function insert(string $table, DSOInterface $dso): bool;
    abstract public function prepareEnvironment(string $table, array $schem): bool;
}

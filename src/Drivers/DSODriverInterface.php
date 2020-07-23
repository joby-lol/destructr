<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;
use Destructr\Search;

interface DSODriverInterface
{
    public function __construct(string $dsn=null, string $username=null, string $password=null, array $options=null);
    public function pdo(\PDO $pdo=null) : ?\PDO;

    public function createTable(string $table, array $virtualColumns) : bool;
    public function select(string $table, Search $search, array $params);
    public function insert(string $table, DSOInterface $dso) : bool;
    public function update(string $table, DSOInterface $dso) : bool;
    public function delete(string $table, DSOInterface $dso) : bool;

    public function errorInfo();
}

<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
namespace Digraph\Destructr\Drivers;

use Digraph\Destructr\DSOInterface;
use Digraph\Destructr\Search;

interface DSODriverInterface
{
    public function __construct(string $dsn, string $username=null, string $password=null, array $options=null);

    public function createTable(string $table, array $virtualColumns) : bool;
    public function select(string $table, Search $search, array $params);
    public function insert(string $table, DSOInterface $dso) : bool;
    public function update(string $table, DSOInterface $dso) : bool;
    public function delete(string $table, DSOInterface $dso) : bool;

    public function errorInfo();
}

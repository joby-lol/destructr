<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr;

class HarnessDriver implements Drivers\DSODriverInterface
{
    const EXTENSIBLE_VIRTUAL_COLUMNS = true;
    public $last_select;
    public $last_insert;
    public $last_update;
    public $last_delete;

    public function __construct(string $dsn, string $username=null, string $password=null, array $options=null)
    {
    }

    public function createTable(string $table, array $virtualColumns) : bool
    {
        //TODO: add tests for this too
        return false;
    }

    public function select(string $table, Search $search, array $params) : array
    {
        $this->last_select = [
            'table' => $table,
            'search' => $search,
            'params' => $params
        ];
        return [];
    }

    public function insert(string $table, DSOInterface $dso) : bool
    {
        $this->dsn = 'inserting';
        $this->last_insert = [
            'table' => $table,
            'dso' => $dso
        ];
        return true;
    }

    public function update(string $table, DSOInterface $dso) : bool
    {
        $this->last_update = [
            'table' => $table,
            'dso' => $dso
        ];
        return true;
    }

    public function delete(string $table, DSOInterface $dso) : bool
    {
        $this->last_delete = [
            'table' => $table,
            'dso' => $dso
        ];
        return true;
    }

    public function errorInfo()
    {
        return [];
    }
}

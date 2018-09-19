<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;
use Destructr\Search;

//TODO: Caching? It should happen somewhere in this class I think.
abstract class AbstractDriver implements DSODriverInterface
{
    public $lastPreparationErrorOn;
    public $pdo;
    const EXTENSIBLE_VIRTUAL_COLUMNS = true;

    public function __construct(string $dsn, string $username=null, string $password=null, array $options=null)
    {
        if (!$this->pdo = new \PDO($dsn, $username, $password, $options)) {
            throw new \Exception("Error creating PDO connection");
        }
    }

    protected function expandPaths($value)
    {
        if ($value === null) {
            return null;
        }
        $value = preg_replace_callback(
            '/\$\{([^\}\\\]+)\}/',
            function ($matches) {
                return $this->expandPath($matches[1]);
            },
            $value
        );
        return $value;
    }

    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }

    public function createTable(string $table, array $virtualColumns) : bool
    {
        $sql = $this->sql_ddl([
            'table'=>$table,
            'virtualColumns'=>$virtualColumns
        ]);
        return $this->pdo->exec($sql) !== false;
    }

    public function update(string $table, DSOInterface $dso) : bool
    {
        if (!$dso->changes() && !$dso->removals()) {
            return true;
        }
        $s = $this->getStatement(
            'setJSON',
            ['table'=>$table]
        );
        return $s->execute([
            ':dso_id' => $dso['dso.id'],
            ':data' => json_encode($dso->get())
        ]);
    }

    public function delete(string $table, DSOInterface $dso) : bool
    {
        $s = $this->getStatement(
            'delete',
            ['table'=>$table]
        );
        return $s->execute([
            ':dso_id' => $dso['dso.id']
        ]);
    }

    public function select(string $table, Search $search, array $params)
    {
        $s = $this->getStatement(
            'select',
            ['table'=>$table,'search'=>$search]
        );
        if (!$s->execute($params)) {
            return [];
        }
        return @$s->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert(string $table, DSOInterface $dso) : bool
    {
        return $this->getStatement(
            'insert',
            ['table'=>$table]
        )->execute(
            [':data'=>json_encode($dso->get())]
        );
    }

    protected function getStatement(string $type, $args=array()) : \PDOStatement
    {
        $fn = 'sql_'.$type;
        if (!method_exists($this, $fn)) {
            throw new \Exception("Error getting SQL statement, driver doesn't have a method named $fn");
        }
        $sql = $this->$fn($args);
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt) {
            $this->lastPreparationErrorOn = $sql;
            throw new \Exception("Error preparing statement: ".implode(': ', $this->pdo->errorInfo()), 1);
        }
        return $stmt;
        //TODO: turn this on someday and see if caching statements helps in the real world
        // $sql = $this->$fn($args);
        // $id = md5($sql);
        // if (!isset($this->prepared[$id])) {
        //     $this->prepared[$id] = $this->pdo->prepare($sql);
        // }
        // return @$this->prepared[$id];
    }
}

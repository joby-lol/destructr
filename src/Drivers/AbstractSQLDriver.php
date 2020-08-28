<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;
use Destructr\Search;
use PDO;

abstract class AbstractSQLDriver extends AbstractDriver
{
    public $lastPreparationErrorOn;
    public $pdo;
    protected $schemas = [];

    abstract protected function sql_ddl(array $args = []): string;
    abstract protected function expandPath(string $path): string;
    abstract protected function sql_set_json(array $args): string;
    abstract protected function sql_insert(array $args): string;
    abstract protected function updateColumns($table,$schema):bool;
    abstract protected function addColumns($table,$schema):bool;
    abstract protected function removeColumns($table,$schema):bool;

    public function __construct(string $dsn = null, string $username = null, string $password = null, array $options = null)
    {
        if ($dsn) {
            if (!($pdo = new \PDO($dsn, $username, $password, $options))) {
                throw new \Exception("Error creating PDO connection");
            }
            $this->pdo($pdo);
        }
    }

    public function tableExists(string $table): bool
    {
        $stmt = $this->pdo()->prepare($this->sql_table_exists($table));
        if ($stmt && $stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    protected function sql_table_exists(string $table): string
    {
        $table = $this->pdo()->quote($table);
        return 'SELECT 1 FROM ' . $table . ' LIMIT 1';
    }

    public function createSchemaTable()
    {
        $sql = $this->sql_create_schema_table();
        return $this->pdo->exec($sql) !== false;
    }

    public function pdo(\PDO $pdo = null): ?\PDO
    {
        if ($pdo) {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->pdo = $pdo;
        }
        return $this->pdo;
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

    public function prepareEnvironment(string $table, array $schema): bool
    {
        return $this->createSchemaTable()
        && $this->createTable($table, $schema);
    }

    public function updateEnvironment(string $table, array $schema): bool
    {
        return $this->updateTable($table, $schema);
    }

    protected function updateTable($table,$schema): bool
    {
        $current = $this->getSchema($table);
        $new = $schema;
        if ($schema == $current) {
            return true;
        }
        $updated = [];
        $added = [];
        $removed = [];
        //remove all unchanged columns from current and new
        foreach($current as $c_id => $c) {
            foreach ($schema as $n_id => $n) {
                if ($n == $c && $n_id == $c_id) {
                    unset($current[$c_id]);
                    unset($new[$n_id]);
                }
            }
        }
        //identify updated columns
        foreach($current as $c_id => $c) {
            foreach ($schema as $n_id => $n) {
                if ($n['name'] == $c['name'] && ($n != $c || $n_id != $c_id)) {
                    $updated[$n_id] = $n;
                    unset($current[$c_id]);
                    unset($new[$n_id]);
                }
            }
        }
        //identify removed columns
        foreach($current as $c_id => $c) {
            $found = false;
            foreach ($schema as $n_id => $n) {
                if ($n['name'] == $c['name']) {
                    $found = true;
                }
            }
            if (!$found) {
                $removed[$c_id] = $c;
            }
        }
        //identify added columns
        $added = $new;
        //apply changes
        return $this->updateColumns($table,$updated)
        && $this->addColumns($table,$added)
        && $this->removeColumns($table,$removed)
        && $this->saveSchema($table,$schema);
    }

    public function createTable(string $table, array $schema): bool
    {
        // check if table exists, if it doesn't, save into schema table
        if (!$this->tableExists($table)) {
            $this->saveSchema($table, $schema);
        }
        // create table from scratch
        $sql = $this->sql_ddl([
            'table' => $table,
            'schema' => $schema,
        ]);
        return $this->pdo->exec($sql) !== false;
    }

    public function getSchema(string $table): ?array
    {
        if (!isset($this->schemas[$table])) {
            $s = $this->getStatement(
                'get_schema',
                ['table' => $table]
            );
            if (!$s->execute(['table' => $table])) {
                $this->schemas[$table] = null;
            }else {
                $this->schemas[$table] = json_decode($s->fetch(\PDO::FETCH_ASSOC)['schema_schema'],true);
            }
        }
        return @$this->schemas[$table];
    }

    public function saveSchema(string $table, array $schema): bool
    {
        return $this->pdo->exec(
            $this->sql_save_schema($table, $schema)
        ) !== false;
    }

    protected function sql_save_schema(string $table, array $schema)
    {
        $table = $this->pdo->quote($table);
        $schema = $this->pdo->quote(json_encode($schema));
        return <<<EOT
INSERT INTO `destructr_schema`
(schema_table,schema_schema)
VALUES ($table,$schema);
EOT;
    }

    protected function sql_get_schema(array $args)
    {
        return <<<EOT
SELECT * FROM `destructr_schema`
WHERE `schema_table` = :table;
EOT;
    }

    public function update(string $table, DSOInterface $dso): bool
    {
        if (!$dso->changes() && !$dso->removals()) {
            return true;
        }
        $s = $this->getStatement(
            'set_json',
            ['table' => $table]
        );
        return $s->execute([
            ':dso_id' => $dso['dso.id'],
            ':data' => json_encode($dso->get()),
        ]);
    }

    public function delete(string $table, DSOInterface $dso): bool
    {
        $s = $this->getStatement(
            'delete',
            ['table' => $table]
        );
        return $s->execute([
            ':dso_id' => $dso['dso.id'],
        ]);
    }

    public function count(string $table, Search $search, array $params): int
    {
        $s = $this->getStatement(
            'count',
            ['table' => $table, 'search' => $search]
        );
        if (!$s->execute($params)) {
            return null;
        }
        return intval($s->fetchAll(\PDO::FETCH_COLUMN)[0]);
    }

    public function select(string $table, Search $search, array $params)
    {
        $s = $this->getStatement(
            'select',
            ['table' => $table, 'search' => $search]
        );
        if (!$s->execute($params)) {
            return [];
        }
        return @$s->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert(string $table, DSOInterface $dso): bool
    {
        return $this->getStatement(
            'insert',
            ['table' => $table]
        )->execute(
            [':data' => json_encode($dso->get())]
        );
    }

    protected function getStatement(string $type, $args = array()): \PDOStatement
    {
        $fn = 'sql_' . $type;
        if (!method_exists($this, $fn)) {
            throw new \Exception("Error getting SQL statement, driver doesn't have a method named $fn");
        }
        $sql = $this->$fn($args);
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt) {
            $this->lastPreparationErrorOn = $sql;
            throw new \Exception("Error preparing statement: " . implode(': ', $this->pdo->errorInfo()), 1);
        }
        return $stmt;
    }

    /**
     * Within the search we expand strings like ${dso.id} into JSON queries.
     * Note that the Search will have already had these strings expanded into
     * column names if there are virtual columns configured for them. That
     * happens in the Factory before it gets here.
     */
    protected function sql_select(array $args): string
    {
        //extract query parts from Search and expand paths
        $where = $this->expandPaths($args['search']->where());
        $order = $this->expandPaths($args['search']->order());
        $limit = $args['search']->limit();
        $offset = $args['search']->offset();
        //select from
        $out = ["SELECT * FROM `{$args['table']}`"];
        //where statement
        if ($where !== null) {
            $out[] = "WHERE " . $where;
        }
        //order statement
        if ($order !== null) {
            $out[] = "ORDER BY " . $order;
        }
        //limit
        if ($limit !== null) {
            $out[] = "LIMIT " . $limit;
        }
        //offset
        if ($offset !== null) {
            $out[] = "OFFSET " . $offset;
        }
        //return
        return implode(PHP_EOL, $out) . ';';
    }

    protected function sql_count(array $args): string
    {
        //extract query parts from Search and expand paths
        $where = $this->expandPaths($args['search']->where());
        //select from
        $out = ["SELECT count(dso_id) FROM `{$args['table']}`"];
        //where statement
        if ($where !== null) {
            $out[] = "WHERE " . $where;
        }
        //return
        return implode(PHP_EOL, $out) . ';';
    }

    protected function sql_delete(array $args): string
    {
        return 'DELETE FROM `' . $args['table'] . '` WHERE `dso_id` = :dso_id;';
    }

}

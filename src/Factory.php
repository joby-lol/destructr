<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */

namespace Destructr;

use Destructr\Drivers\AbstractDriver;

/**
 * The Factory is responsible for keeping track of which columns may or may not
 * be configured as virtual columns (although in the future for NoSQL databases
 * this may not be relevant).
 *
 * The overall responsibilities of the Factory are:
 *  * Tracking which table is to be used
 *  * Holding the driver to be used
 *  * Creating DSOs and passing itself to them
 *  * Calling its own and the DSO's hook_create() and hook_update() methods
 *  * Passing DSOs that need CRUD-ing to the appropriate Driver methods
 *  * Creating Search objects
 *  * Executing Searches (which largely consists of passing them to the Driver)
 *  * Inspecting unstructured data straight from the database and figuring out what class to make it (defaults to just DSO)
 */
class Factory
{
    const ID_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';
    const ID_LENGTH = 8;

    /**
     * @var Drivers\AbstractDriver
     */
    protected $driver;
    /**
     * @var string
     */
    protected $table;

    /**
     * Virtual columns that should be created for sorting/indexing in the SQL server
     */
    protected $schema = [
        'dso.id' => [
            'name' => 'dso_id', //column name to be used
            'type' => 'VARCHAR(16)', //column type
            'index' => 'BTREE', //whether/how to index
            'unique' => true, //whether column should be unique
            'primary' => true, //whether column should be the primary key
        ],
        'dso.type' => [
            'name' => 'dso_type',
            'type' => 'VARCHAR(30)',
            'index' => 'BTREE',
        ],
        'dso.deleted' => [
            'name' => 'dso_deleted',
            'type' => 'BIGINT',
            'index' => 'BTREE',
        ],
    ];

    public function __construct(Drivers\AbstractDriver $driver, string $table)
    {
        $this->driver = $driver;
        $this->table = $table;
    }

    public function checkEnvironment(): bool
    {
        return $this->driver->checkEnvironment(
            $this->table,
            $this->schema
        );
    }

    public function prepareEnvironment(): bool
    {
        return $this->driver->prepareEnvironment(
            $this->table,
            $this->schema
        );
    }

    public function updateEnvironment(): bool
    {
        return $this->driver->updateEnvironment(
            $this->table,
            $this->schema
        );
    }

    public function table(): string
    {
        return $this->table;
    }

    public function driver(): AbstractDriver
    {
        return $this->driver;
    }

    public function tableExists(): bool
    {
        return $this->driver->tableExists($this->table);
    }

    public function createSchemaTable(): bool
    {
        $this->driver->createSchemaTable(AbstractDriver::SCHEMA_TABLE);
        return $this->driver->tableExists(AbstractDriver::SCHEMA_TABLE);
    }

    public function quote(string $str): string
    {
        return $this->driver->pdo()->quote($str);
    }

    protected function hook_create(DSOInterface $dso)
    {
        if (!$dso->get('dso.id')) {
            $dso->set('dso.id', static::generate_id(static::ID_CHARS, static::ID_LENGTH), true);
        }
        if (!$dso->get('dso.created.date')) {
            $dso->set('dso.created.date', time());
        }
        if (!$dso->get('dso.created.user')) {
            $dso->set('dso.created.user', ['ip' => @$_SERVER['REMOTE_ADDR']]);
        }
    }

    protected function hook_update(DSOInterface $dso)
    {
        $dso->set('dso.modified.date', time());
        $dso->set('dso.modified.user', ['ip' => @$_SERVER['REMOTE_ADDR']]);
    }

    /**
     * Override this function to allow a factory to create different
     * sub-classes of DSO based on attributes of the given object's
     * data. For example, you could use a property like dso.class to
     * select a class from an associative array.
     *
     * @param array $data
     * @return string|null
     */
    function class(?array $data): ?string
    {
        return null;
    }

    public function delete(DSOInterface $dso, bool $permanent = false): bool
    {
        if ($permanent) {
            return $this->driver->delete($this->table, $dso);
        }
        $dso['dso.deleted'] = time();
        return $this->update($dso, true);
    }

    public function undelete(DSOInterface $dso): bool
    {
        unset($dso['dso.deleted']);
        return $this->update($dso, true);
    }

    public function create(?array $data = array()): DSOInterface
    {
        if (!($class = $this->class($data))) {
            $class = DSO::class;
        }
        $dso = new $class($data, $this);
        $this->hook_create($dso);
        $dso->hook_create();
        $dso->resetChanges();
        return $dso;
    }

    public function schema(): array
    {
        return $this->driver->getSchema($this->table) ?? $this->schema;
    }

    protected function virtualColumnName($path): ?string
    {
        return @$this->schema()[$path]['name'];
    }

    public function update(DSOInterface $dso, bool $sneaky = false): bool
    {
        if (!$dso->changes() && !$dso->removals()) {
            return true;
        }
        if (!$sneaky) {
            $this->hook_update($dso);
            $dso->hook_update();
        }
        $out = $this->driver->update($this->table, $dso);
        $dso->resetChanges();
        return $out;
    }

    public function search(): Search
    {
        return new Search($this);
    }

    protected function makeObjectFromRow($arr)
    {
        $data = json_decode($arr['json_data'], true);
        return $this->create($data);
    }

    protected function makeObjectsFromRows($arr)
    {
        foreach ($arr as $key => $value) {
            $arr[$key] = $this->makeObjectFromRow($value);
        }
        return $arr;
    }

    public function executeCount(Search $search, array $params = array(), $deleted = false): ?int
    {
        //add deletion clause and expand column names
        $search = $this->preprocessSearch($search, $deleted);
        //run select
        return $this->driver->count(
            $this->table,
            $search,
            $params
        );
    }

    public function executeSearch(Search $search, array $params = array(), $deleted = false): array
    {
        //add deletion clause and expand column names
        $search = $this->preprocessSearch($search, $deleted);
        //run select
        $r = $this->driver->select(
            $this->table,
            $search,
            $params
        );
        return $this->makeObjectsFromRows($r);
    }

    public function read(string $value, string $field = 'dso.id', $deleted = false): ?DSOInterface
    {
        $search = $this->search();
        $search->where('${' . $field . '} = :value');
        if ($results = $search->execute([':value' => $value], $deleted)) {
            return array_shift($results);
        }
        return null;
    }

    public function insert(DSOInterface $dso): bool
    {
        $this->hook_update($dso);
        $dso->hook_update();
        $dso->resetChanges();
        return $this->driver->insert($this->table, $dso);
    }

    protected function preprocessSearch($input, $deleted)
    {
        //clone search so we're not accidentally messing with a reference
        $search = new Search($this);
        $search->where($input->where());
        $search->order($input->order());
        $search->limit($input->limit());
        $search->offset($input->offset());
        /* add deletion awareness to where clause */
        if ($deleted !== null) {
            $where = $search->where();
            if ($deleted === true) {
                $added = '${dso.deleted} is not null';
            } else {
                $added = '${dso.deleted} is null';
            }
            if ($where) {
                $where = '(' . $where . ') AND ' . $added;
            } else {
                $where = $added;
            }
            $search->where($where);
        }
        /* expand virtual column names */
        foreach (['where', 'order'] as $clause) {
            if ($value = $search->$clause()) {
                $value = preg_replace_callback(
                    '/\$\{([^\}\\\]+)\}/',
                    function ($matches) {
                        /* depends on whether a virtual column is expected for this value */
                        if ($vcol = $this->virtualColumnName($matches[1])) {
                            return "`$vcol`";
                        }
                        return $matches[0];
                    },
                    $value
                );
                $search->$clause($value);
            }
        }
        /* return search */
        return $search;
    }

    protected static function generate_id($chars, $length, string $prefix = null): string
    {
        $id = '';
        while (strlen($id) < $length) {
            $id .= substr(
                $chars,
                rand(0, strlen($chars) - 1),
                1
            );
        }
        if ($prefix) $id = $prefix . '_' . $id;
        return $id;
    }

    public function errorInfo()
    {
        return $this->driver->errorInfo();
    }
}

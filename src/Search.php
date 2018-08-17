<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr;

use Destructr\DSOFactoryInterface;
use Destructr\Drivers\DSODriverInterface;

class Search implements \Serializable
{
    use \Digraph\Utilities\ValueFunctionTrait;

    protected $factory;

    public function __construct(DSOFactoryInterface &$factory=null)
    {
        $this->factory = $factory;
    }

    public function execute(array $params = array(), $deleted = false)
    {
        return $this->factory->executeSearch($this, $params, $deleted);
    }

    public function where(string $set = null) : ?string
    {
        return $this->valueFunction('where', $set);
    }

    public function order(string $set = null) : ?string
    {
        return $this->valueFunction('order', $set);
    }

    public function limit(int $set = null) : ?int
    {
        return $this->valueFunction('limit', $set);
    }

    public function offset(int $set = null) : ?int
    {
        return $this->valueFunction('offset', $set);
    }

    public function serialize()
    {
        return json_encode(
            [$this->where(),$this->order(),$this->limit(),$this->offset()]
        );
    }

    public function unserialize($string)
    {
        list($where, $order, $limit, $offset) = json_decode($string, true);
        $this->where($where);
        $this->order($order);
        $this->limit($limit);
        $this->offset($offset);
    }
}

<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Kckj\Mgo\Pool;

use Hyperf\Di\Container;
use Psr\Container\ContainerInterface;

class PoolFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var MongodbPool[]
     */
    protected $pools = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getPool(string $name): MongodbPool
    {
        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }

        if ($this->container instanceof Container) {
            $pool = $this->container->make(MongodbPool::class, ['name' => $name]);
        } else {
            $pool = new MongodbPool($this->container, $name);
        }
        return $this->pools[$name] = $pool;
    }
}

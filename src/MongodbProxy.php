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
namespace Kckj\Mgo;



use Kckj\Mgo\Pool\PoolFactory;
use Mongodb\Client;

/**
 * @mixin Client
 */
class MongodbProxy extends Mongodb
{
    protected $poolName;

    public function __construct(PoolFactory $factory, string $pool)
    {
        parent::__construct($factory);

        $this->poolName = $pool;
    }

    public function __call($name, $arguments)
    {
        return parent::__call($name, $arguments);
    }
}

<?php


namespace Kckj\Mgo;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hyperf\Redis\Exception\InvalidRedisConnectionException;
use Hyperf\Redis\RedisConnection;
use Hyperf\Utils\Context;
use Kckj\Mgo\Exception\InvalidMongodbConnectionException;
use Kckj\Mgo\Pool\PoolFactory;

/**
 * @mixin DocumentManager
 */
class Mongodb
{
    /**
     * @var PoolFactory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $poolName = 'default';

    public function __construct(PoolFactory $factory)
    {
        $this->factory = $factory;
    }

    public function __call($name, $arguments)
    {
        $hasContextConnection = Context::has($this->getContextKey());
        $connection = $this->getConnection($hasContextConnection);

        try {
            $connection = $connection->getConnection();
            $result = $connection->{$name}(...$arguments);
        } finally {
            // Release connection.
            if (! $hasContextConnection) {
                $connection->release();
            }
        }

        return $result;
    }

    /**
     * Get a connection from coroutine context, or from redis connection pool.
     * @param mixed $hasContextConnection
     * @return MongodbConnection
     */
    private function getConnection($hasContextConnection): MongodbConnection
    {
        $connection = null;
        if ($hasContextConnection) {
            $connection = Context::get($this->getContextKey());
        }
        if (! $connection instanceof MongodbConnection) {
            $pool = $this->factory->getPool($this->poolName);
            $connection = $pool->get();
        }
        if (! $connection instanceof MongodbConnection) {
            throw new InvalidMongodbConnectionException('The connection is not a valid MongodbConnection.');
        }
        return $connection;
    }

    /**
     * The key to identify the connection object in coroutine context.
     */
    private function getContextKey(): string
    {
        return sprintf('mongodb.connection.%s', $this->poolName);
    }
}
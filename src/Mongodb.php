<?php


namespace Kckj\Mgo;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Types\Type;
use Hyperf\Redis\Exception\InvalidRedisConnectionException;
use Hyperf\Redis\RedisConnection;
use Hyperf\Utils\Context;
use Kckj\Mgo\Exception\InvalidMongodbConnectionException;
use Kckj\Mgo\Pool\PoolFactory;
use Kckj\Mgo\Type\Arr;
use Kckj\Mgo\Type\NumericArray;
use Kckj\Mgo\Type\StringArray;

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

        if (!is_dir(BASE_PATH . '/runtime/Proxies')) {
            mkdir(BASE_PATH . '/runtime/Proxies', 0777, true);
        }
        if (!is_dir(BASE_PATH . '/runtime/Hydrators')) {
            mkdir(BASE_PATH . '/runtime/Hydrators', 0777, true);
        }
        if (!is_dir(BASE_PATH . '/app/Mongo')) {
            mkdir(BASE_PATH . '/app/Mongo', 0777, true);
        }
        if (!Type::hasType('string_array')) {
            Type::addType('string_array', StringArray::class);
            Type::overrideType('string_array', StringArray::class);
            Type::registerType('string_array', StringArray::class);
        }

        if (!Type::hasType('array')) {
            Type::addType('array', Arr::class);
            Type::overrideType('array', Arr::class);
            Type::registerType('array', Arr::class);
        }

        if (!Type::hasType('numeric_array')) {
            Type::addType('numeric_array', NumericArray::class);
            Type::overrideType('numeric_array', NumericArray::class);
            Type::registerType('numeric_array', NumericArray::class);
        }
    }

    public function __call($name, $arguments)
    {
        $hasContextConnection = Context::has($this->getContextKey());
        $connection = $this->getConnection($hasContextConnection);
var_dump("1111111111111");
        $config = new Configuration();
        $config->setProxyDir(BASE_PATH . '/runtime/Proxies'); // 设置代理类生成目录
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(BASE_PATH . '/runtime/Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setDefaultDB('xfbchain');
        $config->setMetadataDriverImpl(AnnotationDriver::create(BASE_PATH . '/app/Mongo'));

        try {
            $connection = $connection->getConnection();
            $documentManager = DocumentManager::create($connection, $config);
            $result = $documentManager->{$name}(...$arguments);
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
<?php


namespace Kckj\Mgo;


use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Types\Type;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Pool\Connection;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Pool;
use Kckj\Mgo\Type\Arr;
use Kckj\Mgo\Type\NumericArray;
use Kckj\Mgo\Type\StringArray;
use MongoDB\Client;
use Psr\Container\ContainerInterface;
use Throwable;

class MongodbConnection extends Connection implements ConnectionInterface
{
    /**
     * @var array
     */
    protected $config = [
        'uri' => 'mongodb://127.0.0.1:27017',
        'maxPoolSize' => 50,
        'minPoolSize' => 5,
        'maxIdleTimeMS' => 5 * 60 * 1000,
        'waitQueueMultiple' => 10,
        'waitQueueTimeoutMS' => 1000,
        'connect_timeout' => '3s',
        'read_write_timeout' => '60s'
    ];

    /**
     * @var \Mongodb\Client
     */
    protected $connection;

    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = array_replace_recursive($this->config, $config);

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

        $this->reconnect();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws Throwable+
     */
    public function __call($name, $arguments)
    {
        try {
            $config = new Configuration();
            $config->setProxyDir(BASE_PATH . '/runtime/Proxies'); // 设置代理类生成目录
            $config->setProxyNamespace('Proxies');
            $config->setHydratorDir(BASE_PATH . '/runtime/Hydrators');
            $config->setHydratorNamespace('Hydrators');
            $config->setDefaultDB('xfbchain');
            $config->setMetadataDriverImpl(AnnotationDriver::create(BASE_PATH . '/app/Mongo'));

            $documentManager = DocumentManager::create($this->connection, $config);

            $result = $documentManager->{$name}(...$arguments);
        } catch (Throwable $exception) {
            $result = $this->retry($name, $arguments, $exception);
        }

        return $result;
    }

    public function reconnect(): bool
    {
        $uri = $this->config['uri'];
        $maxPoolSize = $this->config['maxPoolSize'];
        $minPoolSize = $this->config['minPoolSize'];
        $maxIdleTimeMS = $this->config['maxIdleTimeMS'];
        $waitQueueMultiple = $this->config['waitQueueMultiple'];
        $waitQueueTimeoutMS = $this->config['waitQueueTimeoutMS'];
        $connect_timeout = $this->config['connect_timeout'];
        $read_write_timeout = $this->config['read_write_timeout'];

        try {
            $mongodb = new Client($uri, [
                'maxPoolSize' => $maxPoolSize,
                'minPoolSize' => $minPoolSize,
                'maxIdleTimeMS' => $maxIdleTimeMS,
                'waitQueueMultiple' => $maxIdleTimeMS,
                'waitQueueTimeoutMS' => $waitQueueTimeoutMS,
            ], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);

            $this->connection = $mongodb;
            $this->lastUseTime = microtime(true);
        } catch (\Exception $e) {
            return false;
        }

//        try {
//            $cursor = $mongodb->getManager()->executeCommand("admin", new Command(['ping' => 1]));
//        } catch (Exception $e) {
//            return false;
//        }

        return true;
    }

    protected function retry($name, $arguments, Throwable $exception)
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->warning(sprintf('Redis::__call failed, because ' . $exception->getMessage()));

        try {
            $this->reconnect();
            $result = $this->connection->{$name}(...$arguments);
        } catch (Throwable $exception) {
            $this->lastUseTime = 0.0;
            throw $exception;
        }

        return $result;
    }

    public function check(): bool
    {
        $maxIdleTime = $this->pool->getOption()->getMaxIdleTime();
        $now = microtime(true);
        if ($now > $maxIdleTime + $this->lastUseTime) {
            return false;
        }

        $this->lastUseTime = $now;
        return true;
    }

    public function close(): bool
    {
        unset($this->connection);
        return true;
    }

    public function release(): void
    {
        parent::release();
    }

    /**
     * @return $this
     * @throws ConnectionException
     */
    public function getActiveConnection()
    {
        if ($this->check()) {
            return $this;
        }

        if (! $this->reconnect()) {
            throw new ConnectionException('Connection reconnect failed.');
        }

        return $this;
    }
}
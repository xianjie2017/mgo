<?php


namespace Kckj\Mgo;


use Doctrine\ODM\MongoDB\DocumentManager;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Connection;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Pool;
use MongoDB\Client;
use Psr\Container\ContainerInterface;

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
        'read_write_timeout' => '60s',
    ];

    /**
     * @var \Mongodb\Client
     */
    protected $connection;

    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = array_replace_recursive($this->config, $config);

        $this->reconnect();
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
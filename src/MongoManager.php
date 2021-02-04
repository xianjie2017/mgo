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

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Hyperf\Contract\ConfigInterface;
use Kckj\Mgo\Type\Arr;
use Kckj\Mgo\Type\NumericArray;
use Kckj\Mgo\Type\StringArray;
use MongoDB\Client;
use Doctrine\ODM\MongoDB\Types\Type;

class MongoManager
{
    /** @var Client */
    private $client;

    /** @var ConfigInterface */
    private $config;

    public function __construct(ConfigInterface $config)
    {
        if (!$this->client instanceof Client) {
            $this->client = new Client($config->get('mongodb.uri', 'mongodb://127.0.0.1:27017'), [
                'maxPoolSize' => $config->get('mongodb.maxPoolSize', 50),
                'minPoolSize' => $config->get('mongodb.minPoolSize', 5),
                'maxIdleTimeMS' => $config->get('mongodb.maxIdleTimeMS', 5 * 60 * 1000),
                'waitQueueMultiple' => $config->get('mongodb.maxIdleTimeMS', 10),
                'waitQueueTimeoutMS' => $config->get('mongodb.waitQueueTimeoutMS', 1000),
            ], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
        }

        if (!is_dir(BASE_PATH . '/runtime/Proxies')) {
            mkdir(BASE_PATH . '/runtime/Proxies', 0777, true);
        }
        if (!is_dir(BASE_PATH . '/runtime/Hydrators')) {
            mkdir(BASE_PATH . '/runtime/Hydrators', 0777, true);
        }
        if (!is_dir(BASE_PATH . '/app/Mongo')) {
            mkdir(BASE_PATH . '/app/Mongo', 0777, true);
        }

        Type::addType('string_array', StringArray::class);
        Type::overrideType('string_array', StringArray::class);
        Type::registerType('string_array', StringArray::class);

        Type::addType('array', Arr::class);
        Type::overrideType('array', Arr::class);
        Type::registerType('array', Arr::class);

        Type::addType('numeric_array', NumericArray::class);
        Type::overrideType('numeric_array', NumericArray::class);
        Type::registerType('numeric_array', NumericArray::class);
    }

    /**
     * 重链
     * @param ConfigInterface $config
     */
    public function ReConnect(ConfigInterface $config)
    {
        $this->client = new Client($config->get('mongodb.uri', 'mongodb://127.0.0.1:27017'), [
            'maxPoolSize' => $config->get('mongodb.maxPoolSize', 50),
            'minPoolSize' => $config->get('mongodb.minPoolSize', 5),
            'maxIdleTimeMS' => $config->get('mongodb.maxIdleTimeMS', 5 * 60 * 1000),
            'waitQueueMultiple' => $config->get('mongodb.maxIdleTimeMS', 10),
            'waitQueueTimeoutMS' => $config->get('mongodb.waitQueueTimeoutMS', 1000),
        ], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
    }

    /**
     * 更新配置
     * @param ConfigInterface $config
     */
    public function config(ConfigInterface $config)
    {
        $isUpdate = false;
        if ($config->get('mongodb.uri', 'mongodb://127.0.0.1:27017') != $this->config->get('mongodb.uri', 'mongodb://127.0.0.1:27017')) {
            $isUpdate = true;
        }
        if ($config->get('mongodb.maxPoolSize', 50) != $this->config->get('mongodb.maxPoolSize', 50)) {
            $isUpdate = true;
        }
        if ($config->get('mongodb.minPoolSize', 5) != $this->config->get('mongodb.minPoolSize', 5)) {
            $isUpdate = true;
        }
        if ($config->get('mongodb.maxIdleTimeMS', 5 * 60 * 1000) != $this->config->get('mongodb.maxIdleTimeMS', 5 * 60 * 1000)) {
            $isUpdate = true;
        }
        if ($config->get('mongodb.maxIdleTimeMS', 10) != $this->config->get('mongodb.maxIdleTimeMS', 10)) {
            $isUpdate = true;
        }
        if ($config->get('mongodb.waitQueueTimeoutMS', 1000) != $this->config->get('mongodb.waitQueueTimeoutMS', 1000)) {
            $isUpdate = true;
        }
        if ($isUpdate) {
            $this->config = $config;
            $this->ReConnect($config);
        }
    }

    public function DocumentManager(): DocumentManager
    {
        $config = new Configuration();
        $config->setProxyDir(BASE_PATH . '/runtime/Proxies'); // 设置代理类生成目录
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(BASE_PATH . '/runtime/Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setDefaultDB('doctrine_odm');
        $config->setMetadataDriverImpl(AnnotationDriver::create(BASE_PATH . '/app/Mongo'));

        return DocumentManager::create($this->client, $config);
    }
}

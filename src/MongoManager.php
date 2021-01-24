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
use MongoDB\Client;

class MongoManager
{
    /** @var Client */
    private $client;

    public function __construct(ConfigInterface $config)
    {
        if (! $this->client instanceof Client) {
            $this->client = new Client($config->get('mongodb.uri', 'mongodb://127.0.0.1:27017'), [
                'maxPoolSize' => $config->get('mongodb.maxPoolSize', 50),
                'minPoolSize' => $config->get('mongodb.minPoolSize', 5),
                'maxIdleTimeMS' => $config->get('mongodb.maxIdleTimeMS', 5 * 60 * 1000),
                'waitQueueMultiple' => $config->get('mongodb.maxIdleTimeMS', 10),
                'waitQueueTimeoutMS' => $config->get('mongodb.waitQueueTimeoutMS', 1000),
            ], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
        }
    }

    public function DocumentManager(): DocumentManager
    {
        if (! is_dir(BASE_PATH . '/runtime/Proxies')) {
            mkdir(BASE_PATH . '/runtime/Proxies', 0777, true);
        }
        if (! is_dir(BASE_PATH . '/runtime/Hydrators')) {
            mkdir(BASE_PATH . '/runtime/Hydrators', 0777, true);
        }
        if (! is_dir(BASE_PATH . '/runtime/Documents')) {
            mkdir(BASE_PATH . '/app/Mongo', 0777, true);
        }
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

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
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
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
                'maxPoolSize' => 50,
                'minPoolSize' => 5,
                'maxIdleTimeMS' => 5 * 60 * 1000,
                'waitQueueMultiple' => 10,
                'waitQueueTimeoutMS' => 1000,
            ], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
        }
    }

    public function DocumentManager(): DocumentManager
    {
        if (! is_dir(BASE_PATH . '/runtime/Proxies')) {
            mkdir(BASE_PATH . '/runtime/Proxies', 777, true);
        }
        if (! is_dir(BASE_PATH . '/runtime/Hydrators')) {
            mkdir(BASE_PATH . '/runtime/Hydrators', 777, true);
        }
        if (! is_dir(BASE_PATH . '/runtime/Documents')) {
            mkdir(BASE_PATH . '/runtime/Documents', 777, true);
        }
        $config = new Configuration();
        $config->setProxyDir(BASE_PATH . '/runtime/Proxies'); // 设置代理类生成目录
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(BASE_PATH . '/runtime/Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setDefaultDB('doctrine_odm');
        $config->setMetadataDriverImpl(AnnotationDriver::create(BASE_PATH . '/runtime/Documents'));
        return DocumentManager::create($this->client, $config);
    }
}

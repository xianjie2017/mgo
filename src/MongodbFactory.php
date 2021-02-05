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

use Hyperf\Contract\ConfigInterface;
use Kckj\Mgo\Exception\InvalidMongodbProxyException;

class MongodbFactory
{
    /**
     * @var MongodbProxy[]
     */
    protected $proxies;

    public function __construct(ConfigInterface $config)
    {
        $redisConfig = $config->get('redis');

        foreach ($redisConfig as $poolName => $item) {
            $this->proxies[$poolName] = make(MongodbProxy::class, ['pool' => $poolName]);
        }
    }

    /**
     * @return MongodbProxy
     */
    public function get(string $poolName)
    {
        $proxy = $this->proxies[$poolName] ?? null;
        if (! $proxy instanceof MongodbProxy) {
            throw new InvalidMongodbProxyException('Invalid Mongodb proxy.');
        }

        return $proxy;
    }
}
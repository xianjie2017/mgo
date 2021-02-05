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

    /** @var array */
    protected $mongodbConfig;

    public function __construct(ConfigInterface $config)
    {
        $this->mongodbConfig = $config->get('mongodb');

        $this->setPool();
    }

    /**
     * @param string $poolName
     * @return MongodbProxy
     */
    public function get(string $poolName = 'default')
    {
        $proxy = $this->proxies[$poolName] ?? null;
        if (!$proxy instanceof MongodbProxy) {
            throw new InvalidMongodbProxyException('Invalid Mongodb proxy.');
        }

        return $proxy;
    }

    protected function setPool()
    {
        foreach ($this->mongodbConfig as $poolName => $item) {
            $this->proxies[$poolName] = make(MongodbProxy::class, ['pool' => $poolName]);
        }
    }

    public function reconnect(ConfigInterface $config)
    {
        $mongodbConfig = $config->get('mongodb');
        if (array_diff($mongodbConfig, $this->mongodbConfig)) {
            $this->mongodbConfig = $mongodbConfig;
            $this->setPool();
        }
    }
}

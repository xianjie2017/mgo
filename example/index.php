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

// 配置
$config = null;
$dm = (new \Kckj\Mgo\MongoManager($config))->DocumentManager();

// 查询
$data = $dm->createQueryBuilder(new \Mongo\User())
    ->field('name')->equals('test')
    ->field('age')->lt(15)
    ->sort('id', 'desc')
    ->getQueryArray();

var_dump($data);

// 添加数据
$user = new \Mongo\User();
$user->setName('test');
$user->setPassword('password');
$dm->persist($user);
$dm->flush();

// 使用完成，关闭dm
$dm->close();

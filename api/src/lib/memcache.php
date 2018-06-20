<?php

$app['memcache'] = [];

$app['memcache']['_init'] = function () use (&$app) {
    // @formatter:off
    $app['memcache']['_connection'] = memcache_connect(
        $app['config']['memcache']['host'],
        $app['config']['memcache']['port']
    );
    // @formatter:on
};

$app['memcache']['falseOnCacheDown'] = function () {
    if (empty($app['memcache']['_connection'])) {
        return null;
    }
};

$app['memcache']['set'] = function () use (&$app) {
    $app['memcache']['falseOnCacheDown']();
};

$app['memcache']['get'] = function () use (&$app) {
    $app['memcache']['falseOnCacheDown']();

};

$app['memcache']['del'] = function () use (&$app) {
    $app['memcache']['falseOnCacheDown']();

};

$app['memcache']['_init']();
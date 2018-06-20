<?php /** @noinspection PhpMethodParametersCountMismatchInspection */

$_cache = [];

function cache_init() {
    global $_cache;
    // @formatter:off
    $_cache['_connection'] = memcache_connect(
        getConfig()['memcache']['host'],
        getConfig()['memcache']['port']
    );

    // @formatter:on
}

function cache_set($key, $value, $ttl = null) {
    global $_cache;

    return memcache_set($_cache['_connection'], $key, $value, 0, $ttl);
}

/**
 * @param $key
 *
 * @return null|string
 */
function cache_get($key) {
    global $_cache;

    return memcache_get($_cache['_connection'], $key);
}

function cache_del($key) {
    global $_cache;

    return memcache_delete($_cache['_connection'], $key);
}

cache_init();
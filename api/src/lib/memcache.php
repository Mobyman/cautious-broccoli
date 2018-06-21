<?php /** @noinspection PhpMethodParametersCountMismatchInspection */

function cache_init() {
    global $_db;
    // @formatter:off
    $_db['cache']['_connection'] = memcache_connect(
        getConfig()['memcache']['host'],
        getConfig()['memcache']['port']
    );
    
    // @formatter:on
}
cache_init();

function cache_set($key, $value, $ttl = null) {
    global $_db;

    return memcache_set($_db['cache']['_connection'], $key, $value, 0, $ttl);
}

/**
 * @param $key
 *
 * @return null|string
 */
function cache_get($key) {
    global $_db;

    return memcache_get($_db['cache']['_connection'], $key);
}

function cache_del($key) {
    global $_db;

    return memcache_delete($_db['cache']['_connection'], $key);
}

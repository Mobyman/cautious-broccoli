<?php /** @noinspection PhpMethodParametersCountMismatchInspection */

function cache_init()
{
    global $_db;
    // @formatter:off
    $_db['cache']['_connection'] = memcache_connect(
        getConfig()['memcache']['host'],
        getConfig()['memcache']['port']
    );
    // @formatter:on
}

function getConnection()
{
    global $_db;

    return $_db['cache']['_connection'];
}

cache_init();

/**
 * @param      $key
 * @param      $value
 * @param null $ttl
 */
function cache_set($key, $value, $ttl = null)
{
    return memcache_set(getConnection(), $key, $value, 0, $ttl);
}

/**
 * @param $key
 *
 * @return null|string
 */
function cache_get($key)
{
    return memcache_get(getConnection(), $key);
}

/**
 * @param $key
 */
function cache_del($key)
{
    return memcache_delete(getConnection(), $key);
}

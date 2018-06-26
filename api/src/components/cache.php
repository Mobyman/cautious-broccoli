<?php

/**
 * @param $type
 * @param $id
 *
 * @return mixed
 */
function cache_get_model($type, $id)
{
    return cache_get($type . ':' . $id);
}

/**
 * @param $type
 * @param $id
 * @param $data
 * @param null $ttl
 * @return null|bool
 */
function cache_set_model($type, $id, $data, $ttl = null)
{
    if ($data) {
        return cache_set($type . ':' . $id, $data, $ttl);
    }

    return null;
}

/**
 * @param $type
 * @param $id
 */
function cache_del_model($type, $id)
{
    return cache_del($type . ':' . $id);
}

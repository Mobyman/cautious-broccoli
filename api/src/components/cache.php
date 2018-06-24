<?php

/**
 * @param $type
 * @param $id
 *
 * @return mixed
 */
function cache_get_model($type, $id)
{
    cache_get($type . ':' . $id);
}

function cache_set_model($type, $id, $data, $ttl = null)
{
    if ($data) {
        cache_set($type . ':' . $id, $data, $ttl);
    }
}

function cache_patch_model($type, $id, array $data)
{
    $cached = cache_get_model($type, $id);
    if ($cached) {
        foreach ($data as $attr => $value) {
            $cached[ $attr ] = $value;
        }

        cache_set_model('order', $id, $cached);
    }
}

function cache_del_model($type, $id)
{
    cache_del($type . ':' . $id);
}

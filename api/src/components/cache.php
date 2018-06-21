<?php

function cache_get_model($type, $id)
{
    cache_get($type . ':' . $id);
}

function cache_set_model($type, $id, $data)
{
    if ($data) {
        cache_set($type . ':' . $id, $data);
    }
}

function cache_del_model($type, $id)
{
    cache_del($type . ':' . $id);
}

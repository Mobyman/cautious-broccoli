<?php

/**
 * @param $userId
 * @param $token
 */
function session_set($userId, $token)
{
    if ($oldToken = cache_get($userId)) {
        cache_del($oldToken);
    }

    cache_set($token, [
        'ip'      => $_SERVER['REMOTE_ADDR'],
        'user_id' => $userId,
    ], 86400);

    cache_set($userId, $token, 86400);
}

/**
 * @param $token
 *
 *@return null|array
*/
function session_get($token)
{
    return cache_get($token) ?: null;
}

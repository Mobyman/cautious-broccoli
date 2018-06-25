<?php

const SESSION_TTL = 86400;

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
    ], SESSION_TTL);
    cache_set($userId, $token, SESSION_TTL);
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

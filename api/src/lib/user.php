<?php


function user_register($params): array
{
    $req = validator_validate([
        'login'    => [
            'type'       => 'string',
            'required'   => true,
            'max_length' => 30,
        ],
        'password' => [
            'type'       => 'string',
            'required'   => true,
            'max_length' => 30,
        ],
        'type'     => [
            'required' => true,
            'enum'     => [
                1,
                2,
            ],
        ],
    ], $params);

    if (!$req) {
        response_error('Invalid request params');
    }

    $isAlreadyRegister = m_User_exists_login($params['login']);

    if ($isAlreadyRegister) {
        response_error('User already exists', 403);
    }

    $status = m_User_insert($params['login'], $params['password'], $params['type']);

    return ['status' => $status];
}


function user_auth($params): array
{
    $req = validator_validate([
        'login'    => [
            'type'       => 'string',
            'required'   => true,
            'max_length' => 30,
        ],
        'password' => [
            'type'       => 'string',
            'required'   => true,
            'max_length' => 30,
        ],
    ], $params);

    if (!$req) {
        response_error('Invalid request params');
    }

    $userId = m_User_exists_login_password($params['login'], $params['password']);

    if (!$userId) {
        response_error('User not found', 404);
    }

    try {
        $token = bin2hex(random_bytes(16));
        if ($oldToken = cache_get($userId)) {
            cache_del($oldToken);
        }

        cache_set($token, [
            'ip'      => $_SERVER['REMOTE_ADDR'],
            'user_id' => $userId,
        ], 86400);

        cache_set($userId, $token, 86400);

        return ['token' => $token];

    } catch (Exception $e) {
        return response_error($e->getMessage(), 404);
    }

}


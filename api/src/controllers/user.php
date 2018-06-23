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

    $status = m_User_create($params['login'], $params['password'], $params['type']);

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

    $userId = m_User_exists_login_password($params['login'], $params['password']);

    if (!$userId) {
        response_error('User not found', 404);
    }

    try {
        $token = bin2hex(random_bytes(16));
        session_set($userId, $token);

        return ['token' => $token];

    } catch (Exception $e) {
        return response_error($e->getMessage(), 404);
    }

}


function user_get_id() {
    global $_user;

    if(empty($_user['id'])) {
        response_error('Unauthorized', 403);
    }

    return $_user['id'];
}

function user_profile()
{
    global $_user;

    if (empty($_user['profile'])) {
        $_user['profile'] = m_User_get_profile(user_get_id());
    }

    return $_user['profile'];
}

function user_role_is($role)
{
    $roles = [
        'hirer'  => 1,
        'worker' => 2,
    ];
    if (empty($roles[ $role ])) {
        response_error('Invalid role param');
    }

    $profile = user_profile();

    return (int) $profile['type'] === $roles[ $role ];
}


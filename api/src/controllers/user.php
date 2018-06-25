<?php

/**
 * @param $params
 * @return array
 */
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
        return response_error('Invalid request params');
    }

    $isAlreadyRegister = m_User_exists_login($params['login']);

    if ($isAlreadyRegister) {
        return response_error('Пользователь с таким именем уже есть.', 403);
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

    $userId = m_User_exists_login_password($req['login'], $req['password']);

    if (!$userId) {
        return response_error('User not found', 404);
    }

    try {
        $token = bin2hex(random_bytes(16));
        session_set($userId, $token);

        return ['token' => $token];

    } catch (Exception $e) {
        return response_error($e->getMessage(), 404);
    }

}

function user_profile($params) {
    $req = request_handle([
        'token' => [
            'type'     => 'string',
            'required' => true,
        ],
    ], $params);

    $profile = user_init_profile();
    return ['profile' => $profile];
}


function user_role_is($role)
{
    $roles = [
        'hirer'  => 1,
        'worker' => 2,
    ];

    if (empty($roles[ $role ])) {
        return response_error('Invalid role param');
    }

    $profile = user_init_profile();

    return (int) $profile['type'] === $roles[ $role ];
}


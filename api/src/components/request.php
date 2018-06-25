<?php

/**
 * @param $token
 * @return bool
 */
function request_init_user($token)
{
    global $_user;

    if (!is_string($token)) {
        return false;
    }

    $session = session_get($token);

    // Подразумевается, что если работаем через балансер, нужно смотреть в другие заголовки (с настоящим IP адресом клиента).
    // Стоит также учитывать, что храня токены только в одном месте (мемкеше), при выходе его из строя,
    // мы не сможем обрабатывать запросы.
    //
    // Однако, счел лишним создавать еще один инстанс базы для токенов - в некоторых случаях лучше перестать работать совсем и быстро решить проблему, чем
    // перенести всю нагрузку на базу, которая гораздо уязвимее и требовательнее к ресурсам, чем кеш.
    if (!empty($session) && $_SERVER['REMOTE_ADDR'] === ($session['ip'] ?? null)) {
        $_user['id'] = $session['user_id'];

        return true;
    }

    return false;
}

/**
 * @return mixed
 */
function request_post()
{
    global $_req;

    if(empty($_req['_data']['_post'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? null;
        if ($contentType !== 'application/json') {
            return response_error('Invalid content type');
        }

        $_req['_data']['_method'] = $_SERVER['REQUEST_METHOD'];
        $_req['_data']['_post']   = json_decode(file_get_contents('php://input'), true) ?? [];
        $_req['_data']['_get']    = $_GET;
    }

    return $_req['_data']['_post'];
}

/**
 * @param $rules
 * @param $req
 * @return array|bool
 */
function request_handle($rules, $req)
{
    $req = validator_validate($rules, $req);

    if (!empty($req['token'])) {
        request_init_user($req['token']);
    }

    return $req;
}

/**
 * @return array|null
 */
function user_init_profile()
{
    if (empty($_user['profile'])) {
        $_user['profile'] = m_User_get_profile(request_user_get_id());
    }

    return $_user['profile'];
}

/**
 * @return mixed
 */
function request_user_get_id()
{
    global $_user;

    if (empty($_user['id'])) {
        return response_error('Unauthorized', 403);
    }

    return $_user['id'];
}


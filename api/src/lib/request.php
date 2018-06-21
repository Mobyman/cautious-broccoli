<?php

function request_init_user($token) {
    global $_user;

    if (!is_string($token)) {
        return false;
    }

    $data = session_get($token);
    if (!empty($data) && $_SERVER['REMOTE_ADDR'] === ($data['ip'] ?? null)) {
        $_user['id'] = $data['user_id'];

        return true;
    }

    return false;
}

function request_post() {

    $contentType = $_SERVER['CONTENT_TYPE'] ?? null;
    if ($contentType !== 'application/json') {
        response_error('Invalid content type');
    }

    $_req['_data']['_method'] = $_SERVER['REQUEST_METHOD'];
    $_req['_data']['_post']   = json_decode(file_get_contents('php://input'), true) ?? [];
    $_req['_data']['_get']    = $_GET;

    return $_req['_data']['_post'];
}

function request_handle($rules, $req) {
    $req = validator_validate($rules, $req);

    if(!empty($req['token'])) {
        request_init_user($req['token']);
    }

    return $req;
}
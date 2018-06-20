<?php

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

<?php

$request = [];

$request['_headers'] = [];
$request['_init']    = function () use ($app) {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? null;
    if ($contentType !== 'application/json') {
        $app['response']['error']('Invalid content type');
    }

    $request['_method'] = $_SERVER['REQUEST_METHOD'];
    $request['_post']   = json_decode(file_get_contents('php://input')) ?? [];
    $request['_get']    = $_GET;

};

$request['post'] = function () use ($app) {

    if (empty($app['request']['_post'])) {
        $app['request']['_init']();
    }

    return $app['request']['_post'];
};

$app['request'] = $request;
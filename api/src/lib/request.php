<?php

$app['request'] = [];

$app['request']['_init']    = function () use (&$app) {
    $app['request']['q'] = 123;

    $contentType = $_SERVER['CONTENT_TYPE'] ?? null;
    if ($contentType !== 'application/json') {
        $app['response']['error']('Invalid content type');
    }


    $app['request']['_data']['_method'] = $_SERVER['REQUEST_METHOD'];
    $app['request']['_data']['_post']   = json_decode(file_get_contents('php://input'), true) ?? [];
    $app['request']['_data']['_get']    = $_GET;
};

$app['request']['post'] = function () use (&$app) {

    if (empty($app['request']['_data'])) {
        $app['request']['_init']();
    }

    return $app['request']['_data']['_post'];
};

<?php

$router = [];

$router['handle'] = function ($body) use (&$app) {

    if (empty($body['method'])) {
        $app['response']['error']('Missing method param');
    }

    $router['_routes'] = [
        'user.auth'    => $app['user']['auth'],
        'order.create' => $app['order']['create'],
        'order.assign' => $app['order']['assign'],
        'order.pay'    => $app['order']['pay'],
        'order.list'   => $app['order']['list'],
    ];

    $method = $body['method'];
    unset($body['method']);
    $params = $body ?? [];

    if (!empty($router['_routes'][ $method ])) {
        return $app['response']['respond']($router['_routes'][ $method ]($params));
    }

    $app['response']['error']('Route not found');
};

$app['router'] = $router;
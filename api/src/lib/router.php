<?php

$router = [];

$router['handle'] = function($method, $params) use($app) {
    $router['_routes'] = [
        'user.login' => $app['user']['login']
    ];

    if(!empty($router['_routes'][$method])) {
        return $router['_routes'][$method]($params);
    }

    $app['response']['error']('Route not found');
};

$app['router'] = $router;
<?php

$router = [];

function router_handle($body)
{
    if (empty($body['method'])) {
        response_error('Missing method param');
    }

    $router['_routes'] = [
        'user.register' => 'user_register',
        'user.auth'     => 'user_auth',
        'order.create'  => 'order_create',
        'order.assign'  => 'order_assign',
        'order.pay'     => 'order_pay',
        'order.list'    => 'order_list',
    ];

    $method = $body['method'];
    unset($body['method']);
    $params = $body ?? [];

    if (!empty($router['_routes'][ $method ])) {
        return response_respond($router['_routes'][ $method ]($params));
    }

    return response_error('Route not found');
}

$app['router'] = $router;
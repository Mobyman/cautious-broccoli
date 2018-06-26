<?php

/**
 * @param $body
 * @return null
 */
function router_handle($body)
{
    global $_router;

    if (empty($body['method'])) {
        return response_error('Missing method param' . var_export($body, true));
    }

    if(empty($_router)) {
        $_router['_routes'] = [
            'user.register' => 'user_register',
            'user.auth'     => 'user_auth',
            'user.profile'  => 'user_profile',
            'order.create'  => 'order_create',
            'order.assign'  => 'order_assign',
            'order.list'    => 'order_list',
            'order.get'     => 'order_get',

            // это тестовый метод, нужно закрывать его в конфиге боевого nginx (я оставлю открытым)
            'test.order.handle'  => 'order_handle',
        ];
    }

    $method = $body['method'];
    unset($body['method']);
    $params = $body ?? [];

    if (!empty($_router['_routes'][ $method ])) {
        return response_respond($_router['_routes'][ $method ]($params));
    }

    return response_error('Route not found');
}
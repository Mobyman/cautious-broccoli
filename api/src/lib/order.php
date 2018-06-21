<?php

const ROLE_WORKER = 'worker';
const ROLE_HIRER  = 'hirer';

function order_create($params): array
{
    $req = request_handle([
        'token'       => [
            'type'     => 'string',
            'required' => true,
        ],
        'title'       => [
            'type'       => 'string',
            'required'   => true,
            'max_length' => 255,
        ],
        'description' => [
            'type'       => 'string',
            'required'   => true,
            'max_length' => 4096,
        ],
    ], $params);

    $isHirer = user_role_is(ROLE_HIRER);
    if (!$isHirer) {
        response_error('You must be hirer for create orders!', 403);
    }

    $orderId = m_Order_create(user_get_id(), $req['title'], $req['description']);

    return ['order_id' => $orderId];
}

function order_assign($params): array
{
    $req = request_handle([
        'token'       => [
            'type'     => 'string',
            'required' => true,
        ],
        'order_id'       => [
            'type'       => 'number',
            'required'   => true,
            'max_length' => 255,
        ],
    ], $params);

    $isWorker = user_role_is(ROLE_WORKER);
    if (!$isWorker) {
        response_error('You must be worker for assign orders!', 403);
    }

    $orderId = m_Order_assign($req['order_id'], user_get_id());

    return ['order_id' => $orderId];

}

function order_pay($params): array
{
}

function order_list($params): array
{
}
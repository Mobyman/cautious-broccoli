<?php

const ROLE_WORKER = 'worker';
const ROLE_HIRER  = 'hirer';
const DEFAULT_PAGE           = 1;

/**
 * @param $params
 * @return array
 */
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
        'cost'        => [
            'type'     => 'cost',
            'required' => true,
            'range'    => [
                100,
                PHP_INT_MAX,
            ],
        ],
    ], $params);

    if (!user_role_is(ROLE_HIRER)) {
        return response_error('You must be hirer for create orders!', 403);
    }

    $profile = user_profile_data();
    if($profile['balance'] >= $req['cost']) {
        $orderId = m_Order_create(request_user_get_id(), $req['cost'], $req['title'], $req['description']);

        return ['order_id' => $orderId];
    }

    response_error('Недостаточно средств');


}

/**
 * @param $params
 * @return array
 */
function order_assign($params): array
{
    $req = request_handle([
        'token'    => [
            'type'     => 'string',
            'required' => true,
        ],
        'order_id' => [
            'type'       => 'number',
            'required'   => true,
            'max_length' => 255,
        ],
    ], $params);

    $isWorker = user_role_is(ROLE_WORKER);
    if (!$isWorker) {
        return response_error('You must be worker for assign orders!', 403);
    }

    $orderId = m_Order_assign($req['order_id'], request_user_get_id());

    return ['order_id' => $orderId];
}

/**
 * @param $params
 * @return array
 */
function order_list($params): array
{
    $req = request_handle([
        'token' => [
            'type'     => 'string',
            'required' => true,
        ],
        'page'  => [
            'type'    => 'number',
            'range'   => [
                1,
                null,
            ],
        ],
    ], $params);

    $req['page'] = $req['page'] ?? DEFAULT_PAGE;

    --$req['page'];

    $items = m_Order_list($req['page']);

    return ['items' => $items];
}

/**
 * @param $params
 * @return array
 */
function order_get($params): array
{
    $req = request_handle([
        'token' => [
            'type'     => 'string',
            'required' => true,
        ],
        'id'    => [
            'type'     => 'number',
            'required' => true,
            'range'    => [
                1,
                null,
            ],
        ],
    ], $params);

    $order = m_Order_get($req['id'], true, [ORDER_STATUS_OPENED]);

    return ['item' => $order];
}

/**
 * @param $params
 * @return array
 */
function order_handle($params): array
{
    $ordersConnection = db_getConnection('order');
    $orders           = m_Order_get_unhandled();

    $status = null;
    while ($row = mysqli_fetch_array($orders, MYSQLI_ASSOC)) {
        $status = m_Order_handle($row['id']);
        break;
    }

    mysqli_free_result($orders);
    mysqli_close($ordersConnection);

    return ['status' => $status];
}


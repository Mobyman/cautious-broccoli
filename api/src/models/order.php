<?php

const STATUS_OPENED   = 0;
const STATUS_RESOLVED = 1;
const STATUS_HOLD     = 2;
const STATUS_CLOSED   = 3;

const PAGE_SIZE = 50;

function m_Order_init()
{
    global $_db;

    $_db['_connections']['order'] = db_getConnection('order');
    if (empty($_db['_connections']['order'])) {
        response_error('Unable to connect database');
    }
}
m_Order_init();

function m_Order_create(int $hirerId, int $cost, string $title, string $description)
{
    $status = STATUS_OPENED;

    $query = 'INSERT INTO `orders` (hirer_id, `cost`, title, description, status) VALUES (?, ?, ?, ?, ?);';
    $s     = mysqli_prepare(db_getConnection('order'), $query);

    mysqli_stmt_bind_param($s, 'iissi', $hirerId, $cost, $title, $description, $status);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        response_error('Cannot insert row');

        return false;
    }

    return mysqli_insert_id(db_getConnection('order'));
}


function m_Order_assign(int $orderId, string $workerId): bool
{
    $query = 'UPDATE `orders` SET worker_id=? WHERE id=? AND worker_id IS NULL AND status=0;';
    $s     = mysqli_prepare(db_getConnection('order'), $query);
    mysqli_stmt_bind_param($s, 'ii', $workerId, $orderId);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        response_error('Order not found');

        return false;
    }

    return true;
}


function m_Order_pay(int $orderId): bool
{
    $query  = 'UPDATE `orders` SET status=? tid=UUID() WHERE id=? AND status=1;';
    $s      = mysqli_prepare(db_getConnection('order'), $query);
    $status = STATUS_HOLD;
    mysqli_stmt_bind_param($s, 'i', $status, $$orderId);
    mysqli_stmt_execute($s);
    mysqli_stmt_close($s);

    if (!mysqli_stmt_affected_rows($s)) {
        response_error('Order not found');

        return false;
    }

    return true;
}

function m_Order_get_unhandled()
{
    $query = 'SELECT id,hirer_id,worker_id,transaction_id,status FROM `orders` WHERE status IN (1,2);';

    $result = mysqli_query(db_getConnection('order'), $query);
    if (!$result) {
        response_error(mysqli_error(db_getConnection('order')));
    }

    return $result;
}

function m_Order_get($orderId)
{
    $orderId = (int) $orderId;
    if($cached = cache_get_model('order', $orderId)) {
        return $cached;
    }

    $query  = "SELECT * FROM `orders` WHERE id=$orderId;";

    $result = mysqli_query(db_getConnection('order'), $query);

    if (!$result) {
        return response_error(mysqli_error(db_getConnection('order')));
    }

    $order = mysqli_fetch_array($result, MYSQLI_ASSOC);
    if (!$order) {
        return response_error('Empty order');
    }

    cache_set_model('order', $orderId, $order);
    return $order;
}

function m_Order_list(int $page)
{
    $status = STATUS_OPENED;
    $offset = 0;
    if ($page) {
        $offset = (PAGE_SIZE * $page);
    }

    $query = 'SELECT id FROM `orders` WHERE status=' . $status . ' LIMIT ' . $offset . ',' . PAGE_SIZE;
    $result = mysqli_query(db_getConnection('order'), $query);
    if (!$result) {
        return response_error(mysqli_error(db_getConnection('order')));
    }

    $orderIds = mysqli_fetch_array($result, MYSQLI_ASSOC);
    if (!$orderIds) {
        return response_error('Orders not found', 404);
    }


    $orders = [];
    foreach ($orderIds as $orderId) {
        $orders[] = m_Order_get($orderId);
    }

    return $orders;
}


function m_Order_handle_transaction()
{

}


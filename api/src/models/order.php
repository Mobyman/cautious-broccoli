<?php

const STATUS_OPENED   = 0;
const STATUS_RESOLVED = 1;
const STATUS_HOLD     = 2;
const STATUS_CLOSED   = 3;

function m_Order_init()
{
    global $_db;

    $_db['_connections']['order'] = db_getConnection('order');
    if (empty($_db['_connections']['order'])) {
        response_error('Unable to connect database');
    }
}

m_Order_init();

function m_Order_create(int $hirerId, string $title, string $description)
{
    global $_db;

    $query  = 'INSERT INTO `orders` (hirer_id, title, description, status) VALUES (?, ?, ?, ?);';
    $s      = mysqli_prepare($_db['_connections']['order'], $query);
    $status = STATUS_OPENED;
    mysqli_stmt_bind_param($s, 'issi', $hirerId, $title, $description, $status);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        response_error('Cannot insert row');

        return false;
    }

    return mysqli_insert_id($_db['_connections']['order']);
}


function m_Order_assign(int $orderId, string $workerId): bool
{
    global $_db;

    $query = 'UPDATE `orders` SET worker_id=? WHERE id=? AND worker_id IS NULL AND status=0;';
    $s     = mysqli_prepare($_db['_connections']['order'], $query);
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
    global $_db;

    $query  = 'UPDATE `orders` SET status=? tid=UUID() WHERE id=? AND status=1;';
    $s      = mysqli_prepare($_db['_connections']['order'], $query);
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
    global $_db;

    $query = 'SELECT id,hirer_id,worker_id,transaction_id,status FROM `orders` WHERE status IN (1,2);';

    $result = mysqli_query($_db['_connections']['order'], $query);
    if (!$result) {
        response_error(mysqli_error($_db['_connections']['order']));
    }

    return $result;
}

function m_Order_handle_transaction()
{

}

function m_Order_update($criteria, $attributes)
{
    global $_db;

}

function m_Order_delete($criteria)
{
    global $_db;

}




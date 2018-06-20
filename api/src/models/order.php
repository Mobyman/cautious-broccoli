<?php

$_db = [];
function m_Order_init()
{
    global $_db;

    $_db['_connection'] = db_getConnection('order');
}

function m_Order_insert(int $hirerId, int $workerId, int $transactionId, int $status): bool
{
    global $_db;

    $query = 'INSERT INTO `orders` (hirer_id, worker_id, transaction_id, status) VALUES (?, ?, ?, ?);';
    $s     = mysqli_prepare($_db['_connection'], $query);
    mysqli_stmt_bind_param($s, 'iiii', $hirerId, $workerId, $transactionId, $status);
    mysqli_stmt_execute($s);
    mysqli_stmt_close($s);

    if (!mysqli_stmt_affected_rows($s)) {
        response_error('Cannot insert row');

        return false;
    }

    return true;
}

function m_Order_update($criteria, $attributes)
{
    global $_db;

}

function m_Order_delete($criteria)
{
    global $_db;

}

m_Order_init();


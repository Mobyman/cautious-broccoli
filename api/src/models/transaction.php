<?php

const TRANSACTION_STATUS_CREATED = 0;
const TRANSACTION_STATUS_HOLD    = 1;
const TRANSACTION_STATUS_SENT    = 2;
const TRANSACTION_STATUS_DONE    = 3;

function m_Transaction_init()
{
    global $_db;

    $_db['_connections']['transaction'] = db_getConnection('transaction');
    if (empty($_db['_connections'])) {
        response_error('Unable to connect database');
    }
}

m_Transaction_init();


function m_Transaction_get($transactionId)
{
    $query = 'SELECT id,type,order_id,status FROM `transactions` WHERE id=?;';
    $s     = mysqli_prepare(db_getConnection('transaction'), $query);
    mysqli_stmt_bind_param($s, 's', $transactionId);
    mysqli_stmt_execute($s);

    mysqli_stmt_bind_result($s, $id, $type, $order_id, $status);
    $result = mysqli_stmt_fetch($s);
    $error  = mysqli_error(db_getConnection('transaction'));
    mysqli_stmt_close($s);


    if ($error) {
        return response_error(mysqli_error(db_getConnection('transaction')));
    }

    if ($result) {
        return compact('id', 'type', 'order_id', 'status');
    }

    return null;
}

function m_Transaction_create(string $id, int $type, int $order_id, int $status)
{
    $query = 'INSERT INTO `transactions` (id, type, order_id, status) VALUES (?, ?, ?, ?);';
    $s     = mysqli_prepare(db_getConnection('transaction'), $query);
    mysqli_stmt_bind_param($s, 'siii', $id, $type, $order_id, $status);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_error(db_getConnection('transaction'));
    mysqli_stmt_close($s);

    if ($error || !$rows) {
        response_error('Cannot create transaction ' . $error);

        return false;
    }

    if ($rows) {
        return compact('id', 'type', 'order_id', 'status');
    }

    return null;
}

function m_Transaction_set_status(string $transactionId, int $status)
{
    $query = 'UPDATE `transactions` SET status=? WHERE id=? AND status<?;';
    $s     = mysqli_prepare(db_getConnection('transaction'), $query);
    mysqli_stmt_bind_param($s, 'isi', $status, $transactionId, $status);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        return false;
    }

    return true;
}

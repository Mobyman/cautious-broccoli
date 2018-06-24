<?php

const ORDER_STATUS_OPENED   = 0;
const ORDER_STATUS_RESOLVED = 1;
const ORDER_STATUS_HOLD     = 2;
const ORDER_STATUS_CLOSED   = 3;

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
    $status = ORDER_STATUS_OPENED;

    $query = 'INSERT INTO `orders` (hirer_id, `cost`, title, description, status) VALUES (?, ?, ?, ?, ?);';
    $s     = mysqli_prepare(db_getConnection('order'), $query);

    mysqli_stmt_bind_param($s, 'iissi', $hirerId, $cost, $title, $description, $status);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_error(db_getConnection('transaction'));
    mysqli_stmt_close($s);

    if (!$rows) {
        response_error('Cannot insert row');

        return false;
    }

    return mysqli_insert_id(db_getConnection('order'));
}


function m_Order_assign(int $orderId, string $workerId)
{
    $status = ORDER_STATUS_OPENED;
    $query  = 'UPDATE `orders` SET worker_id=?, status=1 WHERE id=? AND worker_id IS NULL AND status=?;';
    $s      = mysqli_prepare(db_getConnection('order'), $query);
    mysqli_stmt_bind_param($s, 'iii', $workerId, $orderId, $status);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_error(db_getConnection('transaction'));
    mysqli_stmt_close($s);

    if (!$rows) {
        response_error('Order for assign not found');

        return false;
    }

    if ($orderId) {
        cache_del_model('order', $orderId);
    }

    return $orderId;
}


function m_Order_generate_transaction_id(int $orderId)
{

    $uuid         = str_replace('-', '', uuid_create());
    $packed       = pack('h*', $uuid);
    $openedStatus = ORDER_STATUS_RESOLVED;
    $holdStatus   = ORDER_STATUS_HOLD;

    $query = 'UPDATE `orders` SET status=?, transaction_id=? WHERE id=? AND status=? AND transaction_id IS NULL;';
    $s     = mysqli_prepare(db_getConnection('order'), $query);
    mysqli_stmt_bind_param($s, 'isii', $holdStatus, $packed, $orderId, $openedStatus);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_error(db_getConnection('transaction'));
    mysqli_stmt_close($s);
    $error = mysqli_error(db_getConnection('order'));

    if ($error || !$rows) {
        response_error('Opened order not found' . $error ?? null);

        return false;
    }

    return $packed;
}

function m_Order_close(int $orderId)
{
    $closeStatus  = ORDER_STATUS_CLOSED;
    $holdStatus   = ORDER_STATUS_HOLD;

    $query = 'UPDATE `orders` SET status=?, transaction_id=null WHERE id=? AND status=? AND transaction_id IS NOT NULL;';
    $s     = mysqli_prepare(db_getConnection('order'), $query);
    mysqli_stmt_bind_param($s, 'isi', $closeStatus, $orderId, $holdStatus);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_error(db_getConnection('order'));
    mysqli_stmt_close($s);

    if ($error || !$rows) {
        response_error('Holded order not found' . $error ?? null);

        return false;
    }

    return $rows;
}

function m_Order_get_unhandled()
{
    $query = 'SELECT id FROM `orders` WHERE status IN (1,2);';

    $result = mysqli_query(db_getConnection('order'), $query);
    if (!$result) {
        response_error(mysqli_error(db_getConnection('order')));
    }

    return $result;
}

function m_Order_get(int $orderId, $isWithCache = true)
{
    if ($isWithCache && $cached = cache_get_model('order', $orderId)) {
        return $cached;
    }

    $query = "SELECT * FROM `orders` WHERE id=$orderId;";

    $result = mysqli_query(db_getConnection('order'), $query);
    if (!$result) {
        return response_error(mysqli_error(db_getConnection('order')));
    }

    $order = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $error = mysqli_error(db_getConnection('order'));

    if (!$order) {
        return response_error('Empty order');
    }

    cache_set_model('order', $orderId, $order);

    return $order;
}

function m_Order_list(int $page)
{
    $orderIds = cache_get_model('orders', $page);
    if (!$orderIds) {
        $status = ORDER_STATUS_OPENED;
        $offset = 0;
        if ($page) {
            $offset = (PAGE_SIZE * $page);
        }

        $query  = 'SELECT id FROM `orders` WHERE status=' . $status . ' LIMIT ' . $offset . ',' . PAGE_SIZE;
        $result = mysqli_query(db_getConnection('order'), $query);
        if (!$result) {
            return response_error(mysqli_error(db_getConnection('order')));
        }

        $orderIds = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $error    = mysqli_error(db_getConnection('transaction'));

        if (!$orderIds) {
            return response_error('Orders not found', 404);
        }

        $orderIds = array_column($orderIds, 'id');
        cache_set_model('orders', $page, $orderIds, 60);
    }

    $orders = [];
    foreach ($orderIds as $order) {
        $orders[] = m_Order_get($order);
    }

    return $orders;
}


function m_Order_handle(int $orderId)
{
    $order = m_Order_get($orderId, false);
    if ($order) {

        if (empty($order['transaction_id'])) {
            $transactionId = m_Order_generate_transaction_id((int) $orderId);
            if (!$transactionId) {
                return response_error('Unable to generate transaction id!');
            }

            $order['transaction_id'] = $transactionId;
        }

        $transaction = m_Transaction_get($order['transaction_id']);
        if (!$transaction) {
            $transaction = m_Transaction_create($order['transaction_id'], 1, $orderId, TRANSACTION_STATUS_CREATED);
            if (!$transaction) {
                response_error('Transaction not created');

                return false;
            }
        }

        if (!in_array((int) $transaction['status'], [
            TRANSACTION_STATUS_CREATED,
            TRANSACTION_STATUS_HOLD,
            TRANSACTION_STATUS_SENT,
            TRANSACTION_STATUS_DONE,
        ], true)) {
            response_error('Invalid transaction status');

            return false;
        }

        if ((int) $transaction['status'] === TRANSACTION_STATUS_CREATED) {

            $hirer = m_User_get_profile($order['hirer_id']);

            if (!$hirer) {
                response_error('Hirer not found');

                return false;
            }

            $isHirerHoldOk = $hirer['last_transaction_id'] === $order['transaction_id'];
            if (!$isHirerHoldOk && $hirer['last_transaction_id'] === null) {

                if ($hirer['balance'] < $order['cost']) {
                    response_error('Insufficient hirer balance: ' . $hirer['balance'] . ', needed ' . $order['cost']);

                    return false;
                }

                if (!$isHirerHoldOk = m_User_hold_hirer($order['hirer_id'], $order['transaction_id'], $order['cost'])) {
                    response_error('Hirer handle another transaction, skipping');

                    return false;
                }
            }

            if ($isHirerHoldOk) {
                if (!m_Transaction_set_status($order['transaction_id'], TRANSACTION_STATUS_HOLD)) {
                    response_error('Unable to set transaction status to hold');

                    return false;
                }

                $transaction['status'] = TRANSACTION_STATUS_HOLD;
            }
        }


        if ((int) $transaction['status'] === TRANSACTION_STATUS_HOLD) {
            $worker = m_User_get_profile($order['worker_id']);
            if (!$worker) {
                response_error('Worker not found');

                return false;
            }

            $commission = (int) (($order['cost'] / 100) * 5);
            $reward     = $order['cost'] - $commission;


            $isWorkerHoldOk = $worker['last_transaction_id'] === $order['transaction_id'] && $worker['hold'] === $reward;
            if (!$isWorkerHoldOk && $worker['last_transaction_id'] === null) {
                if (!$isWorkerHoldOk = m_User_hold_worker($order['worker_id'], $order['transaction_id'], $reward)) {
                    response_error('Worker handle another transaction, skipping');

                    return false;
                }
            }

            if ($isWorkerHoldOk) {
                if (!m_Transaction_set_status($order['transaction_id'], TRANSACTION_STATUS_SENT)) {
                    response_error('Unable to set transaction status to sent');

                    return false;
                }

                $transaction['status'] = TRANSACTION_STATUS_SENT;
            }
        }


        if ((int) $transaction['status'] === TRANSACTION_STATUS_SENT) {
            $isUnholdHirer = m_User_unhold_hirer($order['hirer_id'], $order['transaction_id']);
            if (!$isUnholdHirer) {
                response_error('Unable to unhold hirer');

                return false;
            }

            $isUnholdWorker = m_User_unhold_worker($order['worker_id'], $order['transaction_id']);
            if (!$isUnholdWorker) {
                response_error('Unable to unhold worker');

                return false;
            }

            if (!m_Transaction_set_status($order['transaction_id'], TRANSACTION_STATUS_DONE)) {
                response_error('Unable to set transaction status');

                return false;
            }


            $transaction['status'] = TRANSACTION_STATUS_DONE;

            return true;

        }

        if ($transaction['status'] === TRANSACTION_STATUS_DONE) {
            m_Order_close($orderId);
        }

        response_error('Not handled' . var_export($transaction, true));

        return false;
    }

    response_error('Order not found');

    return null;
}


<?php

const ORDER_STATUS_OPENED   = 0;
const ORDER_STATUS_RESOLVED = 1;
const ORDER_STATUS_HOLD     = 2;
const ORDER_STATUS_CLOSED   = 3;

const PAGE_SIZE = 50;

const LAST_ID = 'last_id';
function m_Order_init()
{
    global $_db;

    $_db['_connections']['order'] = db_getConnection('order');
    if (empty($_db['_connections']['order'])) {
        return response_error('Unable to connect database');
    }
}

m_Order_init();

/**
 * @param int    $hirer_id
 * @param int    $cost
 * @param string $title
 * @param string $description
 *
 * @return bool|mixed
 */
function m_Order_create(int $hirer_id, int $cost, string $title, string $description)
{
    $status = ORDER_STATUS_OPENED;

    $query = 'INSERT INTO `orders` (hirer_id, `cost`, title, description, status) VALUES (?, ?, ?, ?, ?);';
    $s     = mysqli_prepare(db_getConnection('order'), $query);

    mysqli_stmt_bind_param($s, 'iissi', $hirer_id, $cost, $title, $description, $status);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_stmt_error($s);
    $id    = mysqli_stmt_insert_id($s);
    mysqli_stmt_close($s);

    if (!$rows || $error) {
        return response_error('Cannot insert row' . $error);

        return false;
    }

    $worker_id      = null;
    $transaction_id = null;
    $order          = compact('id', 'hirer_id', 'worker_id', 'cost', 'transaction_id', 'status', 'title',
        'description');
    cache_set_model('order', $id, $order);
    cache_set_model('order', LAST_ID, $id);

    return $id;
}

/**
 * @param int $orderId
 * @param int $worker_id
 *
 * @return bool|int
 */
function m_Order_assign(int $orderId, int $worker_id)
{
    cache_del_model('order', $orderId);

    $status = ORDER_STATUS_OPENED;
    $query  = 'UPDATE `orders` SET worker_id=?, status=1 WHERE id=? AND worker_id IS NULL AND status=?;';
    $s      = mysqli_prepare(db_getConnection('order'), $query);
    mysqli_stmt_bind_param($s, 'iii', $worker_id, $orderId, $status);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_stmt_error($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        return response_error('Order for assign not found: ' . $orderId, 404);
    }

    if ($error) {
        error_log($error);

        return response_error('DB Error');
    }

    return $orderId;
}

/**
 * @param int $orderId
 *
 * @return bool|string
 */
function m_Order_generate_transaction_id(int $orderId)
{
    cache_del_model('order', $orderId);

    $uuid                = str_replace('-', '', uuid_create());
    $binaryTransactionId = pack('h*', $uuid);
    $openedStatus        = ORDER_STATUS_RESOLVED;
    $holdStatus          = ORDER_STATUS_HOLD;

    $query = 'UPDATE `orders` SET status=?, transaction_id=? WHERE id=? AND status=? AND transaction_id IS NULL;';
    $s     = mysqli_prepare(db_getConnection('order'), $query);
    mysqli_stmt_bind_param($s, 'isii', $holdStatus, $binaryTransactionId, $orderId, $openedStatus);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_stmt_error($s);
    mysqli_stmt_close($s);


    if (!$rows) {
        return response_error('Order not found', 404);
    }

    if ($error) {
        error_log($error);

        return response_error('DB Error');
    }

    return $binaryTransactionId;
}

/**
 * @param int $orderId
 *
 * @return bool|int|string
 */
function m_Order_close(int $orderId)
{
    cache_del_model('order', $orderId);

    $closeStatus = ORDER_STATUS_CLOSED;
    $holdStatus  = ORDER_STATUS_HOLD;

    $query = 'UPDATE `orders` SET status=? WHERE id=? AND status=? AND transaction_id IS NOT NULL;';
    $s     = mysqli_prepare(db_getConnection('order'), $query);
    mysqli_stmt_bind_param($s, 'isi', $closeStatus, $orderId, $holdStatus);
    mysqli_stmt_execute($s);
    $rows  = mysqli_stmt_affected_rows($s);
    $error = mysqli_error(db_getConnection('order'));
    mysqli_stmt_close($s);

    if (!$rows || $error) {
        return response_error('Holded order not found' . $error ?? null);

        return false;
    }

    return $rows;
}

/**
 * @return bool|mysqli_result
 */
function m_Order_get_unhandled()
{
    $query = 'SELECT id FROM `orders` WHERE status IN (1,2);';

    $result = mysqli_query(db_getConnection('order'), $query);
    if (!$result) {
        return response_error(mysqli_error(db_getConnection('order')));
    }

    return $result;
}

/**
 * @param int  $orderId
 * @param bool $isWithCache
 *
 * @param      $allowedStatuses
 *
 * @return array|mixed|null
 */
function m_Order_get(int $orderId, $isWithCache = true, array $allowedStatuses)
{

    $order = null;

    if ($isWithCache) {
        $order = cache_get_model('order', $orderId);
    }

    if (!$order) {
        $query = 'SELECT id, hirer_id, worker_id, cost, transaction_id, status, title, description FROM `orders` WHERE id=?;';

        $s = mysqli_prepare(db_getConnection('order'), $query);
        mysqli_stmt_bind_param($s, 'i', $orderId);
        mysqli_stmt_execute($s);
        mysqli_stmt_bind_result($s, $id, $hirer_id, $worker_id, $cost, $transaction_id, $status, $title, $description);
        $result = mysqli_stmt_fetch($s);
        $error  = mysqli_stmt_error($s);
        mysqli_stmt_close($s);


        if ($error) {
            return response_error($error);
        }

        if ($result) {
            $order = compact('id', 'hirer_id', 'worker_id', 'cost', 'transaction_id', 'status', 'title', 'description');
            cache_set_model('order', $orderId, $order);
        }
    }

    if ($order && (empty($allowedStatuses) || in_array($order['status'], $allowedStatuses, true))) {
        return $order;
    }

    return response_error('Заказ не найден');
}

function m_Order_get_last_id($isWithCache = true)
{
    if ($isWithCache && $cached = cache_get_model('order', LAST_ID)) {
        return $cached;
    }

    $query = 'SELECT id FROM `orders` ORDER BY id DESC LIMIT 1;';

    $s = mysqli_prepare(db_getConnection('order'), $query);
    mysqli_stmt_execute($s);
    mysqli_stmt_bind_result($s, $id);
    $result = mysqli_stmt_fetch($s);
    $error  = mysqli_stmt_error($s);
    mysqli_stmt_close($s);

    if ($error) {
        return response_error($error);
    }

    if ($result) {
        cache_set_model('order', LAST_ID, $id);

        return $id;
    }

    return 0;

}

/**
 * @param int $page
 *
 * @return array|null
 */
function m_Order_list(int $page)
{
    $status   = ORDER_STATUS_OPENED;
    $pageSize = PAGE_SIZE;

    $offset = $page * PAGE_SIZE;

    $lastId  = m_Order_get_last_id();
    $startId = 0;
    if ($lastId) {
        $allPages = (int) round($lastId / 50);
        if ($page > $allPages) {
            return response_error('Заказы не найдены!', 404);
        }

        ++$page;
    }

    $query = 'SELECT id FROM orders WHERE status=? AND id>? ORDER BY id DESC LIMIT ?,?';
    $s     = mysqli_prepare(db_getConnection('order'), $query);


    mysqli_stmt_bind_param($s, 'iiii', $status, $startId, $offset, $pageSize);
    mysqli_stmt_execute($s);
    $error  = mysqli_stmt_error($s);
    $result = mysqli_stmt_bind_result($s, $id);

    if (!$result) {
        return response_error('Заказы не найдены', 404);
    }

    if ($error) {
        error_log($error);

        return response_error('DB error');
    }

    $orderIds = [];
    while (mysqli_stmt_fetch($s)) {
        $orderIds[] = $id;
    }

    mysqli_stmt_close($s);

    if (!$orderIds) {
        return response_error('Заказы не найдены', 404);
    }

    $orders = [];
    foreach ($orderIds as $orderId) {
        $order    = m_Order_get($orderId, true, [ORDER_STATUS_OPENED]);
        $orders[] = $order;
    }

    return $orders;
}

/**
 * @param int $orderId
 *
 * @return bool|null
 */
function m_Order_handle(int $orderId)
{
    $order = m_Order_get($orderId, false, [
        ORDER_STATUS_RESOLVED,
        ORDER_STATUS_HOLD,
    ]);

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
                return response_error('Transaction not created');
            }
        }

        if (!in_array((int) $transaction['status'], [
            TRANSACTION_STATUS_CREATED,
            TRANSACTION_STATUS_HOLD,
            TRANSACTION_STATUS_SENT,
            TRANSACTION_STATUS_DONE,
        ], true)) {
            return response_error('Invalid transaction status');
        }

        if ((int) $transaction['status'] === TRANSACTION_STATUS_CREATED) {

            $hirer = m_User_get_profile($order['hirer_id']);

            if (!$hirer) {
                return response_error('Hirer not found');
            }

            $isHirerHoldOk = $hirer['last_transaction_id'] === $order['transaction_id'];
            if (!$isHirerHoldOk && $hirer['last_transaction_id'] === null) {

                if ($hirer['balance'] < $order['cost']) {
                    return response_error('Insufficient hirer balance: ' . $hirer['balance'] . ', needed ' . $order['cost']);
                }

                if (!$isHirerHoldOk = m_User_hold_hirer($order['hirer_id'], $order['transaction_id'], $order['cost'])) {
                    return response_error('Hirer handle another transaction, skipping');
                }
            }

            if ($isHirerHoldOk) {
                if (!m_Transaction_set_status($order['transaction_id'], TRANSACTION_STATUS_HOLD)) {
                    return response_error('Unable to set transaction status to hold');
                }

                $transaction['status'] = TRANSACTION_STATUS_HOLD;
            }
        }


        if ((int) $transaction['status'] === TRANSACTION_STATUS_HOLD) {
            $worker = m_User_get_profile($order['worker_id']);
            if (!$worker) {
                return response_error('Worker not found');
            }

            $commission = (int) (($order['cost'] / 100) * 5);
            $reward     = $order['cost'] - $commission;


            $isWorkerHoldOk = $worker['last_transaction_id'] === $order['transaction_id'] && $worker['hold'] === $reward;
            if (!$isWorkerHoldOk && $worker['last_transaction_id'] === null) {
                if (!$isWorkerHoldOk = m_User_hold_worker($order['worker_id'], $order['transaction_id'], $reward)) {
                    return response_error('Worker handle another transaction, skipping');
                }
            }

            if ($isWorkerHoldOk) {
                if (!m_Transaction_set_status($order['transaction_id'], TRANSACTION_STATUS_SENT)) {
                    return response_error('Unable to set transaction status to sent');
                }

                $transaction['status'] = TRANSACTION_STATUS_SENT;
            }
        }


        if ((int) $transaction['status'] === TRANSACTION_STATUS_SENT) {
            $isUnholdHirer = m_User_unhold_hirer($order['hirer_id'], $order['transaction_id']);
            if (!$isUnholdHirer) {
                return response_error('Unable to unhold hirer');
            }

            $isUnholdWorker = m_User_unhold_worker($order['worker_id'], $order['transaction_id']);
            if (!$isUnholdWorker) {
                return response_error('Unable to unhold worker');
            }

            if (!m_Transaction_set_status($order['transaction_id'], TRANSACTION_STATUS_DONE)) {
                return response_error('Unable to set transaction status');
            }


            $transaction['status'] = TRANSACTION_STATUS_DONE;
        }

        if ($transaction['status'] === TRANSACTION_STATUS_DONE) {
            m_Order_close($orderId);

            return true;
        }

        return response_error('Not handled' . var_export($transaction, true));
    }

    return response_error('Order not found');
}


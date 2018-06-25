<?php


const USER_ROLE_HIRER = 1;
const USER_ROLE_WORKER = 2;

const DEFAULT_HIRER_BALANCE = 100000;

function m_User_init()
{
    global $_db;
    $_db['_connections']['user'] = db_getConnection('user');
    if (empty($_db['_connections']['user'])) {
        return response_error('Unable to connect database');
    }
}

m_User_init();

/**
 * @param string $login
 * @param string $password
 * @param int $type
 * @return bool
 */
function m_User_create(string $login, string $password, int $type): bool
{
    $query = 'INSERT INTO `users` (login, password, type, balance, hold, last_transaction_id) VALUES (?, ?, ?, ?, ?, ?);';
    $s = mysqli_prepare(db_getConnection('user'), $query);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $hold = 0;
    $last_transaction_id = null;

    $balance = 0;
    if ($type === USER_ROLE_HIRER) {
        $balance = DEFAULT_HIRER_BALANCE;
    }

    mysqli_stmt_bind_param($s, 'ssiiis', $login, $hash, $type, $balance, $hold, $last_transaction_id);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    $id = mysqli_stmt_insert_id($s);
    $error = mysqli_stmt_error($s);
    mysqli_stmt_close($s);

    if (!$rows || $error) {
        return response_error('Cannot insert row ' . $error);
    }

    $profile = compact('id', 'login', 'type', 'balance', 'hold', 'last_transaction_id');
    cache_set_model('user', $id, $profile);

    return true;
}

/**
 * @param string $login
 * @return bool
 */
function m_User_exists_login(string $login): bool
{
    $query = 'SELECT 1 FROM `users` WHERE login=?;';

    $s = mysqli_prepare(db_getConnection('user'), $query);
    mysqli_stmt_bind_param($s, 's', $login);
    mysqli_stmt_execute($s);
    mysqli_stmt_bind_result($s, $result);
    mysqli_stmt_fetch($s);
    mysqli_stmt_close($s);

    return (bool)$result;
}

/**
 * @param string $login
 * @param string $password
 * @return null
 */
function m_User_exists_login_password(string $login, string $password)
{
    $passwordHashCached = cache_get_model('auth', $login);

    $id = $passwordHash['id'] ?? null;
    $passwordHash = $passwordHash['hash'] ?? null;

    if (!$passwordHashCached) {
        $query = 'SELECT id, password FROM `users` WHERE login=?;';
        $s = mysqli_prepare(db_getConnection('user'), $query);

        mysqli_stmt_bind_param($s, 's', $login);
        mysqli_stmt_execute($s);
        mysqli_stmt_bind_result($s, $id, $passwordHash);
        mysqli_stmt_fetch($s);
        mysqli_stmt_close($s);

        cache_set_model('auth', $login, [
            'id' => $id,
            'hash' => $passwordHash,
        ]);
    }

    if (!password_verify($password, $passwordHash)) {
        // если пароль не подходит, мы это не кешируем,
        // это потенциальный вектор атаки, если не использовать капчу
        return null;
    }


    return $id;
}

/**
 * @param int $userId
 * @return array|mixed|null
 */
function m_User_get_profile(int $userId)
{
    if ($cached = cache_get_model('user', $userId)) {
        return $cached;
    }

    $query = 'SELECT id,login,type,balance,hold,last_transaction_id FROM `users` WHERE id=?;';
    $s = mysqli_prepare(db_getConnection('user'), $query);

    mysqli_stmt_bind_param($s, 'i', $userId);
    mysqli_stmt_execute($s);
    mysqli_stmt_bind_result($s, $id, $login, $type, $balance, $hold, $last_transaction_id);
    $result = mysqli_stmt_fetch($s);
    $error = mysqli_stmt_error($s);
    mysqli_stmt_close($s);

    if ($error) {
        return response_error($error);
    }

    if ($result) {
        $profile = compact('id', 'login', 'type', 'balance', 'hold', 'last_transaction_id');
        cache_set_model('user', $userId, $profile);

        return $profile;
    }

    return response_error('Empty profile');
}

/**
 * @param int $userId
 * @param string $transactionId
 * @param string $cost
 * @return bool|int
 */
function m_User_hold_hirer(int $userId, string $transactionId, string $cost)
{
    cache_del_model('user', $userId);

    $query = 'UPDATE `users` SET hold=?, balance=balance+hold, last_transaction_id=? WHERE id=? AND balance>=? AND last_transaction_id IS NULL AND hold=0;';
    $s = mysqli_prepare(db_getConnection('user'), $query);
    $negativeCost = -$cost;
    mysqli_stmt_bind_param($s, 'isii', $negativeCost, $transactionId, $userId, $cost);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    $error  = mysqli_stmt_error($s);
    mysqli_stmt_close($s);

    if (!$rows || $error) {
        return false;
    }

    return $userId;
}

/**
 * @param int $userId
 * @param string $transactionId
 * @param string $cost
 * @return bool|int
 */
function m_User_hold_worker(int $userId, string $transactionId, string $cost)
{
    cache_del_model('user', $userId);

    $query = 'UPDATE `users` SET hold=?, last_transaction_id=? WHERE id=? AND last_transaction_id IS NULL;';
    $s = mysqli_prepare(db_getConnection('user'), $query);
    mysqli_stmt_bind_param($s, 'isi', $cost, $transactionId, $userId);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        return false;
    }

    return $userId;
}

/**
 * @param int $userId
 * @param string $transactionId
 * @return bool|int
 */
function m_User_unhold_hirer(int $userId, string $transactionId)
{
    cache_del_model('user', $userId);

    $query = 'UPDATE `users` SET hold=0, last_transaction_id=null WHERE id=? AND last_transaction_id=?;';
    $s = mysqli_prepare(db_getConnection('user'), $query);
    mysqli_stmt_bind_param($s, 'is', $userId, $transactionId);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        return false;
    }

    return $userId;
}

/**
 * @param int $userId
 * @param string $transactionId
 * @return bool|int
 */
function m_User_unhold_worker(int $userId, string $transactionId)
{
    cache_del_model('user', $userId);

    $query = 'UPDATE `users` SET balance=balance+hold, hold=0, last_transaction_id=null WHERE id=? AND last_transaction_id=?;';
    $s = mysqli_prepare(db_getConnection('user'), $query);
    mysqli_stmt_bind_param($s, 'is', $userId, $transactionId);
    mysqli_stmt_execute($s);
    $error = mysqli_stmt_error($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);


    if (!$rows || $error) {
        return false;
    }

    return $userId;
}

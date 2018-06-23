<?php


const USER_ROLE_HIRER = 1;
const USER_ROLE_WORKER = 2;

function m_User_init()
{
    global $_db;
    $_db['_connections']['user'] = db_getConnection('user');
    if (empty($_db['_connections']['user'])) {
        response_error('Unable to connect database');
    }
}

m_User_init();

function m_User_create(string $login, string $password, int $type): bool
{

    $query = 'INSERT INTO `users` (login, password, type, balance) VALUES (?, ?, ?, ?);';
    $s     = mysqli_prepare(db_getConnection('user'), $query);
    $hash  = password_hash($password, PASSWORD_DEFAULT);

    $defaultBalance = 0;
    if ($type === USER_ROLE_HIRER) {
        $defaultBalance = 1000;
    }

    mysqli_stmt_bind_param($s, 'ssii', $login, $hash, $type, $defaultBalance);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        response_error('Cannot insert row ' . mysqli_error(db_getConnection('user'), $query));

        return false;
    }

    return true;
}

function m_User_exists_login(string $login): bool
{
    $query = 'SELECT 1 FROM `users` WHERE login=? LIMIT 1;';

    $s = mysqli_prepare(db_getConnection('user'), $query);
    mysqli_stmt_bind_param($s, 's', $login);
    mysqli_stmt_execute($s);
    mysqli_stmt_bind_result($s, $result);
    mysqli_stmt_fetch($s);
    mysqli_stmt_close($s);

    return (bool) $result;
}

function m_User_exists_login_password(string $login, string $password)
{
    $loginPasswordPair = $login . $password;
    if ($cached = cache_get_model('auth', $loginPasswordPair)) {
        return $cached;
    }

    $query = 'SELECT id, password FROM `users` WHERE login=? LIMIT 1;';
    $s     = mysqli_prepare(db_getConnection('user'), $query);

    mysqli_stmt_bind_param($s, 's', $login);
    mysqli_stmt_execute($s);
    mysqli_stmt_bind_result($s, $id, $hash);
    mysqli_stmt_fetch($s);
    mysqli_stmt_close($s);

    if (!password_verify($password, $hash)) {
        // если пароль не подходит, мы это не кешируем,
        // это потенциальный вектор атаки, если не использовать капчу
        return null;
    }

    cache_set_model('auth', $loginPasswordPair, $id);

    return $id;
}

function m_User_get_profile($userId)
{
    $userId = (int) $userId;
    if ($cached = cache_get_model('user', $userId)) {
        return $cached;
    }

    $query = "SELECT * FROM `users` WHERE id=$userId;";

    $result = mysqli_query(db_getConnection('user'), $query);
    if (!$result) {
        return response_error(mysqli_error(db_getConnection('order')));
    }

    $profile = mysqli_fetch_array($result, MYSQLI_ASSOC);
    if (!$profile) {
        return response_error('Empty profile');
    }

    cache_set_model('user', $userId, $profile);

    return $profile;
}

function m_User_hold_hirer(int $userId, string $transactionId, string $cost)
{
    $query        = 'UPDATE `users` SET hold=?, balance=balance+hold, last_transaction_id=? WHERE id=? AND balance>=? AND last_transaction_id IS NULL AND hold=0;';
    $s            = mysqli_prepare(db_getConnection('user'), $query);
    $negativeCost = -$cost;
    mysqli_stmt_bind_param($s, 'isii', $negativeCost, $transactionId, $userId, $cost);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);


    mysqli_stmt_close($s);

    if (!$rows) {
        return false;
    }

    return $userId;
}

function m_User_hold_worker(int $userId, string $transactionId, string $cost)
{
    $hold  = TRANSACTION_STATUS_CREATED;
    $query = 'UPDATE `users` SET hold=?, last_transaction_id=? WHERE id=? AND last_transaction_id IS NULL;';
    $s     = mysqli_prepare(db_getConnection('user'), $query);
    mysqli_stmt_bind_param($s, 'isi', $cost, $transactionId, $userId);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        return false;
    }

    return $userId;
}

function m_User_unhold_hirer(int $userId, string $transactionId)
{
    $query = 'UPDATE `users` SET hold=0, last_transaction_id=null WHERE id=? AND last_transaction_id=?;';
    $s     = mysqli_prepare(db_getConnection('user'), $query);
    mysqli_stmt_bind_param($s, 'is', $userId, $transactionId);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        return false;
    }

    return $userId;
}

function m_User_unhold_worker(int $userId, string $transactionId)
{
    $query = 'UPDATE `users` SET balance=balance+hold, hold=0, last_transaction_id=null WHERE id=? AND last_transaction_id=?;';
    $s     = mysqli_prepare(db_getConnection('user'), $query);
    mysqli_stmt_bind_param($s, 'is', $userId, $transactionId);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        return false;
    }

    return $userId;
}

<?php


function m_User_init()
{
    global $_db;
    $_db['_connections']['user'] = db_getConnection('user');
    if (empty($_db['_connections']['user'])) {
        response_error('Unable to connect database');
    }
}

m_User_init();

function m_User_insert(string $login, string $password, int $type): bool
{
    $query = 'INSERT INTO `users` (login, password, type) VALUES (?, ?, ?);';
    $s     = mysqli_prepare(db_getConnection('user'), $query);
    $hash  = password_hash($password, PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($s, 'ssi', $login, $hash, $type);
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
    $query = 'SELECT id, password FROM `users` WHERE login=? LIMIT 1;';
    $s     = mysqli_prepare(db_getConnection('user'), $query);

    mysqli_stmt_bind_param($s, 's', $login);
    mysqli_stmt_execute($s);
    mysqli_stmt_bind_result($s, $id, $hash);
    mysqli_stmt_fetch($s);
    mysqli_stmt_close($s);

    if (!password_verify($password, $hash)) {
        return null;
    }

    return $id;
}

function m_User_get_profile($userId)
{
    $userId = (int) $userId;
    if($cached = cache_get_model('user', $userId)) {
        return $cached;
    }

    $query  = "SELECT * FROM `users` WHERE id=$userId;";

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

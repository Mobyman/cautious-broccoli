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
    global $_db;

    $query = 'INSERT INTO `users` (login, password, type) VALUES (?, ?, ?);';
    $s     = mysqli_prepare($_db['_connections']['user'], $query);
    $hash  = password_hash($password, PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($s, 'ssi', $login, $hash, $type);
    mysqli_stmt_execute($s);
    $rows = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);

    if (!$rows) {
        response_error('Cannot insert row ' . mysqli_error($_db['_connections']['user'], $query));

        return false;
    }

    return true;
}

function m_User_exists_login(string $login): bool
{
    global $_db;

    $query = 'SELECT 1 FROM `users` WHERE login=? LIMIT 1;';

    $s = mysqli_prepare($_db['_connections']['user'], $query);
    mysqli_stmt_bind_param($s, 's', $login);
    mysqli_stmt_execute($s);
    mysqli_stmt_bind_result($s, $result);
    mysqli_stmt_fetch($s);
    mysqli_stmt_close($s);

    return (bool) $result;
}

function m_User_exists_login_password(string $login, string $password)
{
    global $_db;

    $query = 'SELECT id, password FROM `users` WHERE login=? LIMIT 1;';
    $s     = mysqli_prepare($_db['_connections']['user'], $query);

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


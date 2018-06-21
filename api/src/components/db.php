<?php

const DEFAULT_USER     = 'test';
const DEFAULT_PASSWORD = 'test';
const DEFAULT_PORT     = 3306;

/**
 * @param $name
 *
 * @return null|mysqli
 */
function db_getConnection($name)
{
    global $_db;

    if (!empty(getConfig()['db'][ $name ])) {

        if (!empty($_db['_connections'][ $name ])) {
            return $_db['_connections'][ $name ];
        }

        // @formatter:off
        $_db['_connections'][$name] = mysqli_connect(
            getConfig()['db'][ $name ]['host'],
            getConfig()['db'][ $name ]['user'] ?? DEFAULT_USER,
            getConfig()['db'][ $name ]['password'] ?? DEFAULT_PASSWORD,
            getConfig()['db'][ $name ]['database'],
            getConfig()['db'][ $name ]['port'] ?? DEFAULT_PORT
        );
        // @formatter:on

        if ($error = mysqli_connect_errno()) {
            response_error('DB error: ' . $error . mysqli_connect_error());
        }


        if (!$_db['_connections'][ $name ]) {
            response_error('Unable to connect database');
        }

        return $_db['_connections'][ $name ];
    }

    return response_error('Connection config not found ' . $name);
}

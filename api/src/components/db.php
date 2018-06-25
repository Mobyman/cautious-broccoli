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

        try {
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
                error_log(mysqli_connect_error());
                return response_error('DB error');
            }

        } catch (\Exception $e) {
            error_log($e->getMessage());
            return response_error('DB error');
        }

        if (!$_db['_connections'][ $name ]) {
            return response_error('Unable to connect database');
        }

        return $_db['_connections'][ $name ];
    }

    return response_error('Connection config not found ' . $name);
}

<?php

const DEFAULT_USER     = 'test';
const DEFAULT_PASSWORD = 'test';
const DEFAULT_PORT = 3306;

$db['getConnection'] = function ($name) use ($app) {

    if (!empty($app['config']['db'][ $name ])) {

        if (!empty($app['db']['_connections'][ $name ])) {
            return $app['db']['_connections'][ $name ];
        }

        // @formatter:off
        $db['_connections'][$name] = mysqli_connect(
            $app['config']['db'][ $name ]['host'],
            $app['config']['db'][ $name ]['user'] ?? DEFAULT_USER,
            $app['config']['db'][ $name ]['password'] ?? DEFAULT_PASSWORD,
            $app['config']['db'][ $name ]['database'],
            $app['config']['db'][ $name ]['port'] ?? DEFAULT_PORT
        );

        // @formatter:on

        return $db['_connections'][ $name ];
    }

    return 'Empty' . $name;
};

$app['db'] = $db;
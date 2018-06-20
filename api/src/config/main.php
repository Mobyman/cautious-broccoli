<?php

$app['config'] = [
    'memcache' => [
        'host' => 'cache',
        'port' => 11211,
    ],
    'db'       => [
        'order'       => [
            'host'     => 'db-order',
            'database' => 'order',
        ],
        'user'        => [
            'host'     => 'db-user',
            'database' => 'user',
        ],
        'transaction' => [
            'host'     => 'db-transaction',
            'database' => 'transaction',
        ],
    ],
];

<?php

$_db   = [];
$_user = [];

require_once __DIR__ . '/config/main.php';
require_once __DIR__ . '/components/response.php';

require_once __DIR__ . '/components/memcache.php';
require_once __DIR__ . '/components/cache.php';
require_once __DIR__ . '/components/session.php';

require_once __DIR__ . '/components/db.php';

require_once __DIR__ . '/models/order.php';
require_once __DIR__ . '/models/transaction.php';
require_once __DIR__ . '/models/user.php';

require_once __DIR__ . '/components/cli.php';

require_once __DIR__ . '/components/request.php';
require_once __DIR__ . '/controllers/user.php';
require_once __DIR__ . '/controllers/order.php';
require_once __DIR__ . '/components/validator.php';
require_once __DIR__ . '/components/router.php';

register_shutdown_function(function () {
    global $_db;

    if (!empty($_db['_connections'])) {
        foreach ($_db['_connections'] as $connection) {
            mysqli_close($connection);
        }
    }

    if (!empty($_db['cache']['_connection'])) {
        memcache_close($_db['cache']['_connection']);
    }
});
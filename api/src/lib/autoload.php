<?php

global $app;

$app = ['connections' => []];

$app['setConnection'] = function($key, $data) use(&$app) {
    $app['connections'][$key] = $data;
};

$app['getConnection'] = function($key) use(&$app) {
    return $app['connections'][$key] ?? null;
};


require_once __DIR__ . '/../config/main.php';

require_once  __DIR__ . '/../lib/memcache.php';
require_once  __DIR__ . '/../lib/db.php';

require_once  __DIR__ . '/../models/order.php';
require_once  __DIR__ . '/../models/transaction.php';
require_once  __DIR__ . '/../models/user.php';

require_once  __DIR__ . '/../lib/cli.php';
require_once  __DIR__ . '/../lib/response.php';
require_once  __DIR__ . '/../lib/request.php';
require_once  __DIR__ . '/../lib/user.php';
require_once  __DIR__ . '/../lib/order.php';
require_once  __DIR__ . '/../lib/validator.php';
require_once  __DIR__ . '/../lib/router.php';



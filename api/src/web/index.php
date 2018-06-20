<?php

error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors', 'On');

include_once __DIR__ . '/../lib/autoload.php';

if (PHP_SAPI === 'cli') {
    $app['cli']['start']($argc, $argv);
} else {
    $request = $app['request']['post']();

    $app['router']['handle']($request);

    if(empty($request)) {
        echo $app['response']['error']('Invalid method', 404);
    }

}

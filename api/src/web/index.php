<?php

error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors', 'On');

include_once __DIR__ . '/../lib/autoload.php';

if (PHP_SAPI === 'cli') {
    cli_start($argc, $argv);
} else {


    $request = request_post();

    router_handle($request);

    if(empty($request)) {
        echo response_error('Invalid method', 404);
    }

}

<?php


$cli = [];
$cli['start'] = function($argc, $argv) use(&$app) {

    if($argv[1] === 'migrate') {
        echo 'Start migrate...' . PHP_EOL;
        var_dump($app['db']['getConnection']('order'));
        echo 'End migrate...' . PHP_EOL;
    }

};


$app['cli'] = $cli;
<?php

/**
 * @param array $data
 */
$app['response']['respond'] = function (array $data) use ($app) {
    header('Content-Type:application/json');
    echo json_encode($data);
    exit(0);
};

$app['response']['debug'] = function () {
    return [
        'mem_peak' => memory_get_peak_usage()
    ];
};

/**
 * @param string $data
 * @param int    $code
 */
$app['response']['error'] = function (string $data, int $code = 400) use (&$app) {
    $error            = [];
    $error['error']   = true;
    $error['code']    = $code;
    $error['message'] = $data;
    $error['debug']   = $app['response']['debug']();

    $app['response']['respond']($error);
};

/**
 * @param array $data
 */
$app['response']['success'] = function (array $data) use (&$app) {
    $data['code']  = 200;
    $data['debug'] = $app['response']['debug']();

    $app['response']['respond']($data);
};
<?php

/**
 * @param array $data
 */
$app['response']['respond'] = function (array $data) use ($app) {
    header('Content-Type:application/json');
    echo json_encode($data);
    exit(0);
};

/**
 * @param string $data
 * @param int    $code
 */
$app['response']['error'] = function (string $data, int $code = 400) use ($app) {
    $error            = [];
    $error['error']   = true;
    $error['code']    = $code;
    $error['message'] = $data;

    $app['response']['respond']($error);
};

/**
 * @param array $data
 */
$app['response']['success'] = function (array $data) use ($app) {
    $data['code'] = 200;
    $app['response']['respond']($data);
};
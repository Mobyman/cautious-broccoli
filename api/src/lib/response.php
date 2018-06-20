<?php

/**
 * @param array $data
 *
 * @return null
 */
function response_respond(array $data)
{
    header('Content-Type:application/json');
    echo json_encode($data);
    exit(0);
}

/**
 * @return array
 */
function response_debug()
{
    return [
        'mem_peak' => memory_get_peak_usage(),
    ];
}

/**
 * @param string $data
 * @param int    $code
 *
 * @return null;
 */
function response_error(string $data, int $code = 400)
{
    $error            = [];
    $error['error']   = true;
    $error['code']    = $code;
    $error['message'] = $data;
    $error['debug']   = response_debug();

    return response_respond($error);
}

/**
 * @param array $data
 */
function response_success(array $data)
{
    $data['code']  = 200;
    $data['debug'] = response_debug();

    response_respond($data);
}
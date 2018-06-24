<?php

/**
 * @param array $data
 *
 * @param bool  $isFail
 *
 * @param int   $code
 *
 * @return null
 */
function response_respond(array $data, $isFail = false, $code = 400)
{
    if (PHP_SAPI !== 'cli') {
        header('Content-Type:application/json');
    }

    $data['meta']['code'] = $isFail
        ? $code
        : 200;

    echo json_encode($data);

    if($isFail && PHP_SAPI !== 'cli') {
        exit(-1);
    }

    return $isFail ? -1 : 0;

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
    $error['message'] = $data;
    $error['debug']   = response_debug();
    error_log($data);

    return response_respond($error, true, $code);
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
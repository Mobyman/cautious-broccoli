<?php

$validator = [];

$validator['string'] = function ($v) {
    return is_string($v);
};

$validator['number'] = function ($v) {
    return is_numeric($v);
};

$validator['not_null'] = function ($v) {
    return !empty($v);
};

$validator['range'] = function ($min, $max, $v) {
    return $v > $min && $v < $max;
};

$app['validator'] = $validator;
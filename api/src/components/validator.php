<?php


function validator_validate(array $queryRules, array $params)
{

    global $_user;

    $_types = [
        'string' => function ($v) {
            return is_string($v);
        },
        'number' => function ($v) {
            return is_numeric($v);
        },
        'enum'   => function () {
            return false;
        },
        'cost'   => function ($v) {
            if (!is_numeric($v)) {
                return false;
            }

            return (int) $v * 100;
        },
    ];

    $rules = [
        'type'       => function ($type, $v) use ($_types) {
            if (empty($_types[ $type ])) {
                response_error('Invalid validator type: ' . $type);
            }

            return $_types[ $type ]($v);
        },
        'required'   => function ($v) {
            return !empty($v);
        },
        'range'      => function ($params, $v) {
            $min = $params[0] ?? 0;
            $max = $params[1] ?? PHP_INT_MAX;

            return $v >= $min && $v <= $max;
        },
        'max_length' => function ($length, $v) {
            return mb_strlen($v) <= $length;
        },
        'enum'       => function ($values, $v) {
            return in_array($v, $values, true);
        },
        'default' => function($value, $v) {
            if(empty($v)) {
                return $value;
            }

            return $v;
        },
    ];

    foreach ($queryRules as $attribute => $attributeRules) {
        foreach ($attributeRules as $attributeRule => $attributeParam) {
            $attributeValue = $params[ $attribute ] ?? null;
            if (!empty($rules[ $attributeRule ])) {
                if (!$rules[ $attributeRule ]($attributeParam, $attributeValue)) {
                    response_error('Неверный ввод! Атрибут `' . $attribute . '` должен быть ' . $attributeRule . ': ' . json_encode($attributeParam));

                    return false;
                }
            } else {
                response_error('Invalid rule name: ' . $attributeRule);

                return false;
            }
        }
    }

    return $params;
}

;
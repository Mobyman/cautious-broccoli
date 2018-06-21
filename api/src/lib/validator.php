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
        'range'      => function ($min, $max, $v) {
            return $v > $min && $v < $max;
        },
        'max_length' => function ($length, $v) {
            return mb_strlen($v) <= $length;
        },
        'enum'       => function ($values, $v) {
            return in_array($v, $values, true);
        },
    ];

    foreach ($queryRules as $attribute => $attributeRules) {
        foreach ($attributeRules as $attributeRule => $attributeParam) {
            $attributeValue = $params[ $attribute ] ?? null;
            if (!empty($rules[ $attributeRule ])) {
                if (!$rules[ $attributeRule ]($attributeParam, $attributeValue)) {
                    response_error('Invalid param! Attribute `' . $attribute . '` must be ' . $attributeRule . ': ' . json_encode($attributeParam));

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
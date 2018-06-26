<?php

/**
 * @param array $queryRules
 * @param array $params
 * @return array|bool
 */
function validator_validate(array $queryRules, array $params)
{
    global $_validator_types;
    global $_validator_rules;

    if(empty($_validator_types)) {
        $_validator_types = [
            'string' => function ($v) {
                return is_string($v);
            },
            'number' => function ($v) {
                return is_numeric($v);
            },
            'cost'   => function ($v) {
                return is_int($v) && $v > 0 && $v < PHP_INT_MAX;
            },
        ];
    }

    if(empty($_validator_rules)) {
        $_validator_rules = [
            'type'       => function ($type, $v) use ($_validator_types) {
                if (empty($_validator_types[ $type ])) {
                    return response_error('Invalid validator type: ' . $type);
                }

                return $_validator_types[ $type ]($v);
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
        ];
    }

    foreach ($queryRules as $attribute => $attributeRules) {
        foreach ($attributeRules as $attributeRule => $attributeParam) {
            $attributeValue = $params[ $attribute ] ?? null;
            if (!empty($_validator_rules[ $attributeRule ])) {
                if (!$_validator_rules[ $attributeRule ]($attributeParam, $attributeValue)) {
                    return response_error('Неверный ввод! Атрибут `' . $attribute . '` должен быть ' . $attributeRule . ': ' . json_encode($attributeParam));
                }
            } else {
                return response_error('Invalid rule name: ' . $attributeRule);
            }
        }
    }

    return $params;
}
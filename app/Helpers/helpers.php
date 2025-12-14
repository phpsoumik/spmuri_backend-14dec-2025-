<?php

if (!function_exists('arrayKeysToCamelCase')) {
    function arrayKeysToCamelCase($array) {
        if (!is_array($array)) {
            return $array;
        }
        
        $result = [];
        foreach ($array as $key => $value) {
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            $result[$camelKey] = is_array($value) ? arrayKeysToCamelCase($value) : $value;
        }
        return $result;
    }
}
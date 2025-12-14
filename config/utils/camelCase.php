<?php

use Illuminate\Support\Str;

if (!function_exists('arrayKeysToCamelCase')) {
function arrayKeysToCamelCase($array): array
{
    $result = [];
    foreach ($array as $key => $value) {
        $key = Str::camel($key);
        if (is_array($value)) {
            $value = arrayKeysToCamelCase($value);
        }
        $result[$key] = $value;
    }
    return $result;
}
}

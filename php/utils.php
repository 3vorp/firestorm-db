<?php

function pre_dump($val): void {
    echo '<pre>';
    var_dump($val);
    echo '</pre>';
}

function check($var): bool {
    return isset($var) and !empty($var);
}

function sec($var): string {
    return htmlspecialchars($var);
}

function http_response($body, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo $body;

    exit();
}

function http_json_response($json, $code = 200) {
    return http_response(json_encode($json), $code);
}

function http_message($message, $key = 'message', $code = 200) {
    $arr = [$key => $message];
    return http_json_response($arr, $code);
}

function http_error($code, $message) {
    return http_message($message, 'error', $code);
}

function http_success($message) {
    return http_message($message, 'message', 200);
}

function is_primitive($value): bool {
    $value_type = gettype($value);
    return $value_type === 'NULL' ||
        $value_type === 'boolean' ||
        $value_type === 'integer' ||
        $value_type === 'double' ||
        $value_type === 'string';
}

function is_number_like($value): bool {
    $value_type = gettype($value);
    return in_array($value_type, ['integer', 'double']);
}

function is_keyable($value): bool {
    return in_array(gettype($value), ['integer', 'string']);
}

function check_key_json($key, $arr, $parse = false) {
    if (array_key_exists($key, $arr))
        return $parse ? sec($arr[$key]) : $arr[$key];
    return false;
}

function array_assoc($arr): bool {
    if ([] === $arr || !is_array($arr)) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function array_sequential($arr): bool {
    return !array_assoc($arr);
}

function stringifier($obj, $depth = 1) {
    if ($depth == 0) return json_encode($obj);

    $res = "{";

    $formed = [];
    foreach (array_keys($obj) as $key) {
        array_push($formed, '"' . strval($key) . '":' . stringifier($obj[$key], $depth - 1));
    }
    $res .= implode(",", $formed);

    $res .= "}";

    return $res;
}

function cors() {
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}

function remove_dots($path): string {
    $root = ($path[0] === '/') ? '/' : '';

    $segments = explode('/', trim($path, '/'));
    $ret = [];
    foreach ($segments as $segment) {
        if ($segment == '.' || strlen($segment) === 0) continue;
        if ($segment == '..') array_pop($ret);
        else array_push($ret, $segment);
    }
    return $root . implode('/', $ret);
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        $needle_len = strlen($needle);
        return $needle_len === 0 || 0 === substr_compare($haystack, $needle, -$needle_len);
    }
}

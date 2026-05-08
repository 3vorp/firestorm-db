<?php

/** Debug logging function */
function pre_dump($val) {
    echo '<pre>';
    var_dump($val);
    echo '</pre>';
}

/** Set CORS policy correctly */
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

/** Check if a variable exists and isn't empty */
function check($var) {
    return isset($var) and !empty($var);
}

/** Sanitize a string into its HTML entities */
function sec($var) {
    return htmlspecialchars($var);
}

/** Safely get a value from the client request object */
function check_key_json($key, $arr, $parse = false) {
    if (array_key_exists($key, $arr))
        return $parse ? sec($arr[$key]) : $arr[$key];
    return false;
}

/** Return a basic HTTP response with provided code */
function http_response($body, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo $body;

    exit();
}

/** Return a JSON HTTP response with provided code */
function http_json_response($json, $code = 200) {
    http_response(json_encode($json), $code);
}

/** Return an HTTP response message */
function http_message($message, $key = 'message', $code = 200) {
    $arr = [$key => $message];
    http_json_response($arr, $code);
}

/** Return a successful HTTP response with provided message */
function http_success($message) {
    http_message($message, 'message', 200);
}

/** Return HTTP error with provided code and error message */
function http_error($code, $message) {
    http_message($message, 'error', $code);
}

/** Whether a value is a primitive JSON value (not a full collection element) */
function is_primitive($value) {
    $value_type = gettype($value);
    return $value_type == 'NULL' ||
        $value_type == 'boolean' ||
        $value_type == 'integer' ||
        $value_type == 'double' ||
        $value_type == 'string';
}

/**
 * Whether a value can be treated like a number (have math operations performed on it)
 * - Used for testing increment/decrement types in editField
 */
function is_number_like($value) {
    return in_array(gettype($value), ['integer', 'double']);
}

/** Whether a value is usable as a collection key (numeric or string key) */
function is_keyable($value) {
    return in_array(gettype($value), ['integer', 'string']);
}

/** Whether an array is associative (object-like) */
function array_assoc($arr) {
    if ($arr === [] || !is_array($arr))
        return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

/** Whether an array is sequential (list-like) */
function array_sequential($arr) {
    return !array_assoc($arr);
}

/** Encode a JSON object */
function stringifier($obj, $depth = 1) {
    if ($depth == 0)
        return json_encode($obj);

    $res = "{";

    $formed = [];
    foreach (array_keys($obj) as $key) {
        array_push($formed, '"' . strval($key) . '":' . stringifier($obj[$key], $depth - 1));
    }
    $res .= implode(",", $formed);

    $res .= "}";

    return $res;
}

/** Normalize a file path */
function remove_dots($path) {
    $root = ($path[0] === '/') ? '/' : '';

    // split string, handle slashes, and re-join string at end
    $segments = explode('/', trim($path, '/'));
    $ret = [];
    foreach ($segments as $segment) {
        if ($segment == '.' || strlen($segment) === 0)
            continue;
        if ($segment == '..')
            array_pop($ret);
        else
            array_push($ret, $segment);
    }

    return $root . implode('/', $ret);
}

// php 7 compatibility (added in php 8)
if (!function_exists('str_ends_with')) {
    /** Checks if a string ends with a given substring */
    function str_ends_with(string $haystack, string $needle): bool {
        $needle_len = strlen($needle);
        return $needle_len === 0 || 0 === substr_compare($haystack, $needle, -$needle_len);
    }
}

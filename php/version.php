<?php

// require a token for checking the version to prevent being able to search for vulnerable versions
require_once './utils.php';

cors();

$method = sec($_SERVER['REQUEST_METHOD']);
if ($method !== 'GET' && $method !== 'POST') {
	http_error(400, "Incorrect request type, expected GET or POST, not $method");
}

$inputJSON = json_decode(file_get_contents('php://input'), true);

if (!$inputJSON)
	http_error(400, 'No JSON body provided');


$token = check_key_json('token', $inputJSON);
if (!$token)
	http_error(400, 'No token provided');

if (file_exists('./tokens.php') == false)
	http_error(501, 'Developer didn\'t implement a tokens.php file');

// add tokens
require_once './tokens.php';

if (!$db_tokens)
	http_error(400, 'Developer is dumb and forgot to create tokens');

// verifying token
if (!in_array($token, $db_tokens))
	http_error(403, 'Invalid token');

if (file_exists('./config.php') == false)
	http_error(501, 'Developer didn\'t implement a config.php file');

// import db config
require_once './config.php';

if (!isset($FIRESTORM_VERSION))
	http_error(501, 'Server-side Firestorm version is older than the serverVersion field.');

http_response($FIRESTORM_VERSION);

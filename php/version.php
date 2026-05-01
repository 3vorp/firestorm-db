<?php
// Server-side version of Firestorm, used for client validation
// Make sure this matches your installed server version!
$FIRESTORM_VERSION = '2.0.0';

// require a token for checking the version to prevent being able to search for vulnerable versions
require_once './utils.php';

cors();

$method = sec($_SERVER['REQUEST_METHOD']);
if ($method !== 'GET' && $method !== 'POST') {
	http_error(400, "Incorrect request type, expected GET or POST, not $method");
}

$inputJSON = json_decode(file_get_contents('php://input') ?: "", true);

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

http_response($FIRESTORM_VERSION);

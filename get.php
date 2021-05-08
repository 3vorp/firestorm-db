<?php
// display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// import useful functions
require_once('./utils.php');

$method = sec($_SERVER['REQUEST_METHOD']);
if($method !== 'GET') {
    http_error(400, 'Incorrect request type, expected GET, not ' . $method);
}

$inputJSON = json_decode(file_get_contents('php://input'), true);

if(!$inputJSON)
    http_error(400, 'No JSON body provided');
    
// pre_dump($inputJSON);
// exit();

$collection = check_key_json('collection', $inputJSON);
if(!$collection)
    http_error(400, 'No collection provided');

if(file_exists('./config.php') == false)
    http_error(501, 'Admin didn\'t implemented config.php file');

// import db config
require_once('./config.php');

// trying things
try {
    
// checking good collection
if(!array_key_exists($collection, $database_list))
    http_error(404, 'Collection not found: ' . $collection);
    
$db = $database_list[$collection];

$command = check_key_json('command', $inputJSON);
if(!$command)
    http_error(400, 'No command provided');
    
$commands_available = ['read_raw', 'get', 'search'];

// var_dump($command);
// exit();

if(!in_array($command, $commands_available))
    http_error(404, 'Command not found: ' . $command . '. Available commands: ' . join(', ', $commands_available));
    
switch($command) {
    case 'read_raw':
        $res = $db->read_raw();
        $res = $res['content'];
        http_response($res);
        break;
    case 'get':
        $id = check_key_json('id', $inputJSON);
        
        if(!$id)
            http_error(400, 'No id provided');
            
        $result = $db->get($id);
        if(!$result)
            http_error(404, 'get failed on collection ' . $collection . ' with key ' . $id);
            
        http_response(json_encode($result));
        break;
    case 'search':
        $search = check_key_json('search', $inputJSON, false);
        
        if(!$search)
            http_error(400, 'No search provided');
        
        $result = $db->search($search);
        
        http_response(json_encode($result));
        break;
    default:
        break;
}

http_message(400, 'Bad request');


} catch(Exception $e) {
    http_error(500, $e->getMessage());
}
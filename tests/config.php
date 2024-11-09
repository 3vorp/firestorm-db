<?php

require_once './utils.php';
require_once './classes/Collection.php';

// whitelist of correct extensions
$authorized_file_extension = ['.txt', '.png', '.jpg', '.jpeg'];
// subfolder of uploads location, must start with dirname($_SERVER['SCRIPT_FILENAME'])
// to force a subfolder of firestorm installation
$STORAGE_LOCATION = dirname($_SERVER['SCRIPT_FILENAME']) . '/uploads/';

$database_list = [
	// test with constructor/optional args
	"house" => new Collection('house', false)
];

// test without constructor
$tmp = new Collection;
$tmp->fileName = 'base';
$tmp->autoKey = true;
$tmp->autoIncrement = true;

$database_list[$tmp->fileName] = $tmp;


$log_path = 'firestorm.log';

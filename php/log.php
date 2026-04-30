<?php

require_once './config.php';

class Log {
    public static function addLog($message) {
        global $log_path;

        $path = $log_path ?? "out.log";

        $now = new DateTime();
        $fp = fopen($path, 'a');
        if ($fp === false)
            throw new Exception("Could not open log path $path");

        fwrite($fp, $now->format('Y-m-d H:i:s'));
        fwrite($fp, $message);
        fwrite($fp, '\n');
        fclose($fp);
    }
}

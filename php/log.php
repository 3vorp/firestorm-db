<?php

include_once './config.php';

class Log {
    public static function addLog($message) {
        $now = new DateTime();
        $path = $log_path ?? "out.log";
        $fp = fopen($path, 'a');
        fwrite($fp, $now->format('Y-m-d H:i:s'));
        fwrite($fp, $message);
        fwrite($fp, '\n');
        fclose($fp);
    }
}

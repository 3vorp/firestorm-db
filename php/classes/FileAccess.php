<?php
require_once './utils.php';

/**
 * File wrapper class
 */
class FileWrapper {
    /** File path */
    public string $filepath;
    /** File content as string */
    public string $content = '';
    /** File descriptor */
    public $fd;
    /** Decoded file JSON */
    public array|null $json = null;
    public function __construct(string $filepath) {
        $this->filepath = $filepath;
    }

    /**
     * Sync JSON field with current content
     */
    public function decode(): FileWrapper {
        $this->json = json_decode($this->content, true);
        return $this;
    }

    /**
     * Sync content file with current JSON
     */
    public function encode(): FileWrapper {
        $this->content = stringifier($this->json, 1);
        return $this;
    }
}

class FileAccess {
    public static function read(string $filepath, bool $waitLock = false, $default = null): FileWrapper {
        $fileObj = new FileWrapper($filepath);
        // open file as binary
        $file = fopen($filepath, 'rb');

        // exit if couldn't find file
        if ($file === false) {
            if ($default === null)
                throw new Exception("Could not open file: $filepath");

            // set default value if exists
            $fileObj->content = $default;

            // set file to default content (so future reads don't fail)
            file_put_contents($fileObj->filepath, $fileObj->content, LOCK_EX);
            $file = fopen($filepath, 'rb');
        }

        // need to keep file descriptor when writing the file
        $fileObj->fd = $file;

        // if want the lock, we wait for the shared lock
        if ($waitLock) {
            $lock = flock($file, LOCK_SH);
            if (!$lock) {
                fclose($file);
                throw new Exception('Failed to lock file');
            }
        }

        // read file content
        $string = '';
        while (!feof($file)) {
            $string .= fread($file, 8192);
        }

        $fileObj->content = $string;

        // if no wait you can close the file
        if (!$waitLock)
            fclose($file);

        return $fileObj;
    }
    public static function write(FileWrapper $fileObj): bool {
        // unlock and close file descriptor
        flock($fileObj->fd, LOCK_UN);
        fclose($fileObj->fd);

        if (!is_writable($fileObj->filepath)) {
            throw new HTTPException("PHP script can't write to collection file. Check permission, group and owner.", 400);
        }

        // need exclusive lock to write
        $res = file_put_contents($fileObj->filepath, $fileObj->content, LOCK_EX);

        // php uses really inconsistent return types so we need to cast the result
        if ($res === false) return false;
        return true;
    }
}

<?php

require_once './utils.php';
require_once './classes/FileAccess.php';

/**
 * Base JSON database class
 */
class JSONDatabase {
    /** Folder to get the JSON file from */
    public $folderPath = './files/';
    /** Name of the JSON file */
    public $fileName = 'db';
    /** File extension used in collection name */
    public $fileExt = '.json';

    /** Whether to automatically generate the key name or to have explicit key names */
    public $autoKey = true;
    /** Whether to simply start at 0 and increment or to use a random ID name */
    public $autoIncrement = true;

    public function __construct(
        string $fileName = 'db',
        bool $autoKey = true,
        bool $autoIncrement = true
    ) {
        // if no/some args provided they just fall back to their defaults
        $this->fileName = $fileName;
        $this->autoKey = $autoKey;
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * Generate a new unique database key
     * @param array $arr Object to generate the key from
     * @return string Generated key
     */
    protected function generateNewKey(array $arr): string {
        if ($this->autoIncrement) {
            // get last key and add one (holes are preserved)
            $int_keys = array_filter(array_keys($arr), 'is_int');
            sort($int_keys);
            $last_key = count($int_keys) > 0 ? $int_keys[count($int_keys) - 1] + 1 : 0;
        } else {
            $last_key = uniqid();
            while (array_key_exists($last_key, $arr))
                $last_key = uniqid();
        }

        return strval($last_key);
    }

    /**
     * Return the full JSON file path
     * @return string The full JSON file path
     */
    public function getFullPath(): string {
        return "{$this->folderPath}{$this->fileName}{$this->fileExt}";
    }

    /**
     * Writes the contents of a file object to disk, without doing any type checking
     * - Only use this method if you know what you're doing
     * @param FileWrapper $obj File object to write
     */
    protected function write(FileWrapper $obj): bool {
        return FileAccess::write($obj->encode());
    }

    /**
     * Read the raw content of the file as a string
     * @param bool $waitLock Whether to lock the file for writing later
     * @return FileWrapper Read file object
     */
    public function readRaw(bool $waitLock = false): FileWrapper {
        // fall back to empty array if failed
        return FileAccess::read($this->getFullPath(), $waitLock, json_encode([]));
    }
}

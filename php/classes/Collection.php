<?php

require_once './utils.php';
require_once './classes/FileAccess.php';
require_once './classes/JSONDatabase.php';
require_once './classes/HTTPException.php';
require_once './lib/random.php';
require_once './lib/search.php';
require_once './lib/edit.php';

// parameters aren't typed to avoid PHP's awful runtime type checking system
// instead, fields are validated in the method and rejected with a more helpful error message

/**
 * Represents a Firestorm collection
 */
class Collection extends JSONDatabase {
    /**
     * Read the entire collection
     * @param bool $waitLock Whether to lock the file for writing later
     * @return FileWrapper Read file object
     */
    public function read(bool $waitLock = false): FileWrapper {
        $res = $this->readRaw($waitLock);
        return $res->decode();
    }

    /**
     * Get the SHA-1 hash of the collection
     * @return string The SHA-1 hash of the collection
     */
    public function sha1(): string {
        $obj = $this->readRaw();
        return sha1($obj->content);
    }

    /**
     * Get an element from the collection by its key
     * @param string|int $key Key to search
     * @return array|null The found element
     */
    public function get($key): array|null {
        $obj = $this->read();
        if (
            !$obj ||
            !property_exists($obj, 'json') ||
            !array_key_exists(strval($key), $obj->json)
        )
            return null;

        // send { key: value } instead of just the value so the client-side can add the ID_FIELD property
        return [$key => $obj->json[$key]];
    }

    /**
     * Get multiple elements from the collection by their keys
     * @param array $keys Array of keys to search
     * @return array The found elements
     */
    public function searchKeys($keys): array {
        $keysType = gettype($keys);
        if (is_primitive($keys) || array_assoc($keys))
            throw new HTTPException("Key array has wrong type $keysType, expected array", 400);
        $obj = $this->read();

        $res = [];

        foreach ($keys as $key) {
            $key = strval($key);
            if (array_key_exists($key, $obj->json)) {
                $res[$key] = $obj->json[$key];
            }
        }

        return $res;
    }

    /**
     * Search through the collection
     * @param array $options Array of search options
     * @param bool|int $random Random result seed, disabled by default, but can activated with true or a given seed
     * @return array The found elements
     */
    public function search($options, $random = false): array {
        $obj = $this->read();
        return search_collection($obj->json, $options, $random);
    }

    /**
     * Get only selected fields from the collection
     * - Essentially an upgraded version of {@link read_raw}
     * @param array $option The fields you want to select
     * @return array Selected fields
     */
    public function select($option): array {
        if (!array_key_exists('fields', $option))
            throw new HTTPException('Missing required fields field');

        if (!gettype($option['fields']) === 'array' || !array_sequential($option['fields']))
            throw new HTTPException('Incorrect fields type, expected an array');

        // all field arguments should be strings
        $fields = $option['fields'];
        foreach ($fields as $field) {
            if (gettype($field) !== 'string')
                throw new HTTPException('fields field incorrect, expected a string array');
        }

        $obj = $this->read();
        $filtered = array_key_exists('search', $option)
            ? search_collection($obj->json, $option['search'])
            : $obj->json;

        $result = [];
        foreach ($filtered as $key => $value) {
            $result[$key] = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $value))
                    $result[$key][$field] = $value[$field];
            }
        }

        return $result;
    }

    /**
     * Get all distinct non-null values for a given key across a collection
     * @param array $option Value options
     * @return array Array of unique values
     */
    public function values($option): array {
        if (!array_key_exists('field', $option))
            throw new HTTPException('Missing required field field');

        if (!is_string($option['field']))
            throw new HTTPException('Incorrect field type, expected a string');

        if (array_key_exists('flatten', $option)) {
            if (!is_bool($option['flatten']))
                throw new HTTPException('Incorrect flatten type, expected a boolean');
            $flatten = $option['flatten'];
        } else {
            $flatten = false;
        }

        $field = $option['field'];
        $obj = $this->read();

        $result = [];
        foreach ($obj->json as $value) {
            // get correct field and skip existing primitive values (faster)
            if (!array_key_exists($field, $value) || in_array($value, $result))
                continue;

            // flatten array results if array field
            if ($flatten === true && is_array($value[$field]))
                $result = array_merge($result, $value[$field]);
            else
                array_push($result, $value[$field]);
        }

        // remove complex duplicates
        $result = array_intersect_key($result, array_unique(array_map('serialize', $result)));

        return $result;
    }

    /**
     * Read random elements of the collection
     * @param array $params Random parameters
     * @return array The found elements
     */
    public function random($params): array {
        return random($this, $params);
    }


    /**
     * Writes a JSON object to disk, with type checking
     * @param array $content Associative array of content to write
     */
    public function write_raw($content): bool {
        // content must not be primitive
        if (is_primitive($content)) {
            $content_type = gettype($content);
            throw new HTTPException("write_raw value cannot be a $content_type", 400);
        }

        // value must not be a sequential array with values inside [1, 2, 3]
        // we accept sequential arrays but with objects not primitives
        if (is_array($content) and !array_assoc($content)) {
            foreach ($content as $item) {
                $item_type = gettype($item);
                if (is_primitive($item)) {
                    throw new HTTPException("write_raw item cannot be a $item_type", 400);
                }
            }
        }

        // now we know we have an associative array

        // content must be objects
        foreach ($content as $key => $item) {
            // we don't accept primitive keys as value
            $item_type = gettype($item);
            if (is_primitive($item)) {
                throw new HTTPException("write_raw item with key $key cannot be a $item_type", 400);
            }

            // we accept associative array as items because they may have numeric keys (e.g. with autoKey)
        }

        $validated = stringifier($content);

        // fix for php parsing an empty object as []
        if ($validated === '[]')
            $validated = '{}';

        // no need to use FileAccess as we're writing without reading
        return file_put_contents($this->getFullPath(), $validated, LOCK_EX);
    }

    /**
     * Append a value to the collection
     * - Only works if {@link autoKey} is enabled
     * @param array $value The value to add
     * @return string The generated key of the added element
     */
    public function add($value): string {
        if (!$this->autoKey)
            throw new HTTPException('Automatic key generation is disabled');

        // restricts types to objects only
        $value_type = gettype($value);
        if (is_primitive($value) or (is_array($value) and count($value) and !array_assoc($value)))
            throw new HTTPException("add value must be an object, not a $value_type", 400);

        // else set it at the corresponding value
        $obj = $this->read(true);

        $id = $this->generateNewKey($obj->json);
        $obj->json[$id] = $value;

        $this->write($obj);

        return $id;
    }

    /**
     * Append multiple values to the collection
     * - Only works if {@link autoKey} is enabled
     * @param array $values The values (without methods) to add
     * @return array The generated keys of the added elements
     */
    public function addBulk($values): array {
        if (!$this->autoKey)
            throw new HTTPException('Automatic key generation is disabled');

        if ($values !== [] and $values == NULL)
            throw new HTTPException('null-like value not accepted', 400);

        // restricts types to non base variables
        $value_type = gettype($values);
        if (is_primitive($values) or (is_array($values) and count($values) and array_assoc($values)))
            throw new HTTPException("value must be an array, not a $value_type", 400);

        // so here we have a sequential array type
        // now the values inside this array must not be base values
        foreach ($values as $value) {
            $value_type = gettype($value);
            if (is_primitive($value) or (array_sequential($value) and count($value)))
                throw new HTTPException("Array value must be an object, not a $value_type", 400);
        }

        // verify that values is an array with number indices
        if (array_assoc($values))
            throw new HTTPException('Expected sequential array');

        // else set it at the corresponding value
        $obj = $this->read(true);

        // decode and add all values
        $values_decoded = $values;
        $id_array = [];
        foreach ($values_decoded as $value_decoded) {
            $id = $this->generateNewKey($obj->json);
            $obj->json[$id] = $value_decoded;

            array_push($id_array, $id);
        }

        $this->write($obj);
        return $id_array;
    }

    /**
     * Remove an element from the collection by its key
     * @param string|int $key The key from the entry to remove
     * @return bool Whether the operation succeeded
     */
    public function remove($key): bool {
        $key_var_type = gettype($key);
        if (!is_keyable($key))
            throw new HTTPException("Incorrect key type, got $key_var_type, expected string or integer", 400);

        $obj = $this->read(true);
        unset($obj->json[$key]);
        return $this->write($obj);
    }

    /**
     * Remove multiple elements from the collection by their keys
     * @param array $keys The key from the entries to remove
     * @return bool Whether the operation succeeded
     */
    public function removeBulk($keys): bool {
        if ($keys !== [] and $keys == NULL)
            throw new HTTPException('null-like keys not accepted', 400);

        if (gettype($keys) !== 'array' or array_assoc($keys))
            throw new HTTPException('keys must be an array', 400);

        for ($i = 0; $i < count($keys); $i++) {
            $key_var_type = gettype($keys[$i]);
            if (!is_keyable($keys[$i]))
                throw new HTTPException("Incorrect key type, got $key_var_type, expected string or integer", 400);
            else
                $keys[$i] = strval($keys[$i]);
        }

        $obj = $this->read(true);

        // remove all keys
        foreach ($keys as $key_decoded)
            unset($obj->json[$key_decoded]);

        return $this->write($obj);
    }

    /**
     * Set a value in the collection by key
     * @param string $key The key of the element you want to edit
     * @param array $value The value (you want to edit
     * @return bool Whether the operation succeeded
     */
    public function set($key, $value): bool {
        // "===" fixes the empty array "==" comparison
        if ($key === null)
            throw new HTTPException('Key is null', 400);
        if ($value === null)
            throw new HTTPException('Value is null', 400);

        $key_var_type = gettype($key);
        if (!is_keyable($key))
            throw new HTTPException("Incorrect key type, got $key_var_type, expected string or integer", 400);

        $value_var_type = gettype($value);
        if (is_primitive($value))
            throw new HTTPException("Invalid value type, got $value_var_type, expected object", 400);

        if ($value !== [] and !array_assoc($value))
            throw new HTTPException('Value cannot be a sequential array', 400);

        $key = strval($key);

        // set it at the corresponding value
        $obj = $this->read(true);
        $obj->json[$key] = json_decode(json_encode($value), true);
        return $this->write($obj);
    }

    /**
     * Set multiple values in the collection by their keys
     * @param array $keys The keys of the elements you want to edit
     * @param array $values The values you want to edit
     * @return bool Whether the operation succeeded
     */
    public function setBulk($keys, $values): bool {
        // we verify that our keys are in an array
        $key_var_type = gettype($keys);
        if ($key_var_type != 'array')
            throw new HTTPException('Incorrect keys type');

        // else set it at the corresponding value
        $obj = $this->read(true);

        // decode and add all values
        $value_decoded = json_decode(json_encode($values), true);
        $keys_decoded = json_decode(json_encode($keys), true);

        // regular for loop to join keys and values together
        for ($i = 0; $i < count($value_decoded); $i++) {
            $key_var_type = gettype($keys_decoded[$i]);
            if (!is_keyable($keys_decoded[$i]))
                throw new HTTPException("Incorrect key type, got $key_var_type, expected string or integer");

            $key = strval($keys_decoded[$i]);

            $obj->json[$key] = $value_decoded[$i];
        }

        return $this->write($obj);
    }

    /**
     * Edit an element's field in the collection
     * @param array $option The edit object
     * @return bool Whether the operation succeeded
     */
    public function editField($option): bool {
        $fileObj = $this->read(true);
        edit_field($fileObj, $option);
        return $this->write($fileObj);
    }

    /**
     * Edit multiple elements' fields in the collection
     * @param array $options The edit objects
     * @return bool Whether the operation succeeded
     */
    public function editFieldBulk($options): bool {
        // need sequential array
        if (array_assoc($options))
            return false;

        $fileObj = $this->read(true);
        foreach ($options as &$option) {
            // edit by reference, faster than passing values back and forth
            edit_field($fileObj, $option);
        }
        return $this->write($fileObj);
    }
}

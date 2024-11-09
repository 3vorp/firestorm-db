<?php
require_once './lib/random.php';

function array_contains($array, $value, $ignoreCase = false) {
    $tmp = false;
    $tmp_i = 0;
    while ($tmp_i < count($array) and !$tmp) {
        if ($ignoreCase) {
            $tmp = ($ignoreCase ? strcasecmp($array[$tmp_i], $value) : strcmp($array[$tmp_i], $value)) == 0;
        } else {
            $tmp = $array[$tmp_i] == $value;
        }
        $tmp_i = $tmp_i + 1;
    }
    return $tmp;
}

function array_contains_any($concernedField, $value, $ignoreCase = false) {
    $add = false;

    if (gettype($value) === 'array') {
        $tmp = false;
        $val_i = 0;
        while ($val_i < count($value) and !$tmp) {
            $cf_i = 0;
            while ($cf_i < count($concernedField) && !$tmp) {
                if ($ignoreCase) {
                    $tmp = ($ignoreCase ? strcasecmp($concernedField[$cf_i], $value[$val_i]) : strcmp($concernedField[$cf_i], $value[$val_i])) === 0;
                } else {
                    $tmp = $concernedField[$cf_i] == $value[$val_i];
                }
                $cf_i = $cf_i + 1;
            }
            $val_i = $val_i + 1;
        }

        $add = $tmp;
    } else {
        $add = false;
    }

    return $add;
}

function search_filter($field, $criteria, $value, $ignoreCase): bool {
    $fieldType = gettype($field);
    switch ($fieldType) {
        case 'boolean':
            switch ($criteria) {
                case '!=':
                    return $field != $value;
                case '==':
                    return $field == $value;
                default:
                    return false;
            }
        case 'integer':
        case 'double':
            switch ($criteria) {
                case '!=':
                    return $field != $value;
                case '==':
                    return $field == $value;
                case '>=':
                    return $field >= $value;
                case '<=':
                    return $field <= $value;
                case '<':
                    return $field < $value;
                case '>':
                    return $field > $value;
                case 'in':
                    return in_array($field, $value);
                default:
                    return false;
            }
        case 'string':
            // saves a lot of duplicate ternaries, no idea why php needs these to be strings
            $cmpFunc = $ignoreCase ? 'strcasecmp' : 'strcmp';
            $posFunc = $ignoreCase ? 'stripos' : 'strpos';
            switch ($criteria) {
                case '!=':
                    return $cmpFunc($field, $value) != 0;
                case '==':
                    return $cmpFunc($field, $value) == 0;
                case '>=':
                    return $cmpFunc($field, $value) >= 0;
                case '<=':
                    return $cmpFunc($field, $value) <= 0;
                case '<':
                    return $cmpFunc($field, $value) < 0;
                case '>':
                    return $cmpFunc($field, $value) > 0;
                case 'includes':
                case 'contains':
                    if ($value === '')
                        return true;
                    return $posFunc($field, $value) !== false;
                case 'startsWith':
                    if ($value === '')
                        return true;
                    return $posFunc($field, $value) === 0;
                case 'endsWith':
                    if ($value === '')
                        return true;
                    $end = substr($field, -strlen($value));
                    return $cmpFunc($end, $value) === 0;
                case 'in':
                    $found = false;
                    foreach ($value as $val) {
                        $found = $cmpFunc($field, $val) == 0;
                        if ($found)
                            break;
                    }
                    return $found;
                default:
                    return false;
            }
        case 'array':
            switch ($criteria) {
                case 'array-contains':
                    return array_contains($field, $value, $ignoreCase);
                case 'array-contains-none':
                    return !array_contains_any($field, $value, $ignoreCase);
                case 'array-contains-any':
                    return array_contains_any($field, $value, $ignoreCase);
                case 'array-length':
                case 'array-length-eq':
                    return count($field) == $value;
                case 'array-length-df':
                    return count($field) != $value;
                case 'array-length-gt':
                    return count($field) > $value;
                case 'array-length-lt':
                    return count($field) < $value;
                case 'array-length-ge':
                    return count($field) >= $value;
                case 'array-length-le':
                    return count($field) <= $value;
                default:
                    return false;
            }
        default:
            break;
    }

    // unknown type
    return false;
}

function search_collection($values, $conditions, $random = false): array {
    $res = [];

    foreach ($values as $key => $el) {
        $el_root = $el;

        $add = true;
        foreach ($conditions as $condition) {
            if (!$add)
                break;

            // extract field
            $field = $condition['field'];
            $field_path = explode('.', $field);

            // get nested fields if needed
            for ($field_ind = 0; $el != NULL && $field_ind + 1 < count($field_path); ++$field_ind) {
                // don't crash if unknown nested key, break early
                if (!array_key_exists($field_path[$field_ind], $el))
                    break;

                $el = $el[$field_path[$field_ind]];
                $field = $field_path[$field_ind + 1];
            }

            if (
                $el == NULL ||
                !array_key_exists($field, $el) ||
                !array_key_exists('criteria', $condition) ||
                !array_key_exists('value', $condition)
            ) {
                $add = false;
                break;
            }

            $ignoreCase = array_key_exists('ignoreCase', $condition) && !!$condition['ignoreCase'];
            $add = search_filter(
                $el[$field],
                $condition['criteria'],
                $condition['value'],
                $ignoreCase
            );

            $el = $el_root;
        }

        // if all conditions are met, we can add the value to our output
        if ($add)
            $res[$key] = $el_root;
    }

    if ($random !== false) {
        $seed = false;
        if (is_array($random) && array_key_exists('seed', $random)) {
            $rawSeed = sec($random['seed']);
            if (!is_int($rawSeed))
                throw new HTTPException('Seed not an integer value for random search result');
            $seed = intval($rawSeed);
        }
        $res = choose_random($res, $seed);
    }

    return $res;
}

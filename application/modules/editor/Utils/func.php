<?php
/**
 * Performance detection. 'mt' mean 'microtime'
 * Returns the time between the previous and current calls of this function
 *
 * Usage:
 *  ...some operation 1...
 * echo mt(); // print time spent on operations from the very beginning till now, and remember point of time before operation 2
 * ... some operation 2...
 * echo mt(); // print time spent on operation 2, and fix point of time before operation 3
 * .. some operation 3 ...
 * echo mt(); // print time spent on operation 3
 */
$mt = 0; function mt(){$m = microtime();list($mc, $s) = explode(' ', $m); $n = $s + $mc; $ret = $n - $GLOBALS['mt']; $GLOBALS['mt'] = $n; return $ret;} mt();

/**
 * Flush the json-encoded message, containing `success` property, and other optional properties
 *
 * Usages:
 * jflush(true, 'OK')  -> {success: true, msg: "OK"}
 * jflush(['success' => true, 'param1' => 'value1', 'param2' => 'value2']) -> {success: true, param1: "value1", param2: "value2"}
 * jflush(true, ['param1' => 'value1', 'param2' => 'value2']) -> {success: true, param1: "value1", param2: "value2"}
 *
 * @param $success
 * @param mixed $msg1
 * @param mixed $msg2
 * @param bool $die
 */
function jflush($success, $msg1 = null, $msg2 = null) {

    // Start building data for flushing
    $flush = is_array($success) && array_key_exists('success', $success) ? $success : ['success' => $success];

    // Deal with first data-argument
    if (func_num_args() > 1 && func_get_arg(1) != null)
        $mrg1 = is_object($msg1)
            ? (in('toArray', get_class_methods($msg1)) ? $msg1->toArray() : (array) $msg1)
            : (is_array($msg1) ? $msg1 : ['msg' => $msg1]);

    // Deal with second data-argument
    if (func_num_args() > 2 && func_get_arg(2) != null)
        $mrg2 = is_object($msg2)
            ? (in('toArray', get_class_methods($msg2)) ? $msg2->toArray() : (array) $msg2)
            : (is_array($msg2) ? $msg2 : ['msg' => $msg2]);

    // Merge the additional data to the $flush array
    if ($mrg1) $flush = array_merge($flush, $mrg1);
    if ($mrg2) $flush = array_merge($flush, $mrg2);

    // Send headers
    if (!headers_sent()) {

        // Send '400 Bad Request' status code if user agent is not IE
        if ($flush['success'] === false && !isIE()) header('HTTP/1.1 400 Bad Request');

        // Send '200 OK' status code
        if ($flush['success'] === true) header('HTTP/1.1 200 OK');

        // Send content type
        header('Content-Type: '. (isIE() ? 'text/plain' : 'application/json'));
    }

    // Flush json
    echo json_encode($flush, JSON_UNESCAPED_UNICODE);

    // Exit
    exit;
}

/**
 * Try to detect if request was made using Internet Explorer
 *
 * @return bool
 */
function isIE() {
    return !!preg_match('/(MSIE|Trident|rv:)/', $_SERVER['HTTP_USER_AGENT']);
}

/**
 * Displays formatted view of a given value
 *
 * @param mixed $value
 * @return null
 */
function d($value) {

    // Wrap the $value with the '<pre>' tag, and write it to the output
    echo '<pre>'; print_r($value); echo '</pre>';
}

/**
 * Shortcut function for ZfExtended_Factory::get(...)
 *
 * @param $class
 * @return mixed
 */
function m($class) {
    return ZfExtended_Factory::get($class);
}

/**
 * Shortcut for implode() function, but with the reversed order of arguments
 *
 * @param $array
 * @param string $separator
 * @return string
 */
function im(array $array, $separator = ',') {
    return implode($separator, $array);
}

/**
 * Comma-separated values to array converter
 *
 * @param $items
 * @param $allowEmpty - If $items arg is an empty string, function will return an array containing that empty string
 *                      as a first item, rather than returning empty array
 * @return array
 */
function ar($items, $allowEmpty = false) {

    // If $items arg is already an array - return it as is
    if (is_array($items)) return $items;

    // Else if $items arg is strict null - return array containing that null as a first item
    if ($items === null) return $allowEmpty ? array(null) : array();

    // Else if $items arg is a boolean value - return array containing that boolean value as a first item
    if (is_bool($items)) return array($items);

    // Else if $items arg is an object we either return result of toArray() call on that object,
    // or return result, got by php's native '(array)' cast-prefix expression, depending whether
    // or not $items object has 'toArray()' method
    if (is_object($items)) return in_array('toArray', get_class_methods($items)) ? $items->toArray(): (array) $items;

    // Else we assume $items is a string and return an array by comma-exploding $items arg
    if (is_string($items)) {

        // If $items is an empty string - return empty array
        if (!strlen($items) && !$allowEmpty) return array();

        // Explode $items arg by comma
        foreach ($items = explode(',', $items) as $i => $item) {

            // Convert strings 'null', 'true' and 'false' items to their proper types
            if ($item == 'null') $items[$i] = null;
            if ($item == 'true') $items[$i] = true;
            if ($item == 'false') $items[$i] = false;
        }

        // Return normalized $items
        return $items;
    }

    // Else return array, containing $items arg as a single item
    return array($items);
}

/**
 * Shortcut fn to Zend_Registry::get('config')
 *
 * @return mixed
 */
function cfg() {
    return Zend_Registry::get('config');
}

/**
 * Return $then or $else arg depending on whether $if arg is true
 *
 * @param bool $if
 * @param string $then
 * @param string $else
 * @return string
 */
function rif($if, $then, $else = '') {
    return $if ? str_replace('$1', is_scalar($if) ? $if : '$1', $then) : $else;
}

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
 * Displays formatted view of a given value
 *
 * @param mixed $value
 * @return null
 */
function d($value) {

    // Wrap the $value with the '<pre>' tag, and write it to the output
    echo '<pre>'; print_r($value); echo '</pre>';
}
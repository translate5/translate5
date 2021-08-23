<?php
/**
 * Performance detection. 'mt' mean 'microtime'
 * Returns the time between the previous and current calls of this function
 *
 * Usage:
 *  ...some operation 1...
 * echo mt(); // print time spent on operations from the very beginning till now, and remember point of time before operation 2
 * ... some operation 2...
 * echo mt('compeleted operation 2'); // print time spent on operation 2, and fix point of time before operation 3
 * .. some operation 3 ...
 * echo mt(); // print time spent on operation 3
 * .. some operation 4
 * print_r(mt(true)); // Array (
 *  ['0'] => 0.0055539608001708984,                             // time spent on some operation 1
 *  ['1: compeleted operation 2'] => 0.029513120651245117,      // time spent on some operation 2
 *  ['2'] => 2.459089994430542                                  // time spent on some operation 3
 *  ['3: true'] => 1.459089994430542                            // time spent on some operation 4
 * )
 * Note: keys are prefixed with numeric indexes to be used for proper order of appearance in browser json response preview
 */
$mt = 0; function mt($stage = null){
    $m = microtime();
    list($mc, $s) = explode(' ', $m);
    $n = $s + $mc;
    $ret = $n - $GLOBALS['mt'];
    $GLOBALS['mt'] = $n;
    if ($stage !== false) {
        if (!$stage || $stage === true) $GLOBALS['_mt'] [count($GLOBALS['_mt']) . ': true'] = $ret;
        else $GLOBALS['_mt'][count($GLOBALS['_mt']) . ': ' . $stage] = $ret;
    }
    return $stage === true ? $GLOBALS['_mt'] : $ret;
} mt(false);

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
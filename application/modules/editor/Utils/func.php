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
    settype($GLOBALS['_mt'], 'array');
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

/**
 * Write the contents of $value to a file - 'debug.txt' by default, located in the document root
 * This is handy when you need to check some variables or something, but flushing in to the output
 * will break the response json
 *
 * Usages:
 *  i($_POST);
 *  i($_GET, 'a'); // 'a' is here for dump of $_GET to be appended to the $file instead of overwriting
 *
 * @param $value
 * @param string $mode This arg is a 2nd arg for fopen($filename, $mode) call and accepts same values
 * @param string $file
 */
function i($value, $mode = 'w', $file = 'debug.txt') {

    // Get the document root, with trimmed right trailing slash
    $doc = rtrim($_SERVER['DOCUMENT_ROOT'], '\\/');

    // Get the absolute path of a file, that will be used for writing data to
    $abs = $doc . '/' . $file;

    // If value is bool
    if (is_bool($value)) {

        // Use var_dump for dumping, as print_r() will give 1 or 0 instead of 'bool(true)' or 'bool(false)'
        ob_start(); var_dump($value); $value = ob_get_clean();

    // Else
    } else {

        // Use print_r() for dumping
        $value = print_r($value, true);
    }

    // Write the data
    $fp = fopen($abs, $mode); fwrite($fp, $value . "\n"); fclose($fp);
}

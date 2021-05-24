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
 * Return $then or $else arg depending on whether $if arg is truthy
 *
 * @param bool $if
 * @param string $then
 * @param string $else
 * @return string
 */
function rif($if, $then, $else = '') {
    return $if ? str_replace('$1', is_scalar($if) ? $if : '$1', $then) : $else;
}

/**
 * @return Zend_Db_Adapter_Abstract
 */
function db() {
    return Zend_Db_Table_Abstract::getDefaultAdapter();
}

/**
 * Wrap all urls with <a href="..">
 * Code got from: http://stackoverflow.com/questions/1188129/replace-urls-in-text-with-html-links
 *
 * Testing text: <<<EOD

Here are some URLs:
stackoverflow.com/questions/1188129/pregreplace-to-detect-html-php
Here's the answer: http://www.google.com/search?rls=en&q=42&ie=utf-8&oe=utf-8&hl=en. What was the question?
A quick look at http://en.wikipedia.org/wiki/URI_scheme#Generic_syntax is helpful.
There is no place like 127.0.0.1! Except maybe http://news.bbc.co.uk/1/hi/england/surrey/8168892.stm?
Ports: 192.168.0.1:8080, https://example.net:1234/.
Beware of Greeks bringing internationalized top-level domains: xn--hxajbheg2az3al.xn--jxalpdlp.
And remember.Nobody is perfect.

<script>alert('Remember kids: Say no to XSS-attacks! Always HTML escape untrusted input!');</script>
EOD;

 *
 * @param $text
 * @return string
 */
function url2a($text) {

    // Regexps
    $rexProtocol = '(https?://)?';
    $rexDomain   = '((?:[-a-zA-Z0-9а-яА-Я]{1,63}\.)+[-a-zA-Z0-9а-яА-Я]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})';
    $rexPort     = '(:[0-9]{1,5})?';
    $rexPath     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
    $rexQuery    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
    $rexFragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';

    // Valid top-level domains
    $validTlds = array_fill_keys(explode(' ', '.aero .asia .biz .cat .com .coop .edu .gov .info .int .jobs .mil .mobi '
        . '.museum .name .net .org .pro .tel .travel .ac .ad .ae .af .ag .ai .al .am .an .ao .aq .ar .as .at .au .aw '
        . '.ax .az .ba .bb .bd .be .bf .bg .bh .bi .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cc .cd .cf .cg '
        . '.ch .ci .ck .cl .cm .cn .co .cr .cu .cv .cx .cy .cz .de .dj .dk .dm .do .dz .ec .ee .eg .er .es .et .eu '
        . '.fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gp .gq .gr .gs .gt .gu .gw .gy .hk '
        . '.hm .hn .hr .ht .hu .id .ie .il .im .in .io .iq .ir .is .it .je .jm .jo .jp .ke .kg .kh .ki .km .kn .kp '
        . '.kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mk .ml .mm .mn .mo '
        . '.mp .mq .mr .ms .mt .mu .mv .mw .mx .my .mz .na .nc .ne .nf .ng .ni .nl .no .np .nr .nu .nz .om .pa .pe '
        . '.pf .pg .ph .pk .pl .pm .pn .pr .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si '
        . '.sj .sk .sl .sm .sn .so .sr .st .su .sv .sy .sz .tc .td .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .tt '
        . '.tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .ye .yt .yu .za .zm .zw '
        . '.xn--0zwm56d .xn--11b5bs3a9aj6g .xn--80akhbyknj4f .xn--9t4b11yi5a .xn--deba0ad .xn--g6w251d '
        . '.xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--jxalpdlp .xn--kgbechtv .xn--zckzah .arpa .рф .xn--p1ai'), true);

    // Start output buffering
    ob_start();

    // Position
    $position = 0;

    // Split given $text by urls
    while (preg_match("~$rexProtocol$rexDomain$rexPort$rexPath$rexQuery$rexFragment(?=[?.!,;:\"]?(\s|$))~u",
        $text, $match, PREG_OFFSET_CAPTURE, $position)) {

        // Extract $url and $urlPosition from match
        list($url, $urlPosition) = $match[0];

        // Print the text leading up to the URL.
        print(htmlspecialchars(substr($text, $position, $urlPosition - $position)));

        // Pick domain, port and path from matches
        $domain = $match[2][0];
        $port   = $match[3][0];
        $path   = $match[4][0];

        // Get top-level domain
        $tld = mb_strtolower(strrchr($domain, '.'), 'utf-8');

        // Check if the TLD is valid - or that $domain is an IP address.
        if (preg_match('{\.[0-9]{1,3}}', $tld) || isset($validTlds[$tld])) {

            // Prepend http:// if no protocol specified
            $completeUrl = $match[1][0] ? $url : 'http://' . $url;

            // Print the hyperlink.
            printf('<a href="%s">%s</a>', htmlspecialchars($completeUrl), htmlspecialchars("$domain$port$path"));

            // Else if not a valid URL.
        } else print(htmlspecialchars($url));

        // Continue text parsing from after the URL.
        $position = $urlPosition + strlen($url);
    }

    // Print the remainder of the text.
    print(htmlspecialchars(substr($text, $position)));

    // Return
    return ob_get_clean();
}

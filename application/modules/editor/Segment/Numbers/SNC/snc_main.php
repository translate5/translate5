<?php
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
define('__SNC_PH__', '__SNC_PH__');
define('SYMBOL_CRLF', "\xc2\xb6"); // = ¶ = 'PILCROW SIGN' (U+00B6)
define('SYMBOL_CR', "\xe2\x86\xa9"); // = ↩ = 'LEFTWARDS ARROW WITH HOOK' (U+21A9)
define('SYMBOL_LF', "\xE2\x86\xB5"); // ↵ = 'DOWNWARDS ARROW WITH CORNER LEFTWARDS' (U+21B5)
define('SYMBOL_TAB', "\xE2\x87\xA5");
require_once '_snc_misc_helpers.php';
require_once 'morphSegs_sdlxliff.php';
require_once 'check_zahlen_helpers.php';
function numbers_check($source, $target, $sourceLang, $targetLang, editor_Models_Task $task = null) {
    $data = [];
    $package = 'Lose_Dateien';
    $file = 'file';
    $parseInfo = [
        'fileInfo' => [
            'srcLang' => $sourceLang,
            'trgLang' => $targetLang,
            'kombi' => $kombi = $sourceLang . '-' . $targetLang,
            'fileType' => 'unbekannter Dateityp',
            'commentDefs' => [],
            'fileComments' => [],
            'tagDefs' => ['phTags' => [], 'eptTags' => [], 'bptTags' => []],
            'lockedTransUnits' => [],
        ],
        'segInfo' => [1 => ['srcRaw' => $source, 'trgRaw' => $target]],
    ];
    $data[$kombi][$package][$file]['fileInfo']['fileNum'] = 1;
    $data[$kombi][$package][$file]['fileInfo']['segCount'] = 1;
    $data[$kombi][$package][$file]['fileInfo'] += $parseInfo['fileInfo'];
    $data[$kombi][$package][$file]['segInfo'] = $parseInfo['segInfo'];
    $data[$kombi][$package][$file]['segInfo'][1] += morphSegs_sdlxliff(
        ['srcRaw' => $source, 'trgRaw' => $target],
        $parseInfo['fileInfo']['tagDefs'],
        [$sourceLang, $targetLang]
    );

    $checkMessages = []; $msgCounts = []; $msgMatches = []; $tagMatches = []; $emptyTrgMids = [];
    check_zahlen($data, $checkMessages, $msgCounts, $msgMatches, $emptyTrgMids);
    $res = $checkMessages['noVar_kombi_package_file_mid_checkFunc_checkSubFunc'][$kombi]['Lose_Dateien'] ?? false;
    if (!$res) return [];

    $nums = [
        'Unstimmigkeiten in SRC vs TRG'                       => 'num1',
        'Alphanum. Zeichenfolge'                              => 'num2',
        'Formatänderung (Datumsangaben u.ä.)'                 => 'num3',
        'Trenner in'                                          => 'num4',
        'Hinweis: Formatierung 1000er-Zahl geändert'          => 'num5',
        'Unterschiedliche Minuszeichen'                       => 'num6',
        'Trenner aus SRC geändert in'                         => 'num7',
        'Hinweis: Zahlwort als Zahl gefunden'                 => 'num8',
        'Hinweis: Zahl als Zahlwort gefunden'                 => 'num9',
        'Formatänderung (Ordinalzahlen, führende Null u.ä.)'  => 'num10',
        'Untersch. Zeichen/Formatierung für Zahlen-Intervall' => 'num11',
        '1000er-Trenner in'                                   => 'num12',
    ];

    $res = current(current(current($res ?? [[]])));
    $ret = []; $notFound = [];
    foreach ($res as $subCheck => $msgs) {
        foreach ($msgs as $msg) {
            $found = false;
            foreach ($nums as $beg => $key) {
                if (preg_match('~^' . preg_quote($beg, '~') . '~', $msg)) {
                    $ret[$key] []= $msg;
                    $found = true;
                }
            }
            if (!$found) {
                $notFound []= $msg;
                if ($task) {
                    $task->logger('editor.task.autoqa')->error('E1392', 'AutoQA Numbers check: new kind of error detected', [
                        'error' => $msg,
                        'sourceLang' => $sourceLang,
                        'targetLang' => $targetLang,
                        'sourceText' => $source,
                        'targetText' => $target,
                    ]);
                }
            }
        }
    }
    if ($notFound && !$task) {
        echo "Not Found\n";
        print_r($notFound);
    }
    return $ret;
}
if (isset($argv) && is_array($argv) && count($argv) == 5) {
    $res = numbers_check($argv[1], $argv[2], $argv[3], $argv[4]);
    print_r($res);
}
//$res = numbers_check('STARt1', 'STARt2', 'en', 'de');
//$res = numbers_check('STOP!', 'xxx PFERDABC ', 'en', 'de');
//print_r($res);

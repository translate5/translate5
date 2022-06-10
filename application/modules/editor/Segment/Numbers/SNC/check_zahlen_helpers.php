<?php
function check_zahlen($data, &$checkMessages, &$msgCounts, &$msgMatches, $emptyTrgMids)
{
    $sncSettings = [];
    $kombi = key($data);
    $checkProps = ['standard' => true, 'checkFunc' => 'check_zahlen', 'checkID' => 'ST-0'];
    $sourceLang = $data[$kombi]['Lose_Dateien']['file']['fileInfo']['srcLang'];
    $targetLang = $data[$kombi]['Lose_Dateien']['file']['fileInfo']['trgLang'];
    $currentData = ['kombi' => $kombi, 'langs' => [$sourceLang, $targetLang]];
    $checks = ['functions' => ['check_zahlen' => ['checkID' => 'ST-0', 'standard' => true]]];

    $checkFunc = $checkProps['checkFunc'];
    $checkID = $checkProps['checkID'];
    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];

    $srcLang = $langs[0];
    $trgLang = $langs[1];

    $supportedSncTypes['STUDIO'] = true;
    $supportedSncTypes['TRANSIT'] = true;
    $supportedSncTypes['CSV_EDITOR_V1'] = true;
    $supportedSncTypes['CSV_PASSOLO_V1'] = true;
    $supportedSncTypes['TMX'] = true;

    // check properties festlegen
    $checkProps['supportedSncTypes'] = $supportedSncTypes;
    $checkProps += call_user_func_array($checkFunc . '_setCheckProps', [&$checks, $checkProps, $currentData, $sncSettings]);
    call_user_func_array($checkFunc . '_setCheckSubProps', [&$checks, $checkProps, $currentData, $sncSettings]);
    //d($checks);
    $monthInfo = [];
    $dateInfo = [];

    foreach ($data[$kombi] as $package => $files) {
        foreach ($files as $file => $infos) {
            if (isMissingInTPF_transit($data, $kombi, $package, $file)) {
                continue;
            }

            foreach ($infos['segInfo'] as $mid => $segTypes) {
                $currentData['package'] = $package;
                $currentData['file'] = $file;
                $currentData['mid'] = $mid;
                $currentData['segTypes'] = $segTypes;

                if (skipChecksForMid($data, $kombi, $package, $file, $mid, $checkFunc, $sncSettings, $emptyTrgMids)) {
                    continue;
                }

                if (true) {
                    $checkSeg[0] = $segTypes['srcSegSimple'];
                    $checkSeg[1] = $segTypes['trgSegSimple'];
                } else {
                    $checkSeg[0] = $segTypes['srcSegPure'];
                    $checkSeg[1] = $segTypes['trgSegPure'];
                }

                //if((!preg_match('!\d!u', $checkSeg[0])) && (!preg_match('!\d!u', $checkSeg[1]))) continue;

                $result = subCheck_zahlen_normalizeSegs($checkSeg, $dateInfo, $monthInfo, $data, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
                $checkSeg = $result['checkSeg'];
                $dateChecked = $result['dateChecked'];

                subCheck_zahlen_v1($checkSeg, $dateChecked, $data, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);

                subCheck_alphanumStrings($data, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);

                if (true || $sncSettings['zahlen.noDivAllowed']['master_value'] == true) {
                    subCheck_1000er_trenner_nicht_erlaubt($data, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
                }
            }
        }
    }
}
function check_zahlen_setCheckProps(&$checks, $checkProps, $currentData, $sncSettings)
{
    $checkProps['checkType'] = 'Zahlen';
    $checkProps['checkType_EN'] = 'Numbers';
    $checkProps['checkRule'] = '';
    $checkProps['checkRule_EN'] = '';
    $checkProps['hideInReportHeader'] = false;

    setCheckProps($checks, $checkProps);

    return $checkProps;
}
function check_zahlen_setCheckSubProps(&$checks, $checkProps, $currentData, $sncSettings)
{
    $checkSubFunc = 'subCheck_zahlen_normalizeSegs';

    $checkProps['checkSubFunc'] = $checkSubFunc;
    $checkProps['checkSubType'] = 'Abweichende Zeichen/Formatierungen';
    $checkProps['checkSubType_EN'] = 'Different characters/formatting';
    $checkProps['checkSubRule'] = '';
    $checkProps['checkSubRule_EN'] = '';
    $checkProps['checkSeverity'][$checkSubFunc] = 'notice';

    $checkProps['hideInReportHeader'] = false;
    setCheckSubProps($checks, $checkProps);

    $checkSubFunc = 'subCheck_wortwiederholung';

    $checkProps['checkSubFunc'] = $checkSubFunc;
    $checkProps['checkSubFunc'] = 'subCheck_zahlen_v1';
    $checkProps['checkSubType'] = 'Zahlen SRC ≠ TRG';
    $checkProps['checkSubType_EN'] = 'Numbers SRC ≠ TRG';
    $checkProps['checkSubRule'] = 'Zahlen in SRC und TRG sollten i.d.R. übereinstimmen. Ausnahmen: Zahl aus SRC als Zahlwort in TRG oder umgekehrt ("Allrad" vs. "4WD", Datumsangaben etc.)';
    $checkProps['checkSubRule_EN'] = 'Numbers in SRC and TRG should generally be the same. Exceptions: Number from SRC as a numeral in TRG or vice versa ("all-wheel drive" vs. "4WD", date information etc.)';
    $checkProps['checkSeverity'][$checkSubFunc] = 'critical';
    $checkProps['hideInReportHeader'] = false;

    setCheckSubProps($checks, $checkProps);

    if (true || $sncSettings['zahlen.noDivAllowed']['master_value'] == true) {
        $checkSubFunc = 'subCheck_1000er_trenner_nicht_erlaubt';

        $checkProps['checkSubFunc'] = $checkSubFunc;
        $checkProps['checkSubType'] = '[X] Keine Tausendertrenner erlaubt';
        $checkProps['checkSubType_EN'] = '[X] No thousands separators permitted';
        $checkProps['checkSubRule'] = 'Bei diesem Kunden ist die Verwendung von Tausendertrennzeichen nicht erlaubt.';
        $checkProps['checkSubRule_EN'] = 'Thousands separators are not permitted for this customer.';
        $checkProps['hideInReportHeader'] = false;

        setCheckSubProps($checks, $checkProps);
    }

    $checkSubFunc = 'subCheck_alphanumStrings';

    $checkProps['checkSubFunc'] = $checkSubFunc;
    $checkProps['checkSubType'] = 'Alphanumerische Zeichenfolgen';
    $checkProps['checkSubType_EN'] = 'Alphanumerical character sequences';
    $checkProps['checkSubRule'] = 'Alphanumerische Zeichenfolgen müssen i.d.R. identisch sein.';
    $checkProps['checkSubRule_EN'] = 'Alphanumerical character sequences must generally be identical.';
    $checkProps['hideInReportHeader'] = false;
    setCheckSubProps($checks, $checkProps);

    $checkSubFunc = 'subCheck_dates';

    $checkProps['checkSubFunc'] = $checkSubFunc;
    $checkProps['checkSubType'] = 'Datumsangaben';
    $checkProps['checkSubType_EN'] = 'Alphanumerical character sequences';
    $checkProps['checkSubRule'] = 'Alphanumerische Zeichenfolgen müssen i.d.R. identisch sein.';
    $checkProps['checkSubRule_EN'] = 'Alphanumerical character sequences must generally be identical.';
    $checkProps['hideInReportHeader'] = false;
    setCheckSubProps($checks, $checkProps);
}

// verschiedene Functions, die für den Zahlencheck benötigt werden

/** helpers */
function countAndStripKiloSpaceNums($numsCount, &$checkSeg)
{
    $debug = false;

    // Tausender mit LZ als Trenner auslesen
    // Tausender aus Seg. entfernen sonst werden Teile als normale Zahlen erkannt

    $kiloSpaceNumRegEx = getNumTypeRegEx('fullSeg', 'kiloSpaceNum');

    for ($i = 0; $i < 2; $i++) {
        if (preg_match_all($kiloSpaceNumRegEx, $checkSeg[$i], $m)) {
            $matches = $m[0];
            usort($matches, 'sortByLength_desc');

            foreach ($matches as $match) {
                $num = $match;
                if (!isset($numsCount[$i][$num])) {
                    $numsCount[$i][$num] = 0;
                }
                $numsCount[$i][$num]++;

                $match = preg_quote($match);
                $checkSeg[$i] = preg_replace("!$match!", 'XX', $checkSeg[$i]);
            }
        }
    }

    return $numsCount;
}
function countAllOtherNums($numsCount, $checkSeg, $langs)
{
    $debug = false;

    // Andere Zahlen auslesen

    $numCharRegExClass = "[/\-\d,\.]";
    $divCharRegExClass = "[/\-,\.]";

    $splitNums[0] = [];
    $splitNums[1] = [];

    for ($i = 0; $i < 2; $i++) {
        if (preg_match_all("#((?<![\d\p{L}\p{Pd}])[+\-\p{Pd}])?$numCharRegExClass+#u", $checkSeg[$i], $m)) {
            foreach ($m[0] as $num) {
                if (!preg_match('!\d!u', $num)) {
                    continue;
                }

                $isInvalid = false;

                $invalidPatterns = [];
                $invalidPatterns[] = "!\.,!";
                $invalidPatterns[] = "!,\.!";
                $invalidPatterns[] = '!,{2,}!';
                $invalidPatterns[] = "!\.{2,}!";
                $invalidPatterns[] = "!\d{1,2},\d{1,2},\d{1,2}!";
                $invalidPatterns[] = "!\d{4,},\d{4,},\d{4,}!";

                foreach ($invalidPatterns as $pattern) {
                    if (preg_match($pattern, $num)) {
                        $splitNums[$i][] = $num;
                        //echo "splitNums \$i = $i, \$num = $num, \$pattern = $pattern\n";
                        //print_r($splitNums);
                        $isInvalid = true;
                        continue;
                    }
                }

                if ($isInvalid) {
                    continue;
                }

                $numsOrig[$i][] = $num;

                if ($debug) {
                    echo "numPreCleanUp: [$num] \n";
                }
                $num = preg_replace("!^$divCharRegExClass+!u", '', $num);
                $num = preg_replace("!$divCharRegExClass+$!u", '', $num);
                if ($debug) {
                    echo "numPostCleanUp: [$num] \n";
                }

                if (!isset($numsCount[$i][$num])) {
                    $numsCount[$i][$num] = 0;
                }
                $numsCount[$i][$num]++;
            }

            if ($debug) {
                echo "\n\$numsCount [\$i = {$i}]:\n";
            }
            if ($debug) {
                print_r($splitNums[$i]);
            }
            if ($debug) {
                echo "\n\$splitNums [\$i = {$i}]:\n";
            }
            if ($debug) {
                print_r($splitNums[$i]);
            }

            foreach ($splitNums[$i] as $num) {
                $partNums = preg_split('![\.,]!', $num, -1, PREG_SPLIT_NO_EMPTY);
                if ($debug) {
                    echo "\n\$partNums [\$i = {$i}]:\n";
                }
                if ($debug) {
                    print_r($partNums);
                }

                foreach ($partNums as $partNum) {
                    if (!isset($numsCount[$i][$partNum])) {
                        $numsCount[$i][$partNum] = 0;
                    }
                    $numsCount[$i][$partNum]++;
                    if ($debug) {
                        echo "\nnumsCount \$i == $i, \$partNum = $partNum \n";
                    }
                    if ($debug) {
                        print_r($numsCount[$i]);
                    }
                }
            }
        }
    }

    //print_r($numsCount);
    return $numsCount;
}
function getDecimalPointLangs()
{
    $sncLangData = getSncLangData();

    static $decimalPointLangs = [];

    if (!empty($decimalPointLangs)) {
        return $decimalPointLangs;
    }

    foreach ($sncLangData as $lang => $props) {
        if ($props['decimal_separator'] == 'Punkt') {
            $decimalPointLangs[] = $lang;
        }
    }

    return $decimalPointLangs;
}
function isAsianLang($lang)
{
    $asianLangRegEx = '!^(zh-TW|CHT|ch-CN|CHS|ko-KR|KOR|th-TH|THA|ja-JP|JAP)$!';

    if (preg_match($asianLangRegEx, $lang)) {
        return true;
    }

    return false;
}
function isDecimalPointLang($lang)
{
    $decimalPointLangs = getDecimalPointLangs();

    if (in_array($lang, $decimalPointLangs)) {
        return true;
    }

    return false;
}
function isMonoTypeDE($srcLang, $trgLang)
{
    $decimalPointLangs = getDecimalPointLangs();

    if ((!in_array($srcLang, $decimalPointLangs)) && (!in_array($trgLang, $decimalPointLangs))) {
        return true;
    }

    return false;
}
function isMonoTypeEN($srcLang, $trgLang)
{
    $decimalPointLangs = getDecimalPointLangs();
    if ((in_array($srcLang, $decimalPointLangs)) && (in_array($trgLang, $decimalPointLangs))) {
        return true;
    }

    return false;
}
function isMixedTypesDE2EN($srcLang, $trgLang)
{
    $decimalPointLangs = getDecimalPointLangs();

    if ((!in_array($srcLang, $decimalPointLangs)) && (in_array($trgLang, $decimalPointLangs))) {
        return true;
    }

    return false;
}
function isMixedTypesEN2DE($srcLang, $trgLang)
{
    $decimalPointLangs = getDecimalPointLangs();

    if ((in_array($srcLang, $decimalPointLangs)) && (!in_array($trgLang, $decimalPointLangs))) {
        return true;
    }

    return false;
}
function getNumTypeRegEx($mode, $type)
{
    if ($mode == 'numOnly') {
        if ($type == 'kiloNoDivNum') {
            $regEx = "!^[+-]?[1-9]\d{3,11}([\,\.]\d{1,3})?$!u";
        }

        if ($type == 'kiloSpaceNum') {
            $regEx = "!^[+-]?[1-9]\d{0,2}(\p{Zs}\d{3}){1,3}([\,\.]\d{1,3})?$!u";
        }
        if ($type == 'kiloPointNum') {
            $regEx = "!^[+-]?[1-9]\d{0,2}(\.\d{3}){1,3}([\,]\d{1,3})?$!u";
        }
        if ($type == 'kiloCommaNum') {
            $regEx = "!^[+-]?[1-9]\d{0,2}(\,\d{3}){1,3}([\.]\d{1,3})?$!u";
        }
        if ($type == 'deciCommaNum') {
            $regEx = "!^[+-]?([1-9]\d{1,3}|[0-9]),\d{1,8}$!u";
        }
        if ($type == 'deciPointNum') {
            $regEx = "!^[+-]?([1-9]\d{1,3}|[0-9])\.\d{1,8}$!u";
        }
    } elseif ($mode == 'fullSeg') {
        if ($type == 'kiloNoDivNum') {
            $regEx = "#(?<![\d\.\,])[+-]?[1-9]\d{3,11}([\,\.]\d{1,3})?(?![\d])#u";
        }

        if ($type == 'kiloSpaceNum') {
            $regEx = "#(?<![\d\.\,])[+-]?[1-9]\d{0,2}(\p{Zs}\d{3}){1,3}([\,\.]\d{1,3})?(?![\d])#u";
        }
        if ($type == 'kiloPointNum') {
            $regEx = "#(?<![\d\.,])[+-]?[1-9]\d{0,2}(\.\d{3}){1,3}([\,]\d{1,3})?(?![\d\.,])#u";
        }
        if ($type == 'kiloCommaNum') {
            $regEx = "#(?<![\d\.,])[+-]?[1-9]\d{0,2}(\,\d{3}){1,3}([\.]\d{1,3})?(?![\d\.,])#u";
        }
        if ($type == 'deciCommaNum') {
            $regEx = "#(?<![\d\.,])([+-]?[1-9]\d{1,3}|[0-9]),\d{1,8}(?![\d\.,])#u";
        }
        if ($type == 'deciPointNum') {
            $regEx = "#(?<![\d\.,])([+-]?[1-9]\d{1,3}|[0-9])\.\d{1,8}(?![\d\.,])#u";
        }
    } else {
        trigger_error("Mode $mode unbekannt. Abbruch.", E_USER_ERROR);
    }

    return $regEx;
}
function isKiloNoDivNum($num)
{
    $isKiloNoDivNum = false;
    $regEx = getNumTypeRegEx('numOnly', 'kiloNoDivNum');
    if (preg_match($regEx, $num)) {
        $isKiloNoDivNum = true;
    }

    return $isKiloNoDivNum;
}
function isKiloSpaceNum($num)
{
    $isKiloSpaceNum = false;
    $regEx = getNumTypeRegEx('numOnly', 'kiloSpaceNum');
    if (preg_match($regEx, $num)) {
        $isKiloSpaceNum = true;
    }

    return $isKiloSpaceNum;
}
function isKiloPointNum($num)
{
    $isKiloPointNum = false;
    $regEx = getNumTypeRegEx('numOnly', 'kiloPointNum');
    if (preg_match($regEx, $num)) {
        $isKiloPointNum = true;
    }

    return $isKiloPointNum;
}
function isKiloCommaNum($num)
{
    $isKiloCommaNum = false;
    $regEx = getNumTypeRegEx('numOnly', 'kiloCommaNum');
    if (preg_match($regEx, $num)) {
        $isKiloCommaNum = true;
    }

    return $isKiloCommaNum;
}
function isDeciCommaNum($num)
{
    $isDeciCommaNum = false;
    $regEx = getNumTypeRegEx('numOnly', 'deciCommaNum');
    if (preg_match($regEx, $num)) {
        $isDeciCommaNum = true;
    }

    return $isDeciCommaNum;
}
function isDeciPointNum($num)
{
    $isDeciPointNum = false;
    $regEx = getNumTypeRegEx('numOnly', 'deciPointNum');
    if (preg_match($regEx, $num)) {
        $isDeciPointNum = true;
    }

    return $isDeciPointNum;
}
function isRealNum($num)
{
    $debug = false;

    $isReal = false;
    $upperDeciLimit = 4;

    // Leerzeichen als 1000er-Trenner, Komma/Punkt als Dezimaltrenner
    $realRegExDE1 = "!^[+-]?[0-9]{1,3}(([\p{Zs}][0-9]{3})*(\,[0-9]{1,$upperDeciLimit})?|[0-9]*(\,[0-9]{1,$upperDeciLimit})?)$!u";
    $realRegExEN1 = "!^[+-]?[0-9]{1,3}(([\p{Zs}][0-9]{3})*(\.[0-9]{1,$upperDeciLimit})?|[0-9]*(\.[0-9]{1,$upperDeciLimit})?)$!u";

    // Punkt/Komma als 1000er-Trenner, Komma/Punkt als Dezimaltrenner
    $realRegExDE2 = "!^[+-]?[0-9]{1,3}(([\.][0-9]{3})*(\,[0-9]{1,$upperDeciLimit})?|[0-9]*(\,[0-9]{1,$upperDeciLimit})?)$!u";
    $realRegExEN2 = "!^[+-]?[0-9]{1,3}(([\,][0-9]{3})*(\.[0-9]{1,$upperDeciLimit})?|[0-9]*(\.[0-9]{1,$upperDeciLimit})?)$!u";

    if ((preg_match($realRegExDE1, $num)) ||
        (preg_match($realRegExEN1, $num)) ||
        (preg_match($realRegExDE2, $num)) ||
        (preg_match($realRegExEN2, $num))) {
        $isReal = true;
    }

    if (preg_match('!^0\d!u', $num)) {
        $isReal = false;
    }

    if ($debug) {
        if ($isReal) {
            echo "isReal inFunc: $num\n";
        }
    }

    return $isReal;
}
function isNonLokaNum($num, $checkSeg)
{
    $num_quote = preg_quote($num);

    if (preg_match_all("![A-Z]{$num_quote}!ui", $checkSeg[0]) == preg_match_all("!{$num_quote}!ui", $checkSeg[0])) {
        return true;
    }

    if (preg_match_all("!\({$num_quote}\)!ui", $checkSeg[0]) == preg_match_all("!{$num_quote}!ui", $checkSeg[0])) {
        return true;
    }

    if (preg_match_all("!#{$num_quote}!ui", $checkSeg[0]) == preg_match_all("!{$num_quote}!ui", $checkSeg[0])) {
        return true;
    }

    return false;
}
function isRealNum_lang($num, $lang)
{
    $isReal = false;

    $upperDeciLimit = 8;

    // Leerzeichen als 1000er-Trenner, Komma/Punkt als Dezimaltrenner
    if (!isDecimalPointLang($lang)) {
        $realRegEx1 = "!^[+-]?[0-9]{1,3}(([\p{Zs}][0-9]{3})*(\,[0-9]{1,$upperDeciLimit})?|[0-9]*(\,[0-9]{1,$upperDeciLimit})?)$!u";
    } else {
        $realRegEx1 = "!^[+-]?[0-9]{1,3}(([\p{Zs}][0-9]{3})*(\.[0-9]{1,$upperDeciLimit})?|[0-9]*(\.[0-9]{1,$upperDeciLimit})?)$!u";
    }

    // Punkt/Komma als 1000er-Trenner, Komma/Punkt als Dezimaltrenner
    if (!isDecimalPointLang($lang)) {
        $realRegEx2 = "!^[+-]?[0-9]{1,3}(([\.][0-9]{3})*(\,[0-9]{1,$upperDeciLimit})?|[0-9]*(\,[0-9]{1,$upperDeciLimit})?)$!u";
    } else {
        $realRegEx2 = "!^[+-]?[0-9]{1,3}(([\,][0-9]{3})*(\.[0-9]{1,$upperDeciLimit})?|[0-9]*(\.[0-9]{1,$upperDeciLimit})?)$!u";
    }

    if ((preg_match($realRegEx1, $num)) ||
    (preg_match($realRegEx2, $num))) {
        $isReal = true;
    }

    if (preg_match('!^0\d!u', $num)) {
        $isReal = false;
    }

    return $isReal;
}
function flipDiv($num)
{
    $num = str_replace(',', '__SNC_COMMA__', $num);
    $num = str_replace('.', ',', $num);
    $num = str_replace('__SNC_COMMA__', '.', $num);

    return $num;
}
function getNumWords($langs)
{
    $debug = false;

    // ausgeschriebene Zahlen festlegen 0 - 12
    // KEINE ANDEREN ZEICHEN ALS BUCHSTABEN IN DEN ZAHLWÖRTERN!!!
    // TODO: Hatscheks in manchen Sprachen prüfen

    $srcLang = $langs[0];
    $trgLang = $langs[1];

    $numWords = [];

    $numWords['de-DE'][0][] = 'null';
    $numWords['de-DE'][1][] = 'ein';
    $numWords['de-DE'][1][] = 'eins';
    $numWords['de-DE'][1][] = 'eines';
    $numWords['de-DE'][1][] = 'eine';
    $numWords['de-DE'][1][] = 'einen';
    $numWords['de-DE'][1][] = 'einem';
    $numWords['de-DE'][2][] = 'zwei';
    $numWords['de-DE'][3][] = 'drei';
    $numWords['de-DE'][4][] = 'vier';
    $numWords['de-DE'][5][] = 'fünf';
    $numWords['de-DE'][6][] = 'sechs';
    $numWords['de-DE'][7][] = 'sieben';
    $numWords['de-DE'][8][] = 'acht';
    $numWords['de-DE'][9][] = 'neun';
    $numWords['de-DE'][10][] = 'zehn';
    $numWords['de-DE'][11][] = 'elf';
    $numWords['de-DE'][12][] = 'zwölf';

    $numWords['de-AT'][0][] = 'null';
    $numWords['de-AT'][1][] = 'ein';
    $numWords['de-AT'][1][] = 'eins';
    $numWords['de-AT'][1][] = 'eine';
    $numWords['de-AT'][1][] = 'eines';
    $numWords['de-AT'][1][] = 'einen';
    $numWords['de-AT'][1][] = 'einem';
    $numWords['de-AT'][2][] = 'zwei';
    $numWords['de-AT'][3][] = 'drei';
    $numWords['de-AT'][4][] = 'vier';
    $numWords['de-AT'][5][] = 'fünf';
    $numWords['de-AT'][6][] = 'sechs';
    $numWords['de-AT'][7][] = 'sieben';
    $numWords['de-AT'][8][] = 'acht';
    $numWords['de-AT'][9][] = 'neun';
    $numWords['de-AT'][10][] = 'zehn';
    $numWords['de-AT'][11][] = 'elf';
    $numWords['de-AT'][12][] = 'zwölf';

    $numWords['bg-BG'][0][] = 'нула';
    $numWords['bg-BG'][0][] = 'nula';
    $numWords['bg-BG'][1][] = 'едно';
    $numWords['bg-BG'][1][] = 'edno';
    $numWords['bg-BG'][2][] = 'две';
    $numWords['bg-BG'][2][] = 'dve';
    $numWords['bg-BG'][3][] = 'три';
    $numWords['bg-BG'][3][] = 'tri';
    $numWords['bg-BG'][4][] = 'четири';
    $numWords['bg-BG'][4][] = 'četiri';
    $numWords['bg-BG'][5][] = 'пет';
    $numWords['bg-BG'][5][] = 'pet';
    $numWords['bg-BG'][6][] = 'шест';
    $numWords['bg-BG'][6][] = 'šest';
    $numWords['bg-BG'][7][] = 'седем';
    $numWords['bg-BG'][7][] = 'sedem';
    $numWords['bg-BG'][8][] = 'осем';
    $numWords['bg-BG'][8][] = 'osem';
    $numWords['bg-BG'][9][] = 'девет';
    $numWords['bg-BG'][9][] = 'devet';
    $numWords['bg-BG'][10][] = 'десет';
    $numWords['bg-BG'][10][] = 'deset';
    $numWords['bg-BG'][11][] = 'единадесет';
    $numWords['bg-BG'][11][] = 'edinadeset';
    $numWords['bg-BG'][12][] = 'dvanadeset';
    $numWords['bg-BG'][12][] = 'dvanajst';

    $numWords['cs-CZ'][0][] = 'nula';
    $numWords['cs-CZ'][1][] = 'jeden';
    $numWords['cs-CZ'][1][] = 'jedna';
    $numWords['cs-CZ'][1][] = 'jedno';
    $numWords['cs-CZ'][2][] = 'dva';
    $numWords['cs-CZ'][2][] = 'dvě';
    $numWords['cs-CZ'][3][] = 'tři';
    $numWords['cs-CZ'][4][] = 'čtyři';
    $numWords['cs-CZ'][5][] = 'pět';
    $numWords['cs-CZ'][6][] = 'šest';
    $numWords['cs-CZ'][7][] = 'sedm';
    $numWords['cs-CZ'][8][] = 'osm';
    $numWords['cs-CZ'][9][] = 'devět';
    $numWords['cs-CZ'][10][] = 'deset';
    $numWords['cs-CZ'][11][] = 'jedenáct';
    $numWords['cs-CZ'][12][] = 'dvanáct';

    $numWords['ja-JP'][0][] = '〇';
    $numWords['ja-JP'][0][] = '零';
    $numWords['ja-JP'][1][] = '一';
    $numWords['ja-JP'][2][] = '二';
    $numWords['ja-JP'][3][] = '三';
    $numWords['ja-JP'][4][] = '四';
    $numWords['ja-JP'][5][] = '五';
    $numWords['ja-JP'][6][] = '六';
    $numWords['ja-JP'][7][] = '七';
    $numWords['ja-JP'][8][] = '八';
    $numWords['ja-JP'][9][] = '九';
    $numWords['ja-JP'][10][] = '十';

    $numWords['ko-KR'][0][] = '〇';
    $numWords['ko-KR'][0][] = '零';
    $numWords['ko-KR'][1][] = '一';
    $numWords['ko-KR'][2][] = '二';
    $numWords['ko-KR'][3][] = '三';
    $numWords['ko-KR'][4][] = '四';
    $numWords['ko-KR'][5][] = '五';
    $numWords['ko-KR'][6][] = '六';
    $numWords['ko-KR'][7][] = '七';
    $numWords['ko-KR'][8][] = '八';
    $numWords['ko-KR'][9][] = '九';
    $numWords['ko-KR'][10][] = '十';

    $numWords['th-TH'][0][] = ' ๐ ';
    $numWords['th-TH'][1][] = '๑';
    $numWords['th-TH'][2][] = '๒';
    $numWords['th-TH'][3][] = '๓';
    $numWords['th-TH'][4][] = '๔';
    $numWords['th-TH'][5][] = '๕';
    $numWords['th-TH'][6][] = '๖';
    $numWords['th-TH'][7][] = '๗';
    $numWords['th-TH'][8][] = '๘';
    $numWords['th-TH'][9][] = '๙';

    $numWords['zh-CN'][0][] = '〇';
    $numWords['zh-CN'][0][] = '零';
    $numWords['zh-CN'][1][] = '一';
    $numWords['zh-CN'][2][] = '二';
    $numWords['zh-CN'][3][] = '三';
    $numWords['zh-CN'][4][] = '四';
    $numWords['zh-CN'][5][] = '五';
    $numWords['zh-CN'][6][] = '六';
    $numWords['zh-CN'][7][] = '七';
    $numWords['zh-CN'][8][] = '八';
    $numWords['zh-CN'][9][] = '九';
    $numWords['zh-CN'][10][] = '十';

    $numWords['zh-TW'][0][] = '〇';
    $numWords['zh-TW'][0][] = '零';
    $numWords['zh-TW'][1][] = '一';
    $numWords['zh-TW'][2][] = '二';
    $numWords['zh-TW'][3][] = '三';
    $numWords['zh-TW'][4][] = '四';
    $numWords['zh-TW'][5][] = '五';
    $numWords['zh-TW'][6][] = '六';
    $numWords['zh-TW'][7][] = '七';
    $numWords['zh-TW'][8][] = '八';
    $numWords['zh-TW'][9][] = '九';
    $numWords['zh-TW'][10][] = '十';

    $numWords['da-DK'][0][] = 'nul';
    $numWords['da-DK'][1][] = 'en';
    $numWords['da-DK'][1][] = 'et';
    $numWords['da-DK'][2][] = 'to';
    $numWords['da-DK'][3][] = 'tre';
    $numWords['da-DK'][4][] = 'fire';
    $numWords['da-DK'][5][] = 'fem';
    $numWords['da-DK'][6][] = 'seks';
    $numWords['da-DK'][7][] = 'syv';
    $numWords['da-DK'][8][] = 'otte';
    $numWords['da-DK'][9][] = 'ni';
    $numWords['da-DK'][10][] = 'ti';
    $numWords['da-DK'][11][] = 'elleve';
    $numWords['da-DK'][12][] = 'tolv';

    $numWords['el-GR'][0][] = 'μηδέν';
    $numWords['el-GR'][0][] = 'midén';
    $numWords['el-GR'][1][] = 'ένα';
    $numWords['el-GR'][1][] = 'éna';
    $numWords['el-GR'][2][] = 'δύο';
    $numWords['el-GR'][2][] = 'dío';
    $numWords['el-GR'][3][] = 'τρία';
    $numWords['el-GR'][3][] = 'tría';
    $numWords['el-GR'][4][] = 'τέσσερα';
    $numWords['el-GR'][4][] = 'téssera';
    $numWords['el-GR'][5][] = 'πέντε';
    $numWords['el-GR'][5][] = 'pénte';
    $numWords['el-GR'][6][] = 'έξι';
    $numWords['el-GR'][6][] = 'éxi';
    $numWords['el-GR'][7][] = 'εφτά';
    $numWords['el-GR'][7][] = 'eftá';
    $numWords['el-GR'][8][] = 'οχτώ';
    $numWords['el-GR'][8][] = 'ochtó';
    $numWords['el-GR'][9][] = 'εννιά';
    $numWords['el-GR'][9][] = 'enniá';
    $numWords['el-GR'][10][] = 'δέκα';
    $numWords['el-GR'][10][] = 'déka';
    $numWords['el-GR'][11][] = 'έντεκα';
    $numWords['el-GR'][11][] = 'éndeka';
    $numWords['el-GR'][12][] = 'δώδεκα';
    $numWords['el-GR'][12][] = 'dódeka';

    $numWords['en-GB'][0][] = 'zero';
    $numWords['en-GB'][1][] = 'one';
    $numWords['en-GB'][2][] = 'two';
    $numWords['en-GB'][3][] = 'three';
    $numWords['en-GB'][4][] = 'four';
    $numWords['en-GB'][5][] = 'five';
    $numWords['en-GB'][6][] = 'six';
    $numWords['en-GB'][7][] = 'seven';
    $numWords['en-GB'][8][] = 'eight';
    $numWords['en-GB'][9][] = 'nine';
    $numWords['en-GB'][10][] = 'ten';
    $numWords['en-GB'][11][] = 'eleven';
    $numWords['en-GB'][12][] = 'twelve';

    $numWords['en-US'][0][] = 'zero';
    $numWords['en-US'][1][] = 'one';
    $numWords['en-US'][2][] = 'two';
    $numWords['en-US'][3][] = 'three';
    $numWords['en-US'][4][] = 'four';
    $numWords['en-US'][5][] = 'five';
    $numWords['en-US'][6][] = 'six';
    $numWords['en-US'][7][] = 'seven';
    $numWords['en-US'][8][] = 'eight';
    $numWords['en-US'][9][] = 'nine';
    $numWords['en-US'][10][] = 'ten';
    $numWords['en-US'][11][] = 'eleven';
    $numWords['en-US'][12][] = 'twelve';

    $numWords['es-ES'][0][] = 'cero';
    $numWords['es-ES'][1][] = 'un';
    $numWords['es-ES'][1][] = 'uno';
    $numWords['es-ES'][1][] = 'una';
    $numWords['es-ES'][2][] = 'dos';
    $numWords['es-ES'][3][] = 'tres';
    $numWords['es-ES'][4][] = 'cuatro';
    $numWords['es-ES'][5][] = 'cinco';
    $numWords['es-ES'][6][] = 'seis';
    $numWords['es-ES'][7][] = 'siete';
    $numWords['es-ES'][8][] = 'ocho';
    $numWords['es-ES'][9][] = 'nueve';
    $numWords['es-ES'][10][] = 'diez';
    $numWords['es-ES'][11][] = 'once';
    $numWords['es-ES'][12][] = 'doce';

    $numWords['et-EE'][0][] = 'null';
    $numWords['et-EE'][1][] = 'üks';
    $numWords['et-EE'][2][] = 'kaks';
    $numWords['et-EE'][3][] = 'kolm';
    $numWords['et-EE'][4][] = 'neli';
    $numWords['et-EE'][5][] = 'viis';
    $numWords['et-EE'][6][] = 'kuus';
    $numWords['et-EE'][7][] = 'seitse';
    $numWords['et-EE'][8][] = 'kaheksa';
    $numWords['et-EE'][9][] = 'üheksa';
    $numWords['et-EE'][10][] = 'kümme';
    $numWords['et-EE'][11][] = 'üksteist';
    $numWords['et-EE'][12][] = 'kaksteist';

    $numWords['fi-FI'][0][] = 'nolla';
    $numWords['fi-FI'][1][] = 'yksi';
    $numWords['fi-FI'][2][] = 'kaksi';
    $numWords['fi-FI'][3][] = 'kolme';
    $numWords['fi-FI'][4][] = 'neljä';
    $numWords['fi-FI'][5][] = 'viisi';
    $numWords['fi-FI'][6][] = 'kuusi';
    $numWords['fi-FI'][7][] = 'seitsemän';
    $numWords['fi-FI'][8][] = 'kahdeksan';
    $numWords['fi-FI'][9][] = 'yhdeksän';
    $numWords['fi-FI'][10][] = 'kymmenen';
    $numWords['fi-FI'][11][] = 'yksitoista';
    $numWords['fi-FI'][12][] = 'kaksitoista';

    $numWords['fr-FR'][0][] = 'zéro';
    $numWords['fr-FR'][1][] = 'un';
    $numWords['fr-FR'][1][] = 'une';
    $numWords['fr-FR'][2][] = 'deux';
    $numWords['fr-FR'][3][] = 'trois';
    $numWords['fr-FR'][4][] = 'quatre';
    $numWords['fr-FR'][5][] = 'cinq';
    $numWords['fr-FR'][6][] = 'six';
    $numWords['fr-FR'][7][] = 'sept';
    $numWords['fr-FR'][8][] = 'huit';
    $numWords['fr-FR'][9][] = 'neuf';
    $numWords['fr-FR'][10][] = 'dix';
    $numWords['fr-FR'][11][] = 'onze';
    $numWords['fr-FR'][12][] = 'douze';

    $numWords['hr-HR'][0][] = 'nula';
    $numWords['hr-HR'][1][] = 'jedan';
    $numWords['hr-HR'][1][] = 'jedna';
    $numWords['hr-HR'][1][] = 'jedno';
    $numWords['hr-HR'][2][] = 'dva';
    $numWords['hr-HR'][2][] = 'dvije';
    $numWords['hr-HR'][3][] = 'tri';
    $numWords['hr-HR'][4][] = 'četiri';
    $numWords['hr-HR'][5][] = 'pet';
    $numWords['hr-HR'][6][] = 'šest';
    $numWords['hr-HR'][7][] = 'sedam';
    $numWords['hr-HR'][8][] = 'osam';
    $numWords['hr-HR'][9][] = 'devet';
    $numWords['hr-HR'][10][] = 'deset';
    $numWords['hr-HR'][11][] = 'jedanaest';
    $numWords['hr-HR'][12][] = 'dvanaest';

    $numWords['hu-HU'][0][] = 'nulla';
    $numWords['hu-HU'][1][] = 'egy';
    $numWords['hu-HU'][2][] = 'kettő';
    $numWords['hu-HU'][3][] = 'három';
    $numWords['hu-HU'][4][] = 'négy';
    $numWords['hu-HU'][5][] = 'öt';
    $numWords['hu-HU'][6][] = 'hat';
    $numWords['hu-HU'][7][] = 'hét';
    $numWords['hu-HU'][8][] = 'nyolc';
    $numWords['hu-HU'][9][] = 'kilenc';
    $numWords['hu-HU'][10][] = 'tíz';
    $numWords['hu-HU'][11][] = 'tizenegy';
    $numWords['hu-HU'][12][] = 'tizenkettő';

    $numWords['it-IT'][0][] = 'zero';
    $numWords['it-IT'][1][] = 'uno';
    $numWords['it-IT'][2][] = 'due';
    $numWords['it-IT'][3][] = 'tre';
    $numWords['it-IT'][4][] = 'quattro';
    $numWords['it-IT'][5][] = 'cinque';
    $numWords['it-IT'][6][] = 'sei';
    $numWords['it-IT'][7][] = 'sette';
    $numWords['it-IT'][8][] = 'otto';
    $numWords['it-IT'][9][] = 'nove';
    $numWords['it-IT'][10][] = 'dieci';
    $numWords['it-IT'][11][] = 'undici';
    $numWords['it-IT'][12][] = 'dodici';

    $numWords['lt-LT'][0][] = 'nulis';
    $numWords['lt-LT'][1][] = 'vienas';
    $numWords['lt-LT'][1][] = 'vienà';
    $numWords['lt-LT'][2][] = 'dù';
    $numWords['lt-LT'][2][] = 'dvì';
    $numWords['lt-LT'][3][] = 'trys';
    $numWords['lt-LT'][4][] = 'keturì';
    $numWords['lt-LT'][4][] = 'keturios';
    $numWords['lt-LT'][5][] = 'penkì';
    $numWords['lt-LT'][5][] = 'peñkios';
    $numWords['lt-LT'][6][] = 'šešì';
    $numWords['lt-LT'][6][] = 'šešios';
    $numWords['lt-LT'][7][] = 'septynì';
    $numWords['lt-LT'][7][] = 'septýnios';
    $numWords['lt-LT'][8][] = 'aštuonì';
    $numWords['lt-LT'][8][] = 'aštúonios';
    $numWords['lt-LT'][9][] = 'devynì';
    $numWords['lt-LT'][9][] = 'devýnios';
    $numWords['lt-LT'][10][] = 'dešimt';
    $numWords['lt-LT'][11][] = 'vienúolika';
    $numWords['lt-LT'][12][] = 'dvýlika';

    $numWords['lv-LV'][0][] = 'nulle';
    $numWords['lv-LV'][1][] = 'viens';
    $numWords['lv-LV'][1][] = 'vienas';
    $numWords['lv-LV'][2][] = 'divi';
    $numWords['lv-LV'][2][] = 'divas';
    $numWords['lv-LV'][3][] = 'trīs';
    $numWords['lv-LV'][4][] = 'četri';
    $numWords['lv-LV'][4][] = 'četras';
    $numWords['lv-LV'][5][] = 'pieci';
    $numWords['lv-LV'][5][] = 'piecas';
    $numWords['lv-LV'][6][] = 'seši';
    $numWords['lv-LV'][6][] = 'sešas';
    $numWords['lv-LV'][7][] = 'septiņi';
    $numWords['lv-LV'][7][] = 'septiņas';
    $numWords['lv-LV'][8][] = 'astoņi';
    $numWords['lv-LV'][8][] = 'astoņas';
    $numWords['lv-LV'][9][] = 'deviņi';
    $numWords['lv-LV'][9][] = 'deviņas';
    $numWords['lv-LV'][10][] = 'desmit';
    $numWords['lv-LV'][11][] = 'vienpadsmit';
    $numWords['lv-LV'][12][] = 'divpadsmit';

    $numWords['nb-NO'][0][] = 'null';
    $numWords['nb-NO'][1][] = 'en';
    $numWords['nb-NO'][1][] = 'ett';
    $numWords['nb-NO'][2][] = 'to';
    $numWords['nb-NO'][3][] = 'tre';
    $numWords['nb-NO'][4][] = 'fire';
    $numWords['nb-NO'][5][] = 'fem';
    $numWords['nb-NO'][6][] = 'seks';
    $numWords['nb-NO'][7][] = 'syv';
    $numWords['nb-NO'][7][] = 'sju';
    $numWords['nb-NO'][8][] = 'åtte';
    $numWords['nb-NO'][9][] = 'ni';
    $numWords['nb-NO'][10][] = 'ti';
    $numWords['nb-NO'][11][] = 'elleve';
    $numWords['nb-NO'][12][] = 'tolv';

    $numWords['nl-NL'][0][] = 'nul';
    $numWords['nl-NL'][1][] = 'een';
    $numWords['nl-NL'][2][] = 'twee';
    $numWords['nl-NL'][3][] = 'drie';
    $numWords['nl-NL'][4][] = 'vier';
    $numWords['nl-NL'][5][] = 'vijf';
    $numWords['nl-NL'][6][] = 'zes';
    $numWords['nl-NL'][7][] = 'zeven';
    $numWords['nl-NL'][8][] = 'acht';
    $numWords['nl-NL'][9][] = 'negen';
    $numWords['nl-NL'][10][] = 'tien';
    $numWords['nl-NL'][11][] = 'elf';
    $numWords['nl-NL'][12][] = 'twaalf';

    $numWords['pl-PL'][0][] = 'zero';
    $numWords['pl-PL'][1][] = 'jeden';
    $numWords['pl-PL'][1][] = 'jedna';
    $numWords['pl-PL'][1][] = 'jedno';
    $numWords['pl-PL'][2][] = 'dwa';
    $numWords['pl-PL'][2][] = 'dwie';
    $numWords['pl-PL'][3][] = 'trzy';
    $numWords['pl-PL'][4][] = 'cztery';
    $numWords['pl-PL'][5][] = 'pięć';
    $numWords['pl-PL'][6][] = 'sześć';
    $numWords['pl-PL'][7][] = 'siedem';
    $numWords['pl-PL'][8][] = 'osiem';
    $numWords['pl-PL'][9][] = 'dziewięć';
    $numWords['pl-PL'][10][] = 'dziesięć';
    $numWords['pl-PL'][11][] = 'jedenaście';
    $numWords['pl-PL'][12][] = 'dwanaście';

    $numWords['pt-BR'][0][] = 'zero';
    $numWords['pt-BR'][1][] = 'um';
    $numWords['pt-BR'][2][] = 'dois';
    $numWords['pt-BR'][2][] = 'duas';
    $numWords['pt-BR'][3][] = 'três';
    $numWords['pt-BR'][4][] = 'quatro';
    $numWords['pt-BR'][5][] = 'cinco';
    $numWords['pt-BR'][6][] = 'seis';
    $numWords['pt-BR'][7][] = 'sete';
    $numWords['pt-BR'][8][] = 'oito';
    $numWords['pt-BR'][9][] = 'nove';
    $numWords['pt-BR'][10][] = 'dez';
    $numWords['pt-BR'][11][] = 'onze';
    $numWords['pt-BR'][12][] = 'doze';

    $numWords['pt-PT'][0][] = 'zero';
    $numWords['pt-PT'][1][] = 'um';
    $numWords['pt-PT'][2][] = 'dois';
    $numWords['pt-PT'][2][] = 'duas';
    $numWords['pt-PT'][3][] = 'três';
    $numWords['pt-PT'][4][] = 'quatro';
    $numWords['pt-PT'][5][] = 'cinco';
    $numWords['pt-PT'][6][] = 'seis';
    $numWords['pt-PT'][7][] = 'sete';
    $numWords['pt-PT'][8][] = 'oito';
    $numWords['pt-PT'][9][] = 'nove';
    $numWords['pt-PT'][10][] = 'dez';
    $numWords['pt-PT'][11][] = 'onze';
    $numWords['pt-PT'][12][] = 'doze';

    $numWords['ro-RO'][0][] = 'zero';
    $numWords['ro-RO'][1][] = 'unu';
    $numWords['ro-RO'][2][] = 'doi';
    $numWords['ro-RO'][3][] = 'trei';
    $numWords['ro-RO'][4][] = 'patru';
    $numWords['ro-RO'][5][] = 'cinci';
    $numWords['ro-RO'][6][] = 'șase';
    $numWords['ro-RO'][7][] = 'șapte';
    $numWords['ro-RO'][8][] = 'opt';
    $numWords['ro-RO'][9][] = 'nouă';
    $numWords['ro-RO'][10][] = 'zece';
    $numWords['ro-RO'][11][] = 'unsprezece ';
    $numWords['ro-RO'][12][] = 'doisprezece ';

    $numWords['ru-RU'][0][] = 'ноль';
    $numWords['ru-RU'][0][] = 'нуль';
    $numWords['ru-RU'][0][] = 'nol';
    $numWords['ru-RU'][0][] = 'nul';
    $numWords['ru-RU'][1][] = 'один';
    $numWords['ru-RU'][1][] = 'одна';
    $numWords['ru-RU'][1][] = 'одно';
    $numWords['ru-RU'][1][] = 'odin';
    $numWords['ru-RU'][1][] = 'odna';
    $numWords['ru-RU'][1][] = 'odno';
    $numWords['ru-RU'][2][] = 'два';
    $numWords['ru-RU'][2][] = 'две';
    $numWords['ru-RU'][2][] = 'dva';
    $numWords['ru-RU'][2][] = 'dve';
    $numWords['ru-RU'][3][] = 'три';
    $numWords['ru-RU'][3][] = 'tri';
    $numWords['ru-RU'][4][] = 'четыре';
    $numWords['ru-RU'][4][] = 'četyre';
    $numWords['ru-RU'][5][] = 'пять';
    $numWords['ru-RU'][5][] = 'pjat';
    $numWords['ru-RU'][6][] = 'шесть';
    $numWords['ru-RU'][6][] = 'šest';
    $numWords['ru-RU'][7][] = 'семь';
    $numWords['ru-RU'][7][] = 'sem';
    $numWords['ru-RU'][8][] = 'восемь';
    $numWords['ru-RU'][8][] = 'vosem';
    $numWords['ru-RU'][9][] = 'девять';
    $numWords['ru-RU'][9][] = 'devjat';
    $numWords['ru-RU'][10][] = 'десять';
    $numWords['ru-RU'][10][] = 'desjat';
    $numWords['ru-RU'][11][] = 'одиннадцать';
    $numWords['ru-RU'][11][] = 'odinnadcat';
    $numWords['ru-RU'][12][] = 'двенадцать';
    $numWords['ru-RU'][12][] = 'dvenadcat';

    $numWords['sk-SK'][0][] = 'nula';
    $numWords['sk-SK'][1][] = 'jeden';
    $numWords['sk-SK'][1][] = 'jedna';
    $numWords['sk-SK'][1][] = 'jedno';
    $numWords['sk-SK'][1][] = 'jednu';
    $numWords['sk-SK'][2][] = 'dva';
    $numWords['sk-SK'][2][] = 'dvaja';
    $numWords['sk-SK'][2][] = 'dve';
    $numWords['sk-SK'][3][] = 'tri';
    $numWords['sk-SK'][3][] = 'traja ';
    $numWords['sk-SK'][4][] = 'štyri';
    $numWords['sk-SK'][4][] = 'štyria';
    $numWords['sk-SK'][5][] = 'päť';
    $numWords['sk-SK'][6][] = 'šesť';
    $numWords['sk-SK'][7][] = 'sedem';
    $numWords['sk-SK'][8][] = 'osem';
    $numWords['sk-SK'][9][] = 'deväť';
    $numWords['sk-SK'][10][] = 'desať';
    $numWords['sk-SK'][11][] = 'jedenásť';
    $numWords['sk-SK'][12][] = 'dvanásť';

    $numWords['sl-SI'][0][] = 'nič';
    $numWords['sl-SI'][1][] = 'ena';
    $numWords['sl-SI'][2][] = 'dva';
    $numWords['sl-SI'][3][] = 'tri';
    $numWords['sl-SI'][4][] = 'štiri';
    $numWords['sl-SI'][5][] = 'pet';
    $numWords['sl-SI'][6][] = 'šest';
    $numWords['sl-SI'][7][] = 'sedem';
    $numWords['sl-SI'][8][] = 'osem';
    $numWords['sl-SI'][9][] = 'devet';
    $numWords['sl-SI'][10][] = 'deset';
    $numWords['sl-SI'][11][] = 'enajst';
    $numWords['sl-SI'][12][] = 'dvanajst';

    $numWords['sr-Latn-CS'][0][] = 'nula';
    $numWords['sr-Latn-CS'][1][] = 'jedan';
    $numWords['sr-Latn-CS'][1][] = 'jedna';
    $numWords['sr-Latn-CS'][1][] = 'jedno';
    $numWords['sr-Latn-CS'][2][] = 'dva';
    $numWords['sr-Latn-CS'][2][] = 'dvije';
    $numWords['sr-Latn-CS'][3][] = 'tri';
    $numWords['sr-Latn-CS'][4][] = 'četiri';
    $numWords['sr-Latn-CS'][5][] = 'pet';
    $numWords['sr-Latn-CS'][6][] = 'šest';
    $numWords['sr-Latn-CS'][7][] = 'sedam';
    $numWords['sr-Latn-CS'][8][] = 'osam';
    $numWords['sr-Latn-CS'][9][] = 'devet';
    $numWords['sr-Latn-CS'][10][] = 'deset';
    $numWords['sr-Latn-CS'][11][] = 'jedanaest';
    $numWords['sr-Latn-CS'][12][] = 'dvanaest';

    $numWords['sv-SE'][0][] = 'noll';
    $numWords['sv-SE'][1][] = 'en';
    $numWords['sv-SE'][1][] = 'ett';
    $numWords['sv-SE'][2][] = 'två';
    $numWords['sv-SE'][3][] = 'tre';
    $numWords['sv-SE'][4][] = 'fyra';
    $numWords['sv-SE'][5][] = 'fem';
    $numWords['sv-SE'][6][] = 'sex';
    $numWords['sv-SE'][7][] = 'sju';
    $numWords['sv-SE'][8][] = 'åtta';
    $numWords['sv-SE'][9][] = 'nio';
    $numWords['sv-SE'][10][] = 'tio';
    $numWords['sv-SE'][11][] = 'elva';
    $numWords['sv-SE'][12][] = 'tolv';

    $numWords['tr-TR'][0][] = 'sıfır';
    $numWords['tr-TR'][1][] = 'bir';
    $numWords['tr-TR'][2][] = 'iki';
    $numWords['tr-TR'][3][] = 'üç';
    $numWords['tr-TR'][4][] = 'dört';
    $numWords['tr-TR'][5][] = 'beş';
    $numWords['tr-TR'][6][] = 'altı';
    $numWords['tr-TR'][7][] = 'yedi';
    $numWords['tr-TR'][8][] = 'sekiz';
    $numWords['tr-TR'][9][] = 'dokuz';
    $numWords['tr-TR'][10][] = 'on';
    $numWords['tr-TR'][11][] = 'on bir';
    $numWords['tr-TR'][12][] = 'on iki';

    $numWords['uk-UA'][0][] = 'нуль';
    $numWords['uk-UA'][0][] = 'nol';
    $numWords['uk-UA'][0][] = 'nul';
    $numWords['uk-UA'][1][] = 'один';
    $numWords['uk-UA'][1][] = 'одна';
    $numWords['uk-UA'][1][] = 'одне';
    $numWords['uk-UA'][1][] = 'odyn';
    $numWords['uk-UA'][1][] = 'odna';
    $numWords['uk-UA'][1][] = 'odne';
    $numWords['uk-UA'][2][] = 'два';
    $numWords['uk-UA'][2][] = 'дві';
    $numWords['uk-UA'][2][] = 'dva';
    $numWords['uk-UA'][2][] = 'dvi';
    $numWords['uk-UA'][3][] = 'три';
    $numWords['uk-UA'][3][] = 'try';
    $numWords['uk-UA'][4][] = 'чотири';
    $numWords['uk-UA'][4][] = 'čotyry';
    $numWords['uk-UA'][5][] = 'пять';
    $numWords['uk-UA'][5][] = 'pjat';
    $numWords['uk-UA'][6][] = 'шість';
    $numWords['uk-UA'][6][] = 'šist';
    $numWords['uk-UA'][7][] = 'сімь';
    $numWords['uk-UA'][7][] = 'sim';
    $numWords['uk-UA'][8][] = 'вісімь';
    $numWords['uk-UA'][8][] = 'visem';
    $numWords['uk-UA'][9][] = 'девять';
    $numWords['uk-UA'][9][] = 'devjat';
    $numWords['uk-UA'][10][] = 'десять';
    $numWords['uk-UA'][10][] = 'desjat';
    $numWords['uk-UA'][11][] = 'одиннадцять';
    $numWords['uk-UA'][11][] = 'odynnadcjat';
    $numWords['uk-UA'][12][] = 'дванадцять';
    $numWords['uk-UA'][12][] = 'dvanadcjat';

    $numWordsOut = [];

    if (isset($numWords[$srcLang])) {
        $numWordsOut[0] = $numWords[$srcLang];
    }

    if (isset($numWords[$trgLang])) {
        $numWordsOut[1] = $numWords[$trgLang];
    }

    if ($debug) {
        echo "numWordsOut: \n";
        print_r($numWordsOut);
    }

    return $numWordsOut;
}
function getMonthWords($langs)
{
    $debug = false;

    $srcLang = $langs[0];
    $trgLang = $langs[1];

    $monthWords = [];
    $monthWordsOut = [];

    $monthWords['de-DE'][1][] = 'Januar';
    $monthWords['de-DE'][1][] = 'Jan.';
    $monthWords['de-DE'][1][] = 'Jan';
    $monthWords['de-DE'][2][] = 'Februar';
    $monthWords['de-DE'][2][] = 'Feb.';
    $monthWords['de-DE'][2][] = 'Feb';
    $monthWords['de-DE'][3][] = 'März';
    $monthWords['de-DE'][4][] = 'April';
    $monthWords['de-DE'][4][] = 'Apr.';
    $monthWords['de-DE'][4][] = 'Apr';
    $monthWords['de-DE'][5][] = 'Mai';
    $monthWords['de-DE'][6][] = 'Juni';
    $monthWords['de-DE'][6][] = 'Jun.';
    $monthWords['de-DE'][6][] = 'Jun';
    $monthWords['de-DE'][7][] = 'Juli';
    $monthWords['de-DE'][7][] = 'Jul.';
    $monthWords['de-DE'][7][] = 'Jul';
    $monthWords['de-DE'][8][] = 'August';
    $monthWords['de-DE'][8][] = 'Aug.';
    $monthWords['de-DE'][8][] = 'Aug';
    $monthWords['de-DE'][9][] = 'September';
    $monthWords['de-DE'][9][] = 'Sept.';
    $monthWords['de-DE'][9][] = 'Sept';
    $monthWords['de-DE'][9][] = 'Sep.';
    $monthWords['de-DE'][9][] = 'Sep';
    $monthWords['de-DE'][10][] = 'Oktober';
    $monthWords['de-DE'][10][] = 'Okt.';
    $monthWords['de-DE'][10][] = 'Okt';
    $monthWords['de-DE'][11][] = 'November';
    $monthWords['de-DE'][11][] = 'Nov.';
    $monthWords['de-DE'][11][] = 'Nov';
    $monthWords['de-DE'][12][] = 'Dezember';
    $monthWords['de-DE'][12][] = 'Dez.';
    $monthWords['de-DE'][12][] = 'Dez';

    $monthWords['en-GB'][1][] = 'January';
    $monthWords['en-GB'][1][] = 'Jan.';
    $monthWords['en-GB'][1][] = 'Jan';
    $monthWords['en-GB'][2][] = 'February';
    $monthWords['en-GB'][2][] = 'Feb.';
    $monthWords['en-GB'][2][] = 'Feb';
    $monthWords['en-GB'][3][] = 'March';
    $monthWords['en-GB'][3][] = 'Mar.';
    $monthWords['en-GB'][3][] = 'Mar';
    $monthWords['en-GB'][4][] = 'April';
    $monthWords['en-GB'][4][] = 'Apr.';
    $monthWords['en-GB'][4][] = 'Apr';
    $monthWords['en-GB'][5][] = 'May';
    $monthWords['en-GB'][6][] = 'June';
    $monthWords['en-GB'][6][] = 'Jun.';
    $monthWords['en-GB'][6][] = 'Jun';
    $monthWords['en-GB'][7][] = 'July';
    $monthWords['en-GB'][7][] = 'Jul.';
    $monthWords['en-GB'][7][] = 'Jul';
    $monthWords['en-GB'][8][] = 'August';
    $monthWords['en-GB'][8][] = 'Aug.';
    $monthWords['en-GB'][8][] = 'Aug';
    $monthWords['en-GB'][9][] = 'September';
    $monthWords['en-GB'][9][] = 'Sept.';
    $monthWords['en-GB'][9][] = 'Sept';
    $monthWords['en-GB'][10][] = 'October';
    $monthWords['en-GB'][10][] = 'Oct.';
    $monthWords['en-GB'][10][] = 'Oct';
    $monthWords['en-GB'][11][] = 'November';
    $monthWords['en-GB'][11][] = 'Nov.';
    $monthWords['en-GB'][11][] = 'Nov';
    $monthWords['en-GB'][12][] = 'December';
    $monthWords['en-GB'][12][] = 'Dec.';
    $monthWords['en-GB'][12][] = 'Dec';

    $monthWords['en-US'][1][] = 'January';
    $monthWords['en-US'][1][] = 'Jan.';
    $monthWords['en-US'][1][] = 'Jan';
    $monthWords['en-US'][2][] = 'February';
    $monthWords['en-US'][2][] = 'Feb.';
    $monthWords['en-US'][2][] = 'Feb';
    $monthWords['en-US'][3][] = 'March';
    $monthWords['en-US'][3][] = 'Mar.';
    $monthWords['en-US'][3][] = 'Mar';
    $monthWords['en-US'][4][] = 'April';
    $monthWords['en-US'][4][] = 'Apr.';
    $monthWords['en-US'][4][] = 'Apr';
    $monthWords['en-US'][5][] = 'May';
    $monthWords['en-US'][6][] = 'June';
    $monthWords['en-US'][6][] = 'Jun.';
    $monthWords['en-US'][6][] = 'Jun';
    $monthWords['en-US'][7][] = 'July';
    $monthWords['en-US'][7][] = 'Jul.';
    $monthWords['en-US'][7][] = 'Jul';
    $monthWords['en-US'][8][] = 'August';
    $monthWords['en-US'][8][] = 'Aug.';
    $monthWords['en-US'][8][] = 'Aug';
    $monthWords['en-US'][9][] = 'September';
    $monthWords['en-US'][9][] = 'Sept.';
    $monthWords['en-US'][9][] = 'Sept';
    $monthWords['en-US'][10][] = 'October';
    $monthWords['en-US'][10][] = 'Oct.';
    $monthWords['en-US'][10][] = 'Oct';
    $monthWords['en-US'][11][] = 'November';
    $monthWords['en-US'][11][] = 'Nov.';
    $monthWords['en-US'][11][] = 'Nov';
    $monthWords['en-US'][12][] = 'December';
    $monthWords['en-US'][12][] = 'Dec.';
    $monthWords['en-US'][12][] = 'Dec';

    if (isset($monthWords[$srcLang])) {
        $monthWordsOut[0] = $monthWords[$srcLang];
    }

    if (isset($monthWords[$trgLang])) {
        $monthWordsOut[1] = $monthWords[$trgLang];
    }

    if ($debug) {
        echo "monthWordsOut: \n";
        print_r($monthWordsOut);
    }

    return $monthWordsOut;
}
function getFullWidthNums()
{
    $fullWidthNums[0] = "\xef\xbc\x90";
    $fullWidthNums[1] = "\xef\xbc\x91";
    $fullWidthNums[2] = "\xef\xbc\x92";
    $fullWidthNums[3] = "\xef\xbc\x93";
    $fullWidthNums[4] = "\xef\xbc\x94";
    $fullWidthNums[5] = "\xef\xbc\x95";
    $fullWidthNums[6] = "\xef\xbc\x96";
    $fullWidthNums[7] = "\xef\xbc\x97";
    $fullWidthNums[8] = "\xef\xbc\x98";
    $fullWidthNums[9] = "\xef\xbc\x99";

    return $fullWidthNums;
}
/** /helpers */

/** sub checks */
function subCheck_zahlen_normalizeSegs($checkSeg, $dateInfo, $monthInfo, $data, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    $checkProps += getSubCheckProps($checkProps['checkFunc'], __FUNCTION__, $checks);

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];
    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    $srcLang = $langs[0];
    $trgLang = $langs[1];

    // Skip?
    // Nein

    // Wenn kein Skip dann subCheck und übergeordneten check als "checked" setzen
    setAsChecked($checks, $checkFunc, $checkSubFunc, $kombi);

    // Prüfseg.
    // ACHTUNG: checkSeg von außen!

    if ($debug) {
        echo "checkSeg START normalize\n";
        print_r($checkSeg);
    }

    // Normalisieren: bei CN, JP, TH und KO die Fullwidth-Versionen durch Normale ersetzen

    if (isAsianLang($langs[0])) {
        $fullWidthNums = getFullWidthNums();
        foreach ($fullWidthNums as $arabNum => $fullNum) {
            $checkSeg[0] = str_replace($fullNum, $arabNum, $checkSeg[0]);
        }
    }
    if (isAsianLang($langs[1])) {
        $fullWidthNums = getFullWidthNums();
        foreach ($fullWidthNums as $arabNum => $fullNum) {
            $checkSeg[1] = str_replace($fullNum, $arabNum, $checkSeg[1]);
        }
    }

    // normalisieren: 1/min in versch. Versionen raus

    $rpmRegEx = '!(rpm|r\.p\.m|r/min|d\./dak|об/мин|об\./мин|ot/min|ot\./min|obr\./min|obr/min|v/min|tr/min|tr/mn|giri/min|รอบต่อนาที)\.?!';

    if (($langs[0] == 'de-DE') || ($langs[0] == 'DEU')) {
        if ((preg_match('!1/min!i', $checkSeg[0])) && (preg_match($rpmRegEx, $checkSeg[1]))) {
            $checkSeg[0] = preg_replace('!1/min!i', ' ', $checkSeg[0]);
        }

        // Minus als Hyphen minus, en dash und mathe-minus
        if ((preg_match('!\d\p{Zs}min.*?([-−–]1|⁻¹)!u', $checkSeg[0], $m)) && (preg_match($rpmRegEx, $checkSeg[1]))) {
            //$checkSeg[0] = str_replace($m[1], " ", $checkSeg[0]);
            $checkSeg[0] = preg_replace('!<[^>]*?>' . $m[1] . '<[^>]*?>!', ' ', $checkSeg[0]);
            $checkSeg[0] = str_replace('⁻¹', ' ', $checkSeg[0]);
        }
    } elseif (($langs[1] == 'de-DE') || ($langs[1] == 'DEU')) {
        if ((preg_match('!1/min!i', $checkSeg[1])) && (preg_match($rpmRegEx, $checkSeg[0]))) {
            $checkSeg[0] = preg_replace('!1/min!i', ' ', $checkSeg[0]);
        }

        // Minus als Hyphen minus, en dash und mathe-minus
        if ((preg_match('!\d\p{Zs}min.*?([-−–]1|⁻¹)!u', $checkSeg[1], $m)) && (preg_match($rpmRegEx, $checkSeg[0]))) {
            //$checkSeg[1] = str_replace($m[1], " ", $checkSeg[1]);
            $checkSeg[1] = preg_replace('!<[^>]*?>' . $m[1] . '<[^>*?]>!', ' ', $checkSeg[1]);
            $checkSeg[1] = str_replace('⁻¹', ' ', $checkSeg[1]);
        }
    }

    // normalisieren: 1/min raus
    $checkSeg[0] = preg_replace('!1/min!i', ' ', $checkSeg[0]);
    $checkSeg[1] = preg_replace('!1/min!i', ' ', $checkSeg[1]);

    // min hoch -1 raus (Minus als Hyphen minus, en dash und mathe-minus), optionale tags außenrum
    $checkSeg[0] = preg_replace('!min(\p{Zs})?(<[^>]*?>)?([-−–]1)(<[^>*?]>)?!u', ' ', $checkSeg[0]);
    $checkSeg[1] = preg_replace('!min(\p{Zs})?(<[^>]*?>)?([-−–]1)(<[^>*?]>)?!u', ' ', $checkSeg[1]);

    // min hoch -1 raus (als Unicode-Zeichen), optionale tags außenrum (falls Schriftartwechsel oder so)
    $checkSeg[0] = preg_replace('!min(\p{Zs})?(<[^>]*?>)?⁻¹(<[^>*?]>)?!u', ' ', $checkSeg[0]);
    $checkSeg[1] = preg_replace('!min(\p{Zs})?(<[^>]*?>)?⁻¹(<[^>*?]>)?!u', ' ', $checkSeg[1]);

    if (true) {
        // normalisieren: LZ-Tags durch Unicode-zeichen ersetzen

        $checkSeg[0] = str_replace('<:hs>', "\xC2\xA0", $checkSeg[0]);
        $checkSeg[1] = str_replace('<:hs>', "\xC2\xA0", $checkSeg[1]);

        $checkSeg[0] = str_replace('<:ts>', "\xC2\xA0", $checkSeg[0]);
        $checkSeg[1] = str_replace('<:ts>', "\xC2\xA0", $checkSeg[1]);

        //print_r($checkSeg);

        $checkSeg[0] = preg_replace('!<[^>]+>!i', ' ', $checkSeg[0]);
        $checkSeg[1] = preg_replace('!<[^>]+>!i', ' ', $checkSeg[1]);
    }

    /*
    * ACHTUNG +++ ACHTUNG +++ ACHTUNG +++ ACHTUNG
    * Die Funktion subCheck_dates ist neu!
    * Überprüfung von numerischen Datumsformaten scheint weitgehend zu klappen, ist aber noch nicht 100%ig getestet!
    * Der Aufruf der Funktion ist deshalb deaktiviert!
    */
    $checkDate_active = false;
    $dateChecked = false;
    if ($checkDate_active && !empty($dateInfo[$srcLang]) && !empty($dateInfo[$trgLang])) {
        $dateChecked = true;
        $checkSeg = subCheck_dates($checkSeg, $dateInfo, $monthInfo, $data, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
    }

    // normalisieren: Minuszeichen bei neg. Zahlen

    $negNumRegEx = "#(((?<![\d\p{L}\p{Pd}])[\p{Pd}])(\d+))([\d\.,]+)?#u";

    $matches[0] = [];
    $matches[1] = [];

    $replaceMatches[0] = [];
    $replaceMatches[1] = [];

    $normalDivMatches[0] = [];
    $normalDivMatches[1] = [];

    $normalAllMatches[0] = [];
    $normalAllMatches[1] = [];

    if (preg_match_all($negNumRegEx, $checkSeg[0], $m1)) {
        if ($debug) {
            echo "\nvorzeichen [0]!!!!\n";
        }
        if ($debug) {
            print_r($m1);
        }

        foreach ($m1[1] as $key => $vorzeichen) {
            $match = $m1[0][$key];

            $matches[0][] = $match;
            $normalMinusMatch = preg_replace('!\p{Pd}!u', '-', $match);
            $normalDivMatch = preg_replace('![\.,]!u', '|', $match);
            $normalAllMatch = preg_replace('![\.,]!u', '|', $normalMinusMatch);

            $normalDivMatches[0][] = $normalDivMatch;
            $normalAllMatches[0][] = $normalAllMatch;
            $replaceMatches[0][$normalMinusMatch][] = $match;
        }

        sort($normalDivMatches[0]);
        if ($debug) {
            echo "matches[0]\n";
        }
        if ($debug) {
            print_r($matches[0]);
        }
        if ($debug) {
            echo "normalDivMatches[0]\n";
        }
        if ($debug) {
            print_r($normalDivMatches[0]);
        }
        if ($debug) {
            echo "normalAllMatches[0]\n";
        }
        if ($debug) {
            print_r($normalAllMatches[0]);
        }
    }

    if (preg_match_all($negNumRegEx, $checkSeg[1], $m2)) {
        if ($debug) {
            echo "\nvorzeichen [1]!!!!\n";
        }
        if ($debug) {
            print_r($m2);
        }
        foreach ($m2[1] as $key => $vorzeichen) {
            $match = $m2[0][$key];

            $matches[1][] = $match;
            $normalMinusMatch = preg_replace('!\p{Pd}!u', '-', $match);
            $normalDivMatch = preg_replace('![\.,]!u', '|', $match);
            $normalAllMatch = preg_replace('![\.,]!u', '|', $normalMinusMatch);

            $normalDivMatches[1][] = $normalDivMatch;
            $normalAllMatches[1][] = $normalAllMatch;
            $replaceMatches[1][$normalMinusMatch][] = $match;
        }

        sort($normalDivMatches[1]);
        if ($debug) {
            echo "matches[1]\n";
        }
        if ($debug) {
            print_r($matches[1]);
        }
        if ($debug) {
            echo "normalDivMatches[1]\n";
        }
        if ($debug) {
            print_r($normalDivMatches[1]);
        }
        if ($debug) {
            echo "normalAllMatches[1]\n";
        }
        if ($debug) {
            print_r($normalAllMatches[1]);
        }
    }

    if ($normalDivMatches[0] != $normalDivMatches[1]) {
        if ($debug) {
            echo "normalDivMatches[0] != normalDivMatches[1]\n";
        }

        $checkProps['checkSeverity_extra'] = 'notice';

        $checkProps['checkSubType_extra'] = 'Unterschiedliche Minuszeichen';
        $checkProps['checkSubType_extra_EN'] = 'Different minus characters';

        $checkResults['checkMessage'] = 'Unterschiedliche Minuszeichen:<br>';
        $checkResults['checkMessage_EN'] = 'Different minus characters:<br>';

        $doMessage = true;

        foreach ($matches[0] as $num) {
            if (preg_match($negNumRegEx, $num, $m3)) {
                $vz = $m3[2];
                $matchHex = bin2hex($vz);
                $matchHex = matchHexExplain($matchHex);
                $checkResults['checkMessage'] .= "&nbsp;&nbsp;SRC: [{$num} {$matchHex}]";
                $checkResults['checkMessage_EN'] .= "&nbsp;&nbsp;SRC: [{$num} {$matchHex}]";
            }
        }

        foreach ($matches[1] as $num) {
            if (preg_match($negNumRegEx, $num, $m4)) {
                $vz = $m4[2];
                $matchHex = bin2hex($vz);
                $matchHex = matchHexExplain($matchHex);
                $checkResults['checkMessage'] .= "&nbsp;&nbsp;TRG: [{$num} {$matchHex}]";
                $checkResults['checkMessage_EN'] .= "&nbsp;&nbsp;TRG: [{$num} {$matchHex}]";
            }
        }

        if ($doMessage) {
            doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
            // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
        }
    } else {
        if ($debug) {
            echo "normalDivMatches[0] ======================= normalDivMatches[1]\n";
        }
    }

    if (sort($normalAllMatches[0]) == sort($normalAllMatches[1])) {
        if ($debug) {
            print_r($checkSeg);
        }
        foreach ($replaceMatches[0] as $matchNew => $origMatches) {
            foreach ($origMatches as $key => $origMatch) {
                $checkSeg[0] = str_replace($origMatch, $matchNew, $checkSeg[0]);
            }
        }
        foreach ($replaceMatches[1] as $matchNew => $origMatches) {
            foreach ($origMatches as $key => $origMatch) {
                $checkSeg[1] = str_replace($origMatch, $matchNew, $checkSeg[1]);
            }
        }
        //if($debug) print_r($checkSeg);
    }

    // Normalisieren: Zahlenintervalle

    $regExInterval = "!((\d+[,\.])?\d+)(\.{2,})((\d+[,\.])?\d+)!u";

    $srcMatches = [];
    $trgMatches = [];

    if (preg_match_all($regExInterval, $checkSeg[0], $m1)) {
        $srcMatches = $m1[0];
        if ($debug) {
            print_r($m1[0]);
        }
        //print_r($checkSeg[0]);
        $checkSeg[0] = preg_replace($regExInterval, '$1 $3 $4', $checkSeg[0]);
        //print_r($checkSeg[0]);
    }
    if (preg_match_all($regExInterval, $checkSeg[1], $m2)) {
        $trgMatches = $m2[0];
        if ($debug) {
            print_r($m2[0]);
        }
        //print_r($checkSeg[1]);
        $checkSeg[1] = preg_replace($regExInterval, '$1 $3 $4', $checkSeg[1]);
        //print_r($checkSeg[1]);
    }

    $doMessage = false;

    if (count($srcMatches) != count($trgMatches)) {
        $doMessage = true;
    }

    if ($doMessage) {
        $checkProps['checkSubType_extra'] = 'Untersch. Zeichen/Formatierung für Zahlen-Intervall';
        $checkProps['checkSubType_extra_EN'] = 'Different character/formatting for number interval';

        $checkResults['checkMessage'] = 'Untersch. Zeichen/Formatierung für Zahlen-Intervall';
        $checkResults['checkMessage_EN'] = 'Different character/formatting for number interval';

        doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
        // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
    }

    //Sonderbehandlung CH -- wenn SRC DE-Style

    if (($langs[1] == 'fr-CH') && (!isDecimalPointLang($langs[0]))) {
        // in Ch schauen ob <währung><num> enthalten
        // <num> beschränken auf \d{1,3}\.\d{2}
        if (preg_match_all("!(EURO?|€|CHF|Fr\.|\$|£)?\p{Zs}?(\d{1,3},\d{2})\p{Zs}?(EURO?|€|CHF|Fr\.|\$|£)?!iu", $checkSeg[1], $m)) {
            echo "MID: $mid\n";
            print_r($m);

            foreach ($m[2] as $key => $num) {
                if (empty($m[1][$key]) && empty($m[3][$key])) {
                    continue;
                }

                if (preg_match('!(^|[^\d])' . preg_quote($num) . '([^\d]|$)!', $checkSeg[0])) {
                    $checkProps['checkSubType_extra'] = 'Währungsangabe ggf. nicht korrekt lokalisiert ';
                    $checkProps['checkSubType_extra_EN'] = 'Currency value not localised correctly(?)';

                    $checkResults['checkMessage'] = "Währungsangabe {$m[0][$key]} ggf. nicht korrekt lokalisiert";
                    $checkResults['checkMessage_EN'] = "Currency value {$m[0][$key]} not localised correctly(?)";

                    doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                    // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);

                    $checkResults['replaceString'] = '<span class="matchZahlen">' . $num . '</span>';
                    $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($num) . "(?![\d])#u";
                    $checkResults['markSrc'] = false;
                    $checkResults['markTrg'] = true;
                    // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);

                    $checkSeg[0] = preg_replace('!(^|[^\d])' . preg_quote($num) . '([^\d]|$)!', ' ', $checkSeg[0], 1);
                    $checkSeg[1] = preg_replace('!(^|[^\d])' . preg_quote($num) . '([^\d]|$)!', ' ', $checkSeg[1], 1);
                }
            }
        }
    }

    //Sonderbehandlung CH -- wenn SRC EN-Style

    if (($langs[1] == 'fr-CH') && (isDecimalPointLang($langs[0]))) {
        // in Ch schauen ob <währung><num> enthalten
        // <num> beschränken auf \d{1,3}\.\d{2}
        if (preg_match_all("!(EURO?|€|CHF|Fr\.|\$|£)?\p{Zs}?(\d{1,3}\.\d{2})\p{Zs}?(EURO?|€|CHF|Fr\.|\$|£)?!iu", $checkSeg[1], $m)) {
            echo "MID: $mid\n";
            print_r($m);

            foreach ($m[2] as $key => $num) {
                if (empty($m[1][$key]) && empty($m[3][$key])) {
                    continue;
                }

                $srcCount = preg_match_all('!(^|[^\d])' . preg_quote($num) . '([^\d]|$)!', $checkSeg[0]);
                $trgCount = preg_match_all('!(^|[^\d])' . preg_quote($num) . '([^\d]|$)!', $checkSeg[1]);

                if ($srcCount == $trgCount) {
                    $checkSeg[0] = preg_replace('!(^|[^\d])' . preg_quote($num) . '([^\d]|$)!', ' ', $checkSeg[0], 1);
                    $checkSeg[1] = preg_replace('!(^|[^\d])' . preg_quote($num) . '([^\d]|$)!', ' ', $checkSeg[1], 1);
                }
            }
        }
    }

    // Normalisieren: Punkt am Seg-Ende weg, wenn beide Segs einen haben

    if ((preg_match('!\.$!', trim($checkSeg[0]))) && (preg_match('!\.$!', trim($checkSeg[1])))) {
        $checkSeg[0] = preg_replace('!\.$!', '', trim($checkSeg[0]));
        $checkSeg[1] = preg_replace('!\.$!', '', trim($checkSeg[1]));
    }

    if ($debug) {
        echo "checkSeg ENDE normalize\n";
        print_r($checkSeg);
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'END', __FUNCTION__);
    }

    $result['checkSeg'] = $checkSeg;
    $result['dateChecked'] = $dateChecked;

    return $result;
}
function subCheck_zahlen_v1($checkSeg, $dateChecked, $data, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $checkProps += getSubCheckProps($checkProps['checkFunc'], __FUNCTION__, $checks);
    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];
    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    // Wenn kein Skip dann subCheck und übergeordneten check als "checked" setzen
    setAsChecked($checks, $checkFunc, $checkSubFunc, $kombi);

    $numsCount[0] = [];
    $numsCount[1] = [];

    $numsCount = countAndStripKiloSpaceNums($numsCount, $checkSeg); // $checkSeg = referenz
    $numsCount = countAllOtherNums($numsCount, $checkSeg, $langs);
    // tut nicht richtig, erstmal auskommentiert
    //checkForInvalidNums($numsCount, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
    $numsCount = compareNumCounts_asIs2($numsCount, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
    $numsCount = compareNumCounts_sameDiv($numsCount, $checkSeg, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
    $numsCount = compareNumCounts_flipDiv($numsCount, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
    $numsCount = compareNumCounts_noDiv($numsCount, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
    $numsCount = compareNumCounts_leadingZero_trailingPeriod($numsCount, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
    $numsCount = compareNumCounts_explode2($numsCount, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);
    $numsCount = compareNumCounts_spelledOut($numsCount, $currentData, $checkProps, $checks, $sncSettings, $checkMessages, $msgCounts, $msgMatches);

    if ((empty($numsCount[0])) && (empty($numsCount[1]))) return;

    $checkResults['checkMessage'] = 'Unstimmigkeiten in SRC vs TRG, bitte auch Trenner prüfen';
    $checkResults['checkMessage_EN'] = 'Discrepancy in SRC vs TRG, please also check separators <br>';

    if (noLokaAllowed($sncSettings)) {
        $checkResults['checkMessage'] .= SYMBOL_WARNING . ' <i>Bei diesem Kunden keine Lokalisierung/Veränderung von Zahlen erlaubt!</i><br>';
        $checkResults['checkMessage_EN'] .= SYMBOL_WARNING . ' <i>No localisation/modification of numbers permitted for this customer!</i><br>';
    }

    foreach ($numsCount[0] as $origNum => $srcCount) {
        if (!isset($numsCount[1][$origNum])) {
            $numsCount[1][$origNum] = 0;
        }
    }
    foreach ($numsCount[1] as $origNum => $trgCount) {
        if (!isset($numsCount[0][$origNum])) {
            $numsCount[0][$origNum] = 0;
        }
    }

    $hasBuggyCommaList = false;

    foreach ($numsCount[0] as $num => $srcCount) {
        $trgCount = $numsCount[1][$num];

        //$checkResults['checkMessage'] .= '<tr><td style="text-align:center;white-space:nowrap;">';
        //$checkResults['checkMessage_EN'] .= '<tr><td style="width:10%; text-align:center;white-space:nowrap;">';

        $match = $num;
        $match = str_replace("\-", "\p{Pd}", preg_quote($match));
        $checkResults['replaceString'] = '<span class="matchZahlen">' . $num . '</span>';
        if (!preg_match('!^\d+$!', $match)) $checkResults['matchRegEx'] = "#(?<![\d])" . $match . "(?![\d])#u";
        if (preg_match('!^\d+$!', $match)) $checkResults['matchRegEx'] = "#(?<![\d\"])" . $match . "(?![\d\"])#u";
        $num = "<code class=\"term\">{$num}</code>";
        if ($trgCount == 0 || $srcCount == 0 || $srcCount < $trgCount || $srcCount > $trgCount) {
            $checkResults['markSrc'] = true;
            $checkResults['markTrg'] = true;
            // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
        }
    }

    doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
    // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
}
function subCheck_1000er_trenner_nicht_erlaubt($data, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    $checkProps += getSubCheckProps($checkProps['checkFunc'], __FUNCTION__, $checks);

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];
    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    // Wenn kein Skip dann subCheck und übergeordneten check als "checked" setzen
    setAsChecked($checks, $checkFunc, $checkSubFunc, $kombi);

    // Prüfseg.

    if (true) {
        $checkSeg[0] = $currentData['segTypes']['srcSegSimple'];
        $checkSeg[1] = $currentData['segTypes']['trgSegSimple'];

        $checkSeg[0] = str_replace('<:hs>', "\xC2\xA0", $checkSeg[0]);
        $checkSeg[1] = str_replace('<:hs>', "\xC2\xA0", $checkSeg[1]);

        $checkSeg[0] = str_replace('<:ts>', "\xC2\xA0", $checkSeg[0]);
        $checkSeg[1] = str_replace('<:ts>', "\xC2\xA0", $checkSeg[1]);
    } else {
        $checkSeg[0] = $currentData['segTypes']['srcSeg'];
        $checkSeg[1] = $currentData['segTypes']['trgSeg'];
    }

    // Zeichen am SegEnde durch LZ ersetzen

    $checkSeg[0] = preg_replace('![,\.](\p{Zs}+)}$!u', ' ', $checkSeg[0]);
    $checkSeg[1] = preg_replace('![,\.](\p{Zs}+)}$!u', ' ', $checkSeg[1]);

    $checkSeg[0] = preg_replace('!,(\p{Zs}[^\d])!u', '$1', $checkSeg[0]);
    $checkSeg[1] = preg_replace('!,(\p{Zs}[^\d])!u', '$1', $checkSeg[1]);

    if ($debug) {
        echo "checkSeg:\n";
        print_r($checkSeg);
    }

    // Versch. Zahlenmuster einsammeln

    for ($i = 0; $i < 2; $i++) {
        $kiloSpaceNums[$i] = [];
        $kiloPointNums[$i] = [];
        $kiloCommaNums[$i] = [];
        $deciCommaNums[$i] = [];
        $deciPointNums[$i] = [];

        $regEx = getNumTypeRegEx('fullSeg', 'kiloSpaceNum');

        if (preg_match_all($regEx, $checkSeg[$i], $m)) {
            foreach ($m[0] as $match) {
                $kiloSpaceNums[$i][] = $match;
            }
        }

        $regEx = getNumTypeRegEx('fullSeg', 'kiloPointNum');

        if (preg_match_all($regEx, $checkSeg[$i], $m)) {
            foreach ($m[0] as $match) {
                $kiloPointNums[$i][] = $match;
            }
        }

        $regEx = getNumTypeRegEx('fullSeg', 'kiloCommaNum');

        if (preg_match_all($regEx, $checkSeg[$i], $m)) {
            foreach ($m[0] as $match) {
                $kiloCommaNums[$i][] = $match;
            }
        }

        $regEx = getNumTypeRegEx('fullSeg', 'deciCommaNum');

        if (preg_match_all($regEx, $checkSeg[$i], $m)) {
            foreach ($m[0] as $match) {
                $deciCommaNums[$i][] = $match;
            }
        }

        $regEx = getNumTypeRegEx('fullSeg', 'deciPointNum');

        if (preg_match_all($regEx, $checkSeg[$i], $m)) {
            foreach ($m[0] as $match) {
                $deciPointNums[$i][] = $match;
            }
        }
    }

    if ($debug) {
        if (!empty($kiloSpaceNums)) {
            echo 'kiloSpaceNums';
            print_r($kiloSpaceNums);
        }
        if (!empty($kiloPointNums)) {
            echo 'kiloPointNums';
            print_r($kiloPointNums);
        }
        if (!empty($kiloCommaNums)) {
            echo 'kiloCommaNums';
            print_r($kiloCommaNums);
        }
        if (!empty($deciCommaNums)) {
            echo 'deciCommaNums';
            print_r($deciCommaNums);
        }
        if (!empty($deciPointNums)) {
            echo 'deciPointNums';
            print_r($deciPointNums);
        }
    }

    if (isset($kiloSpaceNums[1])) {
        foreach ($kiloSpaceNums[1] as $theNum) {
            //if(in_array($theNum, $srcNumbersPlusDivs)) continue;

            $checkResults['checkMessage'] = "1000er-Trenner in [{$theNum}] nicht erlaubt ({$langs[1]})";
            $checkResults['checkMessage_EN'] = "1000s separator in [{$theNum}] not permitted ({$langs[1]})";

            $match = $theNum;

            $checkResults['replaceString'] = '<span class="matchClient">' . $match . '</span>';
            $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
            $checkResults['markSrc'] = true;
            $checkResults['markTrg'] = true;

            doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
            // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
            // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
        }
    }

    if (isDecimalPointLang($langs[1])) {
        if (isset($kiloCommaNums[1])) {
            foreach ($kiloCommaNums[1] as $theNum) {
                //if(in_array($theNum, $srcNumbersPlusDivs)) continue;

                $checkResults['checkMessage'] = "1000er-Trenner in [{$theNum}] nicht erlaubt ({$langs[1]})";
                $checkResults['checkMessage_EN'] = "1000s separator in [{$theNum}] not permitted ({$langs[1]})";

                $match = $theNum;

                $checkResults['replaceString'] = '<span class="matchClient">' . $match . '</span>';
                $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = true;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
            }
        }
    }

    if (!isDecimalPointLang($langs[1])) {
        if (isset($kiloPointNums[1])) {
            foreach ($kiloPointNums[1] as $theNum) {
                //if(in_array($theNum, $srcNumbersPlusDivs)) continue;

                $checkResults['checkMessage'] = "1000er-Trenner in [{$theNum}] nicht erlaubt ({$langs[1]})";
                $checkResults['checkMessage_EN'] = "1000s separator in [{$theNum}] not permitted ({$langs[1]})";

                $match = $theNum;

                $checkResults['replaceString'] = '<span class="matchClient">' . $match . '</span>';
                $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = true;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
            }
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'END', __FUNCTION__);
    }
}
function subCheck_alphanumStrings($data, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    $checkProps += getSubCheckProps($checkProps['checkFunc'], __FUNCTION__, $checks);

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];
    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    // Wenn kein Skip dann subCheck und übergeordneten check als "checked" setzen
    setAsChecked($checks, $checkFunc, $checkSubFunc, $kombi);

    // Prüfseg.

    $checkSeg[0] = $currentData['segTypes']['srcSegPureSpace'];
    $checkSeg[1] = $currentData['segTypes']['trgSegPureSpace'];

    $regEx = "!(^|[^\p{L}/\.\(\)\[\]\:])([A-Zx0-9]{1,6}[0-9]+(([A-Z]+|[\.][0-9]+))?)([^\p{L}]|$)!u";

    $knowMatches = [];

    if (preg_match_all($regEx, $checkSeg[0], $m)) {
        $m[2] = array_unique($m[2]);

        foreach ($m[2] as $key => $match) {
            if (!preg_match('![A-Zx]!', $match)) {
                continue;
            }
            if (!preg_match('![0-9]!', $match)) {
                continue;
            }
            if (preg_match('!^KW\d{1,2}!', $match)) {
                continue;
            }
            if (preg_match('!^NG\d{1,2}!', $match)) {
                continue;
            }

            $match = preg_replace('!\.+$!', '', $match);

            $knownMatches[$match] = true;

            $srcCount = substr_count($checkSeg[0], $match);
            $trgCount = substr_count($checkSeg[1], $match);

            if ($srcCount != $trgCount) {
                $checkResults['checkMessage'] = "Alphanum. Zeichenfolge [{$match}] in SRC ≠ TRG [{$srcCount}:{$trgCount}]";
                $checkResults['checkMessage_EN'] = "Alphanumerical character sequence [{$match}] in SRC ≠ TRG [{$srcCount}:{$trgCount}]";

                $checkResults['replaceString'] = '<span class="matchStandard">' . $match . '</span>';
                $checkResults['matchRegEx'] = '!' . preg_quote($match) . '!u';
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = true;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
            }
        }
    }

    if (preg_match_all($regEx, $checkSeg[1], $m)) {
        $m[2] = array_unique($m[2]);

        foreach ($m[2] as $key => $match) {
            if (!preg_match('![A-Zx]!', $match)) {
                continue;
            }
            if (!preg_match('![0-9]!', $match)) {
                continue;
            }

            $match = preg_replace('!\.+$!', '', $match);

            if (isset($knownMatches[$match])) {
                continue;
            }

            $srcCount = substr_count($checkSeg[0], $match);
            $trgCount = substr_count($checkSeg[1], $match);

            if ($srcCount != $trgCount) {
                $checkResults['checkMessage'] = "Alphanum. Zeichenfolge [{$match}] in SRC ≠ TRG [{$srcCount}:{$trgCount}]";
                $checkResults['checkMessage_EN'] = "Alphanumerical character sequence [{$match}] in SRC ≠ TRG [{$srcCount}:{$trgCount}]";

                $checkResults['replaceString'] = '<span class="matchStandard">' . $match . '</span>';
                $checkResults['matchRegEx'] = '!' . preg_quote($match) . '!u';
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = true;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
            }
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'END', __FUNCTION__);
    }
}
/*
 * ACHTUNG +++ ACHTUNG +++ ACHTUNG +++ ACHTUNG
 * Die Funktion subCheck_dates ist neu!
 * Überprüfung von numerischen Datumsformaten scheint weitgehend zu klappen, ist aber noch nicht 100%ig getestet!
 * @checkLong: Überprüfung von Datumsformaten in der Langversion also mit ausgeschriebenem Monatsnamen) ist GANZ NEU und funktioniert noch nicht korrekt
 * deshalb via checkLong = false an diversen Stellen deaktiviert
*/
function subCheck_dates($checkSeg, $dateInfo, $monthInfo, $data, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    $checkProps += getSubCheckProps($checkProps['checkFunc'], __FUNCTION__, $checks);

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];
    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    $srcLang = $langs[0];
    $trgLang = $langs[1];

    if (empty($dateInfo[$srcLang]) || empty($dateInfo[$trgLang])) {
        return;
    }

    // Wenn kein Skip dann subCheck und übergeordneten check als "checked" setzen
    setAsChecked($checks, $checkFunc, $checkSubFunc, $kombi);

    // Prüfseg.

    $checkLong = false;

    $regExes[$srcLang] = $dateInfo[$srcLang]['regExes'];
    $formats[$srcLang] = $dateInfo[$srcLang]['formats'];

    if (isset($dateInfo[$srcLang]['regExes_month'])) {
        $regExes_month[$srcLang] = $dateInfo[$srcLang]['regExes_month'];
    } else {
        $checkLong = false;
    }
    if (isset($dateInfo[$srcLang]['formats_month'])) {
        $formats_month[$srcLang] = $dateInfo[$srcLang]['formats_month'];
    } else {
        $checkLong = false;
    }

    $regExes[$trgLang] = $dateInfo[$trgLang]['regExes'];
    $formats[$trgLang] = $dateInfo[$trgLang]['formats'];
    if (isset($dateInfo[$trgLang]['regExes_month'])) {
        $regExes_month[$trgLang] = $dateInfo[$trgLang]['regExes_month'];
    } else {
        $checkLong = false;
    }
    if (isset($dateInfo[$trgLang]['formats_month'])) {
        $formats_month[$trgLang] = $dateInfo[$trgLang]['formats_month'];
    } else {
        $checkLong = false;
    }
    if ($debug) {
        echo "\n=========mid: {$mid}==========dateInfo=======================\n";
        print_r($dateInfo);
    }

    $sourceDates = [];
    $targetDates = [];
    $sourceDates_long = [];
    $targetDates_long = [];
    $vdCount = -1;
    $regExes_months2 = [];

    $shortToLongAllowed = false;
    $longToShortAllowed = true;

    // valide numerische sourceDates aus srcSeg

    $srcSeg = $checkSeg[0];
    $trgSeg = $checkSeg[1];

    foreach ($regExes[$srcLang] as $reCode => $regEx) {
        if (preg_match_all($regEx, $srcSeg, $matches)) {
            if ($debug) {
                echo "Matches:\n";
                print_r($matches);
            }

            $dates = $matches[0];

            foreach ($dates as $key => $date) {
                foreach ($formats[$srcLang] as $format) {
                    if (validateDate($date, $format)) {
                        if ($debug) {
                            echo "OK [$format]: $date\n";
                        }
                        $vdCount++;
                        $sourceDates[$vdCount]['date'] = $date;
                        $sourceDates[$vdCount]['format'] = $format;

                        $formatClean = preg_replace('![^ymndj\./\-]!i', '', $format);
                        if ($debug) {
                            echo "\$formatClean: $formatClean\n";
                        }
                        if ($formatClean[0] == 'd' || $formatClean[0] == 'j') {
                            // Tag an 1. Stelle dmy / dm
                            $day = (int) $matches[2][$key];
                            $sourceDates[$vdCount]['day'] = $day;
                        } elseif ($formatClean[2] == 'd' || $formatClean[2] == 'j') {
                            // Tag an 2. Stelle mdy / md
                            $day = (int) $matches[3][$key];
                            $sourceDates[$vdCount]['day'] = $day;
                        } elseif ($formatClean[4] == 'd' || $formatClean[4] == 'j') {
                            // Tag an 3. Stelle ymd
                            $day = (int) $matches[4][$key];
                            $sourceDates[$vdCount]['day'] = $day;
                        }

                        if ($formatClean[0] == 'm' || $formatClean[0] == 'n') {
                            $month = (int) $matches[2][$key];
                            $sourceDates[$vdCount]['month_num'] = $month;
                        } elseif ($formatClean[2] == 'm' || $formatClean[2] == 'n') {
                            $month = (int) $matches[3][$key];
                            $sourceDates[$vdCount]['month_num'] = $month;
                        } elseif ($formatClean[4] == 'm' || $formatClean[4] == 'n') {
                            $month = (int) $matches[4][$key];
                            $sourceDates[$vdCount]['month_num'] = $month;
                        }

                        if (preg_match('!y!i', $formatClean)) {
                            if ($formatClean[0] == 'Y' || $formatClean[0] == 'y') {
                                $year = $matches[2][$key];
                                $sourceDates[$vdCount]['year'] = $year;
                            } elseif ($formatClean[2] == 'Y' || $formatClean[2] == 'y') {
                                $year = $matches[3][$key];
                                $sourceDates[$vdCount]['year'] = $year;
                            } elseif ($formatClean[4] == 'Y' || $formatClean[4] == 'y') {
                                $year = $matches[4][$key];
                                $sourceDates[$vdCount]['year'] = $year;
                            } else {
                                $year = '';
                                //$validDates[$vdCount]["year"] = $year;
                            }
                        } else {
                            $year = '';
                            //$validDates[$vdCount]["year"] = $year;
                        }

                        break;
                    } else {
                        echo "NO [$format]: $date\n";
                    }
                }
            }
        } else {
            echo "Kein Match auf {$reCode}:\n{$regEx}\nin:\n{$srcSeg}\n\n";
        }
    }
    if ($debug) {
        echo "sourceDates (1):\n";
        print_r($sourceDates);
        print_r(DateTime::getLastErrors());
    }

    // ACHTUNG: NEU!! mit checkLong = true noch nicht richtig getestet!
    $checkLong = false;

    if ($checkLong) {
        // regExe für Datumsangaben im Langformat (= ausgeschriebener Monat) erzeugen

        if (isset($monthInfo[$srcLang]) && isset($regExes_month[$srcLang])) {
            $allMonths_src = [];
            $knownMonths = [];
            foreach ($monthInfo[$srcLang] as $monthNum => $styles) {
                foreach ($styles as $style => $monthName) {
                    if (isset($knownMonths[$monthName])) {
                        continue;
                    }
                    $knownMonths[$monthName] = true;

                    $allMonths_src[$monthNum][] = $monthName;
                }
            }
            $monthLists = [];
            foreach ($allMonths_src as $monthNum => $monthNames) {
                $monthLists[] = "(?<m{$monthNum}>" . join('|', $monthNames) . ')';
            }
            $monthList = '(?<month>' . join('|', $monthLists) . ')';
            if ($debug) {
                echo "monthList: $monthList";
            }
            foreach ($regExes_month[$srcLang] as $regEx) {
                $regEx = str_replace('MONTHLIST', $monthList, $regEx);
                $regExes_months2[$srcLang][] = $regEx;
            }
        }

        // valide sourceDates im Langformat ( = mit ausgeschriebenem Monat) aus srcSeg auslesen

        if (!isset($regExes_months2[$srcLang])) {
            print_r("Keine Suche nach Datumsangaben mit abgekürzten oder ausgeschriebenen Monatsnamen in {$srcLang}. [regExes_months2] fehlt]\n");
            //trigger_error("Keine Suche nach Datumsangaben mit abgekürzten oder ausgeschriebenen Monatsnamen in {$srcLang}. [regExes_months2] fehlt]\n", E_USER_NOTICE);
        } else {
            if ($debug) {
                echo "regExes_months2:\n";
                print_r($regExes_months2[$srcLang]);
            }
            foreach ($regExes_months2[$srcLang] as $regEx) {
                if (preg_match_all($regEx, $srcSeg, $matches)) {
                    if ($debug) {
                        echo "Matches:\n";
                        print_r($matches);
                    }

                    $dates = $matches[0];

                    foreach ($dates as $key => $date) {
                        $day = $matches['day'][$key];
                        $month = $matches['month'][$key];
                        $year = '';
                        $year = $matches['year'][$key];
                        if (!empty($matches['year'][$key])) {
                            $year = $matches['year'][$key];
                        }
                        $monthNum = '';
                        for ($i = 1; $i < 13; $i++) {
                            if (!empty($matches['m' . $i][$key])) {
                                $monthNum = $i;
                                break;
                            }
                        }
                        if ($debug) {
                            echo "month XXX: $month\n";
                            echo "monthNum XXX: $monthNum\n";
                        }

                        // interDate = normalisiertes
                        if ($year != '') {
                            $interDate = "{$day}/{$monthNum}/{$year}";
                            $interFormats[] = 'j/n/Y';
                        } else {
                            $interDate = "{$day}/{$monthNum}";
                            $interFormats[] = 'j/n';
                        }
                        if ($debug) {
                            echo "interDate XXX: $interDate\n";
                        }

                        //continue;

                        foreach ($interFormats as $format) {
                            if (validateDate($interDate, $format)) {
                                if ($debug) {
                                    echo "OK [$format]: $date\n";
                                }

                                $vdCount++;
                                /*
                                $sourceDates[$vdCount]['date'] = $date;
                                $sourceDates[$vdCount]['date_inter'] = $interDate;
                                $sourceDates[$vdCount]['format'] = $format;
                                $sourceDates[$vdCount]['day'] = $day;
                                $sourceDates[$vdCount]['month'] = $month;
                                $sourceDates[$vdCount]['month_num'] = $monthNum;
                                $sourceDates[$vdCount]['year'] = $year;
                                */

                                $sourceDates_long[$vdCount]['date'] = $date;
                                $sourceDates_long[$vdCount]['date_inter'] = $interDate;
                                $sourceDates_long[$vdCount]['format'] = $format;
                                $sourceDates_long[$vdCount]['day'] = $day;
                                $sourceDates_long[$vdCount]['month'] = $month;
                                $sourceDates_long[$vdCount]['month_num'] = $monthNum;
                                $sourceDates_long[$vdCount]['year'] = $year;

                                break;
                            } else {
                                if ($debug) {
                                    echo "NO [$format]: $date\n";
                                }
                            }
                        }
                    }
                } else {
                    if ($debug) {
                        echo "Kein Match auf:\n{$regEx}\nin:\n{$srcSeg}\n\n";
                    }
                }
            }
        }
        if ($debug) {
            echo "sourceDates:\n";
            print_r($sourceDates);
            echo "sourceDates_long:\n";
            print_r($sourceDates_long);
            print_r(DateTime::getLastErrors());
        }
    }

    if (empty($sourceDates) && empty($sourceDates_long)) {
        return $checkSeg;
    }

    // valide targetDates aus sourceDates erzeugen

    $checkLong = false;

    foreach ($sourceDates as $key => $data) {
        if (isset($date['format_normal'])) {
            $format = $data['format_normal'];
        } else {
            $format = $data['format'];
        }
        $interDate = $data['date'];
        $srcDate = $data['date'];
        if (isset($data['date_inter'])) {
            $interDate = $data['date_inter'];
        }
        //$srcDate = $data['date'];
        if ($debug) {
            echo "\n--------------------------------------------------------------\n";
            echo "srcDate: $srcDate\n";
            echo "interDate: $interDate\n";
            echo "format: $format\n";
        }

        $formats_trg = [];

        if (strpos($format, 'Y') !== false) {
            $formats_trg = preg_grep('!Y!', $formats[$trgLang]);
        } elseif (stripos($format, 'y') !== false) {
            $formats_trg = preg_grep('!y!i', $formats[$trgLang]);
        } else {
            $formats_trg = preg_grep('!y!i', $formats[$trgLang], PREG_GREP_INVERT);
        }

        if ($debug) {
            echo "formats_trg:\n";
            print_r($formats_trg);
        }

        // date-Objekt erzeugen (aus Datum und dessen aktuellem Format)
        $d = date_create_from_format($format, $interDate);
        foreach ($formats_trg as $format_trg) {
            // Datum im Objekt mit neuem Format formatieren
            $trgDate = date_format($d, $format_trg);
            if ($debug) {
                echo "trgDate: {$trgDate}\n";
            }
            $targetDates[$srcDate][] = $trgDate;
            $targetDates[$srcDate] = array_unique($targetDates[$srcDate]);
        }
        if ($checkLong) {
            if (isset($data['month_num']) &&
                !empty($data['month_num']) &&
                isset($monthInfo[$trgLang]) &&
                isset($formats_month[$trgLang])) {
                $monthNum = $data['month_num'];
                //$allMonths = array_merge($months[$trgLang][$monthNum]);

                $knownMonths = [];
                foreach ($monthInfo[$trgLang][$monthNum] as $style => $month_trg) {
                    if (isset($knownMonths[$month_trg])) {
                        continue;
                    }
                    $knownMonths[$month_trg] = true;

                    $monthParts = preg_split('!!u', $month_trg);
                    $month_trg_esc = join('\\', $monthParts);
                    if ($debug) {
                        echo "month_trg_esc: $month_trg_esc\n";
                    }
                    foreach ($formats_month[$trgLang] as $format_trg2) {
                        $format_trg2 = str_replace('MONTH', $month_trg_esc, $format_trg2);
                        $trgDate = date_format($d, $format_trg2);
                        $targetDates[$srcDate][] = $trgDate;
                        $targetDates[$srcDate] = array_unique($targetDates[$srcDate]);
                    }
                }
            }
        }
    }
    if ($debug) {
        echo "\n==========================================\n";
        echo "targetDates (1):\n";
        print_r($targetDates);
        print_r(DateTime::getLastErrors());
    }

    // ACHTUNG: mit checkLong = true noch nicht richtig getestet!
    $checkLong = false;
    $targetDates_long = [];

    foreach ($sourceDates_long as $key => $data) {
        if (isset($date['format_normal'])) {
            $format = $data['format_normal'];
        } else {
            $format = $data['format'];
        }
        $interDate = $data['date'];
        $srcDate = $data['date'];
        if (isset($data['date_inter'])) {
            $interDate = $data['date_inter'];
        }
        //$srcDate = $data['date'];
        if ($debug) {
            echo "\n--------------------------------------------------------------\n";
            echo "srcDate: $srcDate\n";
            echo "interDate: $interDate\n";
            echo "format: $format\n";
        }

        $formats_trg = [];

        if (strpos($format, 'Y') !== false) {
            $formats_trg = preg_grep('!Y!', $formats[$trgLang]);
        } elseif (stripos($format, 'y') !== false) {
            $formats_trg = preg_grep('!y!i', $formats[$trgLang]);
        } else {
            $formats_trg = preg_grep('!y!i', $formats[$trgLang], PREG_GREP_INVERT);
        }
        if ($debug) {
            echo "formats_trg:\n";
            print_r($formats_trg);
        }

        // date-Objekt erzeugen (aus Datum und dessen aktuellem Format)
        $d = date_create_from_format($format, $interDate);
        foreach ($formats_trg as $format_trg) {
            // Datum im Objekt mit neuem Format formatieren
            $trgDate = date_format($d, $format_trg);
            if ($debug) {
                echo "trgDate: {$trgDate}\n";
            }
            $targetDates[$srcDate][] = $trgDate;
            $targetDates[$srcDate] = array_unique($targetDates[$srcDate]);
        }
        if ($checkLong) {
            if (isset($data['month_num']) &&
                !empty($data['month_num']) &&
                isset($monthInfo[$trgLang]) &&
                isset($formats_month[$trgLang])) {
                $monthNum = $data['month_num'];
                //$allMonths = array_merge($months[$trgLang][$monthNum]);

                $knownMonths = [];
                foreach ($monthInfo[$trgLang][$monthNum] as $style => $month_trg) {
                    if (isset($knownMonths[$month_trg])) {
                        continue;
                    }
                    $knownMonths[$month_trg] = true;

                    $monthParts = preg_split('!!u', $month_trg);
                    $month_trg_esc = join('\\', $monthParts);
                    if ($debug) {
                        echo "month_trg_esc: $month_trg_esc\n";
                    }
                    foreach ($formats_month[$trgLang] as $format_trg2) {
                        $format_trg2 = str_replace('MONTH', $month_trg_esc, $format_trg2);
                        $trgDate = date_format($d, $format_trg2);
                        $targetDates[$srcDate][] = $trgDate;
                        $targetDates[$srcDate] = array_unique($targetDates[$srcDate]);
                    }
                }
            }
        }
    }
    if ($debug) {
        echo "\n==========================================\n";
        echo "targetDates (2):\n";
        print_r($targetDates);
        print_r(DateTime::getLastErrors());
    }

    foreach ($targetDates as $srcDate => $trgDates) {
        //$srcCount = substr_count($srcSeg, $srcDate);
        $srcCount = preg_match_all("#(?<!\d)" . preg_quote($srcDate) . "(?!\d)#", $srcSeg);
        $trgCountTotal = 0;
        $trgVersions = [];
        //print_r($trgVersions);
        $counts = [];
        foreach ($trgDates as $trgDate) {
            //$trgCount = substr_count($trgSeg, $trgDate);
            $trgCount = preg_match_all("#(?<!\d)" . preg_quote($trgDate) . "(?!\d)#", $trgSeg);
            $trgCountTotal += $trgCount;
            $counts[$trgDate] = $trgCount;
            if ($trgCount > 0) {
                $trgVersions[] = $trgDate;
            }
        }

        $checkProps['checkSubType_extra'] = "Datumsangaben {$srcDate}";
        $checkProps['checkSubType_extra_EN'] = 'Dates';
        if ($debug) {
            echo "\n--------------------------------------------------------------\n";
            //echo "srcCount $srcDate : $srcCount\n";
        }

        if ($srcCount == $trgCountTotal && count($trgVersions) == 1) {
            $trgDate = $trgVersions[0];
            $trgCount = $counts[$trgDate];
            if ($debug) {
                echo "ALLES GUT!\n";
                echo "=> Datumsangaben in SRC == TRG:\nSRC: {$srcDate} x {$srcCount}\nTRG: {$trgDate} x {$trgCount}\n";
            }
            //$srcSeg = str_replace($srcDate, "", $srcSeg);
            $srcSeg = preg_replace("#(?<!\d)" . preg_quote($srcDate) . "(?!\d)#", '', $srcSeg);
            //$trgSeg = str_replace($trgDate, "", $trgSeg);
            $trgSeg = preg_replace("#(?<!\d)" . preg_quote($trgDate) . "(?!\d)#", '', $trgSeg);
            if ($debug) {
                echo "srcSeg out: {$srcSeg}\n";
                echo "trgSeg out: {$trgSeg}\n";
            }

            $checkResults['checkMessage'] = "Datumsangaben in SRC === TRG:<br>SRC: {$srcDate} x {$srcCount}<br>TRG: {$trgDate} x {$trgCount}";
            $checkResults['checkMessage_EN'] = '...';
            doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
            // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);

            $checkResults['replaceString'] = '<span class="matchStandard">' . $srcDate . '</span>';
            $checkResults['matchRegEx'] = "#(?<!\d)" . preg_quote($srcDate) . "(?!\d)#u";
            $checkResults['markSrc'] = true;
            $checkResults['markTrg'] = false;
            // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);

            $checkResults['replaceString'] = '<span class="matchStandard">' . $trgDate . '</span>';
            $checkResults['matchRegEx'] = "#(?<!\d)" . preg_quote($trgDate) . "(?!\d)#u";
            $checkResults['markSrc'] = false;
            $checkResults['markTrg'] = true;
            // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
        } else {
            if (count($trgVersions) == 1) {
                $trgDate = $trgVersions[0];
                $trgCount = $counts[$trgDate];
                if ($debug) {
                    echo "NOPE 1\n";
                    echo "=> Datumsangaben in SRC != TRG:\nSRC: {$srcDate} x {$srcCount}\nTRG (alle Varianten):\n";
                }

                //$srcSeg = str_replace($srcDate, "", $srcSeg);
                $srcSeg = preg_replace("#(?<!\d)" . preg_quote($srcDate) . "(?!\d)#", '', $srcSeg);
                $msg = '';
                foreach ($trgDates as $trgDate) {
                    $trgCount = $counts[$trgDate];
                    if ($debug) {
                        echo "{$trgDate} x {$trgCount}\n";
                    }
                    $msg .= "{$trgDate} x {$trgCount}<br>";
                    //$trgSeg = str_replace($trgDate, "", $trgSeg);
                    if ($trgCount > 0) {
                        $trgSeg = preg_replace("#(?<!\d)" . preg_quote($trgDate) . "(?!\d)#", '', $trgSeg);

                        $checkResults['replaceString'] = '<span class="matchStandard">' . $trgDate . '</span>';
                        $checkResults['matchRegEx'] = "#(?<!\d)" . preg_quote($trgDate) . "(?!\d)#u";
                        $checkResults['markSrc'] = false;
                        $checkResults['markTrg'] = true;
                        // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
                    }
                }

                $checkResults['checkMessage'] = "Datumsangaben in SRC != TRG:<br>SRC: {$srcDate} x {$srcCount}<br>TRG (alle Varianten):<br>{$msg}";
                $checkResults['checkMessage_EN'] = '...';

                $checkResults['replaceString'] = '<span class="matchStandard">' . $srcDate . '</span>';
                $checkResults['matchRegEx'] = "#(?<!\d)" . preg_quote($srcDate) . "(?!\d)#u";
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = false;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
                if ($debug) {
                    echo "srcSeg out: {$srcSeg}\n";
                    echo "trgSeg out: {$trgSeg}\n";
                }
            } elseif (count($trgVersions) < 1) {
                if ($debug) {
                    echo "NOPE 2\n";
                    echo "=> Keine TRG-Entsprechung zu Datumsangabe [{$srcDate}] gefunden. Erlaubte Varianten (bitte konsistent verwenden):\n";
                }
                $msg = '';
                foreach ($trgDates as $trgDate) {
                    $trgCount = $counts[$trgDate];
                    if ($debug) {
                        echo "{$trgDate}\n";
                    }
                    $msg .= "<code class=\"term\">{$trgDate}</code><br>";
                }

                $checkResults['checkMessage'] = "Keine TRG-Entsprechung zu Datumsangabe [<code class=\"term\">{$srcDate}</code>] gefunden.<br>Erlaubte Varianten (bitte konsistent verwenden):<br>{$msg}";
                $checkResults['checkMessage_EN'] = '...';

                $checkResults['replaceString'] = '<span class="matchStandard">' . $srcDate . '</span>';
                $checkResults['matchRegEx'] = "#(?<!\d)" . preg_quote($srcDate) . "(?!\d)#u";
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = false;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
            } elseif (count($trgVersions) > 1) {
                if ($debug) {
                    echo "NOPE 3\n";
                    echo "=> SRC-Datum {$srcDate} in TRG nicht konsistent formatiert. Gefundene Varianten:\n";
                }
                $msg = '';
                foreach ($trgVersions as $trgDate) {
                    $trgCount = $counts[$trgDate];
                    if ($debug) {
                        echo "{$trgDate} x {$trgCount}\n";
                    }
                    $msg .= "{$trgDate} x {$trgCount}<br>";
                }

                $checkResults['checkMessage'] = "SRC-Datum {$srcDate} in TRG nicht konsistent formatiert. Gefundene Varianten:<br>{$msg}";
                $checkResults['checkMessage_EN'] = '...';

                $checkResults['replaceString'] = '<span class="matchStandard">' . $srcDate . '</span>';
                $checkResults['matchRegEx'] = "#(?<!\d)" . preg_quote($srcDate) . "(?!\d)#u";
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = false;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
            }
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'END', __FUNCTION__);
    }

    $checkSeg[0] = $srcSeg;
    $checkSeg[1] = $trgSeg;

    return $checkSeg;
}
function validateDate($date, $format = 'Y-m-d')
{
    $d = date_create_from_format($format, $date);

    return $d && date_format($d, $format) == $date;
}
/** /sub checks */

/** subsub checks */
// Checken ob Counts bei echten Zahlen mit Trenner [,.] identisch
// bei mixed (Trenner sollten sich unterscheiden, außer es geht um Nummerierung die aussieht wie echte Zahl) -> Meldung
function checkForInvalidNums($numsCount, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];

    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    // Abbruchkriterien
    if ((empty($numsCount[0])) || (empty($numsCount[1]))) {
        if ($debug) {
            echo "mind 1 count emtpy, skippe $checkSubFunc \n";
        }

        return;
    }

    $monoTypeDE = isMonoTypeDE($langs[0], $langs[1]);
    $monoTypeEN = isMonoTypeEN($langs[0], $langs[1]);

    $mixedTypesEN2DE = isMixedTypesEN2DE($langs[0], $langs[1]);
    $mixedTypesDE2EN = isMixedTypesDE2EN($langs[0], $langs[1]);

    foreach ($numsCount[0] as $numOrig => $count) {
        if (!isset($numsCount[1][$numOrig])) {
            if ($debug) {
                echo "kein trg-Count für $numOrig \n";
            }
            continue;
        }

        if ($numsCount[0][$numOrig] == $numsCount[1][$numOrig]) {
            $doMessage = false;

            if ($monoTypeDE || $mixedTypesEN2DE) {
                if ((isKiloCommaNum($numOrig)) && (!isDeciCommaNum($numOrig))) {
                    $checkProps['checkSubType_extra'] = "In {$langs[1]} ggf. ungültige Zahl";
                    $checkProps['checkSubType_extra_EN'] = "Possibly invalid number in {$langs[1]}";

                    $checkResults['checkMessage'] = "[{$numOrig}] in {$langs[1]} ggf. ungültig (Komma als 1000er-Trenner?)";
                    $checkResults['checkMessage_EN'] = "[{$numOrig}] possibly invalid in {$langs[1]} (comma as 1000s separator?)";

                    $doMessage = true;
                } elseif ((preg_match('!,!', $numOrig)) && (!isDeciCommaNum($numOrig)) && (!isKiloCommaNum($numOrig))) {
                    $checkProps['checkSubType_extra'] = 'Ggf. ungültige Zahl gefunden';
                    $checkProps['checkSubType_extra_EN'] = 'Possibly invalid number found';

                    $checkResults['checkMessage'] = "[{$numOrig}] ggf. ungültig?";
                    $checkResults['checkMessage_EN'] = "[{$numOrig}] possibly invalid?";

                    $doMessage = true;
                }
            } elseif ($monoTypeEN || $mixedTypesDE2EN) {
                if ((isDeciCommaNum($numOrig)) && (!isKiloCommaNum($numOrig))) {
                    $checkProps['checkSubType_extra'] = "In {$langs[1]} ggf. ungültige Zahl";
                    $checkProps['checkSubType_extra_EN'] = 'Possibly invalid number found';

                    $checkResults['checkMessage'] = "[{$numOrig}] in {$langs[1]} ggf. ungültig (Komma als Dezimaltrenner?)";
                    $checkResults['checkMessage_EN'] = "[{$numOrig}] possibly invalid in {$langs[1]} (Comma used as decimal separator?)";

                    $doMessage = true;
                } elseif ((preg_match('!,!', $numOrig)) && (!isKiloCommaNum($numOrig))) {
                    $checkProps['checkSubType_extra'] = 'Ggf. ungültige Zahl gefunden';
                    $checkProps['checkSubType_extra_EN'] = 'Possibly invalid number found';

                    $checkResults['checkMessage'] = "[{$numOrig}] sieht dubios aus";
                    $checkResults['checkMessage_EN'] = "[{$numOrig}] seems dubious";

                    $doMessage = true;
                }
            }

            if ($doMessage) {
                $match = $numOrig;
                $match = str_replace("\-", "\p{Pd}", preg_quote($match));

                $checkResults['replaceString'] = '<span class="matchStandard">' . $numOrig . '</span>';
                $checkResults['matchRegEx'] = "#(?<![\d])" . $match . "(?![\d])#u";
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = true;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
            }
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
}
// Checken ob Counts bei echten Zahlen mit Trenner [,.] identisch
// bei mixed (Trenner sollten sich unterscheiden, außer es geht um Nummerierung die aussieht wie echte Zahl) -> Meldung
// Ausnahmen: DE2EN, wenn maybeNonLokaPointNum
function compareNumCounts_asIs2($numsCount, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];

    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    if ((empty($numsCount[0])) || (empty($numsCount[1]))) {
        if ($debug) {
            echo "mind 1 count emtpy, skip\n";
        }

        return $numsCount;
    }

    $monoTypeDE = isMonoTypeDE($langs[0], $langs[1]);
    $monoTypeEN = isMonoTypeEN($langs[0], $langs[1]);

    $mixedTypesEN2DE = isMixedTypesEN2DE($langs[0], $langs[1]);
    $mixedTypesDE2EN = isMixedTypesDE2EN($langs[0], $langs[1]);

    foreach ($numsCount[0] as $numOrig => $count) {
        if (!isset($numsCount[1][$numOrig])) {
            if ($debug) {
                echo "kein trg-Count für $numOrig \n";
            }
            continue;
        }

        $doCheck = false;

        if ((noLokaAllowed($sncSettings)) ||
            (is_int($numOrig)) ||
            ($monoTypeDE) ||
            ($monoTypeEN) ||
            (!isRealNum($numOrig))) {
            if ($debug) {
                echo "bed1: $numOrig\n";
            }
            $doCheck = true;
        } elseif (($mixedTypesDE2EN || $mixedTypesEN2DE) && (!isRealNum($numOrig))) {
            if ($debug) {
                echo "bed2: $numOrig\n";
            }
            $doCheck = true;
        }

        if ($doCheck) {
            if ($numsCount[0][$numOrig] == $numsCount[1][$numOrig]) {
                unset($numsCount[0][$numOrig]);
                unset($numsCount[1][$numOrig]);
            }
        } else {
            if ($debug) {
                echo "Keine Bed erfüllt, skip für $numOrig\n";
            }
            continue;
        }
    }

    if ($debug) {
        print_r($numsCount);
    }
    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'ENDE', __FUNCTION__);
    }

    return $numsCount;
}
function compareNumCounts_explode2($numsCount, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];

    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];
    $seg[0] = $currentData['segTypes']['srcSeg'];
    $seg[1] = $currentData['segTypes']['trgSeg'];
    $segPure[0] = $currentData['segTypes']['srcSegPure'];
    $segPure[1] = $currentData['segTypes']['trgSegPure'];

    if (noLokaAllowed($sncSettings)) {
        if ($debug) {
            echo "Bei diesem Kunden keine Lokalisierung von Zahlen -> Skip \n";
        }

        return $numsCount;
    }
    if ((empty($numsCount[0])) && (empty($numsCount[1]))) {
        if ($debug) {
            echo "mind 1 count emtpy, skipp\n";
        }

        return $numsCount;
    }

    $monthWords = getMonthWords($langs);

    $explodeNums = [];
    $origNums2 = [];
    $normalNumsCount = [];
    $allNormalNumsInTU2 = [];

    for ($i = 0; $i < 2; $i++) {
        $numPartsAll[$i] = [];

        foreach ($numsCount[$i] as $numOrig => $count) {
            for ($k = 1; $k <= $count; $k++) {
                $numParts = [];

                if ($debug) {
                    echo "\$k: $k\n";
                }

                $num = $numOrig;
                if ($debug) {
                    echo "num1 $num\n";
                }

                $split = false;

                if (!isRealNum($num)) {
                    $split = true;
                } else {
                    if ($debug) {
                        echo " $num ist echte Zahl, besser nicht splitten\n";
                    }
                    //continue;
                }

                if ($split) {
                    if ($debug) {
                        echo "$num keine echte Zahl -> split\n";
                    }
                    if (preg_match('!\.$!', $num)) {
                        if ($debug) {
                            echo "newCleanOK 2 in: $num\n";
                        }
                        $num = preg_replace("!\.$!u", '', $num);
                        if ($debug) {
                            echo "newCleanOK 2 out: $num\n";
                        }
                    }
                    $numParts = preg_split("![\.]!u", $num);
                } else {
                    $numParts[] = $num;
                }

                foreach ($numParts as $num) {
                    // Tag/Monat gerne auch ohne führende Null

                    if (preg_match('!^0([1-9])$!u', $num, $m)) {
                        $num = $m[1];
                    }

                    $numPartsAll[$i][] = $num;

                    if ($debug) {
                        echo "-> num2 $num\n";
                    }

                    $allNormalNumsInTU2[$num] = true;

                    if (!isset($normalNumsCount[$i][$num])) {
                        $normalNumsCount[$i][$num] = 0;
                    }
                    $normalNumsCount[$i][$num]++;
                    $origNums2[$i][$num][] = $numOrig;
                }
            }
        }
    }

    //print_r($numList);

    if ($debug) {
        echo "numPartsAll ex2: \n";
    }
    if ($debug) {
        print_r($numPartsAll);
    }

    for ($i = 0; $i < 2; $i++) {
        $numPartsAll2[$i] = array_count_values($numPartsAll[$i]);
    }

    if ($debug) {
        echo "numPartsAll2 ex2: \n";
    }
    if ($debug) {
        print_r($numPartsAll2);
    }
    if ($debug) {
        echo "origNums2 ex2: \n";
    }
    if ($debug) {
        print_r($origNums2);
    }
    if ($debug) {
        echo "normalNumsCount #1 ex2: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }
    if ($debug) {
        echo "allNormalNumsInTU2 ex2: \n";
    }
    if ($debug) {
        print_r($allNormalNumsInTU2);
    }

    $doubleDate[0] = false;
    $doubleDate[1] = false;
    $singleDate[0] = false;
    $singleDate[1] = false;

    $dateRegEx = '!\d{1,2}\.\d{1,2}\.\d{4}!u';
    $dates = [];
    if ((count($numPartsAll[0]) == 6) && (count($numPartsAll[1]) == 4)) {
        $doubleDate[0] = true;
        preg_match_all($dateRegEx, $segPure[0], $dates);
        if (count($dates[0]) != 2) {
            $doubleDate[0] = false;
        }
    } elseif ((count($numPartsAll[1]) == 6) && (count($numPartsAll[0]) == 4)) {
        $doubleDate[1] = true;
        preg_match_all($dateRegEx, $segPure[1], $dates);
        if (count($dates[0]) != 2) {
            $doubleDate[1] = false;
        }
    } elseif ((count($numPartsAll[0]) == 3) && (count($numPartsAll[1]) == 2)) {
        $singleDate[0] = true;
        preg_match_all($dateRegEx, $segPure[0], $dates);
        if (count($dates[0]) != 1) {
            $singleDate[0] = false;
        }
    } elseif ((count($numPartsAll[1]) == 3) && (count($numPartsAll[0]) == 2)) {
        $singleDate[1] = true;
        preg_match_all($dateRegEx, $segPure[1], $dates);
        if (count($dates[0]) != 1) {
            $singleDate[1] = false;
        }
    }

    if ($debug) {
        echo "dates:\n";
        print_r($doubleDate);
        print_r($singleDate);
    }

    if ($doubleDate[0] || $doubleDate[1] || $singleDate[0] || $singleDate[1] || (abs(count($numPartsAll[0]) - count($numPartsAll[1])) < 2)) {
        //if($debug) echo "Eine Zahl Unterschied... Könnte Datum oder Telefonnummer sein...\n";
        if ($debug) {
            echo "Plausibler Unterschied. Könnte Datum oder Telefonnummer sein.\n";
        }

        if (count($numPartsAll[0]) > count($numPartsAll[1])) {
            $diffNums = array_values(array_diff($numPartsAll[0], $numPartsAll[1]));

            if (!isset($diffNums[0])) {
                if ($debug) {
                    echo "keine Diffnum (v1) in numPartsAll. nehme anderes Array\n";
                }

                $diffNums = (array_diff_assoc($numPartsAll2[0], $numPartsAll2[1]));

                if ($debug) {
                    print_r($diffNums);
                }

                $diffNums2 = array_keys($diffNums);

                if (count($diffNums2) != 1) {
                    return $numsCount;
                }
                $diffNum = $diffNums2[0];
            } else {
                $diffNum = $diffNums[0];
            }

            if ($debug) {
                echo "unterschied v1: $diffNum \n";
            }

            if (isset($monthWords[1][$diffNum])) {
                $wordsCount = 0;

                foreach ($monthWords[1][$diffNum] as $word) {
                    $word = preg_quote(trim($word));

                    if ($debug) {
                        echo 'month word: |' . $word . "|\n";
                    }
                    if ($debug) {
                        echo 'trgSegPure: |' . $segPure[1] . "|\n";
                    }

                    if (preg_match_all("#(?<!\p{L})$word(?!\p{L})#ui", $segPure[1], $m)) {
                        if ($debug) {
                            echo "match:\n";
                        }
                        if ($debug) {
                            print_r($m);
                        }

                        if ($debug) {
                            echo "\nxxxxxxxxxxxxxxxxx\n";
                        }
                        if ($debug) {
                            echo " month word: $word\n";
                        }
                        if ($debug) {
                            echo " wordsCount in: $wordsCount\n";
                        }
                        if ($debug) {
                            echo "\nxxxxxxxxxxxxxxxxx\n";
                        }

                        $numOrigTrg = $word;
                        $wordsCount = $wordsCount + count($m[0]);
                        if ($debug) {
                            echo "wordsCount out: $wordsCount	\n";
                        }

                        $origNums2[1][$diffNum][] = $word;
                        break;
                    } elseif ($debug) {
                        echo "Kein match!\n";
                    }
                }

                if ($debug) {
                    print_r($normalNumsCount);
                }

                if (!isset($normalNumsCount[1][$diffNum])) {
                    $normalNumsCount[1][$diffNum] = $wordsCount;
                } else {
                    $normalNumsCount[1][$diffNum] = $normalNumsCount[1][$diffNum] + $wordsCount;
                }
                if ($debug) {
                    print_r($normalNumsCount);
                }
            }
        } elseif (count($numPartsAll[0]) < count($numPartsAll[1])) {
            $diffNums = array_values(array_diff($numPartsAll[1], $numPartsAll[0]));

            if (!isset($diffNums[0])) {
                if ($debug) {
                    echo "keine Diffnum (v2) in numPartsAll. nehme anderes Array\n";
                }

                $diffNums = (array_diff_assoc($numPartsAll2[0], $numPartsAll2[1]));

                if ($debug) {
                    print_r($diffNums);
                }

                $diffNums2 = array_keys($diffNums);

                if (count($diffNums2) != 1) {
                    return $numsCount;
                }
                $diffNum = $diffNums2[0];
            } else {
                $diffNum = $diffNums[0];
            }

            if ($debug) {
                echo "unterschied v2: $diffNum \n";
            }

            if (isset($monthWords[0][$diffNum])) {
                $wordsCount = 0;

                foreach ($monthWords[0][$diffNum] as $word) {
                    $word = preg_quote(trim($word));

                    if ($debug) {
                        echo 'check word: |' . $word . "|\n";
                    }
                    if ($debug) {
                        echo 'srcSegPure: |' . $segPure[0] . "|\n";
                    }

                    if (preg_match_all("#(?<!\p{L})$word(?!\p{L})#ui", $segPure[0], $m)) {
                        if ($debug) {
                            echo "match:\n";
                        }
                        if ($debug) {
                            print_r($m);
                        }

                        if ($debug) {
                            echo "\nxxxxxxxxxxxxxxxxx\n";
                        }
                        if ($debug) {
                            echo " month word: $word\n";
                        }
                        if ($debug) {
                            echo " wordsCount in: $wordsCount\n";
                        }
                        if ($debug) {
                            echo "\nxxxxxxxxxxxxxxxxx\n";
                        }

                        $numOrigSrc = $word;
                        $wordsCount = $wordsCount + count($m[0]);
                        if ($debug) {
                            echo "wordsCount out: $wordsCount	\n";
                        }

                        $origNums2[0][$diffNum][] = $word;
                        break;
                    }
                }

                if ($debug) {
                    print_r($normalNumsCount);
                }
                if (!isset($normalNumsCount[0][$diffNum])) {
                    $normalNumsCount[0][$diffNum] = $wordsCount;
                } else {
                    $normalNumsCount[0][$diffNum] = $normalNumsCount[0][$diffNum] + $wordsCount;
                }
                if ($debug) {
                    print_r($normalNumsCount);
                }
            }
        }
    }

    foreach ($allNormalNumsInTU2 as $num => $state) {
        if (!isset($normalNumsCount[0][$num])) {
            $normalNumsCount[0][$num] = 0;
        }
        if (!isset($normalNumsCount[1][$num])) {
            $normalNumsCount[1][$num] = 0;
        }
    }
    if ($debug) {
        echo "normalNumsCount #2 ex2: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }

    ksort($normalNumsCount[0]);
    ksort($normalNumsCount[1]);

    if ($debug) {
        echo "normalNumsCount #2 ex2 sorted: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }

    $unsetAll = false;

    if ($normalNumsCount[0] === $normalNumsCount[1]) {
        if ($debug) {
            echo "normalCount[0] == normalCount[1]\n";
        }
        $unsetAll = true;
    } else {
        if ($debug) {
            echo "normalCount[0] !== normalCount[1]\n";
        }
    }

    $unsetOrigs = [];
    if ($debug) {
        echo "origNums Ex2: \n";
    } //if($debug)

    if ($unsetAll) {
        $numsCount[0] = [];
        $numsCount[1] = [];

        $unsets = [];

        foreach ($normalNumsCount[0] as $numNormal => $count) {
            foreach ($origNums2[0][$numNormal] as $origNum) {
                $unsetOrigs[0][$origNum] = true;
                $unsets[0][] = $origNum;
            }
        }
        foreach ($normalNumsCount[1] as $numNormal => $count) {
            foreach ($origNums2[1][$numNormal] as $origNum) {
                $unsetOrigs[1][$origNum] = true;
                $unsets[1][] = $origNum;
            }
        }

        if ($debug) {
            echo "unsetOrigs: \n";
        }
        if ($debug) {
            print_r($unsetOrigs);
        }

        $unsets[0] = array_unique($unsets[0]);
        $unsets[1] = array_unique($unsets[1]);

        $unsetList[0] = join('|', $unsets[0]);
        $unsetList[1] = join('|', $unsets[1]);

        if ($debug) {
            echo "unsetList: \n";
        }
        if ($debug) {
            print_r($unsetList);
        }

        if ($debug) {
            echo "EX2 Zahlenformat geändert (Datum... o.ä.): SRC [{$unsetList[0]}] vs TRG [{$unsetList[1]}]\n";
        }

        $checkProps['checkSeverity_extra'] = 'notice';

        $checkProps['checkSubType_extra'] = 'Formatänderung (Datumsangaben u.ä.) [Typ2]';
        $checkProps['checkSubType_extra_EN'] = 'Format alteration (date information etc.) [type2]';

        $checkResults['checkMessage'] = "Hinweis: Formatänderung: SRC [{$unsetList[0]}] vs TRG [{$unsetList[1]}] [Typ2]";
        $checkResults['checkMessage'] = "Formatänderung (Datumsangaben u.ä.): SRC [{$unsetList[0]}] vs TRG [{$unsetList[1]}]";
        $checkResults['checkMessage_EN'] = "Note: Format alteration: SRC [{$unsetList[0]}] vs TRG [{$unsetList[1]}] [Typ2]";

        if (true) {
            doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
            // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
        }

        if ($debug) {
            echo "numsCountEx2: \n";
        }
        if ($debug) {
            print_r($numsCount);
        }
        if ($debug) {
            echo "normalNumsCountEx2: \n";
        }
        if ($debug) {
            print_r($normalNumsCount);
        }

        for ($i = 0; $i < 2; $i++) {
            foreach ($unsets[$i] as $match) {
                $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
                if (!is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
                }
                if (is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d\"])" . preg_quote($match) . "(?![\d\"])#u";
                }

                if ($i == 0) {
                    $checkResults['markSrc'] = true;
                    $checkResults['markTrg'] = false;
                }
                if ($i == 1) {
                    $checkResults['markSrc'] = false;
                    $checkResults['markTrg'] = true;
                }
            }
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }

    return $numsCount;
}
// Checken ob Counts identisch, wenn Trenner in SRC-Zahlen umgedreht werden
// Wenn MonoType (Trenner sollten eigentlich identisch sein): Meldung ausgeben
function compareNumCounts_flipDiv($numsCount, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];

    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    $checkSeg[0] = $currentData['segTypes']['srcSegPureNoPH'];
    $checkSeg[1] = $currentData['segTypes']['trgSegPureNoPH'];

    if (noLokaAllowed($sncSettings)) {
        if ($debug) {
            echo "Bei diesem Kunden keine Loka von Zahlen -> Skip\n";
        }

        return $numsCount;
    }
    if ((empty($numsCount[0])) || (empty($numsCount[1]))) {
        if ($debug) {
            echo "mind 1 count emtpy, skip \n";
        }

        return $numsCount;
    }

    $monoTypeDE = isMonoTypeDE($langs[0], $langs[1]);
    $monoTypeEN = isMonoTypeEN($langs[0], $langs[1]);

    $mixedTypesEN2DE = isMixedTypesEN2DE($langs[0], $langs[1]);
    $mixedTypesDE2EN = isMixedTypesDE2EN($langs[0], $langs[1]);

    $flipDivNums = [];
    $origNums2 = [];
    $normalNumsCount = [];
    $allNormalNumsInTU2 = [];

    for ($i = 0; $i < 2; $i++) {
        foreach ($numsCount[$i] as $numOrig => $count) {
            $num = $numOrig;
            if ($debug) {
                echo "num1 $num\n";
            }

            if (isRealNum_lang($num, $langs[$i])) {
                if ($debug) {
                    echo "isRealNum_lang {$langs[$i]}: $num\n";
                }
                if ($debug) {
                    echo "NewRealNumIn: $num\n";
                }
                if ($i != 1) {
                    $num = flipDiv($num);
                }
                if ($debug) {
                    echo "NewRealNumOut: $num\n";
                }
            } else {
                if ($debug) {
                    echo "!isRealNum_lang $num \$i == $i\n";
                }
                //continue;
            }

            if ($debug) {
                echo "-> num2 $num\n";
            }
            $allNormalNumsInTU2[$num] = true;

            if (!isset($normalNumsCount[$i][$num])) {
                $normalNumsCount[$i][$num] = $count;
            } else {
                $normalNumsCount[$i][$num] = $count + $normalNumsCount[$i][$num];
            }

            if ($count == 0) {
                continue;
            }
            $origNums2[$i][$num][] = $numOrig;
        }
    }
    if ($debug) {
        echo "origNums2: \n";
    }
    if ($debug) {
        print_r($origNums2);
    }
    if ($debug) {
        echo "normalNumsCount #1: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }
    if ($debug) {
        echo "allNormalNumsInTU2: \n";
    }
    if ($debug) {
        print_r($allNormalNumsInTU2);
    }

    foreach ($allNormalNumsInTU2 as $num => $state) {
        if (!isset($normalNumsCount[0][$num])) {
            $normalNumsCount[0][$num] = 0;
        }
        if (!isset($normalNumsCount[1][$num])) {
            $normalNumsCount[1][$num] = 0;
        }
    }
    if ($debug) {
        echo "normalNumsCount #2: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }

    foreach ($normalNumsCount[0] as $numSrcNormal => $srcCount) {
        if (!isset($normalNumsCount[1][$numSrcNormal])) {
            continue;
        }
        $trgCount = $normalNumsCount[1][$numSrcNormal];

        if ($srcCount == $trgCount) {
            if ($debug) {
                echo "match srcNormal $numSrcNormal -> $srcCount == $trgCount\n";
            }

            if (count($origNums2[0][$numSrcNormal]) != 1) {
                continue;
            }
            if (count($origNums2[1][$numSrcNormal]) != 1) {
                continue;
            }

            $origNumsNow = $origNums2[0][$numSrcNormal];

            if ($debug) {
                echo "origNumsNow SRC: \n";
            }
            if ($debug) {
                print_r($origNumsNow);
            }

            $origNum = $origNumsNow[0];
            $flipDivNums[0][$numSrcNormal] = $origNum;
            unset($numsCount[0][$origNum]);
        }
    }

    foreach ($normalNumsCount[1] as $numTrgNormal => $trgCount) {
        if (!isset($normalNumsCount[0][$numTrgNormal])) {
            continue;
        }
        $srcCount = $normalNumsCount[0][$numTrgNormal];

        if ($srcCount == $trgCount) {
            if ($debug) {
                echo "match trgNormal $numTrgNormal -> $srcCount == $trgCount\n";
            }

            if (count($origNums2[0][$numTrgNormal]) != 1) {
                continue;
            }
            if (count($origNums2[1][$numTrgNormal]) != 1) {
                continue;
            }

            $origNumsNow = $origNums2[1][$numTrgNormal];

            if ($debug) {
                echo "origNumsNow TRG: \n";
            }
            if ($debug) {
                print_r($origNumsNow);
            }

            $origNum = $origNumsNow[0];
            $flipDivNums[1][$numTrgNormal] = $origNum;
            unset($numsCount[1][$origNum]);
        }
    }

    if ($debug) {
        echo "numsCount: \n";
    }
    if ($debug) {
        print_r($numsCount);
    }
    if ($debug) {
        echo "normalNumsCount: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }
    if ($debug) {
        echo "flipDivNums: \n";
    }
    if ($debug) {
        print_r($flipDivNums);
    }

    if ((!empty($flipDivNums[0])) && (!empty($flipDivNums[1]))) {
        if (count($flipDivNums[0]) == count($flipDivNums[1])) {
            foreach ($flipDivNums[0] as $normalNum => $numOrigSrc) {
                $numOrigTrg = $flipDivNums[1][$normalNum];

                $doMessage = false;

                $chSpezial_DE = '';
                $chSpezial_EN = '';

                if (($monoTypeDE) || ($monoTypeEN) || (noLokaAllowed($sncSettings))) {
                    $doMessage = true;
                }

                if ((!isDecimalPointLang($langs[0])) &&
                    ($langs[1] == 'fr-CH') &&
                    (isDeciPointNum($numOrigTrg))) {
                    if ((preg_match("!(EURO?|€|CHF|Fr\.|\$|£)?\p{Zs}?{$numOrigTrg}\p{Zs}?(EURO?|€|CHF|Fr\.|\$|£)?!iu", $checkSeg[1], $m)) &&
                        ((!empty($m[1])) || (!empty($m[2])))) {
                        if ($debug) {
                            echo "\n!! Waehrung !!\n";
                        }
                        $doMessage = false;
                    } else {
                        $doMessage = true;
                        $chSpezial_DE = '<br><b>Hinweis:</b> Diese Meldung kann ignoriert werden, falls es sich bei der bemängelten Zahl in TRG um eine Währungsangabe oder Koordinate handelt. Diese werden in der Schweiz mit einem Punkt als Dezimaltrenner geschrieben, sind aber im SNC aktuell noch nicht zu 100% berücksichtigt.';
                        $chSpezial_EN = '<br><b>Please note:</b> This message can be ignored if the TRG number is a currency value or coordinate, for which a decimal point is used in Switzerland.';
                    }
                }

                if ($doMessage) {
                    if ($debug) {
                        echo "SRC [$numOrigSrc] und TRG [$numOrigTrg] mit flipDiv identisch (=> $normalNum)\n";
                    }

                    $checkProps['checkSeverity_extra'] = 'warning';

                    $checkProps['checkSubType_extra'] = 'Trenner aus SRC geändert';
                    $checkProps['checkSubType_extra_EN'] = 'Separator from SRC altered';

                    $checkResults['checkMessage'] = "Trenner aus SRC geändert in [{$numOrigSrc} vs. {$numOrigTrg}]. Nicht korrekt lokalisiert (oder Fehler in SRC?){$chSpezial_DE}";
                    $checkResults['checkMessage_EN'] = "Separator from SRC altered in [{$numOrigSrc} vs. {$numOrigTrg}]. Not correctly localised (or error in SRC?){$chSpezial_EN}";

                    doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                    //// doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);

                    $match = $numOrigSrc;
                    $match = str_replace("\-", "\p{Pd}", preg_quote($match));

                    $checkResults['replaceString'] = '<span class="matchStandard">' . $numOrigSrc . '</span>';
                    $checkResults['matchRegEx'] = "#(?<![\d])" . $match . "(?![\d])#u";
                    $checkResults['markSrc'] = true;
                    $checkResults['markTrg'] = false;

                    // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);

                    $match = $numOrigTrg;
                    $match = str_replace("\-", "\p{Pd}", preg_quote($match));

                    $checkResults['replaceString'] = '<span class="matchStandard">' . $numOrigTrg . '</span>';
                    $checkResults['matchRegEx'] = "#(?<![\d])" . $match . "(?![\d])#u";
                    $checkResults['markSrc'] = false;
                    $checkResults['markTrg'] = true;

                    // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
                }
            }
        } else {
            if ($debug) {
                echo "pseudoDivCount ungleich,,,, :(\n";
            }
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }

    return $numsCount;
}
// Datumsangaben (solche am Stück), Ordinalzahlen, führende Null
function compareNumCounts_leadingZero_trailingPeriod($numsCount, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];

    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    if (noLokaAllowed($sncSettings)) {
        if ($debug) {
            echo "Bei diesem Kunden keine Loka von Zahlen -> Skip \n";
        }

        return $numsCount;
    }
    if ((empty($numsCount[0])) || (empty($numsCount[1]))) {
        if ($debug) {
            echo "mind 1 count emtpy, skip \n";
        }

        return $numsCount;
    }

    $explodeNums = [];
    $origNums2 = [];
    $normalNumsCount = [];
    $allNormalNumsInTU2 = [];

    for ($i = 0; $i < 2; $i++) {
        foreach ($numsCount[$i] as $numOrig => $count) {
            $num = $numOrig;
            if ($debug) {
                echo "num1 $num\n";
            }

            $split = false;

            if (!isRealNum($num)) {
                $split = true;
            } else {
                if ($debug) {
                    echo " $num ist echte Zahl, besser nicht splitten\n";
                }
                //continue;
            }

            if ($split) {
                if ($debug) {
                    echo "$num keine echte Zahl -> split\n";
                }
                if (preg_match('!\.$!', $num)) {
                    if ($debug) {
                        echo "newCleanOK 2 in: $num\n";
                    }
                    $num = preg_replace("!\.$!u", '', $num);
                    if ($debug) {
                        echo "newCleanOK 2 out: $num\n";
                    }
                }
                if (preg_match('!^0(\d)$!', $num, $m)) {
                    if ($debug) {
                        echo "newCleanOK 3 in: $num\n";
                    }
                    $num = $m[1];
                    if ($debug) {
                        echo "newCleanOK 3 out: $num\n";
                    }
                }
            }

            if ($debug) {
                echo "-> num2 $num\n";
            }

            $allNormalNumsInTU2[$num] = true;

            if (!isset($normalNumsCount[$i][$num])) {
                $normalNumsCount[$i][$num] = $count;
            } else {
                $normalNumsCount[$i][$num] = $count + $normalNumsCount[$i][$num];
            }
            if ($count == 0) {
                continue;
            }

            $origNums2[$i][$num][] = $numOrig;
        }
    }

    if ($debug) {
        echo "origNums2: \n";
    }
    if ($debug) {
        print_r($origNums2);
    }
    if ($debug) {
        echo "normalNumsCount #1: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }
    if ($debug) {
        echo "allNormalNumsInTU2: \n";
    }
    if ($debug) {
        print_r($allNormalNumsInTU2);
    }

    foreach ($allNormalNumsInTU2 as $num => $state) {
        if (!isset($normalNumsCount[0][$num])) {
            $normalNumsCount[0][$num] = 0;
        }
        if (!isset($normalNumsCount[1][$num])) {
            $normalNumsCount[1][$num] = 0;
        }
    }
    if ($debug) {
        echo "normalNumsCount #2: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }

    foreach ($normalNumsCount[0] as $numSrcNormal => $srcCount) {
        if (!isset($normalNumsCount[1][$numSrcNormal])) {
            continue;
        }
        $trgCount = $normalNumsCount[1][$numSrcNormal];

        if ($srcCount === $trgCount) {
            if ($debug) {
                echo "match $numSrcNormal -> $srcCount === $trgCount\n";
            }

            if (!isset($origNums2[0][$numSrcNormal])) {
                echo "NISSET \n";
            }

            if (count($origNums2[0][$numSrcNormal]) != 1) {
                continue;
            }
            if (count($origNums2[1][$numSrcNormal]) != 1) {
                continue;
            }

            $origNumsNow = $origNums2[0][$numSrcNormal];

            if ($debug) {
                echo "origNumsNow SRC: \n";
            }
            if ($debug) {
                print_r($origNumsNow);
            }

            $origNum = $origNumsNow[0];

            unset($numsCount[0][$origNum]);

            if (isset($explodeNums[0][$numSrcNormal])) {
                trigger_error("Unbekanntes Problem im Zahlencheck aufgetreten. Bitte Daniel Bescheid geben! Abbruch. \n", E_USER_ERROR);
            }
            $explodeNums[0][$numSrcNormal] = $origNum;
        }
    }

    foreach ($normalNumsCount[1] as $numTrgNormal => $trgCount) {
        if (!isset($normalNumsCount[0][$numTrgNormal])) {
            continue;
        }
        $srcCount = $normalNumsCount[0][$numTrgNormal];

        if ($srcCount === $trgCount) {
            if ($debug) {
                echo "match $numTrgNormal -> $srcCount === $trgCount\n";
            }

            if (count($origNums2[0][$numTrgNormal]) != 1) {
                continue;
            }
            if (count($origNums2[1][$numTrgNormal]) != 1) {
                continue;
            }

            $origNumsNow = $origNums2[1][$numTrgNormal];

            if ($debug) {
                echo "origNumsNow TRG: \n";
            }
            if ($debug) {
                print_r($origNumsNow);
            }

            $origNum = $origNumsNow[0];

            unset($numsCount[1][$origNum]);

            if (isset($explodeNums[1][$numTrgNormal])) {
                trigger_error("Unbekanntes Problem im Zahlencheck aufgetreten. Bitte Daniel Bescheid geben! Abbruch. \n", E_USER_ERROR);
            }
            $explodeNums[1][$numTrgNormal] = $origNum;
        }
    }

    if ($debug) {
        echo "explodeNums: \n";
    }
    if ($debug) {
        print_r($explodeNums);
    }
    if ($debug) {
        echo "numsCount: \n";
    }
    if ($debug) {
        print_r($numsCount);
    }
    if ($debug) {
        echo "normalNumsCount: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }

    if ((!empty($explodeNums[0])) && (!empty($explodeNums[1]))) {
        if (count($explodeNums[0]) == count($explodeNums[1])) {
            foreach ($explodeNums[0] as $normalNum => $numOrigSrc) {
                $numOrigTrg = $explodeNums[1][$normalNum];

                if ($debug) {
                    echo "EX1 Zahlenformat geändert (Datum/Artikelnummern/... o.ä.): SRC [$numOrigSrc] vs. TRG [$numOrigTrg]\n";
                }

                $checkProps['checkSeverity_extra'] = 'notice';

                $checkProps['checkSubType_extra'] = 'Formatänderung (Ordinalzahlen, führende Null u.ä.) [Typ1]';
                $checkProps['checkSubType_extra_EN'] = 'Format alteration (ordinal numbers, leading zero etc.) [type1]';

                $checkResults['checkMessage'] = "Hinweis: Formatänderung: SRC [{$numOrigSrc}] vs. TRG [{$numOrigTrg}] [Typ1]";
                $checkResults['checkMessage'] = "Formatänderung (Ordinalzahlen, führende Null u.ä.): SRC [{$numOrigSrc}] vs. TRG [{$numOrigTrg}] [Typ1]";
                $checkResults['checkMessage_EN'] = "Note: Format alteration: SRC [{$numOrigSrc}] vs. TRG [{$numOrigTrg}] [Typ1]";

                if (true) {
                    doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                    // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                }

                // // doMatches SRC
                $match = $numOrigSrc;
                $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
                if (!is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
                }
                if (is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d\"])" . preg_quote($match) . "(?![\d\"])#u";
                }
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = false;

                // // doMatches TRG
                $match = $numOrigTrg;
                $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
                if (!is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
                }
                if (is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d\"])" . preg_quote($match) . "(?![\d\"])#u";
                }
                $checkResults['markSrc'] = false;
                $checkResults['markTrg'] = true;
            }
        } else {
            if ($debug) {
                echo "explodeCount ungleich. :(\n";
            }
            trigger_error("Unbekanntes Problem im Zahlencheck aufgetreten. Bitte Daniel Bescheid geben! Abbruch. \n", E_USER_NOTICE);
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'END', __FUNCTION__);
    }

    return $numsCount;
}
// 1000er-Zahlen mit untersch. Trennern checken (,.\p{Zs})
function compareNumCounts_noDiv($numsCount, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    //$checkProps += getSubCheckProps($checkProps["checkFunc"], __FUNCTION__, $checks);

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];

    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    if (noLokaAllowed($sncSettings)) {
        if ($debug) {
            echo "Bei diesem Kunden keine Loka von Zahlen -> Skip \n";
        }

        return $numsCount;
    }

    if ((empty($numsCount[0])) || (empty($numsCount[1]))) {
        if ($debug) {
            echo "mind 1 count emtpy, skip\n";
        }

        return $numsCount;
    }

    $noDivNums = [];
    $origNums2 = [];
    $normalNumsCount = [];
    $allNormalNumsInTU2 = [];

    for ($i = 0; $i < 2; $i++) {
        foreach ($numsCount[$i] as $numOrig => $count) {
            $num = $numOrig;
            if ($debug) {
                echo "num1 $num\n";
            }

            if (!isDecimalPointLang($langs[$i])) {
                if ((isKiloSpaceNum($num)) ||
                    (isKiloNoDivNum($num)) ||
                    (isKiloPointNum($num)) ||
                    (preg_match('!^\d{4,12}$!u', $num))) {
                    $num = preg_replace("![\.,\p{Zs}]!u", '', $num);
                } else {
                    if ($debug) {
                        echo " $num ist keine gültige 1000er-Zahl in {$langs[$i]}!!\n";
                    }
                    continue;
                }
            } else {
                if ((isKiloSpaceNum($num)) ||
                    (isKiloNoDivNum($num)) ||
                    (isKiloCommaNum($num)) ||
                    (preg_match('!^\d{4,12}$!u', $num))) {
                    $num = preg_replace("![\.,\p{Zs}]!u", '', $num);
                } else {
                    if ($debug) {
                        echo " $num ist keine gültige 1000er-Zahl in {$langs[$i]}!!\n";
                    }
                    continue;
                }
            }

            if ($debug) {
                echo "-> num2 $num\n";
            }
            $allNormalNumsInTU2[$num] = true;
            if (!isset($normalNumsCount[$i][$num])) {
                $normalNumsCount[$i][$num] = $count;
            } else {
                $normalNumsCount[$i][$num] = $count + $normalNumsCount[$i][$num];
            }

            if ($count == 0) {
                continue;
            }
            $origNums2[$i][$num][] = $numOrig;
        }
    }

    if ((!isset($normalNumsCount[0])) || (!isset($normalNumsCount[1]))) {
        return $numsCount;
    }

    if ($debug) {
        echo "origNums2: \n";
    }
    if ($debug) {
        print_r($origNums2);
    }
    if ($debug) {
        echo "normalNumsCount #1: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }
    if ($debug) {
        echo "allNormalNumsInTU2: \n";
    }
    if ($debug) {
        print_r($allNormalNumsInTU2);
    }

    foreach ($allNormalNumsInTU2 as $num => $state) {
        if (!isset($normalNumsCount[0][$num])) {
            $normalNumsCount[0][$num] = 0;
        }
        if (!isset($normalNumsCount[1][$num])) {
            $normalNumsCount[1][$num] = 0;
        }
    }

    if ($debug) {
        echo "normalNumsCount #2: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }

    foreach ($normalNumsCount[0] as $numSrcNormal => $srcCount) {
        if (!isset($normalNumsCount[1][$numSrcNormal])) {
            continue;
        }
        $trgCount = $normalNumsCount[1][$numSrcNormal];

        if ($srcCount == $trgCount) {
            if ($debug) {
                echo "match $numSrcNormal -> $srcCount == $trgCount\n";
            }
            if (count($origNums2[0][$numSrcNormal]) != 1) {
                continue;
            }
            if (count($origNums2[1][$numSrcNormal]) != 1) {
                continue;
            }
            $origNumsNow = $origNums2[0][$numSrcNormal];
            if ($debug) {
                echo "origNumsNow SRC: \n";
            }
            if ($debug) {
                print_r($origNumsNow);
            }
            $origNum = $origNumsNow[0];

            if (isset($noDivNums[0][$numSrcNormal])) {
                trigger_error("Unbekanntes Problem im Zahlencheck aufgetreten. Bitte Daniel Bescheid geben! Abbruch. \n", E_USER_ERROR);
            }
            $noDivNums[0][$numSrcNormal] = $origNum;
            unset($numsCount[0][$origNum]);
        }
    }

    foreach ($normalNumsCount[1] as $numTrgNormal => $trgCount) {
        if (!isset($normalNumsCount[0][$numTrgNormal])) {
            continue;
        }
        $srcCount = $normalNumsCount[0][$numTrgNormal];

        if ($srcCount == $trgCount) {
            if ($debug) {
                echo "match $numTrgNormal -> $srcCount == $trgCount\n";
            }

            if (count($origNums2[0][$numTrgNormal]) != 1) {
                continue;
            }
            if (count($origNums2[1][$numTrgNormal]) != 1) {
                continue;
            }

            $origNumsNow = $origNums2[1][$numTrgNormal];

            if ($debug) {
                echo "origNumsNow TRG: \n";
            }
            if ($debug) {
                print_r($origNumsNow);
            }

            $origNum = $origNumsNow[0];

            if (isset($noDivNums[1][$numTrgNormal])) {
                trigger_error("Unbekanntes Problem im Zahlencheck aufgetreten. Bitte Daniel Bescheid geben! Abbruch. \n", E_USER_ERROR);
            }
            $noDivNums[1][$numTrgNormal] = $origNum;
            unset($numsCount[1][$origNum]);
        }
    }

    if ($debug) {
        echo "numsCount: \n";
    }
    if ($debug) {
        print_r($numsCount);
    }
    if ($debug) {
        echo "normalNumsCount: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }
    if ($debug) {
        echo "noDivNums: \n";
    }
    if ($debug) {
        print_r($noDivNums);
    }

    if ((!empty($noDivNums[0])) && (!empty($noDivNums[1]))) {
        if (count($noDivNums[0]) == count($noDivNums[1])) {
            foreach ($noDivNums[0] as $normalNum => $numOrigSrc) {
                $numOrigTrg = $noDivNums[1][$normalNum];

                $numOrigSrcPattern = preg_replace('!\d!u', '#', $numOrigSrc);
                $numOrigTrgPattern = preg_replace('!\d!u', '#', $numOrigTrg);

                if ($debug) {
                    echo "Hinweis: Zahlenformat (1000er) geändert: SRC $numOrigSrc vs. TRG $numOrigTrg\n";
                }

                $checkProps['checkSeverity_extra'] = 'notice';

                $checkProps['checkSubType_extra'] = 'Hinweis: Formatierung 1000er-Zahl geändert';
                $checkProps['checkSubType_extra_EN'] = 'Note: Formatting of 1000s number altered';
                $checkResults['checkMessage'] = "Hinweis: Formatierung 1000er-Zahl geändert: [{$numOrigSrc} vs. {$numOrigTrg}]";
                $checkResults['checkMessage_EN'] = "Note: Formatting of 1000s number altered [$numOrigSrc vs. $numOrigTrg]";

                if (true) {
                    doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                    // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                }

                // // doMatches SRC

                $match = $numOrigSrc;

                $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
                if (!is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
                }
                if (is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d\"])" . preg_quote($match) . "(?![\d\"])#u";
                }
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = false;

                // // doMatches TRG

                $match = $numOrigTrg;

                $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
                if (!is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
                }
                if (is_int($match)) {
                    $checkResults['matchRegEx'] = "#(?<![\d\"])" . preg_quote($match) . "(?![\d\"])#u";
                }
                $checkResults['markSrc'] = false;
                $checkResults['markTrg'] = true;
            }
        } else {
            if ($debug) {
                echo "noDivCount ungleich... :(\n";
            }
            trigger_error("Unbekanntes Problem im Zahlencheck aufgetreten. Bitte Daniel Bescheid geben! Abbruch. \n", E_USER_NOTICE);
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'END', __FUNCTION__);
    }

    return $numsCount;
}
// Checken ob Counts bei echten Zahlen mit Trenner [,.] identisch
// bei mixed (Trenner sollten sich unterscheiden, außer es geht um Nummerierung die aussieht wie echte Zahl) -> Meldung
// Ausnahmen: DE2EN, wenn maybeNonLokaPointNum
function compareNumCounts_sameDiv($numsCount, $checkSeg, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];

    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    if (noLokaAllowed($sncSettings)) {
        if ($debug) {
            echo "Bei diesem Kunden keine Loka von Zahlen -> Skip\n";
        }

        return $numsCount;
    }
    if ((empty($numsCount[0])) || (empty($numsCount[1]))) {
        if ($debug) {
            echo "mind 1 count emtpy, skip \n";
        }

        return $numsCount;
    }

    // Abbruchkriterien

    $monoTypeDE = isMonoTypeDE($langs[0], $langs[1]);
    $monoTypeEN = isMonoTypeEN($langs[0], $langs[1]);

    $mixedTypesEN2DE = isMixedTypesEN2DE($langs[0], $langs[1]);
    $mixedTypesDE2EN = isMixedTypesDE2EN($langs[0], $langs[1]);

    if ($monoTypeDE || $monoTypeEN) {
        if ($debug) {
            echo "monoTypeDE oder monoTypeEN, skip\n";
        }

        return $numsCount;
    }

    foreach ($numsCount[0] as $numOrig => $count) {
        $numPattern = preg_replace('!\d!', '#', $numOrig);

        if (!isset($numsCount[1][$numOrig])) {
            if ($debug) {
                echo "kein trg-Count für $numOrig \n";
            }
            continue;
        }

        if ($numsCount[0][$numOrig] == $numsCount[1][$numOrig]) {
            unset($numsCount[0][$numOrig]);
            unset($numsCount[1][$numOrig]);

            if (!isRealNum($numOrig)) {
                if ($debug) {
                    echo "!isRealNum $numOrig \n";
                }
                continue;
            }
            if (isNonLokaNum($numOrig, $checkSeg)) {
                if ($debug) {
                    echo "!isRealNum $numOrig \n";
                }
                continue;
            }
            if (isKiloSpaceNum($numOrig)) {
                if ($debug) {
                    echo "KiloSPace -> skip $numOrig\n";
                }
                continue;
            }
            if (!preg_match('![\.,]!', $numOrig)) {
                if ($debug) {
                    echo "kein [.,] -> skip $numOrig\n";
                }
                continue;
            }

            // TODO:
            // Testen, ob das die meisten falschen Fehler abfängt
            // Bei BMW beobachten ob es nach EN ähnliche Probleme gibt
            // Falls es sich lohnt: Bessere Lösung bauen

            if ($debug) {
                echo "isKiloSpaceNum : $numOrig " . isKiloSpaceNum($numOrig) . " \n";
            }
            if ($debug) {
                echo "isDeciCommaNum : $numOrig " . isDeciCommaNum($numOrig) . " \n";
            }
            if ($debug) {
                echo "isKiloCommaNum : $numOrig " . isKiloCommaNum($numOrig) . " \n";
            }
            if ($debug) {
                echo "isDeciPointNum : $numOrig " . isDeciPointNum($numOrig) . " \n";
            }
            if ($debug) {
                echo "isKiloPointNum : $numOrig " . isKiloPointNum($numOrig) . " \n";
            }

            $dubios = false;
            $nonLoka = false;
            $dubString = '';

            if ($mixedTypesDE2EN) {
                if ((isKiloCommaNum($numOrig)) &&
                    (!isDeciCommaNum($numOrig))) {
                    if ($debug) {
                        echo "mixedTypesDE2EN : isKiloCommaNum && !isDeciCommaNum : $numOrig --> in Dezimalkommasprache ist scheinbar Komma als 1000er-Trenner verwendet worden (statt Punkt)\n";
                    }
                    $dubios = true;

                    $checkProps['checkSubType_extra'] = 'Dubiose Zahl aus SRC unverändert in TRG übernommen.';
                    $checkProps['checkSubType_extra_EN'] = 'Dubious number from SRC unchanged in TRG';
                    $checkResults['checkMessage'] = "Dubiose 'Zahl' [{$numOrig}] aus SRC unverändert in TRG übernommen.<br><span class=\"fs_13\">&rarr; Falls es sich dabei um eine fehlerhaft formatierte Liste mit fehlenden Leerzeichen zwischen Listenelementen handelt, fügen Sie bitte in TRG diese Leerzeichen ein, um Mehrdeutigkeit zu vermeiden. <br>&rarr; Falls dagegen in SRC der falsche Tausendertrenner verwendet wurden, igorieren Sie bitte diese Meldung.</span>";
                    $checkResults['checkMessage_EN'] = "Dubious number [{$numOrig}] from SRC found unchanged in TRG.<br><span class=\"fs_13\">&rarr; If this 'number' is actually a badly formatted list with missing spaces between list elements, please insert these spaces in TRG to avoid ambiguity.<br>&rarr; If the wrong 1000s separator was used in a real number in SRC, please ignore this message (and sorry for the inconvenience).</span>";

                    $match = $numOrig;
                    $match = str_replace("\-", "\p{Pd}", preg_quote($match));

                    $checkResults['replaceString'] = '<span class="matchStandard">' . $numOrig . '</span>';
                    $checkResults['matchRegEx'] = "#(?<![\d])" . $match . "(?![\d])#u";
                    $checkResults['markSrc'] = true;
                    $checkResults['markTrg'] = true;

                    doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                    // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                    // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);

                    continue;
                } elseif (!isRealNum_lang($numOrig, $langs[0])) {
                    if ($debug) {
                        echo "!isRealNum_lang $numOrig {$langs[0]} \n";
                    }
                    continue;
                }
            } elseif ($mixedTypesEN2DE) {
                if ((isDeciCommaNum($numOrig)) &&
                    (!isKiloCommaNum($numOrig))) {
                    if ($debug) {
                        echo "mixedTypesEN2DE : isDeciCommaNum && !isKiloCommaNum : $numOrig --> in Dezimalpunktsprache ist scheinbar Komma als Dezimaltrenner verwendet worden (statt Punkt)\n";
                    }
                    $dubios = true;

                    $checkProps['checkSubType_extra'] = 'Ggf. dubiose Zahl aus SRC unverändert in TRG übernommen.';
                    $checkProps['checkSubType_extra_EN'] = 'Potentially dubious number from SRC unchanged in TRG';
                    $checkResults['checkMessage'] = "Dubiose Zahl [{$numOrig}] aus SRC unverändert in TRG übernommen.<br><span class=\"fs_13\">Falls es sich dabei um keine Dezimalzahl, sondern eine  Liste mit fehlenden Leerzeichen zwischen Listenelementen handelt, bitte in TRG Leerzeichen zwischen Listenelementen einfügen. Bei falsch verwendetem Dezimaltrenner in SRC bitte Meldung ignorieren.</span>";
                    $checkResults['checkMessage_EN'] = "Dubious (potential) decimal number [{$numOrig}] from SRC found unchanged in TRG.<br><span class=\"fs_13\">If this is not a decimal number but a list with missing spaces between list elements, please insert these spaces to avoid ambiguity. If the wrong decimal separator was used in SRC, please ignore this message.</span>";

                    $match = $numOrig;
                    $match = str_replace("\-", "\p{Pd}", preg_quote($match));

                    $checkResults['replaceString'] = '<span class="matchStandard">' . $numOrig . '</span>';
                    $checkResults['matchRegEx'] = "#(?<![\d])" . $match . "(?![\d])#u";
                    $checkResults['markSrc'] = true;
                    $checkResults['markTrg'] = true;

                    doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                    // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                    // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);

                    continue;
                } elseif ((isKiloPointNum($numOrig)) &&
                    (!isDeciPointNum($numOrig))) {
                    if ($debug) {
                        echo "mixedTypesEN2DE : isKiloPointNum && !isDeciPointNum : $numOrig --> in Dezimalpunktsprache ist scheinbar Punkt als 1000er-Trenner verwendet worden (statt Komma)\n";
                    }
                    $dubios = true;
                    $checkProps['checkSubType_extra'] = '(Ggf. fehlerhafter) Trenner aus SRC unverändert in TRG übernommen';
                    $checkProps['checkSubType_extra_EN'] = '(Possibly erroneous) separator from SRC found unchanged in TRG';
                    $checkResults['checkMessage'] = "(Ggf. fehlerhafter) Trenner in [{$numOrig}] aus SRC unverändert in TRG übernommen.<br><span class=\"fs_13\">Bitte prüfen, ob es sich um eine echte Zahl vs. eine Produktnummer o.ä. handelt. Bei falsch verwendetem Trenner in einer echten Zahl in SRC bitte Meldung ignorieren.</span>";
                    $checkResults['checkMessage_EN'] = "(Possibly erroneous) separator in [{$numOrig}] from SRC found unchanged in TRG.<br><span class=\"fs_13\">Please check if this is a real number vs. a prodcut number or similar. In case of an erroneous separator in a real number in SRC, please ignore message.</span>";

                    $match = $numOrig;
                    $match = str_replace("\-", "\p{Pd}", preg_quote($match));

                    $checkResults['replaceString'] = '<span class="matchStandard">' . $numOrig . '</span>';
                    $checkResults['matchRegEx'] = "#(?<![\d])" . $match . "(?![\d])#u";
                    $checkResults['markSrc'] = true;
                    $checkResults['markTrg'] = true;

                    doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                    // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                    // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);

                    continue;
                }
            }

            $doMessage = true;

            $chSpezial_DE = '';
            $chSpezial_EN = '';
            if (($langs[1] == 'fr-CH') && (isDecimalPointLang($langs[0]))) {
                $trgCount = preg_match_all("!(EURO?|€|CHF|Fr\.|\$|£)\p{Zs}?" . preg_quote($numOrig) . '|' . preg_quote($numOrig) . "\p{Zs}?(EURO?|€|CHF|Fr\.|\$|£)!iu", $checkSeg[1]);
                $srcCount = preg_match_all("!(^|[^\d])" . preg_quote($numOrig) . "([^\d]|$)!iu", $checkSeg[0]);

                if ($srcCount == $trgCount) {
                    $doMessage = false;
                } else {
                    $chSpezial_DE = ', Währungsangabe, Koordinate ';
                    $chSpezial_EN = ', currency value, coordinate ';
                }
            }

            if ($doMessage) {
                $checkProps['checkSubType_extra'] = 'Trenner nicht lokalisiert';
                $checkProps['checkSubType_extra_EN'] = 'Separator(s) not localised';

                $extraText_DE = '';
                $extraText_EN = '';

                if (isDeciPointNum($numOrig)) {
                    $extraText_DE = "Bitte prüfen, ob es sich hierbei um eine echte Zahl vs. eine Kapitel-/Produktnummer{$chSpezial_DE} o.ä. handelt. Im letzteren Fall, oder wenn die Zahl in SRC mit falschem Trenner geschrieben wurde, bitte Meldung ignorieren (oder Filter nutzen um diese Unterkategorie für alle Segmente auszublenden).";
                    $extraText_EN = "<span class=\"fs_13\">Please check if this is a real number vs. a chapter/product number {$chSpezial_EN} or similar. In the latter case, or if the number has incorrect separator(s) in SRC, please ignore this message (or use the filter to hide this subcategory for all segments).</span>";
                }

                $checkResults['checkMessage'] = "Trenner in [{$numOrig}] nicht lokalisiert.<br>{$extraText_DE}";
                $checkResults['checkMessage_EN'] = "Separator(s) in [{$numOrig}] not localised.<br>{$extraText_EN}";

                $match = $numOrig;
                $match = str_replace("\-", "\p{Pd}", preg_quote($match));

                $checkResults['replaceString'] = '<span class="matchStandard">' . $numOrig . '</span>';
                $checkResults['matchRegEx'] = "#(?<![\d])" . $match . "(?![\d])#u";
                $checkResults['markSrc'] = true;
                $checkResults['markTrg'] = true;

                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
                // doMatches($checkResults, $currentData, $checkProps, $msgMatches, $checks);
            }
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'END', __FUNCTION__);
    }

    return $numsCount;
}
function compareNumCounts_spelledOut($numsCount, $currentData, $checkProps, &$checks, $sncSettings, &$checkMessages, &$msgCounts, &$msgMatches)
{
    $debug = false;

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'START', __FUNCTION__);
    }
    if ($debug) {
        print_r($checkProps);
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];

    $kombi = $currentData['kombi'];
    $langs = $currentData['langs'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];
    $seg[0] = $currentData['segTypes']['srcSeg'];
    $seg[1] = $currentData['segTypes']['trgSeg'];
    $segPure[0] = $currentData['segTypes']['srcSegPure'];
    $segPure[1] = $currentData['segTypes']['trgSegPure'];

    if (noLokaAllowed($sncSettings)) {
        if ($debug) {
            echo "Bei diesem Kunden keine Loka von Zahlen -> skip $checkSubFunc \n";
        }

        return $numsCount;
    }
    if ((empty($numsCount[0])) && (empty($numsCount[1]))) {
        if ($debug) {
            echo "beide counts emtpy, skippe spelledOutCheck\n";
        }

        return $numsCount;
    }

    if (isAsianLang($langs[1])) {
        $asianLangs = true;
    } else {
        $asianLangs = false;
    }

    if ((isAsianLang($langs[0])) && (empty($numsCount[0]))) {
        return $numsCount;
    }
    if ((isAsianLang($langs[1])) && (empty($numsCount[1]))) {
        return $numsCount;
    }

    $numWords = getNumWords($langs);

    $spelledOutNums = [];
    $normalisedNums[0] = [];
    $normalisedNums[1] = [];
    $origNums2 = [];
    $normalNumsCount = [];
    $allNormalNumsInTU2 = [];

    foreach ($numsCount[0] as $numOrigSrc => $srcCount) {
        if (isset($numsCount[1][$numOrigSrc])) {
            $trgCount = $numsCount[1][$numOrigSrc];
        } else {
            $trgCount = 0;
        }

        if ($srcCount <= $trgCount) {
            if ($debug) {
                echo "srcCount <= trgCount: $srcCount <= $trgCount -> Skip\n";
            }
            //$doCheck = false;
        }

        $num = $numOrigSrc;
        $numOrigTrg = $num;

        if ($debug) {
            echo "num1 $num\n";
        }

        $doCheck = true;

        if (!is_integer($num)) {
            if ($debug) {
                echo "$num ist keine ganze Zahl -> Skip\n";
            }
            $doCheck = false;
        }

        if (intval($num) > 12) {
            if ($debug) {
                echo "$num größer 12 -> Skip\n";
            }
            $doCheck = false;
        }

        if (!isset($numWords[1][$num])) {
            if ($debug) {
                echo "Kein numWord für $num in {$langs[1]} -> Skip\n";
            }
            $doCheck = false;
        } else {
            foreach ($numWords[1][$num] as $word) {
                $word = trim($word);

                if ($debug) {
                    echo "Prüfe ob $word in srcSeg matcht\n";
                }

                if (preg_match("#(?<!\p{L})$word(?!\p{L})#ui", $segPure[0], $m)) {
                    if ($debug) {
                        echo "Zahl $num ausgeschrieben als $word in srcSeg gefunden -> skip ausgeschr\n";
                    }
                    $doCheck = false;
                    break;
                } else {
                    if ($debug) {
                        echo "$word matcht NICHT in srcSeg\n";
                    }
                }
            }
        }

        $wordsCount = 0;

        if ($doCheck) {
            foreach ($numWords[1][$num] as $word) {
                $word = preg_quote(trim($word));

                if ($debug) {
                    echo 'check word: |' . $word . "|\n";
                }
                if ($debug) {
                    echo 'trgSegPure: |' . $segPure[1] . "|\n";
                }

                if (!isAsianLang($langs[1])) {
                    $wordRegEx = "#(?<!\p{L})$word(?!\p{L})#ui";
                } else {
                    $wordRegEx = "#$word#ui";
                }

                if (preg_match_all($wordRegEx, $segPure[1], $m)) {
                    if ($debug) {
                        echo "match:\n";
                    }
                    if ($debug) {
                        print_r($m);
                    }

                    if ($debug) {
                        echo "\nxxxxxxxxxxxxxxxxx\n";
                    }
                    if ($debug) {
                        echo " word: $word\n";
                    }
                    if ($debug) {
                        echo " wordsCount: $wordsCount\n";
                    }
                    if ($debug) {
                        echo "\nxxxxxxxxxxxxxxxxx\n";
                    }

                    if ($numOrigTrg !== $num) {
                        if ($debug) {
                            echo "numOrigTrg != num: $numOrigTrg !== $num \n";
                        }
                        $wordsCount = 0;
                        continue;
                    } else {
                        $numOrigTrg = $word;
                        $wordsCount = $wordsCount + count($m[0]);
                    }
                }
            }
        }

        $allNormalNumsInTU2[$num] = true;

        $normalNumsCount[0][$num] = $numsCount[0][$num];

        if (!isset($normalNumsCount[1][$num])) {
            $normalNumsCount[1][$num] = $trgCount + $wordsCount;
        } else {
            $normalNumsCount[1][$num] = $trgCount + $normalNumsCount[1][$num] + $wordsCount;
        }

        //if($count == 0) continue;
        $origNums2[0][$num][] = $numOrigSrc;
        $origNums2[1][$num][] = $numOrigTrg;

        if ($num !== $numOrigSrc) {
            $normalisedNums[0][$num] = true;
        }
        if ($num !== $numOrigTrg) {
            $normalisedNums[1][$num] = true;
        }
    }

    foreach ($numsCount[1] as $numOrigTrg => $trgCount) {
        if (isset($numsCount[0][$numOrigTrg])) {
            $srcCount = $numsCount[0][$numOrigTrg];
        } else {
            $srcCount = 0;
        }

        if ($trgCount <= $srcCount) {
            if ($debug) {
                echo "trgCount <= srcCount: $trgCount <= $srcCount -> Skip\n";
            }
            $doCheck = false;
        }

        $num = $numOrigTrg;
        $numOrigSrc = $num;

        if ($debug) {
            echo "num1 $num\n";
        }

        $doCheck = true;

        if (!is_integer($num)) {
            if ($debug) {
                echo "$num ist keine ganze Zahl -> Skip\n";
            }
            $doCheck = false;
        }

        if (intval($num) > 12) {
            if ($debug) {
                echo "$num größer 12 -> Skip\n";
            }
            $doCheck = false;
        }

        if (!isset($numWords[0][$num])) {
            if ($debug) {
                echo "Kein numWord für $num in {$langs[0]} -> Skip\n";
            }
            $doCheck = false;
        }

        $wordsCount = 0;

        if ($doCheck) {
            foreach ($numWords[0][$num] as $word) {
                $word = preg_quote(trim($word));

                if ($debug) {
                    echo 'check word: |' . $word . "|\n";
                }
                if ($debug) {
                    echo 'srcSegPure: |' . $segPure[0] . "|\n";
                }

                if (!isAsianLang($langs[0])) {
                    $wordRegEx = "#(?<!\p{L})$word(?!\p{L})#ui";
                } else {
                    $wordRegEx = "#$word#ui";
                }

                if (preg_match_all($wordRegEx, $segPure[0], $m)) {
                    if ($debug) {
                        echo "match:\n";
                    }
                    if ($debug) {
                        print_r($m);
                    }

                    if ($debug) {
                        echo "\nxxxxxxxxxxxxxxxxx\n";
                    }
                    if ($debug) {
                        echo " word: $word\n";
                    }
                    if ($debug) {
                        echo " wordsCount: $wordsCount\n";
                    }
                    if ($debug) {
                        echo "\nxxxxxxxxxxxxxxxxx\n";
                    }

                    if ($numOrigSrc !== $num) {
                        if ($debug) {
                            echo "numOrigTrg != num: $numOrigSrc !== $num \n";
                        }
                        $wordsCount = 0;
                        continue;
                    } else {
                        $numOrigSrc = $word;
                        $wordsCount = $wordsCount + count($m[0]);
                    }
                }
            }
        }

        $allNormalNumsInTU2[$num] = true;

        $normalNumsCount[1][$num] = $numsCount[1][$num];

        if (!isset($normalNumsCount[0][$num])) {
            $normalNumsCount[0][$num] = $srcCount + $wordsCount;
        } else {
            $normalNumsCount[0][$num] = $srcCount + $normalNumsCount[0][$num] + $wordsCount;
        }

        //if($count == 0) continue;
        $origNums2[0][$num][] = $numOrigSrc;
        $origNums2[1][$num][] = $numOrigTrg;

        if ($num !== $numOrigSrc) {
            $normalisedNums[0][$num] = true;
        }
        if ($num !== $numOrigTrg) {
            $normalisedNums[1][$num] = true;
        }
    }

    if ($debug) {
        echo "origNums2: \n";
    }
    if ($debug) {
        print_r($origNums2);
    }
    if ($debug) {
        echo "normalNumsCount #1: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }
    if ($debug) {
        echo "allNormalNumsInTU2: \n";
    }
    if ($debug) {
        print_r($allNormalNumsInTU2);
    }

    foreach ($allNormalNumsInTU2 as $num => $state) {
        if (!isset($normalNumsCount[0][$num])) {
            $normalNumsCount[0][$num] = 0;
        }
        if (!isset($normalNumsCount[1][$num])) {
            $normalNumsCount[1][$num] = 0;
        }
    }

    if ($debug) {
        echo "normalNumsCount #2: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }

    foreach ($normalNumsCount[0] as $numSrcNormal => $srcCount) {
        if (!isset($normalNumsCount[1][$numSrcNormal])) {
            continue;
        }
        $trgCount = $normalNumsCount[1][$numSrcNormal];

        if ($srcCount == $trgCount) {
            if ($debug) {
                echo "match $numSrcNormal -> $srcCount == $trgCount\n";
            }

            if (count($origNums2[0][$numSrcNormal]) != 1) {
                if ($debug) {
                    echo "count != 1 {$origNums2[0][$numSrcNormal]}\n";
                }
                continue;
            }
            if (count($origNums2[1][$numSrcNormal]) != 1) {
                if ($debug) {
                    echo "count != 1 {$origNums2[1][$numSrcNormal]}\n";
                }
                continue;
            }

            $origNumsNow = $origNums2[0][$numSrcNormal];

            if ($debug) {
                echo "origNumsNow SRC: \n";
            }
            if ($debug) {
                print_r($origNumsNow);
            }

            $origNum = $origNumsNow[0];

            if (isset($spelledOutNums[0][$numSrcNormal])) {
                trigger_error("Unbekanntes Problem im Zahlencheck aufgetreten. Bitte Daniel Bescheid geben! Abbruch. \n", E_USER_ERROR);
            }
            if (isset($normalisedNums[0][$numSrcNormal])) {
                $spelledOutNums[0][$numSrcNormal] = $origNum;
            }

            unset($numsCount[0][$numSrcNormal]);
        }
    }

    if ($debug) {
        if (!empty($spelledOutNums[0])) {
            echo "spelledOutNums[0]: \n";
            print_r($spelledOutNums[0]);
        }
    }
    if ($debug) {
        echo "numsCount nach unset[0]: \n";
    }
    if ($debug) {
        print_r($numsCount);
    }

    foreach ($normalNumsCount[1] as $numTrgNormal => $trgCount) {
        if (!isset($normalNumsCount[0][$numTrgNormal])) {
            continue;
        }
        $srcCount = $normalNumsCount[0][$numTrgNormal];

        if ($srcCount == $trgCount) {
            if ($debug) {
                echo "match $numTrgNormal -> $srcCount == $trgCount\n";
            }

            if (count($origNums2[0][$numTrgNormal]) != 1) {
                continue;
            }
            if (count($origNums2[1][$numTrgNormal]) != 1) {
                continue;
            }

            $origNumsNow = $origNums2[1][$numTrgNormal];

            if ($debug) {
                echo "origNumsNow TRG: \n";
            }
            if ($debug) {
                print_r($origNumsNow);
            }

            $origNum = $origNumsNow[0];

            if (isset($spelledOutNums[1][$numTrgNormal])) {
                trigger_error("Unbekanntes Problem im Zahlencheck aufgetreten. Bitte Daniel Bescheid geben! Abbruch. \n", E_USER_ERROR);
            }
            if (isset($normalisedNums[1][$numTrgNormal])) {
                $spelledOutNums[1][$numTrgNormal] = $origNum;
            }

            unset($numsCount[1][$numTrgNormal]);
        }
    }

    if ($debug) {
        if (!empty($spelledOutNums[1])) {
            echo "spelledOutNums[1]: \n";
            print_r($spelledOutNums[1]);
        }
    }
    if ($debug) {
        echo "numsCount nach unset[1]: \n";
    }
    if ($debug) {
        print_r($numsCount);
    }
    if ($debug) {
        echo "normalNumsCount: \n";
    }
    if ($debug) {
        print_r($normalNumsCount);
    }

    if ((!empty($spelledOutNums[0]))) {
        $checkProps['checkSeverity_extra'] = 'notice';

        foreach ($spelledOutNums[0] as $normalNum => $numOrigSrc) {
            if ($debug) {
                echo "Hinweis: Zahlwort aus SRC als Ziffer in TRG gefunden: SRC $numOrigSrc vs. TRG $normalNum\n";
            }

            $checkProps['checkSubType_extra'] = 'Hinweis: Zahlwort aus SRC als Zahl in TRG gefunden';
            $checkProps['checkSubType_extra_EN'] = 'Note: Numeral from SRC found as number in TRG';

            $checkResults['checkMessage'] = "Hinweis: Zahlwort als Zahl gefunden: SRC [{$numOrigSrc}] vs. TRG [{$normalNum}]";
            $checkResults['checkMessage_EN'] = "Note: Numeral found as number: SRC [{$numOrigSrc}] vs. TRG [{$normalNum}]";

            if (true) {
                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
            }

            // // doMatches für SRC

            $match = $numOrigSrc;

            $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
            if (!is_int($match)) {
                $checkResults['matchRegEx'] = "#(?<![\p{L}])" . preg_quote($match) . "(?![\p{L}])#u";
            }
            if (is_int($match)) {
                $checkResults['matchRegEx'] = "#(?<![\p{L}])" . preg_quote($match) . "(?![\p{L}])#u";
            }
            $checkResults['markSrc'] = true;
            $checkResults['markTrg'] = false;

            // // doMatches für TRG

            $match = $normalNum;

            $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
            if (!is_int($match)) {
                $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
            }
            if (is_int($match)) {
                $checkResults['matchRegEx'] = "#(?<![\d\"])" . preg_quote($match) . "(?![\d\"])#u";
            }
            $checkResults['markSrc'] = false;
            $checkResults['markTrg'] = true;
        }
    }

    if ((!empty($spelledOutNums[1]))) {
        $checkProps['checkSeverity_extra'] = 'notice';

        foreach ($spelledOutNums[1] as $normalNum => $numOrigTrg) {
            $checkProps['checkSubType_extra'] = 'Hinweis: Zahl aus SRC als Zahlwort in TRG gefunden';
            $checkProps['checkSubType_extra_EN'] = 'Note: Number from SRC found as numeral in TRG';

            $checkResults['checkMessage'] = "Hinweis: Zahl als Zahlwort gefunden: SRC [{$normalNum}] vs. TRG [{$numOrigTrg}]";
            $checkResults['checkMessage_EN'] = "Note: Number found as numeral: SRC [{$normalNum}] vs. TRG [{$numOrigTrg}]";

            if (true) {
                doMessages($checkResults, $currentData, $checkProps, $checkMessages, $checks);
                // doCounts($checkResults, $currentData, $checkProps, $msgCounts, $checks);
            }

            // // doMatches für SRC

            $match = $normalNum;

            $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
            if (!is_int($match)) {
                $checkResults['matchRegEx'] = "#(?<![\d])" . preg_quote($match) . "(?![\d])#u";
            }
            if (is_int($match)) {
                $checkResults['matchRegEx'] = "#(?<![\d\"])" . preg_quote($match) . "(?![\d\"])#u";
            }
            $checkResults['markSrc'] = true;
            $checkResults['markTrg'] = false;

            // // doMatches für TRG

            $match = $numOrigTrg;

            $checkResults['replaceString'] = '<span class="matchHinweis">' . $match . '</span>';
            if (!is_int($match)) {
                $checkResults['matchRegEx'] = "#(?<![\p{L}])" . preg_quote($match) . "(?![\p{L}])#u";
            }
            if (is_int($match)) {
                $checkResults['matchRegEx'] = "#(?<![\p{L}])" . preg_quote($match) . "(?![\p{L}])#u";
            }
            $checkResults['markSrc'] = false;
            $checkResults['markTrg'] = true;
        }
    }

    if ($debug) {
        echo getCurrentCheckFuncEcho($mode = 'END', __FUNCTION__);
    }

    return $numsCount;
}
/** /subsub checks */

function matchHexExplain(&$theMatchHex)
{
    $debug = false;

    if ($theMatchHex == '20') {
        $theMatchHex = ' = SPACE';
    } elseif ($theMatchHex == 'c2a0') {
        $theMatchHex = ' = NO-BREAK SPACE';
    } elseif ($theMatchHex == 'e28080') {
        $theMatchHex = ' = EN QUAD';
    } elseif ($theMatchHex == 'e28081') {
        $theMatchHex = ' = EM QUAD';
    } elseif ($theMatchHex == 'e28082') {
        $theMatchHex = ' = EN SPACE';
    } elseif ($theMatchHex == 'e28083') {
        $theMatchHex = ' = EM SPACE';
    } elseif ($theMatchHex == 'e28087') {
        $theMatchHex = ' = FIGURE SPACE';
    } elseif ($theMatchHex == 'e28088') {
        $theMatchHex = ' = PUNCTUATION SPACE';
    } elseif ($theMatchHex == 'e28089') {
        $theMatchHex = ' = THIN SPACE';
    } elseif ($theMatchHex == 'e2808a') {
        $theMatchHex = ' = HAIR SPACE';
    } elseif ($theMatchHex == 'e280af') {
        $theMatchHex = ' = NARROW NO-BREAK SPACE';
    } elseif ($theMatchHex == 'e2819f') {
        $theMatchHex = ' = MEDIUM MATHEMATICAL SPACE';
    } elseif ($theMatchHex == 'e38080') {
        $theMatchHex = ' = IDEOGRAPHIC SPACE';
    } elseif ($theMatchHex == '2d') {
        $theMatchHex = ' = HYPHEN-MINUS';
    } elseif ($theMatchHex == 'e28892') {
        $theMatchHex = ' = MINUS SIGN';
    } elseif ($theMatchHex == 'e28090') {
        $theMatchHex = ' = HYPHEN';
    } elseif ($theMatchHex == 'c2ad') {
        $theMatchHex = ' = SOFT HYPHEN';
    } elseif ($theMatchHex == 'e28091') {
        $theMatchHex = ' = NON-BREAKING HYPHEN';
    } elseif ($theMatchHex == 'e28092') {
        $theMatchHex = ' = FIGURE DASH';
    } elseif ($theMatchHex == 'e28093') {
        $theMatchHex = ' = EN DASH';
    } elseif ($theMatchHex == 'e28094') {
        $theMatchHex = ' = EM DASH';
    } elseif ($theMatchHex == 'efb998') {
        $theMatchHex = ' = SMALL EM DASH';
    } elseif ($theMatchHex == 'efb9a3') {
        $theMatchHex = ' = SMALL HYPHEN-MINUS';
    } elseif ($theMatchHex == 'efbc8d') {
        $theMatchHex = ' = FULLWIDTH HYPHEN-MINUS';
    } else {
        $theMatchHex = ' = ???';
    }
    //echo "MatchHex: $theMatchHex\n";

    return $theMatchHex;
}

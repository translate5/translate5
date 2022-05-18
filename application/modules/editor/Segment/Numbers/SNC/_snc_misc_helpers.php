<?php
function sortByLength_desc($a, $b)
{
    if (mb_strlen($a) == mb_strlen($b)) {
        return 0;
    }

    return mb_strlen($b) - mb_strlen($a);
}

function getSncLangData()
{
    return [
        'ar-SA' => ['decimal_separator' => 'Punkt'],
        'az-Cyrl-AZ' => ['decimal_separator' => 'Komma'],
        'az-Latn-AZ' => ['decimal_separator' => 'Komma'],
        'be-BY' => ['decimal_separator' => 'Komma'],
        'bg-BG' => ['decimal_separator' => 'Komma'],
        'bs-Cyrl-BA' => ['decimal_separator' => 'Komma'],
        'bs-Latn-BA' => ['decimal_separator' => 'Komma'],
        'cs-CZ' => ['decimal_separator' => 'Komma'],
        'da-DK' => ['decimal_separator' => 'Komma'],
        'de-AT' => ['decimal_separator' => 'Komma'],
        'de-DE' => ['decimal_separator' => 'Komma'],
        'el-GR' => ['decimal_separator' => 'Komma'],
        'en-AU' => ['decimal_separator' => 'Punkt'],
        'en-CA' => ['decimal_separator' => 'Punkt'],
        'en-GB' => ['decimal_separator' => 'Punkt'],
        'en-IE' => ['decimal_separator' => 'Punkt'],
        'en-US' => ['decimal_separator' => 'Punkt'],
        'es-AR' => ['decimal_separator' => 'Komma'],
        'es-ES' => ['decimal_separator' => 'Komma'],
        'es-MX' => ['decimal_separator' => 'Punkt'],
        'et-EE' => ['decimal_separator' => 'Komma'],
        'fi-FI' => ['decimal_separator' => 'Komma'],
        'fr-CH' => ['decimal_separator' => 'Komma'],
        'fr-CA' => ['decimal_separator' => 'Komma'],
        'fr-FR' => ['decimal_separator' => 'Komma'],
        'he-IL' => ['decimal_separator' => 'Punkt'],
        'hi-IN' => ['decimal_separator' => 'Punkt'],
        'hr-HR' => ['decimal_separator' => 'Komma'],
        'hu-HU' => ['decimal_separator' => 'Komma'],
        'id-ID' => ['decimal_separator' => 'Komma'],
        'is-IS' => ['decimal_separator' => 'Komma'],
        'it-IT' => ['decimal_separator' => 'Komma'],
        'ja-JP' => ['decimal_separator' => 'Punkt'],
        'kk-KZ' => ['decimal_separator' => 'Komma'],
        'ko-KR' => ['decimal_separator' => 'Punkt'],
        'lt-LT' => ['decimal_separator' => 'Komma'],
        'lv-LV' => ['decimal_separator' => 'Komma'],
        'mk-MK' => ['decimal_separator' => 'Komma'],
        'ms-MY' => ['decimal_separator' => 'Punkt'],
        'nb-NO' => ['decimal_separator' => 'Komma'],
        'nl-BE' => ['decimal_separator' => 'Komma'],
        'nl-NL' => ['decimal_separator' => 'Komma'],
        'nn-NO' => ['decimal_separator' => 'Komma'],
        'pl-PL' => ['decimal_separator' => 'Komma'],
        'pt-BR' => ['decimal_separator' => 'Komma'],
        'pt-PT' => ['decimal_separator' => 'Komma'],
        'ro-RO' => ['decimal_separator' => 'Komma'],
        'ru-RU' => ['decimal_separator' => 'Komma'],
        'sk-SK' => ['decimal_separator' => 'Komma'],
        'sl-SI' => ['decimal_separator' => 'Komma'],
        'sq-AL' => ['decimal_separator' => 'Komma'],
        'sr-Cyrl-BA ' => ['decimal_separator' => 'Komma'],
        'sr-Cyrl-CS ' => ['decimal_separator' => 'Komma'],
        'sr-Cyrl-ME ' => ['decimal_separator' => 'Komma'],
        'sr-Cyrl-RS ' => ['decimal_separator' => 'Komma'],
        'sr-Latn-BA' => ['decimal_separator' => 'Komma'],
        'sr-Latn-CS' => ['decimal_separator' => 'Komma'],
        'sr-Latn-ME' => ['decimal_separator' => 'Komma'],
        'sr-Latn-RS' => ['decimal_separator' => 'Komma'],
        'sv-SE' => ['decimal_separator' => 'Komma'],
        'th-TH' => ['decimal_separator' => 'Punkt'],
        'tr-TR' => ['decimal_separator' => 'Komma'],
        'uk-UA' => ['decimal_separator' => 'Komma'],
        'vi-VN' => ['decimal_separator' => 'Komma'],
        'zh-CN' => ['decimal_separator' => 'Punkt'],
        'zh-TW' => ['decimal_separator' => 'Punkt'],
    ];
}

function skipChecksForMid($data, $kombi, $package, $file, $mid, $checkFunc, $sncSettings, $emptyTrgMids)
{
    $debug = false;

    // Standardbehandlung
    if ((isEmptyTrgMid($emptyTrgMids, $kombi, $package, $file, $mid)) &&
        ($checkFunc != 'check_meta_data') &&
        ($checkFunc != 'check_fehlende_oder_geteilte_segIDs') &&
        ($checkFunc != '_check_WARNING_FOR_ALL_SEGS')) {
        if ($debug) {
            echo "SKIP! mid: [$mid] isEmptyTrgMid\nfile: $file\n\n";
        }

        return true;
    }

    if (true) {
        if ((isLocked_studio($data, $kombi, $package, $file, $mid)) &&
            (!CHECK_LOCKED)) {
            if ($debug) {
                echo "SKIP! mid: [$mid] isLocked_studio && !CHECK_LOCKED\nfile: $file\n\n";
            }

            return true;
        }


        if ((isPerfectMatch_studio($data, $kombi, $package, $file, $mid)) &&
            ($sncSettings['studio.skipMatches.perfectMatch']['master_value'] == true)) {
            if ($debug) {
                echo "SKIP! mid: [$mid] isPerfectMatch_studio && studio.skipMatches.perfectMatch\nfile: $file\n\n";
            }

            return true;
        }

        if ((isContextMatch_studio($data, $kombi, $package, $file, $mid)) &&
            ($sncSettings['studio.skipMatches.contextMatch']['master_value'] == true)) {
            if ($debug) {
                echo "SKIP! mid: [$mid] isContextMatch_studio && studio.skipMatches.contextMatch\nfile: $file\n\n";
            }

            return true;
        }

        if ((is100PercentMatch_studio($data, $kombi, $package, $file, $mid)) &&
            ($sncSettings['studio.skipMatches.100PercentMatch']['master_value'] == true)) {
            if ($debug) {
                echo "SKIP! mid: [$mid] is100PercentMatch_studio && studio.skipMatches.100PercentMatch\nfile: $file\n\n";
            }

            return true;
        }
    }

    return false;
}

function isLocked_studio($data, $kombi, $package, $file, $mid)
{
    $debug = false;

    if (isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['locked'])) {
        return true;
    }

    return false;
}

function isEmptyTrgMid($emptyTrgMids, $kombi, $package, $file, $mid)
{
    $debug = false;

    if (isset($emptyTrgMids[$kombi][$package][$file][$mid])) {
        return true;
    }

    return false;
}

function isMissingInTPF_transit($data, $kombi, $package, $file)
{
    $debug = false;

    if (isset($data[$kombi][$package][$file]['fileInfo']['missingInTPF'])) {
        return true;
    }

    return false;
}

function isPerfectMatch_studio($data, $kombi, $package, $file, $mid)
{
    if ((isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['origin'])) &&
        ($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['origin'] == 'document-match') &&
        (isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['originSystem'])) &&
        (preg_match('!^(Perfect Match|WorldServer)$!', $data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['originSystem'])) &&
        (isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['percent'])) &&
        ($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['percent'] == 100) &&
        (isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['textMatch'])) &&
        (preg_match('!^(Source|SourceAndTarget)$!', $data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['textMatch']))) {
        return true;
    }

    return false;
}

function isContextMatch_studio($data, $kombi, $package, $file, $mid)
{
    if ((isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['textMatch'])) &&
        (preg_match('!^(Source|SourceAndTarget)$!', $data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['textMatch'])) &&
        (isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['percent'])) &&
        ($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['percent'] == 100) &&
        (isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['origin'])) &&
        ($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['origin'] == 'tm')) {
        return true;
    }

    return false;
}

function is100PercentMatch_studio($data, $kombi, $package, $file, $mid)
{
    if ((!isPerfectMatch_studio($data, $kombi, $package, $file, $mid)) &&
        (!isContextMatch_studio($data, $kombi, $package, $file, $mid)) &&
        (isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['percent'])) &&
        ($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['percent'] == 100) &&
        (isset($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['origin'])) &&
        ($data[$kombi][$package][$file]['segInfo'][$mid]['segDefs']['origin'] == 'tm')) {
        return true;
    }

    return false;
}

function setCheckProps(&$checks, $checkProps)
{
    $checkFunc = $checkProps['checkFunc'];
    $checkType = $checkProps['checkType'];

    if (!function_exists($checkFunc)) {
        trigger_error("function $checkFunc als checkProps definiert, aber !function_exists", E_USER_ERROR);
    }

    $checks['functions'][$checkFunc]['checkType'] = $checkProps['checkType'];
    $checks['functions'][$checkFunc]['checkType_EN'] = $checkProps['checkType_EN'];
    $checks['functions'][$checkFunc]['checkRule'] = $checkProps['checkRule'];
    $checks['functions'][$checkFunc]['checkRule_EN'] = $checkProps['checkRule_EN'];
    $checks['functions'][$checkFunc]['hideInReportHeader'] = $checkProps['hideInReportHeader'];
    $checks['functions'][$checkFunc]['supportedSncTypes'] = $checkProps['supportedSncTypes'];
    $checks['de2en'][$checkType] = $checkProps['checkType_EN'];
}

function setCheckSubProps(&$checks, $checkProps)
{
    $checkFunc = $checkProps['checkFunc'];
    $checkSubFunc = $checkProps['checkSubFunc'];
    $checkSubType = $checkProps['checkSubType'];
    $checkSubType_EN = $checkProps['checkSubType_EN'];
    $checkSubRule = $checkProps['checkSubRule'];
    $checkSubRule_EN = $checkProps['checkSubRule_EN'];

    if (!function_exists($checkSubFunc)) {
        trigger_error("function $checkSubFunc als checkProps definiert, aber !function_exists", E_USER_ERROR);
    }

    $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubType'] = $checkProps['checkSubType'];
    $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubRule'] = $checkProps['checkSubRule'];
    $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubType_EN'] = $checkProps['checkSubType_EN'];
    $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubRule_EN'] = $checkProps['checkSubRule_EN'];

    $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['hideInReportHeader'] = $checkProps['hideInReportHeader'];
    if (isset($checkProps['hideInReportHeader_external'][$checkSubFunc])) {
        $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['hideInReportHeader_external'] = $checkProps['hideInReportHeader_external'][$checkSubFunc];
    }

    if (isset($checkProps['checkSeverity'][$checkSubFunc])) {
        $checks['severityBySubFunc'][$checkSubFunc] = $checkProps['checkSeverity'][$checkSubFunc];
        $checks['severityBySubType'][$checkSubType] = $checkProps['checkSeverity'][$checkSubFunc];
    } else {
        $checks['severityBySubFunc'][$checkSubFunc] = 'warning';
        $checks['severityBySubType'][$checkSubType] = 'warning';
    }

    $checks['de2en'][$checkSubType] = $checkSubType_EN;

    if ($checkProps['checkSubRule'] != '' && $checkProps['checkSubRule_EN'] != '') {
        $checks['de2en'][$checkSubRule] = $checkSubRule_EN;
    }
}

function getSubCheckProps($checkFunc, $checkSubFunc, $checks)
{
    $debug = false;

    $checkProps['checkSubFunc'] = $checkSubFunc;
    $checkProps['checkSubType'] = $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubType'];
    $checkProps['checkSubRule'] = $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubRule'];
    $checkProps['checkSubType_EN'] = $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubType_EN'];
    $checkProps['checkSubRule_EN'] = $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubRule_EN'];

    return $checkProps;
}

function setAsChecked(&$checks, $checkFunc, $checkSubFunc, $kombi)
{
    $debug = false;

    $checks['functions'][$checkFunc]['checked'][$kombi] = true;
    $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checked'][$kombi] = true;
}

function noLokaAllowed($sncSettings)
{
    return false;
}

// doMessages, doCounts und doMatches werden immer aus den einzelnen check_functions aufgerufen, wenn eine Meldung ($checkMessages) samt Count ($msgCounts) und Matches ($msgMatches) erzeugt werden soll
// $checkMessages, $msgCounts und $msgMatches enthalten jeweils diverseste Arrays nach verschiedenen Sotierungen/Gruppierungen (siehe in den Funktionen)
// via Durchlaufen/Abfragen dieser Arrays werden später die verschiedenen Logs ausgegeben

function doMessages($checkResults, $currentData, $checkProps, &$checkMessages, &$checks, $visibility = 'visible')
{
    $debug = false;

    // $checkType/$checkSubType/$checkMessage enthalten immer deutschen Text
    // Die englische Version dieser Variablen heißt $<var>_EN (z.B. "$checkSubType_EN") und wird via $checks["de2en"][$var] = $var_EN gemappt
    // Wenn die englische Version in den checks/subChecks nicht zugewiesen wurde, wird auch $checks["de2en"][$var] nicht gemappt

    // Im Normalfall wird der $subType-Text verwendet, der im check definiert wird
    // In Sonderfällen kann aber im subCheck dieser Text geändert werden
    // der geänderte subType muss dann als $checkSubType_extra geführt werden
    // Wenn also $checkMeta["checkSubType_extra"] existiert, wird dieser Wert als checkSubType verwendet
    // Das ist z.B. für Terminologie relevant, weil hier filtern nach konkreten Termini nötig ist, die im generischen SubType (SRC != TRG) nicht genannt werden

    // ACHTUNG: subTypes_extra müssen im $checks-Array nicht enthalten sein!!

    $kombi = $currentData['kombi'];
    $package = $currentData['package'];
    $file = $currentData['file'];
    $mid = $currentData['mid'];

    if (true) {
        $srcSeg = $currentData['segTypes']['srcMrk2Snc'];
        $trgSeg = $currentData['segTypes']['trgMrk2Snc'];
    } else {
        $srcSeg = $currentData['segTypes']['srcSeg'];
        $trgSeg = $currentData['segTypes']['trgSeg'];
    }

    $checkFunc = $checkProps['checkFunc'];
    $checkID = $checkProps['checkID'];
    $checkType = $checkProps['checkType'];
    $checkSubFunc = $checkProps['checkSubFunc'];
    $checkSubType = $checkProps['checkSubType'];
    $checkSubRule = $checkProps['checkSubRule'];

    $checkMessage = $checkResults['checkMessage'];

    if ($checkSubType == '') {
        exit($checkFunc . '  ' . $checkSubFunc);
    }

    // Prüfen ob checkSubType geändert wurde aber nicht in $checkSubType_extra abgelegt wurde, sondern als checkSubType mitgeführt
    // Dient nur zur internen Kontrolle
    $genericSubType = $checks['functions'][$checkFunc]['subChecks'][$checkSubFunc]['checkSubType'];
    if ($checkSubType != $genericSubType) {
        trigger_error("checkSubType_extra nicht vergeben! $checkSubType != $genericSubType in $checkFunc -> $checkSubFunc", E_USER_ERROR);
    }

    if (isset($checkProps['checkSubType_extra'])) {
        $checkSubType = $checkProps['checkSubType_extra'];
    } else {
        $checkSubType = $checkProps['checkSubType'];
    }

    if (isset($checkProps['checkSubType_extra_EN'])) {
        if ($checkProps['checkSubType_extra_EN'] == '') {
            trigger_error("checkSubType_extra_EN == \"\" zu {$checkSubType}. Abbruch.\n", E_USER_ERROR);
        }
        $checks['de2en'][$checkSubType] = $checkProps['checkSubType_extra_EN'];
    }

    if (isset($checkResults['checkMessage_EN'])) {
        $checks['de2en'][$checkMessage] = $checkResults['checkMessage_EN'];
    }

    $checks['subType2subFunc'][$checkSubType] = $checkFunc;
    $checks['subType2genericSubType'][$checkSubType] = $checkFunc;
    $checks['message2subType'][$checkMessage] = $checkSubType;
    $checks['subType2subRule'][$checkSubType] = $checkSubRule;

    if ($visibility != 'visible') {
        $checks['hiddenSubTypes_kombi_checkFunc_checkSubType'][$kombi][$checkFunc][$checkSubType] = true;
        $checks['hiddenMessages_kombi_package_file_mid_message'][$kombi][$package][$file][$mid][$checkMessage] = true;

        if ($checkFunc == 'check_terminologie' || $checkFunc == 'check_kommentare') {
            $checks['hiddenTX_kombi'][$kombi] = true;
            $checks['hiddenTX_kombi_package'][$kombi][$package] = true;
            $checks['hiddenTX_kombi_package_file'][$kombi][$package][$file] = true;
        }
        if ($checkFunc == 'check_kommentare') {
            $checks['hiddenTermComments_kombi_checkFunc_checkSubType'][$kombi][$checkFunc][$checkSubType] = true;
            $checks['hiddenTermComments_kombi'][$kombi] = true;
            $checks['hiddenTermComments_kombi_package'][$kombi][$package] = true;
            $checks['hiddenTermComments_kombi_package_file'][$kombi][$package][$file] = true;
            $checks['hiddenTermComments_kombi_package_file_mid'][$kombi][$package][$file][$mid] = true;
        }
    }

    if (isset($checkResults['checkMessageColor'])) {
        $checks['message2color'][$checkMessage] = $checkResults['checkMessageColor'];
    }

    if (isset($checkProps['checkFunc2color'])) {
        $checks['checkFunc2color'][$checkFunc] = $checkProps['checkFunc2color'];
    }

    if (isset($checkProps['checkSubType2color'])) {
        $checks['checkSubType2color'][$checkSubType] = $checkProps['checkSubType2color'];
    }

    $checkMessages['kombi_package_checkFunc_checkSubFunc'][$kombi][$package][$checkFunc][$checkSubFunc][] = $checkMessage;
    $checkMessages['kombi_package_checkFunc_checkSubType'][$kombi][$package][$checkFunc][$checkSubType][] = $checkMessage;

    $checkMessages['kombi_package_file_checkFunc_checkSubType'][$kombi][$package][$file][$checkFunc][$checkSubType][] = $checkMessage;
    $checkMessages['kombi_package_file_checkFunc_checkSubFunc'][$kombi][$package][$file][$checkFunc][$checkSubFunc][] = $checkMessage;

    $checkMessages['kombi_package_file_mid'][$kombi][$package][$file][$mid][] = $checkMessage;

    $checkMessages['kombi_package_file_mid_checkFunc_checkSubFunc'][$kombi][$package][$file][$mid][$checkFunc][$checkSubFunc][] = $checkMessage;
    $checkMessages['kombi_package_file_mid_checkFunc_checkSubType'][$kombi][$package][$file][$mid][$checkFunc][$checkSubType][] = $checkMessage;

    $checkMessages['kombi_checkFunc_checkSubType_srcSeg_trgSeg_package_file_mid'][$kombi][$checkFunc][$checkSubType][$srcSeg][$trgSeg][$package][$file][$mid][] = $checkMessage;

    $checkMessages['kombi_package_checkFunc_checkSubType_srcSeg_trgSeg_file_mid'][$kombi][$package][$checkFunc][$checkSubType][$srcSeg][$trgSeg][$file][$mid][] = $checkMessage;

    $checkMessages['kombi_srcSeg_trgSeg_checkFunc_checkSubType'][$kombi][$srcSeg][$trgSeg][$checkFunc][$checkSubType][] = $checkMessage;
    $checkMessages['kombi_srcSeg_trgSeg_checkFunc_checkSubType'][$kombi][$srcSeg][$trgSeg][$checkFunc][$checkSubType] = array_unique($checkMessages['kombi_srcSeg_trgSeg_checkFunc_checkSubType'][$kombi][$srcSeg][$trgSeg][$checkFunc][$checkSubType]);

    $checkMessages['kombi_package_srcSeg_trgSeg_checkFunc_checkSubType'][$kombi][$package][$srcSeg][$trgSeg][$checkFunc][$checkSubType][] = $checkMessage;
    $checkMessages['kombi_package_srcSeg_trgSeg_checkFunc_checkSubType'][$kombi][$package][$srcSeg][$trgSeg][$checkFunc][$checkSubType] = array_unique($checkMessages['kombi_package_srcSeg_trgSeg_checkFunc_checkSubType'][$kombi][$package][$srcSeg][$trgSeg][$checkFunc][$checkSubType]);

    if ($checkFunc != 'check_varianten') {
        $checkMessages['noVar_kombi_checkFunc_checkSubFunc'][$kombi][$checkFunc][$checkSubFunc][] = $checkMessage;
        $checkMessages['noVar_kombi_checkFunc_checkSubType'][$kombi][$checkFunc][$checkSubType][] = $checkMessage;
        $checkMessages['noVar_kombi_package_checkFunc_checkSubFunc'][$kombi][$package][$checkFunc][$checkSubFunc][] = $checkMessage;
        $checkMessages['noVar_kombi_package_checkFunc_checkSubType'][$kombi][$package][$checkFunc][$checkSubType][] = $checkMessage;
        $checkMessages['noVar_kombi_checkFunc_checkSubType_srcSeg_trgSeg_package_file_mid'][$kombi][$checkFunc][$checkSubType][$srcSeg][$trgSeg][$package][$file][$mid][] = $checkMessage;
        $checkMessages['noVar_kombi_package_checkFunc_checkSubType_srcSeg_trgSeg_file_mid'][$kombi][$package][$checkFunc][$checkSubType][$srcSeg][$trgSeg][$file][$mid][] = $checkMessage;

        $checkMessages['noVar_kombi_package_file_mid_checkFunc_checkSubFunc'][$kombi][$package][$file][$mid][$checkFunc][$checkSubFunc][] = $checkMessage;
        $checkMessages['noVar_kombi_package_file_mid_checkFunc_checkSubType'][$kombi][$package][$file][$mid][$checkFunc][$checkSubType][] = $checkMessage;
    }

    if ($checkFunc != 'check_terminologie') {
        $checkMessages['noTerm_kombi_package_checkFunc_checkSubFunc'][$kombi][$package][$checkFunc][$checkSubFunc][] = $checkMessage;
        $checkMessages['noTerm_kombi_package_checkFunc_checkSubType'][$kombi][$package][$checkFunc][$checkSubType][] = $checkMessage;
        $checkMessages['noTerm_kombi_package_file_mid_checkFunc_checkSubFunc'][$kombi][$package][$file][$mid][$checkFunc][$checkSubFunc][] = $checkMessage;
        $checkMessages['noTerm_kombi_package_file_mid_checkFunc_checkSubType'][$kombi][$package][$file][$mid][$checkFunc][$checkSubType][] = $checkMessage;
    }

    if (($checkFunc != 'check_varianten') &&
        ($checkFunc != 'check_terminologie')) {
        $checkMessages['noVar_noTerm_kombi_package_checkFunc_checkSubFunc'][$kombi][$package][$checkFunc][$checkSubFunc][] = $checkMessage;
        $checkMessages['noVar_noTerm_kombi_package_checkFunc_checkSubType'][$kombi][$package][$checkFunc][$checkSubType][] = $checkMessage;
        $checkMessages['noVar_noTerm_kombi_package_file_mid_checkFunc_checkSubFunc'][$kombi][$package][$file][$mid][$checkFunc][$checkSubFunc][] = $checkMessage;
    }
}
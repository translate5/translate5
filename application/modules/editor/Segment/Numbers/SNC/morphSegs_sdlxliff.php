<?php

/*
  Verschiedene Segmentversionen erzeugen für die Checks, Ausgabe in Logs etc
  Format: sdlxliff
*/

function morphSegs_sdlxliff($segTypes, $tagDefs, $langs)
{
    $debug = false; $lockedTransUnits = []; $kombi = join('-', $langs);

    $segTypes['srcMrk2Snc'] = morphMrk2Snc($segTypes['srcRaw'], $lockedTransUnits);
    $segTypes['trgMrk2Snc'] = morphMrk2Snc($segTypes['trgRaw'], $lockedTransUnits);

    $segTypes['srcNoMrk'] = cleanXSdlTags($segTypes['srcRaw']);
    $segTypes['trgNoMrk'] = cleanXSdlTags($segTypes['trgRaw']);

    $segTypes['srcSegVar2b'] = segVar2b($segTypes['srcNoMrk'], $segTypes['trgNoMrk'], $langs[0], $kombi, $lockedTransUnits);
    $segTypes['trgSegVar2b'] = segVar2b($segTypes['trgNoMrk'], $segTypes['srcNoMrk'], $langs[1], $kombi, $lockedTransUnits);

    $segTypes['srcSeg'] = segBase($segTypes['srcNoMrk']);
    $segTypes['trgSeg'] = segBase($segTypes['trgNoMrk']);

    $segTypes['srcSegTagSymbols'] = replaceTagsSymbols($segTypes['srcSeg']);
    $segTypes['trgSegTagSymbols'] = replaceTagsSymbols($segTypes['trgSeg']);

    $segTypes['srcSegPure'] = segPure($segTypes['srcSeg']);
    $segTypes['trgSegPure'] = segPure($segTypes['trgSeg']);

    $segTypes['srcSegPureSpace'] = segPureSpace($segTypes['srcSeg']);
    $segTypes['trgSegPureSpace'] = segPureSpace($segTypes['trgSeg']);

    $segTypes['srcSegPureSpaceNoLF'] = segPureSpaceNoLF($segTypes['srcSegPureSpace']);
    $segTypes['trgSegPureSpaceNoLF'] = segPureSpaceNoLF($segTypes['trgSegPureSpace']);

    $segTypes['srcSegPureNoPH'] = segPureNoPH($segTypes['srcSegPure']);
    $segTypes['trgSegPureNoPH'] = segPureNoPH($segTypes['trgSegPure']);

    $segTypes['srcSegVar2'] = segVar2($segTypes['srcSegPureNoPH'], $segTypes['trgSegPureNoPH'], $langs[0], $kombi);
    $segTypes['trgSegVar2'] = segVar2($segTypes['trgSegPureNoPH'], $segTypes['srcSegPureNoPH'], $langs[1], $kombi);

    $segTypes['srcSegVar3'] = segVar3($segTypes['srcSegVar2'], $langs[0], $kombi);
    $segTypes['trgSegVar3'] = segVar3($segTypes['trgSegVar2'], $langs[1], $kombi);

    $segTypes['srcSegReal'] = segRealTags($segTypes['srcSeg'], $tagDefs);
    $segTypes['trgSegReal'] = segRealTags($segTypes['trgSeg'], $tagDefs);

    $segTypes['srcSegSimple'] = segSimpleTags($segTypes['srcSeg'], $tagDefs);
    $segTypes['trgSegSimple'] = segSimpleTags($segTypes['trgSeg'], $tagDefs);

    //echo "segTypes: \n"; print_r($segTypes);

    return $segTypes;
}

function morphMrk2Snc($seg, $lockedTransUnits)
{
    $debug = false;

    $seg = normalizeCCs($seg);
    $seg = normalizeTags2($seg);
    $seg = stripEmptyXSdlTags($seg);
    $seg = xSdlTags2SncMarkup($seg);
    $seg = lockedTags2sncLocked($seg, $lockedTransUnits);

    return $seg;
}

function cleanXSdlTags($seg)
{
    $debug = false;

    $seg = normalizeCCs($seg);
    $seg = normalizeTags2($seg);
    $seg = stripEmptyXSdlTags($seg);
    $seg = stripXSdlTags($seg);

    return $seg;
}

function segBase($seg)
{
    $debug = false;

    $seg = normalizeCCs($seg);
    $seg = normalizeTags($seg);
    //$seg = unHTMLSpecialChars($seg);

    return $seg;
}

function segPure($seg)
{
    $debug = false;

    $seg = stripSNCComment($seg);
    $seg = stripSNCAdded($seg);
    $seg = stripSNCDeleted($seg);

    $seg = stripGroupTags($seg);
    //$seg = stripPseudoTags($seg);
    $seg = replacePhTagsSNC($seg);
    $seg = unHTMLSpecialChars($seg);

    return $seg;
}

function segPureSpace($seg)
{
    $debug = false;

    $seg = stripSNCComment($seg);
    $seg = stripSNCAdded($seg);
    $seg = stripSNCDeleted($seg);

    $seg = stripGroupTags2Space($seg);
    //$seg = stripPseudoTags2Space($seg);
    $seg = replacePhTagsSNC($seg);
    $seg = unHTMLSpecialChars($seg);

    return $seg;
}

function segPureSpaceNoLF($seg)
{
    $debug = false;

    // Umbruch durch LZ ersetzen.
    $seg = str_replace(SYMBOL_LF, ' ', $seg);
    $seg = preg_replace('!\p{Zs}+!u', ' ', $seg);
    $seg = trim($seg);

    return $seg;
}

function segPureNoPH($seg)
{
    $debug = false;

    $seg = stripSNCPH($seg);

    return $seg;
}

function segVar2($seg, $otherSeg, $lang, $kombi)
{
    $debug = false;

    $seg = trim($seg);

    // Ersatzzeichen für Tags entfernen
    $seg = str_replace('►', '', $seg);
    $seg = str_replace('◄', '', $seg);
    $seg = str_replace('▲', ' ', $seg);

    $seg = preg_replace("!^\p{Zs}+!u", '', $seg);
    $seg = preg_replace("!\p{Zs}+$!u", '', $seg);

    if (preg_match('#^\d{1,2}(\.|\.\d{1,2})?(' . SYMBOL_TAB . '|\p{Zs})+(?=(\p{L}|\d))#u', $seg, $m)) {
        $match_quote = preg_quote($m[0]);
        if (preg_match("#^$match_quote#u", $otherSeg)) {
            $seg = preg_replace('#^\d{1,2}(\.|\.\d{1,2})?(' . SYMBOL_TAB . '|\p{Zs})+(?=(\p{L}|\d))#u', '', $seg);
        }
    }

    // hotkeys
    $seg = str_replace('&lt;', '__SNC_LT__', $seg);
    $seg = str_replace('&gt;', '__SNC_GT__', $seg);
    $seg = str_replace('&amp;', '__SNC_AMP__', $seg);
    $seg = str_replace('&apos;', '__SNC_APOS__', $seg);
    $seg = preg_replace('!(\p{L}*?)&(\p{L}+)!u', '$1' . '$2', $seg);
    $seg = str_replace('__SNC_LT__', '&lt;', $seg);
    $seg = str_replace('__SNC_GT__', '&gt;', $seg);
    $seg = str_replace('__SNC_AMP__', '&amp;', $seg);
    $seg = str_replace('__SNC_APOS__', '&apos;', $seg);

    $seg = preg_replace("!\p{Zs}+!u", ' ', $seg);
    $seg = str_replace(' ' . SYMBOL_LF, SYMBOL_LF, $seg);

    $seg = str_replace(' .', '.', $seg);
    $seg = str_replace(' ,', ',', $seg);
    $seg = str_replace(' :', ':', $seg);
    $seg = str_replace(' )', ')', $seg);
    $seg = str_replace('( ', '(', $seg);

    // EN DASH normalisieren
  $seg = str_replace('–', '-', $seg); // E2 80 93  EN DASH' (U+2013)

  // Bedingten Trennstrich entfernen
    $seg = str_replace("\xc2\xAD", '', $seg);

    //Zero-width space entfernen
    $seg = str_replace("\xe2\x80\x8b", '', $seg);

    $seg = trim($seg);

    return $seg;
}

function segVar2b($seg, $otherSeg, $lang, $kombi, $lockedTransUnits)
{
    $debug = false;

    $seg = trim($seg);
    $seg = lockedTags2sncLocked($seg, $lockedTransUnits);
    $seg = stripSNCLockedMarkup($seg);
    $seg = segPure($seg);
    $seg = segPureNoPH($seg);

    // Ersatzzeichen für Tags entfernen
    $seg = str_replace('►', '', $seg);
    $seg = str_replace('◄', '', $seg);
    $seg = str_replace('▲', ' ', $seg);

    $seg = preg_replace("!^\p{Zs}+!u", '', $seg);
    $seg = preg_replace("!\p{Zs}+$!u", '', $seg);

    if (preg_match('#^\d{1,2}(\.|\.\d{1,2})?(' . SYMBOL_TAB . '|\p{Zs})+(?=(\p{L}|\d))#u', $seg, $m)) {
        $match_quote = preg_quote($m[0]);
        if (preg_match("#^$match_quote#u", $otherSeg)) {
            $seg = preg_replace('#^\d{1,2}(\.|\.\d{1,2})?(' . SYMBOL_TAB . '|\p{Zs})+(?=(\p{L}|\d))#u', '', $seg);
        }
    }

    // hotkeys
    $seg = str_replace('&lt;', '__SNC_LT__', $seg);
    $seg = str_replace('&gt;', '__SNC_GT__', $seg);
    $seg = str_replace('&amp;', '__SNC_AMP__', $seg);
    $seg = str_replace('&apos;', '__SNC_APOS__', $seg);
    $seg = preg_replace('!(\p{L}*?)&(\p{L}+)!u', '$1' . '$2', $seg);
    $seg = str_replace('__SNC_LT__', '&lt;', $seg);
    $seg = str_replace('__SNC_GT__', '&gt;', $seg);
    $seg = str_replace('__SNC_AMP__', '&amp;', $seg);
    $seg = str_replace('__SNC_APOS__', '&apos;', $seg);

    $seg = preg_replace("!\p{Zs}+!u", ' ', $seg);
    $seg = str_replace(' ' . SYMBOL_LF, SYMBOL_LF, $seg);

    $seg = str_replace(' .', '.', $seg);
    $seg = str_replace(' ,', ',', $seg);
    $seg = str_replace(' :', ':', $seg);
    $seg = str_replace(' )', ')', $seg);
    $seg = str_replace('( ', '(', $seg);

    // EN DASH normalisieren
  $seg = str_replace('–', '-', $seg); // E2 80 93  EN DASH' (U+2013)

  // Bedingten Trennstrich entfernen
    $seg = str_replace("\xc2\xAD", '', $seg);

    //Zero-width space entfernen
    $seg = str_replace("\xe2\x80\x8b", '', $seg);

    $seg = trim($seg);

    return $seg;
}

function segVar3($seg, $lang, $kombi)
{
    $debug = false;

    $seg = trim($seg);
    $seg = mb_strtolower($seg);
    $seg = str_replace('ä', 'ae', $seg);
    $seg = str_replace('ö', 'oe', $seg);
    $seg = str_replace('ü', 'ue', $seg);
    $seg = str_replace('ß', 'ss', $seg);
    $seg = preg_replace('![^\p{L}]!ui', '', $seg);
    $seg = trim($seg);

    return $seg;
}

function segRealTags($seg, $tagDefs)
{
    $debug = false;

    $seg = stripSNCComment($seg);
    $seg = stripSNCAdded($seg);
    $seg = stripSNCDeleted($seg);
    $seg = replaceTagsReal($seg, $tagDefs);
    $seg = unHTMLSpecialChars($seg);

    return $seg;
}

function segSimpleTags($seg, $tagDefs)
{
    $debug = false;

    $seg = stripSNCComment($seg);
    $seg = stripSNCAdded($seg);
    $seg = stripSNCDeleted($seg);
    $seg = replaceTagsReal($seg, $tagDefs);
    $seg = replaceTagsSimple($seg);
    $seg = unHTMLSpecialChars($seg);

    return $seg;
}

// Einzelne Functions zum Umformen

function normalizeCCs($seg)
{
    $debug = false;

    $seg = str_replace("\r\n", SYMBOL_CRLF, $seg);
    $seg = str_replace('&#xD;&#xA;', SYMBOL_CRLF, $seg);
    $seg = str_replace("\r", SYMBOL_CR, $seg);
    $seg = str_replace('&#xD;', SYMBOL_CR, $seg);
    $seg = str_replace("\n", SYMBOL_LF, $seg);
    $seg = str_replace('&#xA;', SYMBOL_LF, $seg);
    $seg = str_replace("\t", SYMBOL_TAB, $seg);
    $seg = str_replace('&#x9;', SYMBOL_TAB, $seg);

    return $seg;
}

function normalizeTags($seg)
{
    $debug = false;

    $seg = str_replace(' sdl:start="false"', '', $seg);
    $seg = str_replace(' sdl:end="false"', '', $seg);

    if ($debug) {
        echo "normalizeTags 1 $seg\n";
    }

    $seg = preg_replace('! xmlns:[A-Za-z0-9]+="[^"]*?"!', '', $seg);
    $seg = preg_replace('! xid="[A-Za-z0-9\-\._ ]+"!', '', $seg);
    $seg = str_replace('!<x id="locked!', '<x id="', $seg);

    if ($debug) {
        echo "normalizeTags 2 $seg\n";
    }

    $seg = preg_replace('#<g (id="[^"]*?")[^>]*? />#', '<g $1 />', $seg);
    $seg = preg_replace('#<g (id="[^"]*?")[^>/]*?>#', '<g $1>', $seg);

    if ($debug) {
        echo "normalizeTags 3 $seg\n";
    }

    return $seg;
}

function normalizeTags2($seg)
{
    $debug = false;

    $seg = str_replace(' sdl:start="false"', '', $seg);
    $seg = str_replace(' sdl:end="false"', '', $seg);
    if ($debug) {
        echo "normalizeTags 1 $seg\n";
    }

    $seg = preg_replace('! xmlns:[A-Za-z0-9]+="[^"]*?"!', '', $seg);
    if ($debug) {
        echo "normalizeTags 2 $seg\n";
    }

    $seg = preg_replace('!(<x id="\d+\") xid="[A-Za-z0-9\-\._ ]+"!', '$1', $seg);
    if ($debug) {
        echo "normalizeTags 3 $seg\n";
    }

    $seg = preg_replace('#<g (id="[^"]*?")[^>]*? />#', '<g $1 />', $seg);
    if ($debug) {
        echo "normalizeTags 4 $seg\n";
    }

    $seg = preg_replace('#<g (id="[^"]*?")[^>/]*?>#', '<g $1>', $seg);
    if ($debug) {
        echo "normalizeTags 5 $seg\n";
    }

    return $seg;
}

function unHTMLSpecialChars($seg)
{
    $debug = false;

    while (strpos($seg, '&amp;') !== false) {
        $seg = str_replace('&amp;', '&', $seg);
    }
    $seg = str_replace('&apos;', "'", $seg);
    $seg = str_replace('&quot;', '"', $seg);
    $seg = str_replace('&lt;', '<', $seg);
    $seg = str_replace('&gt;', '>', $seg);

    return $seg;
}

function stripGroupTags($seg)
{
    $debug = false;

    $seg = preg_replace('!<g id="[A-Za-z0-9\-_\.]+">!', '', $seg);
    $seg = preg_replace('!<g id="[A-Za-z0-9\-_\.]+"( )?/>!', '', $seg);
    $seg = preg_replace('!</g>!', '', $seg);

    return $seg;
}

function stripGroupTags2Space($seg)
{
    $debug = false;

    $seg = preg_replace('!<g id="[A-Za-z0-9\-_\.]+">!', ' ', $seg);
    $seg = preg_replace('!<g id="[A-Za-z0-9\-_\.]+"( )?/>!', ' ', $seg);
    $seg = preg_replace('!</g>!', ' ', $seg);

    $seg = preg_replace('!\p{Zs}+!u', ' ', $seg);
    $seg = trim($seg);

    return $seg;
}

function stripSNCComment($seg)
{
    $debug = false;

    $seg = preg_replace('!__SNC_COMMENT_.*?__!', '', $seg);
    $seg = preg_replace('!__SNC_/COMMENT_.*?__!', '', $seg);

    return $seg;
}

function stripSNCAdded($seg)
{
    $debug = false;

    $seg = preg_replace("!__SNC_ADDED_\d+__!", '', $seg);
    $seg = preg_replace("!__SNC_/ADDED_\d+__!", '', $seg);

    return $seg;
}

function stripSNCDeleted($seg)
{
    $debug = false;

    $seg = preg_replace("!__SNC_DELETED_(\d+)_.*?__SNC_/DELETED_\\1__!", '', $seg);

    return $seg;
}

function stripSNCLockedMarkup($seg)
{
    $debug = false;

    $seg = preg_replace('!__SNC_LOCKED_.*?__!', '', $seg);
    $seg = preg_replace('!__SNC_/LOCKED_.*?__!', '', $seg);

    return $seg;
}

function stripSNCPH($seg)
{
    $debug = false;

    $seg = str_replace(__SNC_PH__, ' ', $seg);
    $seg = preg_replace("!\p{Zs}+!u", ' ', $seg);
    $seg = trim($seg);

    return $seg;
}

function replacePhTagsSNC($seg)
{
    $debug = false;

    $seg = preg_replace('!<x id="[A-Za-z0-9\-]+"( )?/>!', __SNC_PH__, $seg);

    return $seg;
}

function replaceTagsSymbols($seg)
{
    $debug = false;

    $seg = stripSNCComment($seg);
    $seg = stripSNCAdded($seg);
    $seg = stripSNCDeleted($seg);

    $seg = preg_replace('!<g id="[A-Za-z0-9\-\._]+">!', '►', $seg);
    $seg = preg_replace('!<g id="[A-Za-z0-9\-\._]+"( )?/>!', '►◄', $seg);
    $seg = preg_replace('!</g>!', '◄', $seg);
    $seg = preg_replace('!<x id="[A-Za-z0-9\-\._]+"( )?/>!', '▲', $seg);

    return $seg;
}

function replaceTagsReal($seg, $tagDefs)
{
    $debug = false;

    $realTags = getRealTags($seg, $tagDefs);
    if ($debug) {
        print_r($realTags);
    }

    if (isset($realTags['tagPairs'])) {
        foreach ($realTags['tagPairs'] as $tagPair => $IDs) {
            $parts = explode('|', $tagPair);
            $part1 = $parts[0];
            $part2 = $parts[1];

            foreach ($IDs as $tagID) {
                $matchRegEx = "!<g id=\"$tagID\">(.*?)</g>!u";
                $replaceString = "$part1$1$part2";

                $seg = preg_replace($matchRegEx, $replaceString, $seg);
                if ($debug) {
                    echo "\nsrcSegRealTags Pairs: $seg\n";
                }
            }
        }
    }

    if (isset($realTags['tagPairPHs'])) {
        if ($debug) {
            print_r($realTags['tagPairPHs']);
        }

        foreach ($realTags['tagPairPHs'] as $tagPairPH => $IDs) {
            $parts = explode('|', $tagPairPH);
            $part1 = $parts[0];
            $part2 = $parts[1];

            foreach ($IDs as $tagID) {
                $matchRegEx = "!<g id=\"$tagID\"( )?/>!u";
                $replaceString = "$part1$part2";

                $seg = preg_replace($matchRegEx, $replaceString, $seg);
                if ($debug) {
                    echo "\nsrcSegRealTags tagPairPH: $seg\n";
                }
            }
        }
    }

    if (isset($realTags['tagPHs'])) {
        foreach ($realTags['tagPHs'] as $realPhTag => $IDs) {
            foreach ($IDs as $tagID) {
                $matchRegEx = "!<x id=\"$tagID\"( )?/>!u";
                $seg = preg_replace($matchRegEx, $realPhTag, $seg);
                if ($debug) {
                    echo "\nsrcSegRealTags tagPH: $seg\n";
                }
            }
        }
    }

    return $seg;
}

function replaceTagsSimple($seg)
{
    $debug = false;

    $seg = preg_replace_callback('!<([a-z0-9\?\-]+)([^>]*?)(/)?>!iu',
              function ($match) {
                  $debug = false;
                  if ($debug) {
                      echo "MMM match \n";
                      print_r($match);
                  }
                  $plus = '';
                  $slash = '';
                  if (isset($match[2])) {
                      $plus = '+';
                  }
                  if (isset($match[3])) {
                      $slash = '/';
                  }
                  $newName = '<' . $match[1] . $plus . $slash . '>';
                  if ($debug) {
                      echo "MMM newName $newName \n";
                  }

                  return $newName;
              },
              $seg);

    return $seg;
}

function stripEmptyXSdlTags($seg)
{
    $debug = false;

    $seg = preg_replace('!<mrk mtype="x-sdl-comment" sdl:cid="[A-Za-z0-9\-_]+"( )?/>!', '', $seg);
    $seg = preg_replace('!<mrk mtype="x-sdl-deleted" sdl:revid="[A-Za-z0-9\-_]+"( )?/>!', '', $seg);
    $seg = preg_replace('!<mrk mtype="x-sdl-added" sdl:revid="[A-Za-z0-9\-_]+"( )?/>!', '', $seg);
    $seg = preg_replace('!<mrk mtype="x-sdl-location"[^>]*?( )?/>!', '', $seg);

    $seg = preg_replace('!<mrk mtype="x-term[^>]*?>(.*?)</mrk>!', '$1', $seg);

    if ($debug) {
        echo "seg out ENDE:\n----------\n{$seg}\n----------\n";
    }

    return $seg;
}

function xSdlTags2SncMarkup($seg)
{
    $debug = false;

    if ((substr_count($seg, 'x-sdl-comment') > 0) ||
      (substr_count($seg, 'SNC_COMMENT') > 0)) {
        $hasComments = true;
    } else {
        $hasComments = false;
    }

    if (preg_match('!x-sdl-feedback!i', $seg, $m)) {
        trigger_error("Segment mit TQA-Auszeichung gefunden! Prüfung solcher Aufträge derzeit nicht unterstützt! Abbruch.\n\nSegment: {$seg}", E_USER_WARNING);
    }

    $segOrig = $seg;

    // Wenn Kommentare in gelöschten Bereichen eines Segs enthalten sind, kann es vorkommen, dass das damit gelöschte Kommentar-Element ( = <mrk mtype="x-sdl-comment"... >) außerhalb des Bereichs noch einmal vorkommt -- und zwar mit der gleichen ID!
    // Deshalb im nächsten Schritt alle sdl:cid und :revid unique machen (per Durchzählen und Count an die ID anhängen
    // ACHTUNG: Unique-ID muss unten wieder rückgängig gemacht werden, bevor sie ins SNC-Commentmarkup geschrieben wird

    if (preg_match_all("!<mrk mtype=\"x-sdl-(comment|added|deleted)\" sdl:(?:cid|revid)=\"([A-Za-z0-9\-_]+)\">!", $seg, $m, PREG_SET_ORDER)) {
        if ($debug) {
            echo "ALL MRK:\n";
            print_r($m);
        }

        foreach ($m as $key => $info) {
            $id = $m[$key][2];
            if ($debug) {
                echo "id: $id\n";
            }

            $seg = preg_replace('!"' . $id . '"!', '"' . $id . '_' . $key . '"', $seg, 1);
        }
    }

    if (preg_match_all('!(<mrk mtype="x-sdl-(added|deleted|comment)"[^>]*?>|</mrk>)!', $seg, $m, PREG_SET_ORDER)) {
        if ($debug) {
            print_r($m);
        }

        $commentCount = 0;
        $deletedCount = 0;
        $addedCount = 0;

        foreach ($m as $currentKey => $currentMatch) {
            $currentTag = $m[$currentKey][0];

            if ($debug) {
                echo "\ncurrentKey: $currentKey\n";
            }
            if ($debug) {
                echo "\ncurrentTag: $currentTag\n";
            }

            $set = false;

            $previousTag = '';

            if ($currentKey == 0) {
                $previousTag = '';
            } else {
                for ($j = 1; $currentKey - $j >= 0; $j++) {
                    if (isset($m[$currentKey - $j])) {
                        $previousTag = $m[$currentKey - $j][0];
                        $previousKey = $currentKey - $j;
                        $set = true;
                        break;
                    }
                }
            }

            if ($debug && !$set) {
                echo "!set!!!!!!!!!!\n";
            }
            if ($debug) {
                echo "previousTag: $previousTag\n";
            }

            if ((preg_match('!^</mrk>$!', $currentTag, $m1)) &&
                (preg_match('!^<mrk mtype="x-sdl-(added|deleted|comment)" sdl:(revid|cid)="([A-Za-z0-9\-_]+)">$!', $previousTag, $m2))) {
                //if($debug) print_r($m2);

                $previousTag = preg_quote($previousTag);
                $currentTag = preg_quote($currentTag);

                if ($m2[1] == 'deleted') {
                    $deletedCount++;
                    if ($hasComments) {
                        $seg = preg_replace("!{$previousTag}(.*?){$currentTag}!s", "__SNC_DELETED_{$deletedCount}__$1__SNC_/DELETED_{$deletedCount}__", $seg, 1);
                    } else {
                        $seg = preg_replace("!{$previousTag}(.*?){$currentTag}!s", '', $seg, 1);
                    }
                }
                if ($m2[1] == 'added') {
                    $addedCount++;
                    if ($hasComments) {
                        $seg = preg_replace("!{$previousTag}(.*?){$currentTag}!s", "__SNC_ADDED_{$addedCount}__$1__SNC_/ADDED_{$addedCount}__", $seg, 1);
                    } else {
                        $seg = preg_replace("!{$previousTag}(.*?){$currentTag}!s", '$1', $seg, 1);
                    }
                }
                if ($m2[1] == 'comment') {
                    $commentCount++;
                    // ursprüngliche Comment-ID wieder herstellen, weil darüber der Kommentar-Text referenziert wird
                    $commentID = preg_replace('!_\d+$!', '', $m2[3]);
                    $seg = preg_replace("!{$previousTag}(.*?){$currentTag}!s", "__SNC_COMMENT_C{$commentCount}_{$commentID}__$1__SNC_/COMMENT_C{$commentCount}_{$commentID}__", $seg, 1);
                }

                unset($m[$currentKey]);
                unset($m[$previousKey]);

                if ($debug) {
                    echo "\nSEG OUT ({$m2[1]}): {$seg}\n-----------------------------------------\n";
                }
            }
        }
        // $m sollte nach Durchlaufen aller Matches leer sein, außer ein <mrk> mit unbekannten Attributen wurde gefunden oder es ist etwas anderes grob schief gegangen
        if (!empty($m)) {
            trigger_error('Problem [!empty($m)] bei Umwandlung SDL-Markup -> SNC-Markup aufgetreten (' . __FUNCTION__ . ")...\nORIG:\n{$segOrig}\n\nNEU:\n{$seg}\n\n", E_USER_NOTICE);
        }
    }

    // <mrk|/mrk> sollte nicht matchen, außer ein <mrk> mit unbekannten Attributen wurde gefunden oder es ist etwas anderes grob schief gegangen
    if (preg_match('!(<mrk|/mrk>)!', $seg)) {
        trigger_error("Problem [preg_match('!(<mrk|/mrk>)!'] bei Umwandlung SDL-Markup -> SNC-Markup aufgetreten (" . __FUNCTION__ . ')' . __FUNCTION__ . "\nORIG:\n{$segOrig}\n\nNEU:\n{$seg}\n\n", E_USER_NOTICE);
    }

    if ($debug) {
        echo "seg out ENDE:\n----------\n{$seg}\n----------\n";
    }

    return $seg;
}

function stripXSdlTags($seg)
{
    $debug = false;

    $segOrig = $seg;

    // Wenn Kommentare in gelöschten Bereichen eines Segs enthalten sind, kann es vorkommen, dass das damit gelöschte Kommentar-Element ( = <mrk mtype="x-sdl-comment"... >) außerhalb des Bereichs noch einmal vorkommt -- und zwar mit der gleichen ID!
    // Deshalb im nächsten Schritt alle sdl:cid und :revid unique machen (per Durchzählen und Count an die ID anhängen

    if (preg_match_all("!<mrk mtype=\"x-sdl-(comment|added|deleted)\" sdl:(?:cid|revid)=\"([A-Za-z0-9\-_]+)\">!", $seg, $m, PREG_SET_ORDER)) {
        if ($debug) {
            echo "ALL MRK:\n";
            print_r($m);
        }

        foreach ($m as $key => $info) {
            $id = $m[$key][2];
            if ($debug) {
                echo "id: $id\n";
            }
            $seg = preg_replace('!"' . $id . '"!', '"' . $id . '_' . $key . '"', $seg, 1);
        }
    }

    if (preg_match_all('!(<mrk mtype="x-sdl-(added|deleted|comment)"[^>]*?>|</mrk>)!', $seg, $m, PREG_SET_ORDER)) {
        if ($debug) {
            echo "segOrig: $segOrig\n";
        }
        if ($debug) {
            print_r($m);
        }

        $commentCount = 0;

        foreach ($m as $currentKey => $currentMatch) {
            $currentTag = $m[$currentKey][0];

            if ($debug) {
                echo "\ncurrentKey: $currentKey\n";
            }
            if ($debug) {
                echo "\ncurrentTag: $currentTag\n";
            }

            $set = false;
            $previousTag = '';

            if ($debug) {
                print_r($m);
            }

            if ($currentKey == 0) {
                $previousTag = '';
            } else {
                for ($j = 1; $currentKey - $j >= 0; $j++) {
                    if (isset($m[$currentKey - $j])) {
                        $previousTag = $m[$currentKey - $j][0];
                        $previousKey = $currentKey - $j;
                        $set = true;
                        break;
                    }
                }
            }

            if ($debug && !$set) {
                echo "!set!!!!!!!!!!\n";
            }
            if ($debug) {
                echo "previousTag: $previousTag\n";
            }

            if ((preg_match('!^</mrk>$!', $currentTag, $m1)) &&
                (preg_match('!^<mrk mtype="x-sdl-(added|deleted|comment)" sdl:(revid|cid)="([A-Za-z0-9\-_]+)">$!', $previousTag, $m2))) {
                //if($debug) print_r($m2);

                $previousTag = preg_quote($previousTag);
                $currentTag = preg_quote($currentTag);

                if ($m2[1] == 'deleted') {
                    $seg = preg_replace("!{$previousTag}(.*?){$currentTag}!s", '', $seg, 1);
                }
                if ($m2[1] == 'added') {
                    $seg = preg_replace("!{$previousTag}(.*?){$currentTag}!s", '$1', $seg, 1);
                }
                if ($m2[1] == 'comment') {
                    $seg = preg_replace("!{$previousTag}(.*?){$currentTag}!s", '$1', $seg, 1);
                }

                unset($m[$currentKey]);
                unset($m[$previousKey]);

                if ($debug) {
                    echo "\nSEG OUT ({$m2[1]}): $seg\n-----------------------------------------\n";
                }
            }
        }
        // $m sollte nach Durchlaufen aller Matches leer sein, außer ein <mrk> mit unbekannten Attributen wurde gefunden oder es ist etwas anderes grob schief gegangen
        if (!empty($m)) {
            trigger_error('Problem [!empty($m)] beim Entfernen von SDL-Markup aufgetreten (' . __FUNCTION__ . ")...\nORIG:\n{$segOrig}\n\nNEU:\n{$seg}\n\n", E_USER_NOTICE);
        }
    }

    // <mrk|/mrk> sollte nicht matchen, außer ein <mrk> mit unbekannten Attributen wurde gefunden oder es ist etwas anderes grob schief gegangen

    if (preg_match('!(<mrk|/mrk>)!', $seg)) {
        trigger_error("Problem [preg_match('!(<mrk|/mrk>)!'] beim Entfernen von SDL-Markup aufgetreten (" . __FUNCTION__ . ")\nORIG:\n{$segOrig}\n\nNEU:\n{$seg}", E_USER_NOTICE);
    }

    return $seg;
}

function lockedTags2sncLocked($seg, $lockedTransUnits)
{
    $debug = false;

    if (strpos($seg, 'lockTU_') == false) {
        return $seg;
    }

    $lockTagRegEx = '!<x id="locked[^"]+" xid="(.*?)"( )?/>!';

    if (!preg_match_all($lockTagRegEx, $seg, $m)) {
        trigger_error("strpos auf 'lockTU_', aber kein preg_match auf {$lockTagRegEx}... Da ist was faul, bitte prüfen (lassen). Überspringe Schritt.", E_USER_NOTICE);
    } else {
        foreach ($m[0] as $key => $tag) {
            $id = $m[1][$key];

            if (isset($lockedTransUnits[$id])) {
                $replace = "__SNC_LOCKED_{$id}__" . $lockedTransUnits[$id]['content'] . "__SNC_/LOCKED_{$id}__";
                $seg = preg_replace('!' . preg_quote($tag) . '!', $replace, $seg);
            } else {
                trigger_error("preg_match auf <x id=\"locked..., aber keine lockedTU mit id {$id}. Da ist was faul, bitte prüfen (lassen). Überspringe Schritt.\nSEG: {$seg}\n", E_USER_NOTICE);
            }
        }
    }

    return $seg;
}

function getRealTags($seg, $tagDefs)
{
    $debug = false;

    $tagPairs = [];
    $tagPairsAll = [];
    $tagPairPHs = [];
    $tagPHs = [];

    preg_match_all('!(<g id="[A-Za-z0-9\-\._]+"([^>])*?>|</g>|<x id="[A-Za-z0-9\-\._]+"([^>])*?( )?/>)!', $seg, $m);

    $tags = $m[0];

    // Platzhalter-Tags
    foreach ($tags as $currentKey => $currentTag) {
        if (preg_match('!<x id="([A-Za-z0-9\-\._]+)"([^>])*?/>!', $currentTag, $m)) {
            $tagID = $m[1];

            if (preg_match('!^locked!', $tagID)) {
                $realPhTag = SYMBOL_LOCKED_SEG . '<SNC-LOCKED-TAG>' . SYMBOL_LOCKED_SEG;
            } else {
                $realPhTag = $tagDefs['phTags'][$tagID];
            }

            $tagPHs[$realPhTag][] = $tagID;
            unset($tags[$currentKey]);
        }
    }

    $realTags['tagPHs'] = $tagPHs;

    $tags = array_values($tags);

    // leere Gruppen-Tags
    foreach ($tags as $currentKey => $currentTag) {
        if (preg_match('!<g id="([A-Za-z0-9\-\._]+)"([^>])*?/>!', $currentTag, $m)) {
            $tagID = $m[1];

            $realOpenTag = $tagDefs['bptTags'][$tagID];
            $realCloseTag = $tagDefs['eptTags'][$tagID];

            $fakeTagPair2 = $realOpenTag . '|' . $realCloseTag;

            $tagPairPHs[$fakeTagPair2][] = $tagID;

            unset($tags[$currentKey]);
        }
    }

    $realTags['tagPairPHs'] = $tagPairPHs;

    $tags = array_values($tags);

    // Gruppen-Tags
    foreach ($tags as $currentKey => $currentTag) {
        if ($currentKey == 0) {
            $previousTag = '';
        }

        for ($j = 1; $currentKey - $j >= 0; $j++) {
            if (isset($tags[$currentKey - $j])) {
                $previousTag = $tags[$currentKey - $j];
                $previousKey = $currentKey - $j;
                break;
            }
        }

        if ((preg_match('!^</g>$!', $currentTag, $m1)) && (preg_match("!^<g id=\"([A-Za-z0-9\-\._]+)\"([^>])*?>$!", $previousTag, $m))) {
            $tagID = $m[1];

            $realOpenTag = $tagDefs['bptTags'][$tagID] ?? '';
            $realCloseTag = $tagDefs['eptTags'][$tagID] ?? '';

            $tagPair2 = $realOpenTag . '|' . $realCloseTag;

            $tagPairs[$tagPair2][] = $tagID;
            $tagPairsAll[][$tagPair2] = $tagID;

            unset($tags[$currentKey]);
            unset($tags[$previousKey]);

            if (empty($tags)) {
                continue;
            }
        }
    }

    $realTags['tagPairs'] = $tagPairs;
    $realTags['tagPairsAll'] = $tagPairsAll;

    if (!empty($tags)) {
        //echo "nicht alle tags erwischt... $seg \n -> continue; \n";
        trigger_error("Nicht alle Tags erwischt... $seg Abbruch.\n", E_USER_ERROR);
    }

    //print_r($realTags);
    return $realTags;
}
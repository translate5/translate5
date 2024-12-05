<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * This script imports all fprms extracted to the bconf-data-folders additionally to the DB
 */

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FprmUpdaterTo147;

set_time_limit(0);

$NEW_SPECIAL_CHARS_JSON = '[
  {
    "unicode": "U+202F",
    "visualized": "NNBSP"
  },
  {
    "unicode": "U+200A",
    "visualized": "HSP"
  },
  {
    "unicode": "U+2009",
    "visualized": "THSP"
  },
  {
    "unicode": "U+2008",
    "visualized": "PSP"
  },
  {
    "unicode": "U+2007",
    "visualized": "FSP"
  },
  {
    "unicode": "U+2006",
    "visualized": "6/MSP"
  },
  {
    "unicode": "U+2005",
    "visualized": "4/MSP"
  },
  {
    "unicode": "U+2004",
    "visualized": "3/MSP"
  },
  {
    "unicode": "U+2001",
    "visualized": "MQSP"
  },
  {
    "unicode": "U+2028",
    "visualized": "LSEP"
  },
  {
    "unicode": "U+200B",
    "visualized": "ZWSP"
  },
  {
    "unicode": "U+200C",
    "visualized": "ZWNJ"
  },
  {
    "unicode": "U+00AD",
    "visualized": "SHY"
  },
  {
    "unicode": "U+2011",
    "visualized": "NBH"
  },
  {
    "unicode": "U+00AD",
    "visualized": "ZWNBSP"
  }
]';

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '014-TRANSLATE-4093-3647-3202-okapi-147.php';

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

if (! isset($config) || ! str_contains($config->runtimeOptions->plugins->Okapi->serverUsed, '147')) {
    throw new ZfExtended_Exception(
        __FILE__ . ': searching for Okapi 1.47 in config FAILED - stop migration script'
    );
}

$fprmUpdater = new FprmUpdaterTo147();
$bconf = new BconfEntity();

$pipelineProps = [
    'doNotSegmentIfHasTarget.b=false' => 'SegmentationStep',
    'writerOptions.includeNoTranslate.b=true' => 'ExtractionStep',
    'writerOptions.escapeGT.b=true' => 'ExtractionStep',
];

foreach ($bconf->loadAll() as $bconfData) {
    try {
        $bconf->load($bconfData['id']);
        $bconfDir = $bconf->getDataDirectory();
        $pipelineFile = $bconfDir . '/pipeline.pln';
        $pipelineChanged = false;
        $pipelineData = file_get_contents($pipelineFile);
        foreach ($pipelineProps as $propLine => $step) {
            if (str_contains($pipelineData, $propLine)) {
                continue;
            }
            [$propKey] = explode('=', $propLine);
            if (str_contains($pipelineData, $propKey)) {
                $pipelineData = preg_replace('/' . $propKey . '=\w*/', $propLine, $pipelineData);
            } else {
                $pipelineData = preg_replace('/\.' . $step . '">#v1[\r\n]+/', '\\0' . $propLine . "\n", $pipelineData);
            }
            $pipelineChanged = true;
        }
        if ($pipelineChanged) {
            file_put_contents($pipelineFile, $pipelineData);
        }

        $fprmUpdater->updateInDir($bconfDir, $bconf->getId(), $bconf->getName());

        $extensionMapping = $bconf->getExtensionMapping();
        $extensionMapping->rescanFilters();
        $bconf->repackIfOutdated(true);
    } catch (Exception $e) {
        $msg = 'ERROR rescanning filters for bconf ' . $bconf->getId() . ', "' . $bconf->getName(
        ) . '": ' . $e->getMessage();
        error_log($msg);
    }
}

$db = Zend_Db_Table::getDefaultAdapter();
$db->query(
    "DELETE FROM `Zf_configuration` WHERE `name` IN (" .
    "'runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName'," .
    "'runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName'" .
    ")"
);

$okapiInfo147 = 'No version below 1.47 can be used with t5 file-format-settings, only "bconf-in-zip" works with older versions';
$descr = $db->fetchOne('SELECT `description` FROM `Zf_configuration` WHERE `name` = "runtimeOptions.plugins.Okapi.serverUsed"');

if (! str_contains($descr, $okapiInfo147)) {
    $db->query(
        'UPDATE `Zf_configuration` SET `description` = :descr WHERE `name` = "runtimeOptions.plugins.Okapi.serverUsed"',
        [
            'descr' => trim($descr, '.') . '. ' . $okapiInfo147,
        ]
    );
}

$specialCharacters = json_decode($config->runtimeOptions->editor->segments->editorSpecialCharacters, true);
if (! isset($specialCharacters['all'])) {
    $specialCharacters['all'] = [];
}

$charsToAdd = [];
$newCharsList = json_decode($NEW_SPECIAL_CHARS_JSON, true);
foreach ($newCharsList as $newChar) {
    $charFound = false;
    foreach ($specialCharacters['all'] as $specialChar) {
        if ($newChar['unicode'] === $specialChar['unicode']) {
            $charFound = true;

            break;
        }
    }
    if (! $charFound) {
        $charsToAdd[] = $newChar;
    }
}
if (! empty($charsToAdd)) {
    $specialCharacters['all'] = array_merge($specialCharacters['all'], $charsToAdd);
    $specialCharactersJson = json_encode(
        $specialCharacters,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $db->query(
        'UPDATE `Zf_configuration` SET `value` = :value WHERE `name` = "runtimeOptions.editor.segments.editorSpecialCharacters"',
        [
            'value' => $specialCharactersJson,
        ]
    );
}

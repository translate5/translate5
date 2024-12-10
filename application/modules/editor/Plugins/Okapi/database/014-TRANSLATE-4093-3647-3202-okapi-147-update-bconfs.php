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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FprmUpdaterTo147;

set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '014-TRANSLATE-4093-3647-3202-okapi-147-update-bconfs.php';

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

<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

namespace Translate5\MaintenanceCli\Worker;

/**
 * Class defines, with worker can be part of what operation
 */
final class Operation
{
    public const OPERATIONS = [
        'taskImport',
        'taskExport',
        'taskOperation',
        'taskPackageExport',
        'taskPackageReimport',
        'languageResourceImport',
        'visualExchange',
        'UNKNOWN',
    ];

    /**
     * Finds the operations the worker supports or 'UNKNOWN'
     * @return string[]
     */
    public static function detectOperations(string $worker): array
    {
        return self::findOperation($worker);
    }

    /**
     * Checks, if the given worker can be part of the passed operation(s)
     */
    public static function workerValidForOperation(string $worker, array $operations): bool
    {
        if (in_array('UNKNOWN', $operations)) {
            return true;
        }

        $workerOperations = self::findOperation($worker);

        if (in_array('UNKNOWN', $workerOperations)) {
            return true;
        }

        $intersection = array_intersect($workerOperations, $operations);

        return (count($intersection) > 0);
    }

    public static function calculateWeight(array $operations): int
    {
        foreach (self::OPERATIONS as $index => $operation) {
            if ($operations[0] === $operation) {
                // deduce a bit to ensure, combined operations succeed singular ones
                if (count($operations) > 1) {
                    return 2 * $index;
                }

                return 2 * $index - 1;
            }
        }

        return 0;
    }

    /**
     * This defies, which worker may be part of which operation
     * This needs to be updated when workers are added, otherwise a proper tree can not be built
     */
    private static function findOperation(string $worker): array
    {
        return match ($worker) {
            'editor_Models_Import_Worker_FileTree',
            'editor_Models_Import_Worker_ReferenceFileTree',
            'editor_Models_Import_Worker_FinalStep',
            'editor_Models_Import_Worker_SetTaskToOpen', => ['taskImport'],
            'editor_Models_Export_Worker' => ['taskExport'],
            'MittagQI\Translate5\Task\Export\Package\Worker' => ['taskPackageExport'],
            'MittagQI\Translate5\Task\Reimport\Worker' => ['taskPackageReimport'],
            'editor_Models_Export_ExportedWorker',
            'editor_Models_Export_Exported_FiletranslationWorker',
            'editor_Models_Export_Exported_TransferWorker',
            'editor_Models_Export_Exported_ZipDefaultWorker',
            'MittagQI\Translate5\Task\Export\Exported\PackageWorker' => ['taskExport', 'taskPackageExport'],
            'editor_Task_Operation_StartingWorker',
            'editor_Task_Operation_FinishingWorker' => ['taskOperation'],
            'MittagQI\Translate5\Plugins\MatchAnalysis\PauseMatchAnalysisWorker',
            'MittagQI\Translate5\LanguageResource\Pretranslation\PausePivotWorker',
            'MittagQI\Translate5\LanguageResource\Pretranslation\BatchCleanupWorker',
            'editor_Segment_Quality_OperationWorker',
            'editor_Segment_Quality_OperationFinishingWorker',
            'editor_Plugins_MatchAnalysis_Worker',
            'editor_Plugins_ModelFront_Worker',
            'editor_Plugins_MatchAnalysis_BatchWorker' => ['taskImport', 'taskOperation'],
            'editor_Services_ImportWorker' => ['languageResourceImport'],
            'editor_Plugins_VisualReview_HtmlImportWorker',
            'editor_Plugins_VisualReview_PdfToHtmlWorker',
            'editor_Plugins_VisualReview_HeadlessBrowserHtmlWorker',
            'editor_Plugins_VisualReview_ImageOcrWorker',
            'editor_Plugins_VisualReview_VideoHtmlWorker',
            'editor_Plugins_VisualReview_XmlHtmlWorker',
            'editor_Plugins_VisualReview_XmlXsltToHtmlWorker',
            'editor_Plugins_VisualReview_SegmentationWorker',
            'editor_Plugins_VisualReview_ImageHtmlWorker' => ['taskImport', 'visualExchange'],
            'MittagQI\Translate5\Plugins\VisualReview\Worker\VisualExchangeStartingWorker',
            'MittagQI\Translate5\Plugins\VisualReview\Worker\VisualExchangeFinishingWorker' => ['visualExchange'],

            default => ['UNKNOWN'],
        };
    }
}

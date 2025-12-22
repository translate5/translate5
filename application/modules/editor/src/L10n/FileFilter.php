<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
declare(strict_types=1);

namespace MittagQI\Translate5\L10n;

use editor_Models_Task as Task;
use Exception;
use MittagQI\Translate5\File\Filter\FileFilterByConfigInterface;
use MittagQI\Translate5\File\Filter\FileFilterInterface;
use MittagQI\Translate5\File\Filter\FilterConfig;
use MittagQI\Translate5\File\Filter\FilterException;
use MittagQI\Translate5\File\Filter\Manager;
use MittagQI\Translate5\File\Filter\Type;
use MittagQI\ZfExtended\Localization;
use Zend_Registry;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * File Filter to for invoking Okapi post process files on export
 */
class FileFilter implements FileFilterByConfigInterface
{
    protected Manager $manager;

    protected FilterConfig $config;

    /**
     * @see FileFilterInterface::initFilter()
     */
    public function initFilter(Manager $manager, FilterConfig $config): void
    {
        $this->config = $config;
        $this->manager = $manager;
    }

    public function applyImportFilter(Task $task, int $fileId, string $filePath, ?string $parameters): string
    {
        throw new FilterException('E1720', 'L10n File Filter has no Import Filter!');
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function applyExportFilter(Task $task, int $fileId, string $filePath, ?string $parameters): string
    {
        $isJson = str_ends_with($filePath, '.json');
        if (! str_ends_with($filePath, Localization::FILE_EXTENSION_WITH_DOT) && ! $isJson) {
            return $filePath;
        }

        $source = $task->getSourceLanguage()->getRfc5646();
        $target = $task->getTargetLanguage()->getRfc5646();
        $resultFilePath = preg_replace('#([^a-zA-Z])' . $source . '([^a-zA-Z])#', '\1' . $target . '\2', $filePath);
        $resultFilePath = str_replace(
            [Localization::FILE_EXTENSION_WITH_DOT . '$', '.json$'],
            [Localization::FILE_EXTENSION_WITH_DOT, '.json'],
            $resultFilePath . '$'
        );

        if (! $isJson) {
            return $resultFilePath;
        }
        $worker = new ExportWorker();

        try {
            $worker->init($task->getTaskGuid(), [
                'fileId' => $fileId,
                'sourceLanguage' => $source,
                'targetLanguage' => $target,
                'inputFilePath' => $filePath,
                'outputFilePath' => $resultFilePath,
            ]);
            $logger = Zend_Registry::get('logger');
            $worker->queue($this->config->parentWorkerId ?? 0);
        } catch (Exception $e) {
            if (isset($logger)) {
                $logger->exception($e);
            }
        }

        return $filePath;
    }

    public static function getTypesForFile(string $filePath): array
    {
        return [Type::Export];
    }

    public static function getWeight(): int
    {
        return 9999;
    }
}

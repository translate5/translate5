<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Workflow;

use editor_Models_Converter_SegmentsToXliff2;
use editor_Models_Converter_SegmentsToXliffAbstract;
use editor_Models_Customer_Customer;
use editor_Models_Languages;
use editor_Models_Task;
use FilesystemIterator;
use League\Flysystem\FilesystemException;
use League\Flysystem\MountManager;
use MittagQI\Translate5\Tools\FlysystemFactory;
use RecursiveDirectoryIterator;
use ReflectionException;
use stdClass;
use Zend_Db_Statement_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Utils;
use ZfExtended_Worker_Abstract;
use ZipArchive;
use const NOW_ISO;

/**
 * Worker to archive a task as XLF2, saves it to the destination given in the config
 * and optionally removes the task (enabled by default)
 */
class ArchiveWorker extends ZfExtended_Worker_Abstract
{
    // Most file systems have filename length restriction ~255 chars; when exceeded, an attempt to create a file fails
    private const MAX_FILENAME_LENGTH = 200;

    private editor_Models_Task $task;

    /**
     * @throws ArchiveException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function work(): bool
    {
        $parameters = $this->workerModel->getParameters();
        if (! $this->validateParameters($parameters)) {
            return false;
        }

        $this->task = ZfExtended_Factory::get('editor_Models_Task');
        $this->task->loadByTaskGuid($this->taskGuid);
        $this->task->createMaterializedView();

        $taskJson = $this->exportTaskMetaData($parameters['exportToFolder']);
        $this->exportXlf2($parameters['exportToFolder']);

        //zip content
        $zipfile = $this->zipFile($parameters['exportToFolder']);

        //move to destination (flexible)!
        try {
            $filename = self::replaceTargetPathVariables($parameters['options']->targetPath, $taskJson, self::MAX_FILENAME_LENGTH);
            $targetFile = $this->backupZip($zipfile, $filename, $parameters['options']);

            //we have to set the taskGuid to null here,
            // otherwise the below task delete would delete the worker too, before the worker is finished...
            $this->workerModel->setTaskGuid(null);
            $this->workerModel->save();

            $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', [$this->task]);
            $dryRun = 'KEPT - DRYRUN; ';
            if (! $parameters['keepTasks']) {
                $remover->remove();
                $dryRun = '';
            }
            $this->log->info(
                'E1402',
                'Task successfully removed (' . $dryRun . 'with backup before). ID: {id} {name}',
                [
                    'id' => $taskJson->id,
                    'name' => $taskJson->taskName,
                    'targetPath' => $targetFile,
                    'guid' => $taskJson->taskGuid,
                ]
            );

            return true;
        } catch (FilesystemException $e) {
            //Task could not backuped there fore it also was not deleted.
            throw new ArchiveException('E1400', [
                'task' => $this->task,
            ], $e);
        }
    }

    /**
     * replaces the {NAME} placeholders in the configured target name with the same NAMEd values from task meta JSON
     */
    private static function replaceTargetPathVariables(string $targetPath, object $taskJson, int $maxLength = 0): string
    {
        $path = preg_replace_callback('#{([a-zA-Z0-9_-]+)}#', function ($matches) use ($taskJson) {
            $var = $matches[1];
            if ($var === 'time') {
                return NOW_ISO;
            }

            return trim(
                preg_replace(
                    '/[^a-zA-Z0-9_-]+/',
                    '-',
                    isset($taskJson->$var) ? trim($taskJson->$var) : $var
                ),
                '-'
            );
        }, $targetPath);

        if ($maxLength > 0 && strlen($path) > $maxLength) {
            $meta = pathinfo($path);
            $len = strlen($meta['extension']) + 1;
            $path = substr(substr($path, 0, -$len), 0, $maxLength - $len) . '.' . $meta['extension'];
        }

        return preg_replace('/-+/', '-', $path);
    }

    /**
     * Exports the task as XLIFF V2
     * @throws ReflectionException
     */
    protected function exportXlf2(string $path): void
    {
        $xliffConf = [
            editor_Models_Converter_SegmentsToXliffAbstract::CONFIG_ADD_TERMINOLOGY => true,
            editor_Models_Converter_SegmentsToXliffAbstract::CONFIG_INCLUDE_DIFF => true,
            editor_Models_Converter_SegmentsToXliff2::CONFIG_ADD_QM => true,
        ];
        /** @var editor_Models_Converter_SegmentsToXliff2 $xliffConverter */
        $xliffConverter = ZfExtended_Factory::get(
            editor_Models_Converter_SegmentsToXliff2::class,
            [$xliffConf, $this->task->getWorkflowStepName()]
        );

        $filename = $path . '/export-xlf2.xliff';
        file_put_contents($filename, $xliffConverter->export($this->task));
    }

    protected function validateParameters(array $parameters): bool
    {
        if (empty($parameters['exportToFolder'])
            || (! is_dir($parameters['exportToFolder'])
            || ! is_writable($parameters['exportToFolder']))) {
            $this->log->error('E0000', 'Export folder not found or not write able: ' . $parameters['exportToFolder']);

            return false;
        }
        if (empty($parameters['options']) || ! is_object($parameters['options'])) {
            $this->log->error('E0000', 'Given options are no valid configuration object!');

            return false;
        }
        if (empty($parameters['options']->targetPath)) {
            $this->log->error('E0000', 'No option "targetPath" given in ArchiveWorker!');

            return false;
        }

        return true;
    }

    /**
     * exports the tasks data object as json file
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ReflectionException
     */
    private function exportTaskMetaData(string $path): stdClass
    {
        $json = $this->task->getDataObject();

        /** @var editor_Models_Languages $language */
        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $language->load($this->task->getSourceLang());
        $json->sourceLangRfc5646 = $language->getRfc5646();
        $language->load($this->task->getTargetLang());
        $json->targetLangRfc5646 = $language->getRfc5646();

        /** @var editor_Models_Customer_Customer $customer */
        $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
        $customer->load((int) $this->task->getCustomerId());
        $json->customerName = $customer->getName();
        $json->customerNumber = $customer->getNumber();

        $filename = $path . '/task-metadata.json';
        file_put_contents($filename, json_encode($json));

        return $json;
    }

    /**
     * creates a zipfile with the data to be archived
     * @return string returns the zipfile
     * @throws ArchiveException
     */
    private function zipFile(string $exportToFolder): string
    {
        $zipFile = $exportToFolder . "foo.zip";

        $zip = new ZipArchive();
        $res = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($res !== true) {
            //Could not zip the export of the task!
            throw new ArchiveException('E1401', [
                'task' => $this->task,
            ]);
        }

        //ensure slashes
        $exportToFolder = str_replace(['/', '\\'], '/', realpath($exportToFolder));
        $this->addDir($zip, $exportToFolder);
        $zip->close();

        ZfExtended_Utils::recursiveDelete($exportToFolder);

        return $zipFile;
    }

    /**
     * Adds a directory recursively.
     * @param string $filename filepath to be added
     * @param string $localname local file name in ZIP
     */
    protected function addDir(ZipArchive $zip, string $filename, string $localname = ''): void
    {
        $iter = new RecursiveDirectoryIterator($filename, FilesystemIterator::SKIP_DOTS);

        if ($localname !== '') {
            $zip->addEmptyDir($localname);
            $localname = rtrim($localname, '/') . '/';
        }

        foreach ($iter as $fileinfo) {
            if (! $fileinfo->isFile() && ! $fileinfo->isDir()) {
                continue;
            }

            if ($fileinfo->isFile()) {
                $zip->addFile($fileinfo->getPathname(), $localname . $fileinfo->getFilename());
            } else {
                $this->addDir($zip, $fileinfo->getPathname(), $localname . $fileinfo->getFilename());
            }
        }
    }

    /**
     * Backups the given zipfile and returns the final target file path
     * @throws FilesystemException
     */
    private function backupZip(string $zipfile, string $targetPath, object $backupConfig): string
    {
        //if we copy to local filesystem, we just need one Filesystem instance:
        if ($backupConfig->filesystem === FlysystemFactory::TYPE_LOCAL) {
            $filesystem = FlysystemFactory::create($backupConfig->filesystem, $backupConfig);
            $filesystem->move($zipfile, $targetPath);

            return $targetPath;
        }

        //if we move to remote, we need the mount manager:
        $manager = new MountManager([
            'local' => FlysystemFactory::create(FlysystemFactory::TYPE_LOCAL, $backupConfig),
            'remote' => FlysystemFactory::create($backupConfig->filesystem, $backupConfig),
        ]);
        $zipfile = 'local:/' . $zipfile;
        $targetPath = 'remote:/' . $targetPath;
        $manager->move($zipfile, $targetPath);

        return $targetPath;
    }
}

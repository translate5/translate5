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

namespace MittagQI\Translate5\Plugins\Okapi\Worker;

use editor_Models_Export_FileParser_Xlf as XlfFileparser;
use editor_Models_Languages;
use editor_Models_Task_AbstractWorker;
use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\OkapiAdapter;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use SplFileInfo;
use Throwable;
use Zend_Registry;
use ZfExtended_Debug;
use ZfExtended_Factory;
use ZfExtended_Logger;

class OkapiExportWorker extends editor_Models_Task_AbstractWorker
{
    private string $okapiDataDir;

    private bool $hadEmptyTargets = false;

    private ZfExtended_Logger $logger;

    public function __construct()
    {
        parent::__construct();
        $this->doDebug = $this->doDebug || ZfExtended_Debug::hasLevel('plugin', 'OkapiWorkers');
    }

    protected function validateParameters(array $parameters): bool
    {
        return true;
    }

    public function onInit(array $parameters): bool
    {
        if (parent::onInit($parameters)) {
            $this->behaviour->setConfig([
                'isMaintenanceScheduled' => true,
            ]);

            return true;
        }

        return false;
    }

    /**
     * @throws OkapiException
     * @throws \ReflectionException
     * @throws \Zend_Exception
     * @throws \Zend_Http_Client_Exception
     * @throws \ZfExtended_BadGateway
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_NotFoundException
     * @throws \editor_Models_ConfigException
     */
    public function work(): bool
    {
        $this->logger = Zend_Registry::get('logger')->cloneMe('plugin.okapi.export');
        $this->okapiDataDir = OkapiWorkerHelper::getDataDir($this->task);
        $params = $this->workerModel->getParameters();

        $fileId = (int) $params['fileId'];
        $workFile = new SplFileInfo($params['file']);
        $manifestFile = new SplFileInfo($this->okapiDataDir . '/' . OkapiWorkerHelper::createManifestFileName($fileId));

        $taskConfig = $this->task->getConfig();
        $api = new OkapiAdapter($taskConfig);

        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $sourceLang = $language->loadLangRfc5646($this->task->getSourceLang());
        $targetLang = $language->loadLangRfc5646($this->task->getTargetLang());
        $result = false;

        try {
            $exportBconf = editor_Plugins_Okapi_Init::getExportBconfPath($this->task);
            if ($this->doDebug) {
                $message = 'Okapi Plug-In: File "{fileName}" (id: {fileId}) will be exported with Okapi "{okapi}"';
                $extra = [
                    'task' => $this->task,
                    'okapi' => $taskConfig->runtimeOptions->plugins->Okapi->serverUsed ?? 'not set',
                    'fileId' => $fileId,
                    'fileName' => $workFile->getBasename(),
                    'usedBconf' => basename($exportBconf),
                ];
                error_log(
                    "\n=====\n" .
                    ZfExtended_Logger::renderMessageExtra($message, $extra) . ', task: ' . $this->task->getId() .
                    ', used BCONF: ' . $extra['usedBconf'] . ', used server: ' . $extra['okapi']
                );
            }
            $api->createProject();
            $api->uploadOkapiConfig($exportBconf, false);
            $api->uploadInputFile('manifest.rkm', $manifestFile);
            $originalFile = $this->findOriginalFile($fileId);
            $api->uploadOriginalFile($originalFile, new SplFileInfo($this->okapiDataDir . '/' . $originalFile));

            $this->xliffExportPreValidation($workFile);

            //if a file with source in empty targets exists, take that for okapi reconvertion
            $workfile2 = new SplFileInfo($workFile . XlfFileparser::SOURCE_TO_EMPTY_TARGET_SUFFIX);
            if ($workfile2->isFile()) {
                $api->uploadWorkFile($originalFile . $api::OUTPUT_FILE_EXTENSION, $workfile2);
                if (ZfExtended_Debug::hasLevel('plugin', 'OkapiKeepIntermediateFiles')) {
                    $this->saveIntermediateWorkFile($workfile2, $fileId);
                }
                unlink($workfile2);
                //workfile (.xlf) is kept in export for further processing of the XLF
            } else {
                $api->uploadWorkFile($originalFile . $api::OUTPUT_FILE_EXTENSION, $workFile);
                if (ZfExtended_Debug::hasLevel('plugin', 'OkapiKeepIntermediateFiles')) {
                    $this->saveIntermediateWorkFile($workFile, $fileId);
                }
            }
            $api->executeTask($sourceLang, $targetLang);

            //the exported work file (containing xlf) must be renamed so that
            // the merged file can be saved under the original file name
            if ($taskConfig->runtimeOptions->plugins->Okapi->preserveGeneratedXlfFiles) {
                rename($workFile, $workFile . $api::OUTPUT_FILE_EXTENSION);
            }
            $api->downloadMergedFile($originalFile, $workFile);
            //TRANSLATE-2002: Currently Okapi can not reconvert PDF files,
            // therefore it provides a txt file, so we have to rename the file though
            if (strtolower($workFile->getExtension()) === 'pdf'
                && mime_content_type((string) $workFile) == 'text/plain') {
                rename($workFile, $workFile . '.txt');
            } else {
                XmlEntitiesPatcher::patchAfterExport($workFile);
            }

            $result = true;
        } catch (Throwable $e) {
            $message = 'Okapi Plug-In: Error in converting file "{file}" on export: {message}';
            $extra = [
                'task' => $this->task,
                'message' => $e->getMessage(),
                'fileId' => $fileId,
                'file' => basename($workFile->__toString()),
            ];
            $this->logger->error('E1151', $message, $extra);

            if ($this->doDebug) {
                error_log(
                    "\n=====\n" .
                    ZfExtended_Logger::renderMessageExtra($message, $extra) .
                    ', task: ' . $this->task->getId() . ', fileId: ' . $fileId
                );
            }
            $event = $this->logger->exception($e, [
                'extra' => [
                    'task' => $this->task,
                ],
                'level' => ZfExtended_Logger::LEVEL_DEBUG,
            ]);

            $this->logEmptyTargets($fileId, $workFile);

            if (file_exists($workFile)) {
                //we add the XLF file suffix, since the workfile is now still a XLF file.
                rename($workFile, $workFile . $api::OUTPUT_FILE_EXTENSION);
            }
            // add an export-error file to notify users
            file_put_contents(
                dirname($workFile) . '/export-error.txt',
                'The file "' . basename($workFile) . '" could not be exported due to errors in Okapi:' .
                "\n " . (is_null($event) ? $e->getMessage() : $event->oneLine()),
                FILE_APPEND
            );
        } finally {
            $api->removeProject();
        }

        return $result;
    }

    /**
     * Does some validation of the XLIFF file to improve debugging
     */
    protected function xliffExportPreValidation(SplFileInfo $workFile): void
    {
        $content = file_get_contents($workFile);
        $this->hadEmptyTargets = (bool) preg_match('#<target[^>]*/>#', $content);
    }

    /**
     * returns the original file for a stored file (stored in the okapi data dir)
     * @throws OkapiException
     */
    protected function findOriginalFile(int $fileId): string
    {
        $regex = '/' . OkapiWorkerHelper::createOriginalFileName($fileId . '\\', '.*$/');
        $files = preg_grep($regex, scandir($this->okapiDataDir));
        $file = reset($files);
        if ($file === false) {
            throw new OkapiException('E1058', [
                'message' => 'Original file could not be found',
                'file' => OkapiWorkerHelper::createOriginalFileName($fileId, '*'),
                'task' => $this->task,
            ]);
        }
        if ($this->doDebug) {
            error_log(
                "\n=====\n" .
                'Okapi Plug-In: Original file could not be found, task: ' . $this->task->getId() .
                ', file: ' . OkapiWorkerHelper::createOriginalFileName($fileId, '*')
            );
        }

        return (string) $file;
    }

    /**
     * The okapi export worker takes approximately 20% of the export time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int
    {
        return 20;
    }

    private function logEmptyTargets(mixed $fileId, SplFileInfo $workFile): void
    {
        if ($this->hadEmptyTargets) {
            $message = 'Okapi Plug-In: The exported XLIFF "{file}" contains empty targets,' .
                ' the Okapi process will probably fail then.';
            $extra = [
                'task' => $this->task,
                'fileId' => $fileId,
                'file' => basename($workFile),
            ];
            $this->logger->warn('E1150', $message, $extra);
            if ($this->doDebug) {
                error_log(
                    "\n=====\n" .
                    ZfExtended_Logger::renderMessageExtra($message, $extra) .
                    ', task: ' . $this->task->getId() . ', fileId: ' . $fileId
                );
            }
        }
    }

    /**
     * Saves the workfile for further investigtion when debugging
     */
    private function saveIntermediateWorkFile(string $workFilePath, int $fileId): void
    {
        $targetFileName = 'workfile-' . $fileId . '.' . pathinfo($workFilePath, PATHINFO_EXTENSION);
        copy($workFilePath, $this->okapiDataDir . '/' . $targetFileName);
    }
}

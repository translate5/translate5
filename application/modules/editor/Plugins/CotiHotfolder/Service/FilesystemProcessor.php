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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\CotiHotfolder\Service;

use DateTime;
use editor_Models_Customer_Customer as Customer;
use editor_Models_Import_Configuration;
use editor_Models_Import_DataProvider_Abstract;
use editor_Models_Import_DataProvider_Directory;
use editor_Models_Task;
use editor_Models_TaskConfig;
use editor_Models_TaskUserAssoc;
use editor_Plugins_Okapi_Init;
use editor_Task_Type_ProjectTask;
use MittagQI\Translate5\Plugins\CotiHotfolder\CotiHotfolder;
use MittagQI\Translate5\Plugins\CotiHotfolder\DTO\InstructionsDTO;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\FileNotFoundException;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\HotfolderExceptionInterface;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\InvalidInstructionsXmlFileException;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\UnableToCreateFileException;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\UnableToMoveFileToProcessingException;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\UnableToOpenZipArchiveException;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\UnableToReadInstructionsFileException;
use MittagQI\Translate5\Plugins\CotiHotfolder\Factory\ProjectFactory;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem\FilesystemFactory;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem\FilesystemService;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\Exception\ProjectAbortException;
use MittagQI\Translate5\Task\Import\ImportService;
use MittagQI\Translate5\Task\TaskService;
use ReflectionException;
use Throwable;
use Zend_Db_Statement_Exception;
use Zend_Db_Table;
use Zend_Exception;
use Zend_Filter_Compress_Zip;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_User as User;
use ZfExtended_Utils;
use ZipArchive;

class FilesystemProcessor
{
    public function __construct(
        private readonly ImportService $importService,
        private readonly CustomerRepository $customerRepository,
        private readonly T5Logger $logger,
        private readonly SendMail $mailer,
        private readonly ProjectFactory $projectFactory,
        private readonly ProjectManagerProvider $projectManagerProvider,
        private readonly TaskService $taskService,
        private readonly CotiLogger $cotiLogger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new ImportService(),
            new CustomerRepository(),
            T5Logger::create(),
            SendMail::create(),
            ProjectFactory::create(),
            ProjectManagerProvider::create(),
            new TaskService(),
            new CotiLogger(),
        );
    }

    public function process(FilesystemService $filesystem, string $key): void
    {
        if (! $filesystem->validDir("{$key}://")) {
            return;
        }

        $processingFiles = [];
        $fallbackPm = $this->projectManagerProvider->getFallbackPm($key);
        $filesystemCustomer = $this->getCustomerByKey($key);

        foreach ($filesystem->getReadyCotiFilesList("{$key}://") as $cotiFile) {
            try {
                $processingFiles[] = $filesystem->moveReadyToProcessing($cotiFile);
            } catch (UnableToMoveFileToProcessingException) {
                $cotiFileName = basename($cotiFile);
                $this->mailer->sendErrorsToPm(
                    $fallbackPm,
                    $filesystemCustomer->getNumber(),
                    $cotiFileName,
                    ["Unable to move file to processing: {$cotiFileName}"]
                );
            }
        }

        $db = Zend_Db_Table::getDefaultAdapter();

        foreach ($processingFiles as $cotiFile) {
            try {
                $tempDirPath = self::makeTempDirectory();
                // download and unpack
                $cotiFileName = basename($cotiFile);
                $localFile = $tempDirPath . DIRECTORY_SEPARATOR . $cotiFileName;
                $filesystem->downloadCotiFile($cotiFile, 'local://' . ltrim($localFile, DIRECTORY_SEPARATOR));

                $this->cotiLogger->setLogTarget($localFile);
                $this->cotiLogger->log(CotiLogEntry::PackageMoved, '', __FILE__);

                self::extractZip($tempDirPath, $cotiFileName);
                unlink($localFile);

                $instructionsList = $this->getGroupedCotiProjects($tempDirPath);
                if (empty($instructionsList)) {
                    throw new UnableToReadInstructionsFileException($cotiFileName);
                }

                $this->cotiLogger->log(CotiLogEntry::PackageRead, '', __FILE__);
            } catch (HotfolderExceptionInterface $e) {
                $this->logger->errorsInCotiPackage($cotiFileName, $e->getErrors());

                $this->mailer->sendErrorsToPm(
                    $fallbackPm,
                    $filesystemCustomer->getNumber(),
                    $cotiFileName,
                    $e->getErrors()
                );

                $filesystem->moveToFailedDir($cotiFile);

                if ($e instanceof FileNotFoundException) {
                    if (str_contains($e->path, 'reference files')) {
                        $this->cotiLogger->log(CotiLogEntry::PackageFilesMissing, $e->path, __FILE__);
                    } else {
                        $this->cotiLogger->log(CotiLogEntry::DocumentNotFound, $e->path, __FILE__);
                    }
                } else {
                    $this->cotiLogger->log(CotiLogEntry::PackageInvalid, '', __FILE__);
                }

                $this->uploadCotiLog($filesystem, $cotiFile);
                ZfExtended_Utils::recursiveDelete($tempDirPath);

                continue;
            }

            $cotiUploadId = 0;
            $archive = false;
            foreach ($instructionsList as $cotiDir => $instructions) {
                $cotiTempDir = $tempDirPath . DIRECTORY_SEPARATOR . $cotiDir;
                if ($instructions->archive) {
                    $archive = true;
                }
                $this->cotiLogger->setLogLevel($instructions->logLevel);

                /*$customer = $this->fetchCustomer($instructions) ?: $filesystemCustomer;

                if (FilesystemFactory::DEFAULT_HOST_LABEL !== $key && $customer->getId() !== $filesystemCustomer->getId()) {
                    $this->logger->customerNotEqual(
                        $instructions->project->name,
                        $customer->getName(),
                        $filesystemCustomer->getName()
                    );

                    $customer = $filesystemCustomer;
                }*/
                $customer = $filesystemCustomer;
                $foreignId = CotiHotfolder::PLUGIN_PREFIX . str_replace(FilesystemService::IMPORT_FILE_EXT, DIRECTORY_SEPARATOR . $cotiDir, $cotiFile);

                try {
                    $projectId = $this->processDir($cotiTempDir, $customer, $instructions, $foreignId);

                    if (count($instructions->targetLang) > 1) {
                        // for 2+ tasks adjust foreignId to match imported project dir
                        $lang2CotiDir = array_flip($instructions->targetLang);
                        foreach ($this->taskService->getProjectTaskList($projectId) as $task) {
                            $taskCotiDir = $lang2CotiDir[$task->getTargetLang()];
                            if ($taskCotiDir !== $cotiDir) {
                                $task->setForeignId(preg_replace('/' . preg_quote($cotiDir) . '$/', $taskCotiDir, $foreignId));
                                $task->save();
                            }
                        }
                    }

                    if (count($instructionsList) > 1) {
                        // for 2+ projects add upload_assoc entries into DB
                        if ($cotiUploadId === 0) {
                            $db->query('INSERT INTO LEK_coti_upload SET uploaded=NOW(),pkg_name=?', $cotiFileName);
                            $cotiUploadId = $db->lastInsertId();
                        }
                        $db->query('INSERT INTO LEK_coti_project_upload_assoc SET project_id=' . $projectId . ',upload_id=' . $cotiUploadId);
                    }
                } catch (Throwable $e) {
                    $this->logger->exception($e);
                    $filesystem->moveToFailedDir($cotiFile);

                    $this->cotiLogger->log(CotiLogEntry::PackageInvalid, '', __FILE__);
                    $this->uploadCotiLog($filesystem, $cotiFile);
                    ZfExtended_Utils::recursiveDelete($tempDirPath);

                    if ($cotiUploadId > 0) {
                        $db->query('DELETE FROM LEK_coti_upload WHERE id=' . $cotiUploadId);
                    }

                    continue 2;
                }
            }

            $this->uploadCotiLog($filesystem, $cotiFile);
            ZfExtended_Utils::recursiveDelete($tempDirPath);

            // keep coti file if at least one COTI.xml has an archive attribute set to true
            $filesystem->moveToSuccessfulDir($cotiFile, $archive);
        }
    }

    private function uploadCotiLog(FilesystemService $filesystem, string $cotiFile): void
    {
        $filesystem->uploadCotiLog(
            'local://' . ltrim($this->cotiLogger->getLogFilePath(), DIRECTORY_SEPARATOR),
            $cotiFile
        );
    }

    private static function getSubDirs(string $dir): array
    {
        $dh = opendir($dir);
        $subDirs = [];
        while (false !== ($entry = readdir($dh))) {
            if (! $entry || $entry[0] == '.' || ! is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
                continue;
            }
            $subDirs[] = $entry;
        }
        closedir($dh);

        return $subDirs;
    }

    /**
     * @throws ProjectAbortException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function processDir(
        string $processingDir,
        Customer $customer,
        InstructionsDTO $instructions,
        string $foreignId,
    ): int {
        $project = $this->projectFactory->createProject($instructions, $customer, $foreignId);
        //$bconfId = $this->fetchBconfId($project, $instructions->project->bconf);

        $data = [
            'pmGuid' => $project->getPmGuid(),
            'pmName' => $project->getPmName(),
            'targetLang' => array_values($instructions->targetLang),
            'customerId' => $project->getCustomerId(),
            //'bconfId' => $bconfId,
        ];

        $this->importService->prepareTaskType(
            $project,
            count($data['targetLang']) > 1,
            editor_Task_Type_ProjectTask::ID
        );

        $project->initTaskDataDirectory();

        $tempDirPath = $project->getAbsoluteTaskDataPath()
            . DIRECTORY_SEPARATOR
            . editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT;
        mkdir($tempDirPath);

        try {
            $this->moveProjectFiles($instructions, $processingDir, $tempDirPath);

            $directoryProvider = new editor_Models_Import_DataProvider_Directory($tempDirPath);

            $this->importService->importProject($project, $directoryProvider, $data, User::loadSystemUser());
            if (null !== $instructions->project->deadline) {
                $this->setDeadlineByProject($project, $instructions->project->deadline);
            }
        } catch (Throwable $e) {
            $this->mailer->sendErrorsToPm(
                $this->projectManagerProvider->getByGuid($project->getPmGuid()),
                $customer->getNumber(),
                basename($processingDir) . FilesystemService::IMPORT_FILE_EXT,
                [sprintf('Import of project "%s" aborted. Reason: %s', $project->getTaskName(), $e->getMessage())]
            );

            $project->setErroneous();
            foreach (TaskRepository::create()->getProjectTaskList((int) $project->getId()) as $task) {
                $task->setErroneous();
            }

            throw new ProjectAbortException($project, previous: $e);
        }

        $projectConfig = ZfExtended_Factory::get(editor_Models_TaskConfig::class);
        $projectConfig->updateInsertConfig(
            $project->getTaskGuid(),
            editor_Plugins_Okapi_Init::CONFIG_PRESERVE_XLF_FILES,
            false
        );

        $this->importService->startWorkers($project);

        return (int) $project->getId();
    }

    private function moveProjectFiles(
        InstructionsDTO $instructions,
        string $projectDir,
        string $tempDirPath,
    ): void {
        $tempDirPath .= DIRECTORY_SEPARATOR;
        $workFilesDir = $tempDirPath . editor_Models_Import_Configuration::WORK_FILES_DIRECTORY;
        $referenceFilesDir = $tempDirPath . editor_Models_Import_Configuration::REFERENCE_FILES_DIRECTORY;
        $visualFilesDir = $tempDirPath . editor_Models_Import_Configuration::VISUAL_FILES_DIRECTORY;

        rename(
            $projectDir . DIRECTORY_SEPARATOR . FilesystemService::INSTRUCTION_FILE_NAME,
            $tempDirPath . '..' . DIRECTORY_SEPARATOR . FilesystemService::INSTRUCTION_FILE_NAME
        );

        foreach ($instructions->projectFiles->files as $file) {
            // File path may consist windows separator, so we have to replace
            $filePath = str_replace('\\', DIRECTORY_SEPARATOR, $file);
            $currentPath = $projectDir . DIRECTORY_SEPARATOR . $filePath;
            $targetFilePath = $workFilesDir . DIRECTORY_SEPARATOR . $filePath;

            if (! is_file($currentPath)) {
                // should never happen as we check all files early with verifyAllFilesExist()
                $this->logger->fileNotFound($file);

                continue;
            }
            @mkdir(dirname($targetFilePath), 0777, true);
            rename($currentPath, $targetFilePath);
        }

        foreach ($instructions->projectFiles->referenceFiles as $file) {
            // File path may consist windows separator, so we have to replace
            $filePath = str_replace('\\', DIRECTORY_SEPARATOR, $file);
            $currentPath = $projectDir . DIRECTORY_SEPARATOR . $filePath;

            if (! is_file($currentPath)) {
                // should never happen as we check all files early with verifyAllFilesExist()
                $this->logger->fileNotFound($file);

                continue;
            }

            $targetFilePath = $visualFilesDir . DIRECTORY_SEPARATOR . $filePath;
            @mkdir(dirname($targetFilePath), 0777, true);
            if (str_contains($file, '.pdf')) {
                rename($currentPath, $targetFilePath);

                continue;
            }

            copy($currentPath, $targetFilePath);

            $targetFilePath = $referenceFilesDir . DIRECTORY_SEPARATOR . $filePath;
            @mkdir(dirname($targetFilePath), 0777, true);
            rename($currentPath, $targetFilePath);
        }
    }

    private function setDeadlineByProject(editor_Models_Task $project, DateTime $deadline): void
    {
        $db = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class)->db;
        $s = $db->select()
            ->setIntegrityCheck(false)
            ->from([
                'assoc' => 'LEK_taskUserAssoc',
            ], ['assoc.id'])
            ->join([
                'task' => 'LEK_task',
            ], 'assoc.taskGuid = task.taskGuid', '')
            ->where('task.projectId = ?', $project->getId())
            ->where('task.id != ?', $project->getId());
        $ids = array_column($db->fetchAll($s)->toArray(), 'id');

        if (empty($ids)) {
            return;
        }

        $db->update(
            [
                'deadlineDate' => $deadline->format('Y-m-d H:i:s'),
            ],
            ['id IN (' . implode(',', $ids) . ')']
        );
    }

    /**
     * @throws InvalidInstructionsXmlFileException
     * @throws UnableToReadInstructionsFileException
     */
    private function getInstructions(string $xmlFile, string $cotiProjectDir): InstructionsDTO
    {
        $relInstructionFilePath = $cotiProjectDir . '/' . FilesystemService::INSTRUCTION_FILE_NAME;
        if (! is_file($xmlFile)) {
            $this->logger->fileNotFound($xmlFile);
            $this->cotiLogger->log(CotiLogEntry::ProjectNotFound, $relInstructionFilePath, __FILE__);

            throw new UnableToReadInstructionsFileException($xmlFile);
        }

        if (($instructions = simplexml_load_file($xmlFile)) === false) {
            $this->cotiLogger->log(CotiLogEntry::CotiFileInvalid, $relInstructionFilePath, __FILE__);

            throw new InvalidInstructionsXmlFileException(['Invalid XML file']);
        }

        return new InstructionsDTO($instructions, basename(dirname($xmlFile)), $this->logger, $this->cotiLogger);
    }

    private function getCustomerByKey(string $filesystemKey): Customer
    {
        if (FilesystemFactory::DEFAULT_HOST_LABEL === $filesystemKey) {
            return $this->customerRepository->getDefaultCustomer();
        }

        return $this->customerRepository->get((int) base64_decode($filesystemKey));
    }

    /**
     * @throws FileNotFoundException
     * @throws UnableToReadInstructionsFileException
     * @throws InvalidInstructionsXmlFileException
     */
    private function getGroupedCotiProjects(string $tempDirPath): array
    {
        $cotiDirs = self::getSubDirs($tempDirPath);
        if (empty($cotiDirs)) {
            return [];
        }

        // Assumption: COTI projects with the same source language and different target languages,
        // having config file of the same size + the same translation and referenced files paths,
        // can be considered similar tasks in T5 multi-target project.
        // Should we check according to naming convention as well ?
        $instructionsList = $cotiLangMap = $cotiConfigMap = [];
        foreach ($cotiDirs as $cotiDir) {
            $cotiTempDir = $tempDirPath . DIRECTORY_SEPARATOR . $cotiDir;
            $instructionsFile = $cotiTempDir . DIRECTORY_SEPARATOR . FilesystemService::INSTRUCTION_FILE_NAME;
            $instructions = $this->getInstructions($instructionsFile, $cotiDir);
            settype($cotiLangMap[$instructions->sourceLang], 'array');
            $sign = $cotiConfigMap[$cotiDir] = (string) filesize($instructionsFile) . $instructions->projectFiles->getHash();
            settype($cotiLangMap[$instructions->sourceLang][$sign], 'array');
            $cotiLangMap[$instructions->sourceLang][$sign][$cotiDir] = $instructions->targetLang[0];
            $instructionsList[$cotiDir] = $instructions;
        }

        // Filter out duplicate dirs which have different target languages by config file size
        foreach ($instructionsList as $cotiDir => $instructions) {
            $sign = $cotiConfigMap[$cotiDir];
            if (! isset($cotiLangMap[$instructions->sourceLang][$sign])) {
                // skip as part of multi-target project
                unset($instructionsList[$cotiDir]);

                continue;
            } elseif (count($cotiLangMap[$instructions->sourceLang][$sign]) > 1) {
                // multi-target project
                $instructionsList[$cotiDir]->targetLang = $cotiLangMap[$instructions->sourceLang][$sign];
                unset($cotiLangMap[$instructions->sourceLang][$sign]);
            }
            // verify only for projects to be imported
            $instructions->projectFiles->verifyAllFilesExist($tempDirPath . DIRECTORY_SEPARATOR . $cotiDir);
        }

        return $instructionsList;
    }

    /*private function fetchCustomer(InstructionsDTO $instructions): ?Customer
    {
        if (null === $instructions->project->customer) {
            return null;
        }

        $projectDto = $instructions->project;

        try {
            return $this->customerRepository->getByNumber($projectDto->customer);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $this->logger->customerNotFound($projectDto->name, $projectDto->customer);

            return null;
        }
    }*/

    /*private function fetchBconfId(editor_Models_Task $project, ?string $bconfName): ?int
    {
        if (null === $bconfName) {
            return null;
        }

        try {
            $bconf = new BconfEntity();
            $bconf->loadRow('name = ? ', $bconfName);

            return (int) $bconf->getId();
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $this->logger->bconfNotFound($project->getTaskName(), $bconfName);

            return null;
        }
    }*/

    private static function extractZip(string $zipDir, string $zipFileName): void
    {
        $zip = new ZipArchive();
        $zipOpened = $zip->open($zipDir . DIRECTORY_SEPARATOR . $zipFileName);
        if ($zipOpened === true) {
            $zip->extractTo($zipDir);
            $zip->close();

            return;
        }

        $errorHelper = new class() extends Zend_Filter_Compress_Zip {
            public function _errorString($error): string
            {
                return parent::_errorString($error) . ' (' . $error . ')';
            }
        };

        /** @phpstan-ignore-next-line */
        throw new UnableToOpenZipArchiveException($zipFileName, $errorHelper->_errorString($zipOpened));
    }

    private static function makeTempDirectory(): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), '');
        if (! $tempPath || ! file_exists($tempPath)) {
            throw new UnableToCreateFileException($tempPath);
        }
        unlink($tempPath);
        mkdir($tempPath);

        return $tempPath;
    }
}

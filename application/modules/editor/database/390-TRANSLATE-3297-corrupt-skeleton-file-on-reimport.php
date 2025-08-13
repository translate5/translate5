<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2023 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/***
 * Update all corrupt task skeleton files from the task import archive.
 */

use MittagQI\Translate5\Plugins\Okapi\Worker\OkapiWorkerHelper;
use MittagQI\Translate5\Task\Reimport\Exception;

set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '390-TRANSLATE-3297-corrupt-skeleton-file-on-reimport.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

class ReimportOverwrite extends editor_Models_Import_SegmentProcessor
{
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $segmentTagger;

    /***
     * @var array
     */
    private array $segmentErrors = [];

    /***
     * Collection of segments which are updated with the reimport (target, source or both)
     * @var array
     */
    private array $updatedSegments = [];

    /***
     * Segment timestamp
     * @var string
     */
    private string $saveTimestamp;

    public function __construct(
        editor_Models_Task $task,
        private editor_Models_SegmentFieldManager $sfm,
        private ZfExtended_Models_User $user
    ) {
        parent::__construct($task);
        $this->segmentTagger = ZfExtended_Factory::get(editor_Models_Segment_InternalTag::class);
    }

    /**
     * Verarbeitet ein einzelnes Segment und gibt die ermittelte SegmentId zurück
     * @return int|false MUST return the segmentId or false
     * @throws Exception
     */
    public function process(editor_Models_Import_FileParser $parser): bool|int
    {
        $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
        $segment->init([
            'taskGuid' => $this->taskGuid,
        ]);

        $mid = $parser->getMid();

        try {
            $segment->loadByFileidMid($this->fileId, $mid);

            return (int) $segment->getId();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            return false;
        }
    }

    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser)
    {
        $file = ZfExtended_Factory::get(editor_Models_File::class);
        $file->load($this->fileId);

        $content = $parser->getSkeletonFile();

        error_log("Write to file" . $file->getId() . '[' . date('m/d/Y h:i:s a', time()));

        if (isCorruptSkeletonFileForFix($content)) {
            error_log("Corrupted skeleton file on postParseHandler for: " . $file->getId() . '[' . date('m/d/Y h:i:s a', time()));

            return;
        }

        if (empty($content)) {
            error_log("No content found for parsed file:" . $file->getId());
        } else {
            if (method_exists($file, 'saveSkeletonToDisk')) {
                $file->saveSkeletonToDisk($content, $this->task);
            } else {
                $skeletonFile = new \MittagQI\Translate5\Task\Import\SkeletonFile($this->task);
                $skeletonFile->saveToDisk($file, $content);
            }
        }
    }

    /***
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId)
    {
    }

    /**
     * get all updated segments in the task
     */
    public function getUpdatedSegments(): array
    {
        return $this->updatedSegments;
    }

    /**
     * get all segment errors
     */
    public function getSegmentErrors(): array
    {
        return $this->segmentErrors;
    }

    public function setSaveTimestamp(string $saveTimestamp): void
    {
        $this->saveTimestamp = $saveTimestamp;
    }
}

$db = Zend_Db_Table::getDefaultAdapter();
$res = $db->query("SELECT distinct(taskGuid) FROM LEK_task_log where eventCode = 'E1440'");
$tasks = $res->fetchAll(Zend_Db::FETCH_COLUMN);

if (empty($tasks)) {
    return;
}

$corruptedFiles = [];

foreach ($tasks as $task) {
    $model = ZfExtended_Factory::get(editor_Models_Task::class);
    $model->loadByTaskGuid($task);

    $file = ZfExtended_Factory::get(editor_Models_File::class);
    $allTaskFiles = $file->loadByTaskGuid($model->getTaskGuid());

    foreach ($allTaskFiles as $taskFile) {
        $file = ZfExtended_Factory::get(editor_Models_File::class);
        $file->load($taskFile['id']);

        if (method_exists($file, 'loadSkeletonFromDisk')) {
            $skeleton = $file->loadSkeletonFromDisk($model);
        } else {
            $skeletonFile = new \MittagQI\Translate5\Task\Import\SkeletonFile($model);
            $skeleton = $skeletonFile->loadFromDisk($file);
        }

        // check if the skeleton file contains corrupted empty
        if (isCorruptSkeletonFileForFix($skeleton)) {
            error_log("Corrupted skeleton found for task:" . $model->getTaskGuid() . ', and file:' . $file->getFileName() . ', fileId:' . $file->getId());
            $corruptedFiles[] = $file;
        }
    }
}

if (empty($corruptedFiles)) {
    return;
}

foreach ($corruptedFiles as $file) {
    fixFileCorruptedSkeletonFile($file);
}

foreach ($tasks as $task) {
    $model = ZfExtended_Factory::get(editor_Models_Task::class);
    $model->loadByTaskGuid($task);
    cleanUpTempReimportFixFolder($model);
}

/***
 * @param editor_Models_File $file
 * @return void
 */
function fixFileCorruptedSkeletonFile(editor_Models_File $file): void
{
    try {
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($file->getTaskGuid());

        $fullFile = getFileCorruptedSkeletonFile($task, $file);

        if (empty($fullFile)) {
            throw new \Exception('No file was found on the disk for file:' . $file->getId());
        }

        $sfm = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);
        $sfm->initFields($task->getTaskGuid());

        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->loadByGuid(ZfExtended_Models_User::SYSTEM_GUID);

        $processor = ZfExtended_Factory::get(ReimportOverwrite::class, [$task, $sfm, $user]);

        if (! class_exists($file->getFileParser())) {
            error_log("No parser was found for the file:" . $file->getId() . ' | ' . $file->getFileParser());
        }

        $parser = ZfExtended_Factory::get($file->getFileParser(), [
            $fullFile,
            $file->getFileName(),
            $file->getId(),
            $task,
        ]);

        $parser->setSegmentFieldManager($sfm);
        $processor->setSegmentFile((int) $file->getId(), $parser->getFileName());
        $parser->setIsReimport(true);
        $parser->addSegmentProcessor($processor);
        $parser->parseFile();

        error_log("Parsed file" . $file->getId() . '[' . date('m/d/Y h:i:s a', time()));

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query('INSERT INTO LEK_task_migration (`taskGuid`, `filename`) VALUES (?,?)', [$task->getTaskGuid(), '390-TRANSLATE-3297-corrupt-skeleton-file-on-reimport.php']);
    } catch (Throwable $e) {
        error_log("Error on fixing the task" . $file->getTaskGuid() . ' .The error was:' . $e->getMessage());
        error_log($e->getTraceAsString());
        cleanUpTempReimportFixFolder($task);
    }
}

/***
 * Get the which will be parsed and used as skeleton source.
 * @param editor_Models_Task $task
 * @param editor_Models_File $file
 * @return string
 * @throws JsonException
 * @throws Zend_Exception
 * @throws ZfExtended_Models_Entity_NotFoundException
 * @throws Exception
 */
function getFileCorruptedSkeletonFile(editor_Models_Task $task, editor_Models_File $file)
{
    // in case it is okapi
    $okapiPath = $task->getAbsoluteTaskDataPath() . '/' . OkapiWorkerHelper::OKAPI_REL_DATA_DIR . '/';

    if (is_dir($okapiPath)) {
        // original file extension.
        $ext = ZfExtended_Utils::getFileExtension($file->getFileName());

        $fileName = OkapiWorkerHelper::createOriginalFileName($file->getId(), $ext);

        // get the okapi xlf version of this file
        $fileName .= '.xlf';

        if (is_file($okapiPath . $fileName)) {
            return $okapiPath . $fileName;
        }
    }

    $dir = getReplacementFromImportArchive($task);

    $config = Zend_Registry::get('config');
    $rootDir = $config->runtimeOptions->import->proofReadDirectory;

    if (! is_dir($dir . $rootDir)) {
        $rootDir = 'proofRead';
        if (! is_dir($dir . $rootDir)) {
            $rootDir = editor_Models_Import_Configuration::WORK_FILES_DIRECTORY;

            if (! is_dir($dir . $rootDir)) {
                throw new \Exception("No workfiles found for the task package for the file:" . $file->getId());
            }
        }
    }

    $treeDb = ZfExtended_Factory::get(editor_Models_Foldertree::class);

    // Assume is workfiles directory for the corrupted tasks.
    $treeDb->setPathPrefix($rootDir);

    $filelist = $treeDb->getPaths($file->getTaskGuid(), 'file');

    foreach ($filelist as $fileId => $path) {
        if ((int) $fileId !== (int) $file->getId()) {
            continue;
        }

        $fileToProcess = $dir . $path . '.xlf';

        // in case this is the file processed by okapi, it will always will be "original name" + .xlf
        if (is_file($fileToProcess)) {
            return $fileToProcess;
        }

        $fileToProcess = $dir . $path;
        if (! is_file($fileToProcess)) {
            continue;
        }

        return $fileToProcess;
    }

    return '';
}

/***
 * Get the path where the corrupted file will be searched.
 * @param editor_Models_Task $task
 * @return string
 * @throws Zend_Exception
 */
function getReplacementFromImportArchive(editor_Models_Task $task)
{
    $tempReimportRepair = $task->getAbsoluteTaskDataPath() . '/_tempReimportRepair';

    if (is_dir($tempReimportRepair)) {
        cleanUpTempReimportFixFolder($task);
    }
    if (! mkdir($tempReimportRepair) && ! is_dir($tempReimportRepair)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $tempReimportRepair));
    }

    $archivePath = getReplacementArchivePath($task);

    $zip = ZfExtended_Factory::get('ZipArchive');
    if (! $zip->open($archivePath)) {
        throw new Zend_Exception('The file' . $archivePath . ' could not be opened!');
    }
    if (! $zip->extractTo($tempReimportRepair)) {
        throw new Zend_Exception('The file ' . $tempReimportRepair . ' could not be extracted!');
    }
    $zip->close();

    return $tempReimportRepair . '/';
}

/***
 * Find the archive after the first reimport
 * @param editor_Models_Task $task
 * @return false|mixed
 */
function getReplacementArchivePath(editor_Models_Task $task)
{
    $search_name = editor_Models_Import_DataProvider_Abstract::TASK_ARCHIV_ZIP_NAME;
    $dir_path = $task->getAbsoluteTaskDataPath();

    $files = glob("$dir_path/*$search_name*"); // get an array of all import archives versions

    // sort by name so the last one will be the oldest generated archive on reimport
    rsort($files);

    return end($files);
}

function cleanUpTempReimportFixFolder(editor_Models_Task $task)
{
    $dir = $task->getAbsoluteTaskDataPath() . '/_tempReimportRepair';
    if (is_dir($dir)) {
        ZfExtended_Utils::recursiveDelete($dir);
    }
}

function isCorruptSkeletonFileForFix(string $skeleton): bool
{
    $pattern = '/<(lekTargetSeg)(?=[^>]*\sid\s*=\s*(?:""|\'\'))[^>]*\/?>/';
    preg_match_all($pattern, $skeleton, $matches, PREG_SET_ORDER, 0);

    return ! empty($matches);
}

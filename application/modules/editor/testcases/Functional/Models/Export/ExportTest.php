<?php

namespace MittagQI\Translate5\Test\Functional\Models\Export\FileParser;

use editor_Models_ConfigException;
use editor_Models_Export;
use editor_Models_File;
use editor_Models_Foldertree;
use editor_Models_Import_FileParser_Xlf;
use editor_Models_Logger_Task;
use editor_Models_Task;
use editor_Test_UnitTest;
use MittagQI\Translate5\Task\Import\SkeletonFile;
use ReflectionException;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Utils;

class ExportTest extends editor_Test_UnitTest
{
    private ?editor_Models_Task $task;

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->task = ZfExtended_Factory::get(editor_Models_Task::class);
        $this->task->setTaskGuid(ZfExtended_Utils::uuid());
        $this->task->setState('Import');
        $this->task->setTaskName('Test Task');
        $this->task->setSourceLang(4);
        $this->task->setTargetLang(5);
        $this->task->save();

        $this->task->initTaskDataDirectory();

        $foldertree = ZfExtended_Factory::get(editor_Models_Foldertree::class);
        $foldertree->setTree([
            $this->addFileAndCreateTreeLeaf('foo.odt', 'Test', editor_Models_Import_FileParser_Xlf::class),
            // A successful export with empty fileparser tests TRANSLATE-3986
            $this->addFileAndCreateTreeLeaf('TRANSLATE-3986.xlms', ''),
        ]);
        $foldertree->setTaskGuid($this->task->getTaskGuid());
        $foldertree->save();
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Exception
     */
    private function addFileAndCreateTreeLeaf(string $fileName, string $data, ?string $fileParser = null): array
    {
        $file = ZfExtended_Factory::get(editor_Models_File::class);
        $file->setTaskGuid($this->task->getTaskGuid());
        $file->setFileName($fileName);
        $file->setFileParser($fileParser);
        $file->save();

        $skeleton = ZfExtended_Factory::get(SkeletonFile::class, [$this->task]);
        $skeleton->saveToDisk($file, $data);

        return [
            "id" => $file->getId(),
            "parentId" => 0,
            "cls" => "file",
            "isFile" => true,
            "filename" => $fileName,
            "segmentid" => 0,
            "segmentgridindex" => 0,
            "path" => "/",
        ];
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->task->delete();
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    public function testExport(): void
    {
        $export = new editor_Models_Export();
        $export->setTaskToExport($this->task, false);

        $exportPath = $this->task->getAbsoluteTaskDataPath() . '/test/';
        $export->export($exportPath, 1);

        $events = ZfExtended_Factory::get(editor_Models_Logger_Task::class);
        $logs = $events->loadByTaskGuid($this->task->getTaskGuid());
        $found = false;
        foreach ($logs as $log) {
            if ($log['eventCode'] == 'E1157' && str_contains($log['extra'], '"file":"TRANSLATE-3986.xlms"')) {
                $found = true;
            }
        }

        $this->assertTrue($found, 'E1157 for file none.xmls not found in tasklog!');
        $this->assertStringEqualsFile($exportPath . 'foo.odt', 'Test', 'File content not as expected');
        $this->assertFileDoesNotExist($exportPath . 'none.xlms', 'File should not exist since empty fileparser');
    }
}

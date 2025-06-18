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

namespace MittagQI\Translate5\Test\Unit\Models\Import;

use editor_Models_Import_Configuration;
use editor_Models_Import_TaskConfig;
use editor_Models_Task as Task;
use editor_Models_TaskConfig as TaskConfigModel;
use Exception;
use MittagQI\Translate5\Test\UnitTestAbstract;
use Zend_Db_Statement_Interface;

/**
 * Test if the alt trans element is correctly parsed when self-closing tag exists in all trans
 */
class TaskConfig extends UnitTestAbstract
{
    public const GUID = '{a2c23beb-a6e5-445b-b099-2895c6c11ebc}';

    private string $tmpDir = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir();
        file_put_contents($this->tmpDir . '/task-config.ini', 'runtimeOptions.foo = bar
runtimeOptions.fou = baz
fileFilter[] = filter1
fileFilter[] = filter2
');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->tmpDir . '/task-config.ini')) {
            unlink($this->tmpDir . '/task-config.ini');
        }
    }

    /**
     * @throws Exception
     */
    public function test(): void
    {
        $task = new Task();
        $task->setTaskGuid(self::GUID);
        $importConfig = new editor_Models_Import_Configuration();
        $importConfig->importFolder = $this->tmpDir;

        $calledParams = [];

        $taskConfigModel = $this->createMock(TaskConfigModel::class);
        $taskConfigModel
            ->method('updateInsertConfig')
            ->willReturnCallback(function ($guid, $name, $value) use (&$calledParams) {
                $this->assertSame(self::GUID, $guid);
                $calledParams[$name] = $value;

                return $this->createMock(Zend_Db_Statement_Interface::class);
            });
        $taskConfig = new editor_Models_Import_TaskConfig($taskConfigModel);
        $taskConfig->loadAndProcessConfigTemplate($task, $importConfig);
        $this->assertEquals(
            ['filter1', 'filter2'],
            $importConfig->fileFilters,
            'Task filters from task-config.ini not set as expected'
        );
        $this->assertEquals([
            'runtimeOptions.foo' => 'bar',
            'runtimeOptions.fou' => 'baz',
        ], $calledParams, 'Updated task config is not as expected');
    }
}

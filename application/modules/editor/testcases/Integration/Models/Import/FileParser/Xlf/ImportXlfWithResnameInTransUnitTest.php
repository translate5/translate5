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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\Models\Import\FileParser\Xlf;

use editor_Models_File;
use editor_Models_Import_FileParser;
use editor_Models_Import_FileParser_Xlf as Xlf;
use editor_Models_Task;
use PHPUnit\Framework\TestCase;
use ZfExtended_Factory;
use ZfExtended_Utils;

class ImportXlfWithResnameInTransUnitTest extends TestCase
{
    private editor_Models_Task $task;

    private editor_Models_File $file;

    public function setUp(): void
    {
        parent::setUp();

        $customer = ZfExtended_Factory::get(\editor_Models_Customer_Customer::class);
        $customer->loadByDefaultCustomer();

        $workflow = ZfExtended_Factory::get(\editor_Models_Workflow::class);
        $workflow->loadByName('default');

        $workflowStep = ZfExtended_Factory::get(\editor_Models_Workflow_Step::class);
        /** @var array{id: int} $step */
        $step = $workflowStep->loadByWorkflow($workflow)[0];

        $this->task = ZfExtended_Factory::get(editor_Models_Task::class);
        $this->task->setTaskGuid(ZfExtended_Utils::uuid());
        $this->task->setTaskNr('1');
        $this->task->setCustomerId((int) $customer->getId());
        $this->task->setState('Import');
        $this->task->setTaskName('Test Task');
        $this->task->setTaskType('translate');
        $this->task->setWorkflow($workflow->getName());
        $this->task->setWorkflowStep($step['id']);
        $this->task->setSourceLang(4);
        $this->task->setTargetLang(5);
        $this->task->save();

        $this->file = ZfExtended_Factory::get(editor_Models_File::class);
        $this->file->setTaskGuid($this->task->getTaskGuid());
        $this->file->save();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->file->delete();
        $this->task->delete();
    }

    /**
     * @see          \editor_Models_Import_FileParser_Sdlxliff::parseSegment
     * @dataProvider expectedSegmentsProvider
     */
    public function testUserCases(string $filename, string $descriptor): void
    {
        $segmentFieldManager = \editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());

        $parser = new Xlf(
            __DIR__ . '/testfiles/ImportXlfWithResnameInTransUnitTest/' . $filename,
            'ImportXlfWithResnameInTransUnitTest.xlf',
            (int) $this->file->getId(),
            $this->task,
        );
        $parser->setSegmentFieldManager($segmentFieldManager);

        $sp = new class($this->task) extends \editor_Models_Import_SegmentProcessor {
            private string $transunitDescriptor;

            public function __construct(
                editor_Models_Task $task,
            ) {
                parent::__construct($task);
            }

            public function process(editor_Models_Import_FileParser $parser)
            {
                $mid = $parser->getMid();
                $attributes = $parser->getSegmentAttributes($mid);

                $this->transunitDescriptor = $attributes->transunitDescriptor;

                return false;
            }

            public function transunitDescriptor(): string
            {
                return $this->transunitDescriptor;
            }
        };

        $parser->addSegmentProcessor($sp);

        $parser->parseFile();

        TestCase::assertSame($descriptor, $sp->transunitDescriptor());
    }

    public function expectedSegmentsProvider(): iterable
    {
        yield 'tags-that-were-wrongly-parsed-as-duplicates' => [
            '1-seg-resname.xlf',
            'document.title',
        ];
    }
}

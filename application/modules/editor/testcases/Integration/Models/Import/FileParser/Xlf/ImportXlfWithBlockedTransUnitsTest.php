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
use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\Repository\LanguageRepository;
use PHPUnit\Framework\TestCase;
use ZfExtended_Utils;

class ImportXlfWithBlockedTransUnitsTest extends TestCase
{
    private editor_Models_Task $task;

    private editor_Models_File $file;

    public function setUp(): void
    {
        parent::setUp();

        $customer = new \editor_Models_Customer_Customer();
        $customer->loadByDefaultCustomer();

        $workflow = new \editor_Models_Workflow();
        $workflow->loadByName('default');

        $workflowStep = new \editor_Models_Workflow_Step();
        /** @var array{id: int} $step */
        $step = $workflowStep->loadByWorkflow($workflow)[0];

        $this->task = new editor_Models_Task();
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

        $this->file = new editor_Models_File();
        $this->file->setTaskGuid($this->task->getTaskGuid());
        $this->file->save();

        $number = new ContentRecognition();
        $number->setName('1024');
        $number->setType(KeepContentProtector::getType());
        $number->setEnabled(true);
        $number->setKeepAsIs(true);
        $number->setRegex('#1024#');
        $number->setMatchId(0);
        $number->save();

        $languageRepository = LanguageRepository::create();

        $en = $languageRepository->findByRfc5646('de');

        $inputMapping = new InputMapping();
        $inputMapping->setLanguageId((int) $en->getId());
        $inputMapping->setContentRecognitionId($number->getId());
        $inputMapping->setPriority(4);
        $inputMapping->save();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->file->delete();
        $this->task->delete();

        $contentRecognition = new ContentRecognition();

        $inputMapping = new InputMapping();
        foreach ($inputMapping->loadAll() as $item) {
            $inputMapping->load($item['id']);
            $inputMapping->delete();
        }

        $contentRecognition->loadBy(KeepContentProtector::getType(), '1024');
        $contentRecognition->delete();
    }

    /**
     * @see \editor_Models_Import_FileParser_Sdlxliff::parseSegment
     * @dataProvider expectedSegmentsProvider
     */
    public function test(string $filename): void
    {
        $segmentFieldManager = \editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());

        $parser = new Xlf(
            __DIR__ . '/testfiles/ImportXlfWithBlockedTransUnitsTest/' . $filename,
            $filename,
            (int) $this->file->getId(),
            $this->task,
        );
        $parser->setSegmentFieldManager($segmentFieldManager);

        $sp = new class($this->task) extends \editor_Models_Import_SegmentProcessor {
            public function process(editor_Models_Import_FileParser $parser)
            {
                $mid = $parser->getMid();
                $attributes = $parser->getSegmentAttributes($mid);
                $fields = $parser->getFieldContents();

                if ($attributes->locked) {
                    TestCase::assertStringNotContainsString('number', $fields['source']['original']);
                    TestCase::assertEmpty($fields['target']['original']);
                } else {
                    TestCase::assertStringContainsString('number', $fields['source']['original']);
                    TestCase::assertStringContainsString('number', $fields['target']['original']);
                }

                return false;
            }
        };

        $parser->addSegmentProcessor($sp);

        $parser->parseFile();
    }

    public function expectedSegmentsProvider(): iterable
    {
        yield '1-seg-blocked-and-1-not' => [
            '1-seg-blocked-and-1-not.xlf',
        ];
    }
}

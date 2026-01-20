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

namespace MittagQI\Translate5\Test\Unit\Segment;

use editor_Models_Segment;
use editor_Models_Task;
use editor_Segment_FieldTags;
use MittagQI\Translate5\Segment\ReferenceFieldService;
use PHPUnit\Framework\TestCase;
use Zend_Config;

class ReferenceFieldServiceTest extends TestCase
{
    /**
     * @dataProvider referenceFieldCases
     */
    public function testUseSourceReferenceField(
        bool $useSourceForReference,
        string $target,
        int $pretrans,
        ?bool $fieldTagsEmpty,
        bool $expected
    ): void {
        $task = $this->createTaskMock($useSourceForReference);
        $segment = $this->createSegmentMock($target, $pretrans);
        $fieldTags = $this->createFieldTagsMock($fieldTagsEmpty);

        $service = new ReferenceFieldService();
        $result = $service->useSourceReferenceField($task, $segment, $fieldTags);

        $this->assertSame($expected, $result);
    }

    public static function referenceFieldCases(): array
    {
        return [
            'config forces source' => [true, 'target', 0, null, true],
            'empty target uses source' => [false, '', 0, null, true],
            'target non-empty, no pretrans' => [false, 'target', 0, null, false],
            'pretrans uses source' => [false, 'target', 1, null, true],
            'empty field tags uses source' => [false, 'target', 0, true, true],
            'non-empty field tags, no pretrans' => [false, 'target', 0, false, false],
            'non-empty field tags, pretrans' => [false, 'target', 2, false, true],
        ];
    }

    private function createTaskMock(bool $useSourceForReference): editor_Models_Task
    {
        $task = $this->getMockBuilder(editor_Models_Task::class)
            ->onlyMethods(['getConfig'])
            ->getMock();
        $task->method('getConfig')->willReturn(new Zend_Config([
            'runtimeOptions' => [
                'editor' => [
                    'frontend' => [
                        'reviewTask' => [
                            'useSourceForReference' => $useSourceForReference,
                        ],
                    ],
                ],
            ],
        ]));

        return $task;
    }

    private function createSegmentMock(string $target, int $pretrans): editor_Models_Segment
    {
        $segment = $this->getMockBuilder(editor_Models_Segment::class)
            ->addMethods(['getTarget', 'getPretrans'])
            ->disableOriginalConstructor()
            ->getMock();
        $segment->method('getTarget')->willReturn($target);
        $segment->method('getPretrans')->willReturn($pretrans);

        return $segment;
    }

    private function createFieldTagsMock(?bool $isEmpty): ?editor_Segment_FieldTags
    {
        if ($isEmpty === null) {
            return null;
        }

        $tags = $this->getMockBuilder(editor_Segment_FieldTags::class)
            ->onlyMethods(['isEmpty'])
            ->disableOriginalConstructor()
            ->getMock();
        $tags->method('isEmpty')->willReturn($isEmpty);

        return $tags;
    }
}

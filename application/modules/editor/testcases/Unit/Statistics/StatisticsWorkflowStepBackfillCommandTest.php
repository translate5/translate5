<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2026 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Test\Unit\Statistics;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Translate5\MaintenanceCli\Command\StatisticsEditedInStepBackfillCommand;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class StatisticsWorkflowStepBackfillCommandTest extends TestCase
{
    private const string TASK_GUID = '11111111-1111-1111-1111-111111111111';

    private ?Zend_Db_Adapter_Abstract $originalDefaultAdapter = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDefaultAdapter = Zend_Db_Table::getDefaultAdapter();
    }

    protected function tearDown(): void
    {
        if ($this->originalDefaultAdapter !== null) {
            Zend_Db_Table::setDefaultAdapter($this->originalDefaultAdapter);
        }

        parent::tearDown();
    }

    public function testDryRunDoesNotWriteButReportsCounts(): void
    {
        $db = $this->createMockedDbAdapter(
            hasPmCheckData: true,
            countDirectSegments: 2,
            countPmCheckSegments: 3,
            countDirectHistory: 4,
            countPmCheckHistory: 5,
            countWorkflowEventsToInsert: 6,
            rowsPerWriteQuery: [],
        );

        $db->expects($this->never())->method('beginTransaction');
        $db->expects($this->never())->method('commit');
        $db->expects($this->never())->method('rollBack');
        $db->expects($this->never())->method('query');

        Zend_Db_Table::setDefaultAdapter($db);

        $output = $this->runCommand([
            '--dry-run' => true,
            '--taskGuid' => self::TASK_GUID,
        ]);

        self::assertStringContainsString('Done [dry-run].', $output);
        self::assertStringContainsString('Workflow events to insert: 6', $output);
        self::assertStringContainsString('Segment rows to update: 5', $output);
        self::assertStringContainsString('History rows to update: 9', $output);
    }

    public function testWritePathUpdatesAndCommitsTransaction(): void
    {
        $db = $this->createMockedDbAdapter(
            hasPmCheckData: true,
            countDirectSegments: 0,
            countPmCheckSegments: 0,
            countDirectHistory: 0,
            countPmCheckHistory: 0,
            countWorkflowEventsToInsert: 0,
            rowsPerWriteQuery: [2, 1, 3, 4, 5],
        );

        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->once())->method('commit');
        $db->expects($this->never())->method('rollBack');
        $db->expects($this->exactly(5))->method('query');

        Zend_Db_Table::setDefaultAdapter($db);

        $output = $this->runCommand([
            '--taskGuid' => self::TASK_GUID,
        ]);

        self::assertStringContainsString('Done.', $output);
        self::assertStringContainsString('Workflow events inserted: 3', $output);
        self::assertStringContainsString('Segment rows updated: 6', $output);
        self::assertStringContainsString('History rows updated: 6', $output);
    }

    /**
     * @param array<int, int> $rowsPerWriteQuery
     */
    private function createMockedDbAdapter(
        bool $hasPmCheckData,
        int $countDirectSegments,
        int $countPmCheckSegments,
        int $countDirectHistory,
        int $countPmCheckHistory,
        int $countWorkflowEventsToInsert,
        array $rowsPerWriteQuery
    ): Zend_Db_Adapter_Abstract|MockObject {
        $db = $this->getMockBuilder(Zend_Db_Adapter_Abstract::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchOne', 'fetchCol', 'query', 'beginTransaction', 'commit', 'rollBack'])
            ->getMockForAbstractClass();

        $db->method('fetchCol')
            ->willReturn([]);

        $db->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $bind = []) use (
                $hasPmCheckData,
                $countDirectSegments,
                $countPmCheckSegments,
                $countDirectHistory,
                $countPmCheckHistory,
                $countWorkflowEventsToInsert
            ) {
                if (str_contains($sql, 'INFORMATION_SCHEMA.STATISTICS')) {
                    return 1;
                }
                if (str_contains($sql, 'AS hasData')) {
                    return $hasPmCheckData ? 1 : 0;
                }
                if (str_contains($sql, 'FROM LEK_segments s') && str_contains($sql, "s.workflowStep <> 'pmCheck'")) {
                    return $countDirectSegments;
                }
                if (str_contains($sql, 'FROM LEK_segments s') && str_contains($sql, "s.workflowStep IS NULL OR s.workflowStep = 'pmCheck'")) {
                    return $countPmCheckSegments;
                }
                if (str_contains($sql, 'FROM LEK_segment_history h') && str_contains($sql, "h.workflowStep <> 'pmCheck'")) {
                    return $countDirectHistory;
                }
                if (str_contains($sql, 'FROM LEK_segment_history h') && str_contains($sql, "h.workflowStep IS NULL OR h.workflowStep = 'pmCheck'")) {
                    return $countPmCheckHistory;
                }
                if (str_contains($sql, 'FROM LEK_task_log tl')) {
                    return $countWorkflowEventsToInsert;
                }

                throw new \RuntimeException('Unhandled fetchOne SQL in test: ' . $sql);
            });

        $writeCount = 0;
        $db->method('query')
            ->willReturnCallback(static function (string $sql, array $bind = []) use (&$writeCount, $rowsPerWriteQuery) {
                $isWriteDml = str_starts_with(ltrim($sql), 'UPDATE') || str_starts_with(ltrim($sql), 'INSERT');
                if (! $isWriteDml) {
                    throw new \RuntimeException('Unexpected query SQL in test: ' . $sql);
                }

                $rows = $rowsPerWriteQuery[$writeCount] ?? 0;
                $writeCount++;

                return new class($rows) {
                    public function __construct(
                        private readonly int $rows
                    ) {
                    }

                    public function rowCount(): int
                    {
                        return $this->rows;
                    }
                };
            });

        return $db;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function runCommand(array $options): string
    {
        $command = new class() extends StatisticsEditedInStepBackfillCommand {
            protected static $defaultName = 'statistics:workflowstep:backfill';

            protected function initTranslate5(string $applicationEnvironment = 'application'): void
            {
                // no-op in unit test
            }
        };

        $app = new Application();
        $app->setAutoExit(false);
        $app->add($command);

        $input = new ArrayInput(array_merge([
            'command' => StatisticsEditedInStepBackfillCommand::getDefaultName(),
            '--no-interaction' => true,
        ], $options));
        $output = new BufferedOutput();

        $exitCode = $app->run($input, $output);
        $printedOutput = $output->fetch();
        self::assertSame(0, $exitCode, str_replace("\n", '', $printedOutput));

        return $printedOutput;
    }
}

<?php

namespace MittagQI\Translate5\Test\Unit\Models\Import\FileParser\Sdlxliff;

use DateTime;
use editor_Models_Import_FileParser_Sdlxliff_TransunitParser as TransunitParser;
use editor_Models_Task;
use MittagQI\Translate5\Test\UnitTestAbstract;
use Zend_Config;

class TransunitParserTest extends UnitTestAbstract
{
    private ?string $savedTimezone = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->savedTimezone = date_default_timezone_get() === 'UTC' ? null : date_default_timezone_get();

        date_default_timezone_set('UTC');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if (null !== $this->savedTimezone) {
            date_default_timezone_set($this->savedTimezone);
        }
    }

    public function test(): void
    {
        /**
         * @var array<string, array{author: string, date: DateTime, workflowStep: string}> $revIdToUserDataMap
         */
        $revIdToUserDataMap = [
            'b6c4320f-5ea7-463b-bd7f-0c87196b9cff' => [
                'author' => 'Sylvi',
                'date' => new DateTime('2024/04/24 18:18:50'),
                'workflowStep' => 'first',
            ],
            '6d9e7514-e5d8-4b10-96f6-f21a4fb53873' => [
                'author' => 'Sylvi',
                'date' => new DateTime('2024/04/24 18:19:50'),
                'workflowStep' => 'first',
            ],
            '1a9a3b11-e066-44e8-87ec-81c999230b94' => [
                'author' => 'Sylvi',
                'date' => new DateTime('2024/04/24 18:19:50'),
                'workflowStep' => 'first',
            ],
        ];

        /**
         * @var array<string, array{id: string, taskOpenerNumber: string}> $authorToTrackChangeIdAndNr
         */
        $authorToTrackChangeIdAndNr = [
            'Sylvi' => [
                'id' => '123',
                'taskOpenerNumber' => 1,
            ],
        ];

        $transUnit = <<<TU
<trans-unit id="46c699e1-bdd9-410b-b20a-d2c5ed00a001">
    <source>
        von <g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987">Marc Mittag</g> | Mrz 19, 2021 | <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">Presse</g> | <g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 Kommentare</g>
    </source>
    <seg-source>
        <mrk mtype="seg" mid="2">von <g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987">Marc Mittag</g> | Mrz 19, 2021 | <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">Presse</g> | <g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 Kommentare</g></mrk>
    </seg-source>
    <target>
        <mrk mtype="seg" mid="2">amer <mrk mtype="x-sdl-deleted" sdl:revid="b6c4320f-5ea7-463b-bd7f-0c87196b9cff"><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false">hello</g></mrk><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:start="false">vides <mrk mtype="x-sdl-added" sdl:revid="6d9e7514-e5d8-4b10-96f6-f21a4fb53873"><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></mrk> poussage</g> à COLD 19, 2021 è <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">soupapes</g> à <mrk mtype="x-sdl-added" sdl:revid="1a9a3b11-e066-44e8-87ec-81c999230b94"><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></mrk><g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 collaboration</g></mrk>
    </target>
    <sdl:seg-defs>
        <sdl:seg id="2" conf="Draft" origin="interactive">
            <sdl:value key="SegmentIdentityHash">bAC6VVuP0j1hczzWnfH1f7VC/S0=</sdl:value>
            <sdl:value key="created_by">WindowsForTrado\translate5</sdl:value>
            <sdl:value key="created_on">04/24/2024 18:18:50</sdl:value>
            <sdl:value key="last_modified_by">WindowsForTrado\translate5</sdl:value>
            <sdl:value key="modified_on">04/24/2024 18:18:50</sdl:value>
        </sdl:seg>
    </sdl:seg-defs>
</trans-unit>
TU;

        $task = $this->createConfiguredMock(editor_Models_Task::class, []);
        $config = new Zend_Config([
            'runtimeOptions' => [
                'import' => [
                    'sdlxliff' => [
                        'importComments' => false,
                    ],
                ],
                'segment' => [
                    'useStrictEscaping' => false,
                ],
            ],
        ]);
        $parser = new TransunitParser($config, $task, true, $authorToTrackChangeIdAndNr);

        $saver = function ($mid, $source, $target, $comments) {
            self::assertSame(
                'von <g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987">Marc Mittag</g> | Mrz 19, 2021 | <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">Presse</g> | <g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 Kommentare</g>',
                $source
            );
            self::assertSame(
                'amer <del class="trackchanges ownttip deleted" data-usertrackingid="123" data-usercssnr="usernr1" data-workflowstep="first" data-timestamp="2024-04-24T18:18:50+00:00" ><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false">hello</del>vides <ins class="trackchanges ownttip" data-usertrackingid="123" data-usercssnr="usernr1" data-workflowstep="first" data-timestamp="2024-04-24T18:19:50+00:00" ><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></ins> poussage</g> à COLD 19, 2021 è <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">soupapes</g> à <ins class="trackchanges ownttip" data-usertrackingid="123" data-usercssnr="usernr1" data-workflowstep="first" data-timestamp="2024-04-24T18:19:50+00:00" ><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></ins><g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 collaboration</g>',
                $target
            );

            return $target;
        };

        $expectedResult = <<<ER
<trans-unit id="46c699e1-bdd9-410b-b20a-d2c5ed00a001">
    <source>
        von <g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987">Marc Mittag</g> | Mrz 19, 2021 | <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">Presse</g> | <g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 Kommentare</g>
    </source>
    <seg-source>
        <mrk mtype="seg" mid="2">von <g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987">Marc Mittag</g> | Mrz 19, 2021 | <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">Presse</g> | <g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 Kommentare</g></mrk>
    </seg-source>
    <target>
        <mrk mtype="seg" mid="2">amer <del class="trackchanges ownttip deleted" data-usertrackingid="123" data-usercssnr="usernr1" data-workflowstep="first" data-timestamp="2024-04-24T18:18:50+00:00" ><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false">hello</del>vides <ins class="trackchanges ownttip" data-usertrackingid="123" data-usercssnr="usernr1" data-workflowstep="first" data-timestamp="2024-04-24T18:19:50+00:00" ><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></ins> poussage</g> à COLD 19, 2021 è <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">soupapes</g> à <ins class="trackchanges ownttip" data-usertrackingid="123" data-usercssnr="usernr1" data-workflowstep="first" data-timestamp="2024-04-24T18:19:50+00:00" ><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></ins><g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 collaboration</g></mrk>
    </target>
    <sdl:seg-defs>
        <sdl:seg id="2" conf="Draft" origin="interactive">
            <sdl:value key="SegmentIdentityHash">bAC6VVuP0j1hczzWnfH1f7VC/S0=</sdl:value>
            <sdl:value key="created_by">WindowsForTrado\translate5</sdl:value>
            <sdl:value key="created_on">04/24/2024 18:18:50</sdl:value>
            <sdl:value key="last_modified_by">WindowsForTrado\translate5</sdl:value>
            <sdl:value key="modified_on">04/24/2024 18:18:50</sdl:value>
        </sdl:seg>
    </sdl:seg-defs>
</trans-unit>
ER;

        self::assertSame($expectedResult, $parser->parse($transUnit, $saver, $revIdToUserDataMap));
    }
}

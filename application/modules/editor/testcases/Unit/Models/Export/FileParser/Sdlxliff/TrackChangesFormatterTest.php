<?php

namespace MittagQI\Translate5\Test\Unit\Models\Export\FileParser\Sdlxliff;

use editor_Models_Export_FileParser_Sdlxliff_TrackChangesFormatter;
use PHPUnit\Framework\TestCase;

class TrackChangesFormatterTest extends TestCase
{
    /**
     * @dataProvider segmnetsProvider
     */
    public function testToSdlxliffFormat(string $segment, string $expected, array $expectedRevisions): void
    {
        $trackChangeIdToUserName = [
            '1' => 'user1',
            '2' => 'user2',
            '3' => 'user3',
        ];

        $formatter = new editor_Models_Export_FileParser_Sdlxliff_TrackChangesFormatter($trackChangeIdToUserName);

        $uuidRegex = '/(?<!xid)="([0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})/';
        $uuidCounter = 1;
        $revisions = [];

        $segment = preg_replace_callback(
            $uuidRegex,
            function ($match) use (&$uuidCounter) {
                return str_replace($match[1], (string) $uuidCounter++, $match[0]);
            },
            $formatter->toSdlxliffFormat($segment, $revisions)
        );

        $revId = 1;
        foreach ($revisions as &$revision) {
            $revision = preg_replace_callback(
                $uuidRegex,
                function ($match) use (&$revId) {
                    return str_replace($match[1], (string) $revId++, $match[0]);
                },
                $revision
            );
        }

        self::assertSame($expected, $segment);
        self::assertSame($expectedRevisions, $revisions);
    }

    public function segmnetsProvider(): iterable
    {
        yield 'no changes' => [
            'segment' => <<<SEGMENT
<div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>some text here<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;/g&gt;</span></div>
SEGMENT,
            'expected' => '<g id="3">some text here</g>',
            'expectedRevisions' => [],
        ];

        yield 'closing g tag moved' => [
            'segment' => <<<SEGMENT
<div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>some text <del class="trackchanges ownttip" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00">here <div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00">there <div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></ins>
SEGMENT,
            'expected' => '<g id="3" sdl:end="false">some text </g><mrk mtype="x-sdl-deleted" sdl:revid="1">here <g id="3" sdl:start="false"/></mrk><mrk mtype="x-sdl-added" sdl:revid="2">there <g id="3" sdl:start="false"/></mrk>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user1" date="04/10/2024 22:10:10" />',
                '<rev-def id="2" author="user2" date="04/11/2024 22:10:10" />',
            ],
        ];

        yield 'single g tag at beginning, g closing tag moved' => [
            'segment' => <<<SEGMENT
<div class="single 672069643d2231222f internal-tag ownttip"><span title="&lt;g id=&quot;1&quot;/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;g id=&quot;1&quot;/&gt;</span></div><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>some text <del class="trackchanges ownttip" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00">here<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00">there<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></ins>
SEGMENT,
            'expected' => '<g id="1"/><g id="3" sdl:end="false">some text </g><mrk mtype="x-sdl-deleted" sdl:revid="1">here<g id="3" sdl:start="false"/></mrk><mrk mtype="x-sdl-added" sdl:revid="2">there<g id="3" sdl:start="false"/></mrk>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user1" date="04/10/2024 22:10:10" />',
                '<rev-def id="2" author="user2" date="04/11/2024 22:10:10" />',
            ],
        ];

        yield 'single g tag moved' => [
            'segment' => <<<SEGMENT
<div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>some text <div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;/g&gt;</span></div><del class="trackchanges ownttip" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00">here<div class="single 672069643d2231222f internal-tag ownttip"><span title="&lt;g id=&quot;1&quot;/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;g id=&quot;1&quot;/&gt;</span></div></del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00">there<div class="single 672069643d2231222f internal-tag ownttip"><span title="&lt;g id=&quot;1&quot;/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;g id=&quot;1&quot;/&gt;</span></div></ins>
SEGMENT,
            'expected' => '<g id="3">some text </g><mrk mtype="x-sdl-deleted" sdl:revid="1">here<g id="1"/></mrk><mrk mtype="x-sdl-added" sdl:revid="2">there<g id="1"/></mrk>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user1" date="04/10/2024 22:10:10" />',
                '<rev-def id="2" author="user2" date="04/11/2024 22:10:10" />',
            ],
        ];

        yield 'opening g tag moved' => [
            'segment' => <<<SEGMENT
some text <del class="trackchanges ownttip" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00"><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>here</del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00"><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>there</ins><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div>
SEGMENT,
            'expected' => 'some text <mrk mtype="x-sdl-deleted" sdl:revid="1"><g id="3" sdl:end="false">here</g></mrk><g id="3" sdl:start="false"><mrk mtype="x-sdl-added" sdl:revid="2"><g id="3" sdl:end="false">there</g></mrk></g>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user1" date="04/10/2024 22:10:10" />',
                '<rev-def id="2" author="user2" date="04/11/2024 22:10:10" />',
            ],
        ];

        yield 'opening g tag moved by 2 users' => [
            'segment' => <<<SEGMENT
some text <del class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-20T22:10:10+02:00"><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>was here</del><del class="trackchanges ownttip" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00"><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>here</del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00"><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>there</ins> <div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div>
SEGMENT,
            'expected' => 'some text <mrk mtype="x-sdl-deleted" sdl:revid="1"><g id="3" sdl:end="false">was here</g></mrk><g id="3" sdl:start="false"><mrk mtype="x-sdl-deleted" sdl:revid="2"><g id="3" sdl:end="false">here</g></mrk><mrk mtype="x-sdl-added" sdl:revid="3"><g id="3" sdl:end="false">there</g></mrk> </g>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user2" date="04/20/2024 22:10:10" />',
                '<rev-def id="2" type="Delete" author="user1" date="04/10/2024 22:10:10" />',
                '<rev-def id="3" author="user2" date="04/11/2024 22:10:10" />',
            ],
        ];

        yield 'closing x tag moved' => [
            'segment' => <<<SEGMENT
<div class="open 782069643d223422 internal-tag ownttip"><span title="&lt;x id=&quot;4&quot;&gt;" class="short">&lt;4&gt;</span><span data-originalid="4" data-length="-1" class="full">&lt;x id=&quot;4&quot;&gt;</span></div>some text <del class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00">here <div class="close 2f78 internal-tag ownttip"><span title="&lt;/x&gt;" class="short">&lt;/4&gt;</span><span data-originalid="4" data-length="-1" class="full">&lt;/x&gt;</span></div></del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00">there <div class="close 2f78 internal-tag ownttip"><span title="&lt;/x&gt;" class="short">&lt;/4&gt;</span><span data-originalid="4" data-length="-1" class="full">&lt;/x&gt;</span></div></ins>
SEGMENT,
            'expected' => '<x id="4" sdl:end="false">some text </x><mrk mtype="x-sdl-deleted" sdl:revid="1">here <x id="4" sdl:start="false"/></mrk><mrk mtype="x-sdl-added" sdl:revid="2">there <x id="4" sdl:start="false"/></mrk>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user2" date="04/10/2024 22:10:10" />',
                '<rev-def id="2" author="user2" date="04/11/2024 22:10:10" />',
            ],
        ];

        yield 'single x tag at beginning, g closing tag moved' => [
            'segment' => <<<SEGMENT
<div class="single 782069643d2232222f internal-tag ownttip"><span title="&lt;x id=&quot;2&quot;/&gt;" class="short">&lt;2/&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;x id=&quot;2&quot;/&gt;</span></div><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>some text <del class="trackchanges ownttip" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00">here<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00">there<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></ins>
SEGMENT,
            'expected' => '<x id="2"/><g id="3" sdl:end="false">some text </g><mrk mtype="x-sdl-deleted" sdl:revid="1">here<g id="3" sdl:start="false"/></mrk><mrk mtype="x-sdl-added" sdl:revid="2">there<g id="3" sdl:start="false"/></mrk>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user1" date="04/10/2024 22:10:10" />',
                '<rev-def id="2" author="user2" date="04/11/2024 22:10:10" />',
            ],
        ];

        yield 'single x tag moved, g closing tag moved' => [
            'segment' => <<<SEGMENT
<del class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-05-10T22:10:10+02:00"><div class="single 782069643d2232222f internal-tag ownttip"><span title="&lt;x id=&quot;2&quot;/&gt;" class="short">&lt;2/&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;x id=&quot;2&quot;/&gt;</span></div></del><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>some text <del class="trackchanges ownttip" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00">here<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00">there<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></ins><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-05-10T22:10:10+02:00"><div class="single 782069643d2232222f internal-tag ownttip"><span title="&lt;x id=&quot;2&quot;/&gt;" class="short">&lt;2/&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;x id=&quot;2&quot;/&gt;</span></div></ins>
SEGMENT,
            'expected' => '<mrk mtype="x-sdl-deleted" sdl:revid="1"><x id="2"/></mrk><g id="3" sdl:end="false">some text </g><mrk mtype="x-sdl-deleted" sdl:revid="2">here<g id="3" sdl:start="false"/></mrk><mrk mtype="x-sdl-added" sdl:revid="3">there<g id="3" sdl:start="false"/></mrk><mrk mtype="x-sdl-added" sdl:revid="4"><x id="2"/></mrk>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user2" date="05/10/2024 22:10:10" />',
                '<rev-def id="2" type="Delete" author="user1" date="04/10/2024 22:10:10" />',
                '<rev-def id="3" author="user2" date="04/11/2024 22:10:10" />',
                '<rev-def id="4" author="user2" date="05/10/2024 22:10:10" />',
            ],
        ];

        yield 'g closing tag moved inside x tag' => [
            'segment' => <<<SEGMENT
<div class="open 782069643d223422 internal-tag ownttip"><span title="&lt;x id=&quot;4&quot;&gt;" class="short">&lt;4&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;x id=&quot;4&quot;&gt;</span></div><div class="open 672069643d223322 internal-tag ownttip"><span title="&lt;g id=&quot;3&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;g id=&quot;3&quot;&gt;</span></div>some text <del class="trackchanges ownttip" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2024-04-10T22:10:10+02:00">here<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></del><ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2024-04-11T22:10:10+02:00">there<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/g&gt;</span></div></ins><div class="close 2f78 internal-tag ownttip"><span title="&lt;/x&gt;" class="short">&lt;/4&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;/x&gt;</span></div>
SEGMENT,
            'expected' => '<x id="4"><g id="3" sdl:end="false">some text </g><mrk mtype="x-sdl-deleted" sdl:revid="1">here<g id="3" sdl:start="false"/></mrk><mrk mtype="x-sdl-added" sdl:revid="2">there<g id="3" sdl:start="false"/></mrk></x>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user1" date="04/10/2024 22:10:10" />',
                '<rev-def id="2" author="user2" date="04/11/2024 22:10:10" />',
            ],
        ];

        yield 'real test case' => [
            'segment' => <<<SEGMENT
amer <del class="trackchanges ownttip deleted" data-usertrackingid="1" data-usercssnr="usernr1" data-workflowstep="default4" data-timestamp="2024-04-24T18:18:50+00:00" ><div class="open 672069643d22323722207869643d2232623734336562342d343635382d343539322d613433652d643562623864326437393837222073646c3a656e643d2266616c7365222f internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;" class="short">&lt;27&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;</span></div></del>vides<ins class="trackchanges ownttip" data-usertrackingid="2" data-usercssnr="usernr2" data-workflowstep="default4" data-timestamp="2024-04-24T18:18:59+00:00" ><div class="open 672069643d22323722207869643d2232623734336562342d343635382d343539322d613433652d643562623864326437393837222073646c3a656e643d2266616c7365222f internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;" class="short">&lt;27&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;</span></div></ins>poussage<div class="close 2f67 internal-tag ownttip"><span title="&lt;/hyperlink&gt;" class="short">&lt;/27&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;/hyperlink&gt;</span></div>à COLD 19, 2021 è <div class="open 672069643d22323822207869643d2239333636643432632d303631352d346535362d383133632d64333363666266343561656422 internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/category/presse&quot;&gt;" class="short">&lt;28&gt;</span><span data-originalid="28" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/category/presse&quot;&gt;</span></div>soupapes<div class="close 2f67 internal-tag ownttip"><span title="&lt;/hyperlink&gt;" class="short">&lt;/28&gt;</span><span data-originalid="28" data-length="-1" class="full">&lt;/hyperlink&gt;</span></div> à<ins class="trackchanges ownttip" data-usertrackingid="3" data-usercssnr="usernr3" data-workflowstep="default4" data-timestamp="2024-04-24T18:19:09+00:00" ><div class="open 672069643d22323722207869643d2232623734336562342d343635382d343539322d613433652d643562623864326437393837222073646c3a656e643d2266616c7365222f internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;" class="short">&lt;27&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;</span></div></ins><div class="open 672069643d22323922207869643d2236386364356438372d643461632d343933332d623164332d37393534653163643861393522 internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/2021/03/19/das-translate5-konsortium-ein-vollumfaengliches-open-source-cloud-uebersetzungssystem#respond&quot;&gt;" class="short">&lt;29&gt;</span><span data-originalid="29" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/2021/03/19/das-translate5-konsortium-ein-vollumfaengliches-open-source-cloud-uebersetzungssystem#respond&quot;&gt;</span></div>0 collaboration<div class="close 2f67 internal-tag ownttip"><span title="&lt;/hyperlink&gt;" class="short">&lt;/29&gt;</span><span data-originalid="29" data-length="-1" class="full">&lt;/hyperlink&gt;</span></div>
SEGMENT,
            'expected' => 'amer <mrk mtype="x-sdl-deleted" sdl:revid="1"><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></mrk><g id="27" sdl:start="false">vides<mrk mtype="x-sdl-added" sdl:revid="2"><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></mrk>poussage</g>à COLD 19, 2021 è <g id="28" xid="9366d42c-0615-4e56-813c-d33cfbf45aed">soupapes</g> à<mrk mtype="x-sdl-added" sdl:revid="3"><g id="27" xid="2b743eb4-4658-4592-a43e-d5bb8d2d7987" sdl:end="false"/></mrk><g id="29" xid="68cd5d87-d4ac-4933-b1d3-7954e1cd8a95">0 collaboration</g>',
            'expectedRevisions' => [
                '<rev-def id="1" type="Delete" author="user1" date="04/24/2024 18:18:50" />',
                '<rev-def id="2" author="user2" date="04/24/2024 18:18:59" />',
                '<rev-def id="3" author="user3" date="04/24/2024 18:19:09" />',
            ],
        ];
    }
}

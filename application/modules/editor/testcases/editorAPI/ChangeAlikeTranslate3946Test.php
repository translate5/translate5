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

use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

class ChangeAlikeTranslate3946Test extends JsonTestAbstract
{
    protected static array $forbiddenPlugins = [];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de', 'en', -1, 'TRANSLATE-3946-en-de.csv')
            ->addTaskConfig('runtimeOptions.import.fileparser.csv.active', '1')
            ->setToEditAfterImport();
    }

    public function testMathRatePreservedForFirstAmongRepeated()
    {
        // Numbers of segments was we're going to work with
        $segmentNrInTask = [
            'first' => 3, // Segment for which we'll check matchRate is kept unchanged despite repetitions change accepted
            '4edit' => 5, // Segment for which we'll make a change and accept that change to be replicated among repetitions
        ];

        // Get segments at the point where no any changes are made yet
        $segmentA['was'] = static::api()->getSegments();

        // Segment that is among the repeated ones and that is going to be edited
        $segment['4edit'] = $segmentA['was'][$segmentNrInTask['4edit'] - 1];

        // Do edit
        static::api()->saveSegment($segment['4edit']->id, "{$segment['4edit']->targetEdit} - edited");

        // Fetch alikes and and get their ids
        $alikeA = static::api()->getJson("editor/alikesegment/{$segment['4edit']->id}");
        $alikeIdA = array_map(fn ($item) => $item->id, $alikeA);

        // Accept changes for repeated segments
        static::api()->putJson("editor/alikesegment/{$segment['4edit']->id}", [
            'duration' => 777, // Faked duration value. Do we need this?
            'alikes' => json_encode($alikeIdA),
        ], null, false);

        // Get segment list again
        $segmentA['now'] = static::api()->getSegments();

        // Get match rate of first segment among repeated ones before accepting repetiion
        $matchRate['was'] = $segmentA['was'][$segmentNrInTask['first'] - 1]->matchRate;
        $matchRate['now'] = $segmentA['now'][$segmentNrInTask['first'] - 1]->matchRate;

        // Check match rate of first segment was unchanged
        $this->assertEquals($matchRate['was'], $matchRate['now'], 'Match rate of first segment among repeated was NOT preserved');
    }
}

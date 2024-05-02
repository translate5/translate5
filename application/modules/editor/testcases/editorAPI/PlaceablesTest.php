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

use MittagQI\Translate5\Segment\Tag\Placeable;
use MittagQI\Translate5\Test\Import\Config;

/**
 * Tests the Import of Placeables
 */
class PlaceablesTest extends editor_Test_JsonTest
{
    public const EXPECTED_NUM_PLACEABLES = 5;

    public const EXPECTED_PLACEABLES = [
        "COMPANY" => 1,
        "Product® BPU 26" => 1,
        "Bluetooth®" => 2,
        "Product® some.thing" => 3,
    ];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'sl')
            ->addUploadFolder('testfiles')
            ->setToEditAfterImport();
    }

    /**
     * Validate if the placeables could be found in the source/target segments
     */
    public function testPlaceables()
    {
        // detect segments with placeables
        $sources = [];
        $targets = [];
        foreach (static::api()->getSegments() as $segment) {
            if (str_contains($segment->source, Placeable::MARKER_CLASS)) {
                $sources[] = $segment->source;
            }
            if (str_contains($segment->targetEdit, Placeable::MARKER_CLASS)) {
                $targets[] = $segment->targetEdit;
            }
        }

        // Check amount of placeables
        static::assertEquals(self::EXPECTED_NUM_PLACEABLES, count($sources), 'Not all Placeables in the segment-sources have been detected.');
        static::assertEquals(self::EXPECTED_NUM_PLACEABLES, count($targets), 'Not all Placeables in the segment-targets have been detected.');

        // Check concrete Placeables
        $sourcePlaceables = [];
        $targetPlaceables = [];
        foreach ($sources as $source) {
            $this->detectPlaceables($source, $sourcePlaceables);
        }
        foreach ($targets as $target) {
            $this->detectPlaceables($target, $targetPlaceables);
        }

        static::assertEquals(self::EXPECTED_PLACEABLES, $sourcePlaceables, 'Not all concrete Placeables in the segment-sources have been detected.');
        static::assertEquals(self::EXPECTED_PLACEABLES, $targetPlaceables, 'Not all concrete Placeables in the segment-targets have been detected.');
    }

    /**
     * Extracts the placeables, a modified copy of Placeable::replace
     */
    private function detectPlaceables(string $segment, array &$found): void
    {
        preg_replace_callback(Placeable::DETECTION_REGEX, function ($matches) use (&$found) {
            if (count($matches) === 1) {
                $inner = [];
                if (preg_match('~<span[^>]+full[^>]+>(.+)</span>~i', $matches[0], $inner) === 1) {
                    if (count($inner) === 2) {
                        $placeable = strip_tags($inner[1]);
                        if (array_key_exists($placeable, $found)) {
                            $found[$placeable]++;
                        } else {
                            $found[$placeable] = 1;
                        }

                        return $placeable;
                    }
                }
            }

            return '';
        }, $segment);
    }
}

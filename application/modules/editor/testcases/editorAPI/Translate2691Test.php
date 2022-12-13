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

/**
 * Testcase for TRANSLATE-2691 SDLXLIFF diff export fails with old version of diff library and the here used content in an endless loop
 * For details see the issue.
 */
class Translate2691Test extends \editor_Test_UnitTest {

    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {

        $target = "Xyxyx abcdexyz d'foobar par un partenaire de service apr\xc3\xa8s-vente du fooxxx ou un autre partenaire de service apr\xc3\xa8s-vente qualifi\xc3\xa9 ou par un blabla qualifi\xc3\xa9 et en faire";
        $edited = "Xyxyx abcdexyz d'foobar par un R\xc3\xa9zyxvewe Xyz\xc3\xa9\xc3\xa9 du fooxxx ou un autre R\xc3\xa9zyxvewe Xyz\xc3\xa9\xc3\xa9 qualifi\xc3\xa9 ou par un blabla sp\xc3\xa9cialis\xc3\xa9 et en faire";
        $date = '2021-11-03 08:15';
        $name = 'Thomas Test';
        $approval = 'Xyxyx abcdexyz d\'foobar par un <mrk mtype="x-sdl-added" sdl:revid="XXX">Rézyxvewe</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">partenaire</mrk> <mrk mtype="x-sdl-added" sdl:revid="XXX">Xyzéé</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">de service après-vente</mrk> du fooxxx ou un autre <mrk mtype="x-sdl-added" sdl:revid="XXX">Rézyxvewe</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">partenaire</mrk> <mrk mtype="x-sdl-added" sdl:revid="XXX">Xyzéé</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">de service après-vente</mrk> qualifié ou par un blabla <mrk mtype="x-sdl-added" sdl:revid="XXX">spécialisé</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">qualifié</mrk> et en faire';
        $tagger = new \editor_Models_Export_DiffTagger_Sdlxliff();
        // diff tagging
        $result = $tagger->diffSegment($target, $edited, $date, $name);
        // replace/normalize ids
        $result = preg_replace('/sdl:revid="[^"]+"/', 'sdl:revid="XXX"', $result);

        $this->assertEquals($approval, $result, 'The diff-tagged content is not as expected.');
    }
}

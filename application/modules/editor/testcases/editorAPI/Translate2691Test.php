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
class Translate2691Test extends ZfExtended_Test_ApiTestcase {
    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        $worker = new \ZfExtended_Worker_Callback();
        $worker->init(null, [
            'class' => 'editor_Models_Export_DiffTagger_Sdlxliff',
            'callback' => 'diffTestCall',
            'target' => "Xyxyx abcdexyz d'foobar par un partenaire de service apr\xc3\xa8s-vente du fooxxx ou un autre partenaire de service apr\xc3\xa8s-vente qualifi\xc3\xa9 ou par un blabla qualifi\xc3\xa9 et en faire",
            'edited' => "Xyxyx abcdexyz d'foobar par un R\xc3\xa9zyxvewe Xyz\xc3\xa9\xc3\xa9 du fooxxx ou un autre R\xc3\xa9zyxvewe Xyz\xc3\xa9\xc3\xa9 qualifi\xc3\xa9 ou par un blabla sp\xc3\xa9cialis\xc3\xa9 et en faire",
            'date' => '2021-11-03 08:15',
            'name' => 'Thomas Test',
            'result' => 'Xyxyx abcdexyz d\'foobar par un <mrk mtype="x-sdl-added" sdl:revid="XXX">Rézyxvewe</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">partenaire</mrk> <mrk mtype="x-sdl-added" sdl:revid="XXX">Xyzéé</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">de service après-vente</mrk> du fooxxx ou un autre <mrk mtype="x-sdl-added" sdl:revid="XXX">Rézyxvewe</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">partenaire</mrk> <mrk mtype="x-sdl-added" sdl:revid="XXX">Xyzéé</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">de service après-vente</mrk> qualifié ou par un blabla <mrk mtype="x-sdl-added" sdl:revid="XXX">spécialisé</mrk><mrk mtype="x-sdl-deleted" sdl:revid="XXX">qualifié</mrk> et en faire',
        ]);

        $worker->setBlocking(true, 10); //we have to wait for the underlying worker to provide the download
        try {
            $worker->queue();
        }
        catch(\ZfExtended_Exception $e) {
            if(strpos($e->getMessage(), 'was queued blocking and timed out') !== false) {
                $this->fail('Diff algorithm timed out, the diff lib is not up to date!');
            }
            if(strpos($e->getMessage(), 'is defunct!') !== false) {
                $this->fail('Check the error log why the test call failed!');
            }
            throw $e;
        }
        $this->assertTrue(true);
        //code to call the differ directly (use above data)
        // $bar = new \editor_Models_Export_DiffTagger_Sdlxliff();
        // $result = $bar->diffSegment($foo1,$foo2,'2021-11-03 08:15','Thomas Test');

        // $this->assertEquals($approval, preg_replace('/sdl:revid="[^"]+"/', 'sdl:revid="XXX"', $result), 'The diff content is not as expected.');
    }
}

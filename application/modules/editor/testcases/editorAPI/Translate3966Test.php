<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * This will test the export of sdlxliff files when locked x tags are moved within segment. When moving segment from one
 * place to another(ctrl+x, ctrl+v), we will generate add and del track changes for this tag. This will result with 2
 * x tags in target with same xid(reference to locked transunit). If this file is reimported in trados, where 2 x tags
 * are referencing to same locket transunit, trados will not be able to handle this and will throw an error. To avoid this
 * we will generate clone of the locked transunit with newly unique id and replace the x tags in target with this new id.
 * This test will not validate the values of the newly created id, as they are dynamic. This test will only validate the
 * number of newly create transunits.
 */
class Translate3966Test extends editor_Test_JsonTest
{
    protected static array $forbiddenPlugins = [
        editor_Plugins_TrackChanges_Init::class,
    ];

    protected static array $requiredRuntimeOptions = [
    ];

    protected static bool $setupOwnCustomer = false;

    protected static string $setupUserLogin = 'testmanager';

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'fr-FR';
        $targetLangRfc = 'en-GB';
        $config
            ->addTask(
                $sourceLangRfc,
                $targetLangRfc,
                static::getTestCustomerId(),
                'FileForTest_fr-FR_en-GB.sdlxliff'
            )
            ->setToEditAfterImport();
    }

    public function testEditing()
    {
        //get segment list
        $segments = static::api()->getSegments();
        $segToEdit = $segments[0];

        // move the locked tag from place to another in the segment with track changes
        $editedData = '(**)<del class="trackchanges ownttip deleted" data-usertrackingid="1731" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2024-06-07T08:53:01+02:00"><div class="single 782069643d226c6f636b6564363922207869643d226c6f636b54555f38303566393630372d653464632d343538652d623861662d636139323964373830383436222f internal-tag ownttip"><span title="\u21b5                    &lt;g id=&quot;703&quot;&gt;Marque&lt;/g&gt;\u21b5" class="short">&lt;locked2/&gt;</span><span data-originalid="locked69" data-length="-1" class="full">\u21b5                    &lt;g id=&quot;703&quot;&gt;Marque&lt;/g&gt;\u21b5               ~@#!WS~</span></div></del>Passive tilt corrector for trailing shoes<ins class="trackchanges ownttip" data-usertrackingid="1731" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2024-06-07T08:53:05+02:00"> <div class="single 782069643d226c6f636b6564363922207869643d226c6f636b54555f38303566393630372d653464632d343538652d623861662d636139323964373830383436222f internal-tag ownttip"><span title="\u21b5                    &lt;g id=&quot;703&quot;&gt;Marque&lt;/g&gt;\u21b5" class="short">&lt;locked2/&gt;</span><span data-originalid="locked69" data-length="-1" class="full">\u21b5                    &lt;g id=&quot;703&quot;&gt;Marque&lt;/g&gt;\u21b5               ~@#!WS~</span></div></ins> only.';
        static::api()->saveSegment($segToEdit->id, $editedData);
    }

    /**
     * tests the special characters in the exported data
     * @depends testEditing
     */
    public function testExport()
    {
        $task = static::api()->getTask();

        //start task export with diff
        static::api()->get('editor/task/export/id/' . $task->id . '/diff/1');

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path . 'export.zip';
        $this->assertFileExists($pathToZip);

        $replaceDynamicContent = function ($string) {
            $string = preg_replace('#author="manager test" date="[0-9]{2}/[0-9]{2}/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}"#', 'author="manager test" date="NOW"', $string);
            $string = preg_replace('/(sdl:revid="|rev-def id=")[a-z0-9-]{36}"/', '\1foo-bar"', $string);

            return preg_replace(
                '/id="lockTU_[a-f0-9\-]+"/',
                'id="static_guid"',
                $string
            );
        };

        $exportedData = $replaceDynamicContent(static::api()->getFileContentFromZipPath($pathToZip, '/FileForTest_fr-FR_en-GB.sdlxliff'));

        $expectedData = $replaceDynamicContent(static::api()->getFileContent('expectedResult.sdlxliff'));

        file_put_contents($path . 'exported.sdlxliff', $exportedData);
        $this->assertEquals(rtrim($expectedData), rtrim($exportedData), 'Exported result does not equal to expected SDLXLIFF content');
    }
}

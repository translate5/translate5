<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/***
 * This test will create one task with pivot file where the workflow/pivot file-name matching is done until the first "."
 * ex: the pivot file with a name "test-aleks.de.xliff" will match the workflow file with a name "test-aleks.en.test.xliff"
 */
class Translate2827Test extends JsonTestAbstract
{
    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de', 'es-ES', static::getTestCustomerId())
            ->addUploadFiles([
                'import-project.de-es-ES.workfile.sdlxliff',
                'import-project.de-mk-MK.pivot.sdlxliff',
            ])
            ->addProperty('relaisLang', 'mk-MK')
            ->addProperty('importUpload_language', ['es-ES', 'mk-MK'])
            ->addProperty('importUpload_type', ['workfiles', 'pivot']);
    }

    /**
     * Create the task with pivot
     */
    public function testImportProjectWithRelais()
    {
        $projectTasks = static::getTask()->getProperty('projectTasks');
        $this->assertEquals(count($projectTasks), 1, 'No tasks where created.');
    }

    /**
     * Check if the pivot content is as expected
     */
    public function testRelaisContent()
    {
        $task = static::api()->getTask();
        //open task for whole testcase
        static::api()->setTaskToEdit($task->id);
        //get segment list
        $segments = static::api()->getSegments();
        $segments = array_map(function ($segment) {
            return $segment;
        }, $segments);
        $relais = array_column($segments, 'relais', 'segmentNrInTask');

        $expected = [
            '1' => 'XXXXXX Tamiz de cepillos rotativos. -Tamiz de cepillos rotativos- ¡Tamiz de cepillos rotativos! Denominación <div class="single 782069643d2232303938222f internal-tag ownttip"><span title="&lt;bookmarkstart name=&quot;Bezeichnung&quot;/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="2098" data-length="-1" class="full">&lt;bookmarkstart name=&quot;Bezeichnung&quot;/&gt;</span></div>Tamiz de cepillos <div class="single 782069643d2232303939222f internal-tag ownttip"><span title="&lt;bookmarkend name=&quot;Bezeichnung&quot;/&gt;" class="short">&lt;3/&gt;</span><span data-originalid="2099" data-length="-1" class="full">&lt;bookmarkend name=&quot;Bezeichnung&quot;/&gt;</span></div>rotativos<div class="single 782069643d2232313030222f internal-tag ownttip"><span title="" class="short">&lt;6/&gt;</span><span data-originalid="2100" data-length="-1" class="full"></span></div>Tamiz de cepillos <div class="open 672069643d22313230393022 internal-tag ownttip"><span title="&lt;figure&gt;" class="short">&lt;8&gt;</span><span data-originalid="12090" data-length="-1" class="full">&lt;figure&gt;</span></div>rotativos<div class="close 2f67 internal-tag ownttip"><span title="&lt;/figure&gt;" class="short">&lt;/8&gt;</span><span data-originalid="12090" data-length="-1" class="full">&lt;/figure&gt;</span></div><div class="open 672069643d22313230393122 internal-tag ownttip"><span title="" class="short">&lt;9&gt;</span><span data-originalid="12091" data-length="-1" class="full"></span></div>Tamiz de cepillos<div class="close 2f67 internal-tag ownttip"><span title="" class="short">&lt;/9&gt;</span><span data-originalid="12091" data-length="-1" class="full"></span></div> rotativos. Tamiz de <div class="open 672069643d22313230393222 internal-tag ownttip"><span title="" class="short">&lt;10&gt;</span><span data-originalid="12092" data-length="-1" class="full"></span></div>cepillos<div class="close 2f67 internal-tag ownttip"><span title="" class="short">&lt;/10&gt;</span><span data-originalid="12092" data-length="-1" class="full"></span></div> rotativos; tamiz de CEPpillos Rotativos ; Tamiz de cepillos rotativos; Tamiz de cepillos rotativos',
        ];
        $this->assertEquals($expected, $relais, 'Relais columns not filled as expected!');
    }
}

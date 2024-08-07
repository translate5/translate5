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

/**+
 * Create project with 2 tasks using importUpload_language and importUpload_type api endpoints.
 * On the backend side, the editor_Models_Import_DataProvider_Project data provider will be used to generate the file structure for the uploaded files.
 */
class Translate2080Test extends JsonTestAbstract
{
    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', ['en', 'mk'], static::getTestCustomerId())
            ->addUploadFile('en.xlf')
            ->addUploadFile('mk.xlf')
            ->addProperty('relaisLang', 'it')
            ->addProperty('importUpload_language', ['en', 'mk', 'it'])
            ->addProperty('importUpload_type', ['workfiles', 'workfiles', 'pivot'])
            ->setNotToFailOnError();
    }

    /***
     * Create 2 project tasks. The second task will have "it" as relais language.
     * The relais file will be matched based on the name.
     */
    public function testImportProjectWithRelais()
    {
        $task = static::api()->getTask();
        $this->assertEquals(count($task->projectTasks), 2, 'No project tasks created.');
    }
}

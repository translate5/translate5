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

/**
 */
class Translate2551Test extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init'
    ];

    protected static array $requiredRuntimeOptions = [
    ];
 
    protected static bool $setupOwnCustomer = false;
    
    protected static string $setupUserLogin = 'testmanager';
    
    protected static function setupImport(Config $config): void
    {
        $file = 'TRANSLATE-2551-de-en.xlf';
        static::api()->addFile('fileReimport', static::api()->getFile($file), "application/xml");

        self::api()->post('editor/file/reimport',[
            'oldFileId' => 1,
        ]);
    }

}

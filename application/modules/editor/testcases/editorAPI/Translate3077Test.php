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

/***
 * This will create a task, resource and customer and assign the resource by default as pivot resource.
 * Based on runtimeOptions.import.autoStartPivotTranslations , the pivot pre-translation will be auto queue
 */
class Translate3077Test extends editor_Test_JsonTest
{
    protected static array $requiredPlugins = [
        'editor_Plugins_DeepL_Init'
    ];

    protected static array $requiredRuntimeOptions = [
        'plugins.DeepL.server' => null, //null checks for no concrete value but if not empty
        'plugins.DeepL.authkey' => null, //null checks for no concrete value but if not empty
    ];
    
    protected static bool $setupOwnCustomer = true;

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $relaisLangRfc = 'it';
        $customerId = static::getTestCustomerId();
        $config
            ->addLanguageResource('deepl', null, $customerId, $sourceLangRfc, $relaisLangRfc)
            ->addProperty('customerPivotAsDefaultIds', [ $customerId ]);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFolder('testfiles')
            ->addProperty('relaisLang', $relaisLangRfc)
            ->setToEditAfterImport();
    }

    /***
     * Check if the segment pivot is pretranslated
     */
    public function testPivotAutoPretranslation() {
        //get segment list
        $segments = $this->api()->getSegments();

        $this->assertCount(1, $segments);

        foreach($segments as $segment) {
            static::assertNotEmpty($segment->relais,'The pivot field for the segment is empty');
        }

    }
}

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
use MittagQI\Translate5\Test\Import\LanguageResource;

/**
 *
 */
class Translate3052Test extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_ZDemoMT_Init',
        'editor_Plugins_MatchAnalysis_Init',
    ];

    protected static array $requiredRuntimeOptions = [
    ];
 
    protected static bool $setupOwnCustomer = true;
    
    protected static string $setupUserLogin = 'testmanager';

    private static LanguageResource $dummyMt;
    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $customerId = static::$ownCustomer->id;

        self::$dummyMt = $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'API Testing::ZDemoMT_Translate3052Test_one');

        $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'API Testing::ZDemoMT_Translate3052Test_two');
        $config
            ->addPretranslation()
            ->setProperty('pretranslateMt', 1);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFolder('testfiles')
            ->setProperty('taskName', 'API Testing::Translate3052Test');
    }
    
    /**
     */
    public function testCleanResource()
    {
        // check if 2 resources are available to the task
        $before = $this->getTask()->getAvaliableResources(self::api());
        self::assertEquals(2,count($before));

        $params = [
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'customerPivotAsDefaultIds' => [],
            'customerIds' => [static::getTestCustomerId()],
            'useAsGlossarySource' => false,
            'id' => self::$dummyMt->getId()
        ];

        // remove the "ownCustomer" from the customers list in one of the resources. This shoup throw an exception
        // since the resource is assigned to the task via the customer
        self::api()->putJson('editor/languageresourceinstance/'.self::$dummyMt->getId(),$params,expectedToFail: true);

        $response = static::api()->getLastResponseDecodeed();
        self::assertEquals($response->status,422,'Exception should be thrown on removing customer with assigned tasks');
        self::assertEquals($response->errorCode,'E1447','Exception event code does not match the expected');

        // do the same as above, but now with the forced flag. This will remove all task association matching the
        // removed customer
        self::api()->postJson('editor/languageresourceinstance/'.self::$dummyMt->getId(),[
            'data' => json_encode($params, JSON_THROW_ON_ERROR),
            'forced' => true
        ],encodeParamsAsData: false);

        $before = $this->getTask()->getAvaliableResources(self::api());
        self::assertEquals(1,count($before));
    }
    
}

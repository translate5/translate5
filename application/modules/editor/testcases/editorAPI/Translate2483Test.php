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

/**+
 * This test will:
 * - create 2 temporary customers
 * - insert customer specific config for source customer, copy the values to target customer using the copy customer operation, and compare if the values are the same
 * - insert user defaults for source customer, copy the values to target, compare the values between them
 * - remove the temporary customers
 */
class Translate2483Test extends editor_Test_JsonTest {

    public static $sourceCustomerId;
    public static $targetCustomerId;

    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }

    /***
     * Creat the source and target customers
     * @return void
     */
    public static function testCreateCustomers(){

        $customer = self::$api->requestJson('editor/customer/', 'POST',[
            'name'=>'API Testing::TRANSLATE-2717-source',
            'number'=>uniqid('API Testing::ResourcesLogCustomer'),
        ]);

        self::assertIsObject($customer,'Unable to create the source customer');
        self::$sourceCustomerId = $customer->id;

        $customer = self::$api->requestJson('editor/customer/', 'POST',[
            'name'=>'API Testing::TRANSLATE-2717-target',
            'number'=>uniqid('API Testing::ResourcesLogCustomer'),
        ]);
        self::assertIsObject($customer,'Unable to create the target customer');
        self::$targetCustomerId = $customer->id;
    }

    /***
     * Add customer specific configs to the source customer and copy the configs from the source to the target customer
     * @return void
     */
    public static function testCopyCustomerConfig(){
        $setConfigs = [];
        $setConfigs['runtimeOptions.editor.showConfirmFinishTaskPopup'] = '1';
        $setConfigs['runtimeOptions.lengthRestriction.sizeUnit'] = 'pixel';
        $setConfigs['runtimeOptions.workflow.default.reviewing.defaultDeadlineDate'] = '3.23';

        foreach ($setConfigs as $name => $value){
            // add config to the source customer
            self::addCustomerConfig($name,$value,self::$sourceCustomerId);
        }

        // copy the configs from the source to the target customer
        self::$api->requestJson('editor/customer/'.self::$targetCustomerId.'/copy/operation', 'POST',[],[
            'copyConfigCustomer'=>self::$sourceCustomerId
        ]);

        // get all target customer configs
        $configs = self::$api->requestJson('editor/config/', 'GET',[
            'customerId'=>self::$targetCustomerId,
        ]);

        $matches = 0;
        foreach ($configs as $config) {
            if(isset($setConfigs[$config->name]) && $setConfigs[$config->name] === $config->value){
                $matches++;
            }
        }
        self::assertEquals($matches,count($setConfigs),'Not all of the configs are copied to the target customer');
    }

    /***
     * Add default user associations for the source customer, copy the values to target customer and compare
     * the user defaults between them after copy
     * @return void
     */
    public static function testCopyUserDefaults(){
        self::addUserDefaults([
            "deadlineDate"=>1,
            "sourceLang"=>'en',
            "targetLang"=>'mk',
            "workflowStepName"=>"translation",
        ]);
        self::addUserDefaults([
            "deadlineDate"=>2,
            "sourceLang"=>'en',
            "targetLang"=>'de',
            "workflowStepName"=>"reviewing",
        ]);
        self::addUserDefaults([
            "deadlineDate"=>3,
            "sourceLang"=>'en',
            "targetLang"=>'it',
            "workflowStepName"=>"translatorCheck"
        ]);

        // copy the default user assoc from the source to the target customer
        self::$api->requestJson('editor/customer/'.self::$targetCustomerId.'/copy/operation', 'POST',[],[
            'copyDefaultAssignmentsCustomer'=>self::$sourceCustomerId
        ]);

        $sourceValues = self::$api->requestJson('editor/userassocdefault/', 'GET',[
            'filter' => '[{"value":"'.self::$sourceCustomerId.'","property":"customerId","operator":"eq"}]',
        ]);

        array_map(function ($r){
            unset($r->id);
            unset($r->customerId);
            return $r;
        },$sourceValues);
        $targetValues = self::$api->requestJson('editor/userassocdefault/', 'GET',[
            'filter' => '[{"value":"'.self::$targetCustomerId.'","property":"customerId","operator":"eq"}]',
        ]);
        array_map(function ($r){
            unset($r->id);
            unset($r->customerId);
            return $r;
        },$targetValues);

        self::assertEquals($targetValues,$sourceValues,'Not all user default assignments are copied for the target customer.');
    }

    /***
     * Insert customer specific config
     *
     * @param string $name
     * @param string $value
     * @param int $customerId
     * @return void
     */
    private static function addCustomerConfig(string $name,string $value,int $customerId){
        self::$api->requestJson('editor/config/', 'put',[
            'data' => json_encode([
                'value' => $value,
                'customerId' => $customerId,
                'name' => $name
            ])
        ]);
    }

    private static function addUserDefaults(array $data){
        $user = self::assertLogin('testmanager');
        $newData = array_merge([
            "customerId"=>self::$sourceCustomerId,
            "deadlineDate"=>1,
            "workflow"=>"default",
            "sourceLang"=>'en',
            "targetLang"=>'mk',
            "userGuid"=>$user->user->userGuid,
            "workflowStepName"=>"translation",
            "segmentrange"=>"",
            "trackchangesShow"=>1,
            "trackchangesShowAll"=>1,
            "trackchangesAcceptReject"=>1
        ],$data);
         self::$api->requestJson('editor/userassocdefault/', 'POST',$newData);
    }

    public static function tearDownAfterClass(): void {
        self::$api->login('testmanager');
        self::$api->requestJson('editor/customer/'.self::$sourceCustomerId, 'DELETE');
        self::$api->requestJson('editor/customer/'.self::$targetCustomerId, 'DELETE');
    }
}

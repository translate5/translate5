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

/**
 * Testcase for TRANSLATE-2266 Custom file filter configuration with GUI / BCONF Management
 * For details see the issue.
 */
class OkapiBconfTest extends editor_Test_JsonTest {

    private static editor_Plugins_Okapi_Bconf_Entity $bconf;
    private static int $bconfId = 0;
    private static Zend_Config $okapiConf;
    public const OKAPI_CONFIG = 'runtimeOptions.plugins.Okapi';

    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::$api->login('testmanager');
        self::assertLogin('testmanager');

        $appState = self::$api->requestJson('editor/index/applicationstate');
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case');

        // Test essential configs
        $okapiConf = self::$okapiConf = Zend_Registry::get('config')->runtimeOptions->plugins->Okapi;

        /** @var editor_Plugins_Okapi_Connector $api */
        $api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');

        self::assertNotEmpty($okapiConf->dataDir, self::OKAPI_CONFIG . ".dataDir not set");
        self::assertNotEmpty($api->getApiUrl(), self::OKAPI_CONFIG . ".api.url not set");

        $t5defaultImportBconf = editor_Utils::joinPath(editor_Plugins_Okapi_Init::getDataDir(), editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT);
        self::assertFileExists($t5defaultImportBconf,
            "File '$t5defaultImportBconf' missing. As the Translate5 provided default import .bconf file for Okapi Task Imports it must exist!");

        // Needed for localized error messages in Unit Test like ZfExtended_NoAccessException
        if(!Zend_Registry::isRegistered('Zend_Locale')){
            Zend_Registry::set('Zend_Locale', new Zend_Locale('en'));
        }
    }

    /***
     * Unpack, Pack a Bconf to verify the Bconf Parser and Packer
     */
    public function test10_BconfImportExport() {
        $input = new SplFileInfo(self::$api->getFile('minimal/batchConfiguration.t5.bconf'));
        $bconfName = 'Translate2266BconfTest-' . time() . '.bconf';
        self::$api->addFile('bconffile', $input->getPathname(), 'application/octet-stream');
        // Run as api test that if case runtimeOptions.plugins.Okapi.dataDir is missing it's created as webserver user
        $res = self::$api->requestJson('editor/plugins_okapi_bconf/uploadbconf', 'POST', [
            'name' => $bconfName,
        ]);
        self::assertEquals(true, $res?->success, 'uploadbconf did not respond with success:true');
        self::$bconfId = $res->id;
        self::$bconf = new editor_Plugins_Okapi_Bconf_Entity();
        self::$bconf->load(self::$bconfId);
        self::assertEquals(self::$bconf->getName(), $bconfName, "Imported bconf's name is not '$bconfName' but '" . self::$bconf->getName() . "'");
        $output = self::$bconf->getPath();

        $failureMsg = "Original and repackaged Bconfs do not match\nInput was '$input', Output was '$output";
        self::assertFileEquals($input, $output, $failureMsg);
    }

    /***
     * Test if new srx files are packed into bconf.
     * @depends test_BconfImportExport
     */
    public function test20_SrxUpload() {
        $api = self::$api;
        $bconf = self::$bconf;
        $id = $bconf->getId();

        // Upload sourceSRX
        $api->addFile('srx', $api->getFile('srx/idSource.srx'), 'application/octet-stream');
        $res = $api->request("editor/plugins_okapi_bconf/uploadsrx?id=$id", 'POST', [
            'purpose' => 'source',
        ]);
        self::assertEquals(200, $res->getStatus());
        // Upload targetSRX
        $api->addFile('srx', $api->getFile('srx/idTarget.srx'), 'application/octet-stream');
        $res = $api->request("editor/plugins_okapi_bconf/uploadsrx?id=$id", 'POST', [
            'purpose' => 'target',
        ]);
        self::assertEquals(200, $res->getStatus());

        $res = $api->request("editor/plugins_okapi_bconf/downloadbconf?id=$id");
        self::assertEquals(200, $res->getStatus());
        $bconfString = $res->getBody();

        $res = $api->request("editor/plugins_okapi_bconf/downloadsrx?id=$id&purpose=source");
        self::assertEquals(200, $res->getStatus());
        $sourceSrx = $res->getBody();
        self::assertStringContainsString($sourceSrx, $bconfString, "sourceSrx update failed for bconf #$id");

        $targetSrx = $api->request("editor/plugins_okapi_bconf/downloadsrx?id=$id&purpose=target")->getBody();
        self::assertStringContainsString($targetSrx, $bconfString, "targetSrx update failed for bconf #$id");
    }

    /**
     * @depends test10_BconfImportExport
     */
    public function test30_AutoImportAndVersionUpdate() {
        if(!self::isMasterTest()){
            self::assertTrue(true);
            fwrite(STDERR, "\n" . __FUNCTION__ . " runs only in master test to not mess with important default bconf.\n");
            return;
        }
        $bconf = self::$bconf;
        $bconf->importDefaultWhenNeeded();

        $systemBconf = new editor_Plugins_Okapi_Bconf_Entity();
        $systemBconf->loadRow('name = ? ', editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);
        $systemBconf->setName('NotSystemBconfAnymore-' . time() . rand()); // Unmark as system bconf
        $systemBconf->save();

        // Ensure system bconf is imported when not present
        $total = $systemBconf->getTotalCount();
        $bconf->importDefaultWhenNeeded();
        $newTotal = $systemBconf->getTotalCount();

        $autoImportFailureMsg = 'AutoImport of missing system bconf failed.';
        self::assertEquals($total + 1, $newTotal, $autoImportFailureMsg . ' Totalcount not increased');
        $newSystemBconf = new editor_Plugins_Okapi_Bconf_Entity();
        $expectedName = editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME;
        $newSystemBconf->loadRow('name = ?', $expectedName);
        self::assertEquals($expectedName, $newSystemBconf->getName(), $autoImportFailureMsg . " No record name matches '$expectedName'");
        $newBconfFile = $newSystemBconf->getPath();
        self::assertFileExists($newBconfFile, $autoImportFailureMsg . " File '$newBconfFile' does not exist");

        // Ensure system bconf dir can't be deleted
        $e = null;
        try {
            $newSystemBconf->delete();
        } catch(ZfExtended_NoAccessException $e){
        } finally {
            self::assertNotNull($e, 'Deleting the system bconf directory was not prevented by ZfExtended_NoAccessException');
        }

        // Ensure system bconf is updated on version mismatch when using it
        $version = (int)$newSystemBconf->getVersionIdx();
        self::assertGreaterThan(0, $version, 'Bconf version is 0');
        $newSystemBconf->setVersionIdx($version - 1);
        $newSystemBconf->save();
        $newSystemBconf->repackIfOutdated();
        self::assertEquals(editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX, $newSystemBconf->getVersionIdx(), 'Bconf version was not updated after being outdated');
        self::assertFileExists($newSystemBconf->getPath(), $autoImportFailureMsg . " Version Auto Update failed. File '$newBconfFile' does not exist.");

        // Reset to initial system bconf, delete  newly imported
        $newSystemBconfId = $newSystemBconf->getId();
        $newSystemBconfDir = $newSystemBconf->getDataDirectory();
        $newSystemBconf->setName('ToDelete-' . time() . rand());
        $newSystemBconf->save();
        $systemBconf->setName(editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME);
        $systemBconf->save();

        try {
            $newSystemBconf->delete();
        } catch(ZfExtended_NoAccessException $e){ }
        $e = null;
        try {
            $newSystemBconf->load($newSystemBconfId);
        } catch(ZfExtended_Models_Entity_NotFoundException $e){
        } finally {
            self::assertNotNull($e, "Deleting the bconf with id '$newSystemBconfId' did not work");
        }
    }

    /***
     * Verify Task Import using Okapi is working with the LEK_okapi_bconf based Bconf management
     * @depends test30_AutoImportAndVersionUpdate AutoImport must work for initializing LEK_okapi_bconf after installation
     */
    public function test40_OkapiTaskImport() {
        try {
            /** @var editor_Plugins_Okapi_Connector $api */
            $api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');

            $msg = "Okapi Longhorn not reachable.\nCan't GET HTTP Status 200 under '" . $api->getApiUrl() . "' (per {" . self::OKAPI_CONFIG . "}.api.url)";
            $longHornResponse = (new Zend_Http_Client($api->getApiUrl()))->request();
            self::assertTrue($longHornResponse->getStatus() === 200, $msg);
        } catch(Exception $e){
            self::fail($msg . "\n" . $e->getMessage());
        }

        $api = self::$api;
        $task = [
            'sourceLang' => 'de',
            'targetLang' => 'en',
            //'bconfId' omitted to test fallback to Okapi_Models_Bconf::getDefaultBconfId
        ];
        $api->addImportFile($api->getFile('workfiles/TRANSLATE-2266-de-en.txt'));
        $api->import($task);
        $api->deleteTask();
    }

    /***
     * Verify Task Import using Okapi is working with the LEK_okapi_bconf based Bconf management
     * @depends test40_OkapiTaskImport
     */
    public function test50_OkapiTaskImportWithBconfIdAndMultipleFiles() {
        $api = self::$api;
        $task = [
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'bconfId'    => self::$bconf->getDefaultBconfId(),
        ];
        $api->addImportFile($api->getFile('workfiles/TRANSLATE-2266-de-en.txt'));
        $api->addImportFile($api->getFile('workfiles/TRANSLATE-2266-de-en-2.txt'));
        $api->import($task);
        $api->deleteTask();
    }

    /***
     * Verify ImportArchives with bconfs are supported
     * @depends test50_OkapiTaskImportWithBconfIdAndMultipleFiles
     */
    public function test55_BconfInImportArchive() {
        $api = self::$api;
        $task = [
            'sourceLang' => 'de',
            'targetLang' => 'en',
        ];
        $api->addImportFile($api->getFile('workfiles/BconfWithin.zip'));
        $api->import($task);
        $task = $api->getTask();
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        $segments= $api->requestJson('editor/segment?page=1&start=0&limit=3');
        // Leave task so it becomes deleteable
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));

        $this->assertSegmentsEqualsJsonFile('expectedSegments.json', $segments, 'Imported segments are not as expected!');
        $api->deleteTask();
    }

    /***
     * Provoke Exceptions via invalid inputs
     * @depends test10_BconfImportExport
     */
    public function test60_InvalidFiles() {
        $bconf = new editor_Plugins_Okapi_Bconf_Entity();
        $testDir = NULL;
        try {
            $bconf->setId(0);
            $testDir = $bconf->getDataDirectory();
            if(!is_dir($testDir)){
                mkdir($testDir); // Created as test user for unit test. Make sure to remove in every circumstance!
            }
            $filesToTest = [
                'Signature.bconf',
                'Version.bconf',
                'MalformedReferences.bconf',
                'NoReferences.bconf',
                'NoPipeline.bconf',
                'NoExtMapping.bconf',
            ];
            foreach($filesToTest as $file){
                $e = null;
                try {
                    $bconf->unpack(self::$api->getFile("invalid/$file"));
                } catch(ZfExtended_UnprocessableEntity|editor_Plugins_Okapi_Exception $e){
                    self::assertNotNull($e, "Did not reject invalid/$file with ZfExtended_UnprocessableEntity.");
                }
            }
        } catch(Exception $outerEx){
        } finally {
            // Make sure to delete directory
            if($testDir !== NULL){
                /** @var ZfExtended_Controller_Helper_Recursivedircleaner $cleaner */
                $cleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('Recursivedircleaner');
                $cleaner->delete($testDir);
            }
            if(!empty($outerEx)){
                throw $outerEx;
            }
        }
    }

    /**
     * @depends test10_BconfImportExport
     */
    public function test70_DeleteBconf() {
        $bconf = self::$bconf;
        $bconf->load(self::$bconfId);

        $bconfDir = $bconf->getDataDirectory();
        $bconf->delete(); // delete record, which deletes directory as well
        self::assertDirectoryDoesNotExist($bconfDir);
    }
}

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
 * @see Translate2432Test
 */
class Translate2266BconfTest extends editor_Test_JsonTest {
    private static editor_Plugins_Okapi_Models_Bconf $bconf;
    private static int $bconfId = 0;
    private static Zend_Config $okapi;
    public const OKAPI_CONFIG = 'runtimeOptions.plugins.Okapi';

    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::$api->login('testmanager');
        self::assertLogin('testmanager');
        $appState = self::$api->requestJson('editor/index/applicationstate');
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case');

        // Test essential configs
        $okapi = self::$okapi = Zend_Registry::get('config')->runtimeOptions->plugins->Okapi;
        self::assertNotEmpty($okapi->dataDir, self::OKAPI_CONFIG . ".dataDir not set");
        self::assertNotEmpty($okapi->api->url, self::OKAPI_CONFIG . ".api.url not set");

        $t5defaultImportBconf = editor_Utils::joinPath(editor_Plugins_Okapi_Init::getOkapiDataFilePath(), editor_Plugins_Okapi_Models_Bconf::SYSTEM_BCONF_IMPORTFILE);
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
    public function test_BconfImportExport() {
        $input = new SplFileInfo(self::$api->getFile('testfiles/minimal/batchConfiguration.t5.bconf'));
        $postFile = [ // loose definition https://www.php.net/manual/features.file-upload.post-method.php
            "name"     => 'Translate2266BconfTest-' . time() . '.bconf',
            "tmp_name" => $input->getPathname(),
        ];
        $params = [];

        // unpack and pack
        self::$bconf = new editor_Plugins_Okapi_Models_Bconf($postFile, $params);
        self::$bconfId = self::$bconf->getId();
        $output = self::$bconf->getFilePath();

        $failureMsg = "Original and repackaged Bconfs do not match\nInput was '$input', Output was '$output";
        self::assertFileEquals($input, $output, $failureMsg);

    }

    /***
     * Test if new srx files are packed into bconf.
     * @depends test_BconfImportExport
     */
    public function test_SrxUpload() {
        $bconf = self::$bconf;
        $sourceSrx = $bconf->srxNameFor('source');
        $targetSrx = $bconf->srxNameFor('target');

        $sourceSrxInput = new SplFileInfo(self::$api->getFile('testfiles/srx/idSource.srx'));
        $targetSrxInput = new SplFileInfo(self::$api->getFile('testfiles/srx/idTarget.srx'));

        copy($sourceSrxInput, $bconf->getFilePath(fileName: $sourceSrx));
        copy($targetSrxInput, $bconf->getFilePath(fileName: $targetSrx));

        $bconf->file->pack();

        $bconfPath = $bconf->getFilePath();
        $bconfAfterUpload = file_get_contents($bconfPath);
        self::assertStringContainsString(file_get_contents($sourceSrxInput), $bconfAfterUpload, "sourceSrx update failed in '$bconfPath'");
        self::assertStringContainsString(file_get_contents($targetSrxInput), $bconfAfterUpload, "targetSrx update failed in '$bconfPath'");

    }

    /**
     * @depends test_BconfImportExport
     */
    public function test_AutoImportAndVersionUpdate() {
        $bconf = self::$bconf;
        $bconf->importDefaultWhenNeeded();

        $systemBconf = new editor_Plugins_Okapi_Models_Bconf();
        $systemBconf->loadRow('name = ? ', $bconf::SYSTEM_BCONF_NAME);
        $systemBconf->setName('NotSystemBconfAnymore-' . time() . rand()); // Unmark as system bconf
        $systemBconf->save();

        // Ensure system bconf is imported when not present
        $total = $systemBconf->getTotalCount();
        $bconf->importDefaultWhenNeeded();
        $newTotal = $systemBconf->getTotalCount();

        $autoImportFailureMsg = 'AutoImport of missing system bconf failed.';
        self::assertEquals($total + 1, $newTotal, $autoImportFailureMsg . ' Totalcount not increased');
        $newSystemBconf = new editor_Plugins_Okapi_Models_Bconf();
        $newSystemBconf->loadRow('name = ? ', $bconf::SYSTEM_BCONF_NAME);
        self::assertEquals($newSystemBconf->getName(), $bconf::SYSTEM_BCONF_NAME, $autoImportFailureMsg . " No record name matches '" . $bconf::SYSTEM_BCONF_NAME . "'");
        $newBconfFile = $newSystemBconf->getFilePath();
        self::assertFileExists($newBconfFile, $autoImportFailureMsg . " File '$newBconfFile' does not exist");

        // Ensure system bconf dir can't be deleted
        $e = null;
        try {
            $newSystemBconf->deleteDirectory($newSystemBconf->getId());
        } catch(ZfExtended_NoAccessException $e){
        } finally {
            self::assertNotNull($e, 'Deleting the system bconf directory was not prevented by ZfExtended_NoAccessException');
        }

        // Ensure system bconf is updated on version mismatch
        $version = (int)$newSystemBconf->getVersionIdx();
        self::assertGreaterThan(0, $version, 'Bconf version is 0');
        $newSystemBconf->setVersionIdx($version - 1);
        $newSystemBconf->save();

        unlink($newBconfFile);
        self::assertFileDoesNotExist($newBconfFile, "Could not delete '$newBconfFile' for testing purpose");
        $bconf->importDefaultWhenNeeded();
        self::assertFileExists($newBconfFile, $autoImportFailureMsg . " Version Auto Update failed. File '$newBconfFile' does not exist.");

        // Reset to initial system bconf, delete  newly imported
        $newSystemBconfId = $newSystemBconf->getId();
        $newSystemBconfDir = $newSystemBconf->getDir();
        $newSystemBconf->setName('ToDelete-' . time() . rand());
        $newSystemBconf->save();
        $systemBconf->setName($bconf::SYSTEM_BCONF_NAME);
        $systemBconf->save();

        $newSystemBconf->deleteDirectory($newSystemBconf->getId());
        self::assertDirectoryDoesNotExist($newSystemBconfDir, "Could not delete directory'$newSystemBconfDir' for testing purpose");

        $newSystemBconf->delete();
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
     * @depends test_AutoImportAndVersionUpdate AutoImport must work for initializing LEK_okapi_bconf after installation
     */
    public function test_OkapiTaskImport() {
        try {
            $msg = "Okapi Longhorn not reachable.\nCan't GET HTTP Status 200 under '" . self::$okapi->api->url . "' (per {" . self::OKAPI_CONFIG . "}.api.url)";
            $longHornResponse = (new Zend_Http_Client($this::$okapi->api->url))->request();
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
        $api->addImportFile($api->getFile('testfiles/workfiles/TRANSLATE-2266-de-en.txt'));
        $api->import($task);
        /** @var editor_Models_Task $realTask */
        $realTask = ZfExtended_Factory::get('editor_Models_Task');
        $realTask->load($api->getTask()->id);
        /** @var editor_Models_Task_Remover $remover */
        $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', [$realTask]);
        $remover->removeForced();
    }

    /***
     * Verify Task Import using Okapi is working with the LEK_okapi_bconf based Bconf management
     * @depends test_OkapiTaskImport
     */
    public function test_OkapiTaskImportWithBconfIdAndMultipleFiles() {
        try {
            $msg = "Okapi Longhorn not reachable.\nCan't GET HTTP Status 200 under '" . self::$okapi->api->url . "' (per {" . self::OKAPI_CONFIG . "}.api.url)";
            $longHornResponse = (new Zend_Http_Client($this::$okapi->api->url))->request();
            self::assertTrue($longHornResponse->getStatus() === 200, $msg);
        } catch(Exception $e){
            self::fail($msg . "\n" . $e->getMessage());
        }

        $api = self::$api;
        $task = [
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'bconfId'    => self::$bconf->getDefaultBconfId(),
        ];
        $api->addImportFile($api->getFile('testfiles/workfiles/TRANSLATE-2266-de-en.txt'));
        $api->addImportFile($api->getFile('testfiles/workfiles/TRANSLATE-2266-de-en-2.txt'));
        $api->import($task);
    }

    /***
     * Provoke Exceptions via invalid inputs
     * @depends test_BconfImportExport
     */
    public function test_InvalidFiles() {
        $bconf = self::$bconf;
        $api = self::$api;
        $invalid = 'testfiles/invalid/';
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
                $bconf->file->unpack($api->getFile($invalid.$file ));
            } catch(ZfExtended_UnprocessableEntity $e){
            } finally {
                self::assertNotNull($e, "Did not reject ${invalid}$file with ZfExtended_UnprocessableEntity.");
            }
        }
    }

    /**
     * @depends test_BconfImportExport
     */
    public function test_DeleteBconf() {
        $bconf = self::$bconf;
        $bconf->load(self::$bconfId);

        $bconfDir = $bconf->getDir();
        $bconf->deleteDirectory(self::$bconfId);
        $bconf->delete(); // delete record
        self::assertDirectoryDoesNotExist($bconfDir);
    }

    public static function tearDownAfterClass(): void {
        parent::tearDownAfterClass();
    }
}

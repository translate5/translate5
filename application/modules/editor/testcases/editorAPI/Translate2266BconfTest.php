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
 * Testcase for TRANSLATE-2266 Mixing XLF id and rid values led to wrong tag numbering
 * For details see the issue.
 */
class Translate2266BconfTest extends editor_Test_JsonTest {
    private static ?editor_Plugins_Okapi_Models_Bconf $bconf;
    private static ?Zend_Config $okapi;
    public final const OKAPI_CONFIG = 'runtimeOptions.plugins.Okapi';

    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);

        $appState = self::assertAppState();

        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case');

        self::assertNeededUsers();

        //self::assertLogin('testapiuser');
        self::$api->login('testmanager');
        self::assertLogin('testmanager');

        self::$okapi = null;
        self::$bconf = null;

    }

    // TODO: Abort whole testcase when this one fails.
    public function test_ConfigsAndDefaults() {
        $okapi = self::$okapi = Zend_Registry::get('config')->runtimeOptions->plugins->Okapi;
        $neededConfigs = [
            self::OKAPI_CONFIG . ".dataDir" => $okapi?->dataDir, // QUIRK cannot read via $api->testConfig()
            self::OKAPI_CONFIG . ".api.url" => $okapi?->api?->url,
        ];
        foreach($neededConfigs as $name => $value){
            !$value && self::fail("Needed config '$name' is not set");
        }

        $t5defaultImportBconf = editor_Utils::joinPath(editor_Plugins_Okapi_Init::getOkapiDataFilePath(), 'okapi_default_import.bconf');

        self::assertFileExists($t5defaultImportBconf,
            "File '$t5defaultImportBconf' missing. As the Translate5 provided default import .bconf file for Okapi Task Imports it must exist!");


    }

    /***
     * Unpack, Pack a Bconf to verify the Bconf Parser and Packer
     */
    public function test_BconfImportExport() {

        $input = new SplFileInfo(self::$api->getFile('testfiles/minimal/batchConfiguration.bconf'));
        $postFile = [ // loose definition https://www.php.net/manual/features.file-upload.post-method.php
            "name"     => time() . '.bconf',
            "tmp_name" => $input->getPathname(),
        ];
        $params = [];

        // unpack and pack
        self::$bconf = new editor_Plugins_Okapi_Models_Bconf($postFile, $params);
        $output = self::$bconf->getFilePath();

        $failureMsg = "Original and repackaged Bconfs do not match\nInput was '$input', Output was '$output";
        self::assertFileEquals($input, $output, $failureMsg);
    }

    /***
     * Test if new srx files are packed into bconf.
     */
    public function test_SrxUpload() {
        $bconf = self::$bconf;
        $sourceSrx = $bconf->srxNameFromPipeline('source');
        $targetSrx = $bconf->srxNameFromPipeline('target');

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

    /***
     * Verify Task Import using Okapi is working with the LEK_okapi_bconf based Bconf management
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
            'bconfId' => self::$bconf->getDefaultBconfId()
        ];
        $api->addImportFile($api->getFile('testfiles/workfiles/TRANSLATE-2266-de-en.txt'));
        //$api->addImportFile($api->getFile('testfiles/workfiles/TRANSLATE-2266-2-de-en.txt'));
        $api->import($task);
    }

    /***
     * Provoke Exceptions via invalid inputs
     */
    public function test_InvalidFiles() {
        self::assertTrue(true);
    }

    /**
     * @depends test_BconfImportExport
     */
    public function test_DeleteBconf() {
        $bconf = self::$bconf;

        $bconfDir = $bconf->getDataDirectory();
        $bconf->deleteDirectory($bconf->getId());
        $bconf->delete(); // delete record
        self::assertDirectoryDoesNotExist($bconfDir);
    }

    public static function tearDownAfterClass(): void {}
}

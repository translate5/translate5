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

use MittagQI\Translate5\Plugins\Okapi\Service;
use MittagQI\Translate5\Test\Import\Bconf;
use MittagQI\Translate5\Test\Import\Config;

/**
 * Testcase for TRANSLATE-2266 Custom file filter configuration with GUI / BCONF Management
 * For details see the issue.
 */
class OkapiBconfTest extends editor_Test_JsonTest
{
    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
    ];

    private static Bconf $testBconf;

    private static editor_Plugins_Okapi_Bconf_Entity $bconf;

    /**
     * Just imports a bconf to test with
     */
    protected static function setupImport(Config $config): void
    {
        // TODO FIXME: still neccessary ?
        if (! Zend_Registry::isRegistered('Zend_Locale')) {
            Zend_Registry::set('Zend_Locale', new Zend_Locale('en'));
        }

        static::$testBconf = $config->addBconf('TestBconfMinimal', 'minimal/batchConfiguration.t5.bconf');
    }

    public function test10_ConfigurationAndApi()
    {
        $conf = Zend_Registry::get('config');
        $okapiConf = $conf->runtimeOptions->plugins->Okapi;
        /* @var Service $service */
        $service = editor_Plugins_Okapi_Init::createService('okapi', $conf);

        self::assertNotEmpty($okapiConf->dataDir, 'runtimeOptions.plugins.Okapi.dataDir not set');
        self::assertNotEmpty($service->getConfiguredServiceUrl($okapiConf->serverToUse, false), 'runtimeOptions.plugins.Okapi.api.url not set');

        $t5defaultImportBconf = editor_Utils::joinPath(editor_Plugins_Okapi_Init::getDataDir(), editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT);
        self::assertFileExists(
            $t5defaultImportBconf,
            "File '$t5defaultImportBconf' missing. As the Translate5 provided default import .bconf file for Okapi Task Imports it must exist!"
        );

        static::$bconf = new editor_Plugins_Okapi_Bconf_Entity();
        static::$bconf->load(static::$testBconf->getId());
        static::assertStringStartsWith('TestBconfMinimal', static::$bconf->getName(), 'Imported bconf\'s name is not like ' . 'TestBconfMinimal' . ' but ' . static::$bconf->getName());

        $input = static::api()->getFile('minimal/batchConfiguration.t5.bconf');
        $output = static::$bconf->getPath();
        $failureMsg = "Original and repackaged Bconfs do not match\nInput was '$input', Output was '$output";
        self::assertFileEquals($input, $output, $failureMsg);

        self::assertTrue($service->check());
    }

    /**
     * Test if new srx files are packed into bconf.
     * @depends test10_ConfigurationAndApi
     */
    public function test20_SrxUpload()
    {
        $bconf = static::$bconf;
        $id = $bconf->getId();

        // Upload sourceSRX
        static::api()->addFile('srx', static::api()->getFile('srx/idSource.srx'), 'application/octet-stream');
        $res = static::api()->post("editor/plugins_okapi_bconf/uploadsrx?id=$id", [
            'purpose' => 'source',
        ]);
        self::assertEquals(200, $res->getStatus());
        // Upload targetSRX
        static::api()->addFile('srx', static::api()->getFile('srx/idTarget.srx'), 'application/octet-stream');
        $res = static::api()->post("editor/plugins_okapi_bconf/uploadsrx?id=$id", [
            'purpose' => 'target',
        ]);
        self::assertEquals(200, $res->getStatus());

        $res = static::api()->get("editor/plugins_okapi_bconf/downloadbconf?id=$id");
        self::assertEquals(200, $res->getStatus());
        $bconfString = $res->getBody();

        $res = static::api()->get("editor/plugins_okapi_bconf/downloadsrx?id=$id&purpose=source");
        self::assertEquals(200, $res->getStatus());
        $sourceSrx = $res->getBody();
        self::assertStringContainsString($sourceSrx, $bconfString, "sourceSrx update failed for bconf #$id");

        $targetSrx = static::api()->get("editor/plugins_okapi_bconf/downloadsrx?id=$id&purpose=target")->getBody();
        self::assertStringContainsString($targetSrx, $bconfString, "targetSrx update failed for bconf #$id");
    }

    /**
     * @depends test10_ConfigurationAndApi
     */
    public function test30_AutoImportAndVersionUpdate()
    {
        if (! self::isMasterTest()) {
            self::markTestSkipped('runs only in master test to not mess with important default bconf.');

            return;
        }
        $bconf = static::$bconf;
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
        } catch (ZfExtended_NoAccessException $e) {
        } finally {
            self::assertNotNull($e, 'Deleting the system bconf directory was not prevented by ZfExtended_NoAccessException');
        }

        // Ensure system bconf is updated on version mismatch when using it
        $version = (int) $newSystemBconf->getVersionIdx();
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
        } catch (ZfExtended_NoAccessException $e) {
        }
        $e = null;

        try {
            $newSystemBconf->load($newSystemBconfId);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
        } finally {
            self::assertNotNull($e, "Deleting the bconf with id '$newSystemBconfId' did not work");
        }
    }

    /**
     * Verify Task Import using Okapi is working with the LEK_okapi_bconf based Bconf management
     */
    public function test40_OkapiTaskImport()
    {
        $config = static::getConfig();
        $config->import(
            $config
                ->addTask('de', 'en')
                ->addUploadFile('workfiles/TRANSLATE-2266-de-en.txt')
        );
    }

    /***
     * Verify Task Import using Okapi is working with the LEK_okapi_bconf based Bconf management
     * @depends test10_ConfigurationAndApi
     * @depends test40_OkapiTaskImport
     */
    public function test50_OkapiTaskImportWithBconfIdAndMultipleFiles()
    {
        // cleanup will be atomatically done on teardown
        $config = static::getConfig();
        $config->import(
            $config
                ->addTask('de', 'en')
                ->setImportBconfId(static::$bconf->getDefaultBconfId())
                ->addUploadFiles([
                    'workfiles/TRANSLATE-2266-de-en.txt',
                    'workfiles/TRANSLATE-2266-de-en-2.txt',
                ])
        );
    }

    /**
     * Verify ImportArchives with bconfs are supported
     * @depends test50_OkapiTaskImportWithBconfIdAndMultipleFiles
     */
    public function test55_BconfInImportArchive()
    {
        $config = static::getConfig();
        $config->import(
            $config
                ->addTask('de', 'en', -1, 'workfiles/BconfWithin-de-en.zip')
                ->setToEditAfterImport()
        );
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 3);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }

    /**
     * Provoke Exceptions via invalid inputs
     */
    public function test60_InvalidFiles()
    {
        $bconf = new editor_Plugins_Okapi_Bconf_Entity();
        $testDir = null;

        try {
            $bconf->setId(0);
            $testDir = $bconf->getDataDirectory();
            if (! is_dir($testDir)) {
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
            foreach ($filesToTest as $file) {
                $e = null;

                try {
                    $bconf->unpack(static::api()->getFile("invalid/$file"));
                } catch (ZfExtended_UnprocessableEntity|editor_Plugins_Okapi_Exception $e) {
                    self::assertNotNull($e, "Did not reject invalid/$file with ZfExtended_UnprocessableEntity.");
                }
            }
        } catch (Exception $outerEx) {
        } finally {
            // Make sure to delete directory
            if ($testDir !== null) {
                ZfExtended_Utils::recursiveDelete($testDir);
            }
            if (! empty($outerEx)) {
                throw $outerEx;
            }
        }
    }
}

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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Content;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\Fprm;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\OkapiFilterInventory;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\T5FilterInventory;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filters;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Pipeline;
use MittagQI\Translate5\Plugins\Okapi\Bconf\ResourceFile;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\Srx;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\T5SrxInventory;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use MittagQI\Translate5\Test\Import\Bconf;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * Testcase for the Custom file filter configuration with GUI / BCONF Management
 * This Test currently has uncommented parts due to the phenomenon, the target-srx is not respected on some systems and OKAPI always uses the source-SRX for segmentation
 * TODE FIXME: seperate the unit-test part and make a pure unit-test out of it ...
 */
class OkapiBconfFilterTest extends JsonTestAbstract
{
    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
    ];

    private static Bconf $testBconf;

    private static BconfEntity $bconf;

    protected static function setupImport(Config $config): void
    {
        // TODO FIXME: still neccessary ?
        if (! Zend_Registry::isRegistered('Zend_Locale')) {
            Zend_Registry::set('Zend_Locale', new Zend_Locale('en'));
        }
        static::$testBconf = $config->addBconf('TestBconfAllFilters', 'all-customized-filters.bconf');
    }

    /**
     * Verifies that all file-stores (FPRMs, SRXs, testfiles) are valid (= all referenced files present)
     */
    public function test10_BconfFilterInventories()
    {
        $this->assertEquals(true, OkapiFilterInventory::instance()->validate());
        $this->assertEquals(true, T5FilterInventory::instance()->validate());
        $this->assertEquals(true, T5SrxInventory::instance()->validate());
        $this->assertEquals(true, Filters::instance()->validate());
    }

    /**
     * Just imports the BCONF we need for our tests
     */
    public function test20_createEntity()
    {
        static::$bconf = new BconfEntity();
        static::$bconf->load(static::$testBconf->getId());
        static::assertStringStartsWith('TestBconfAllFilters', static::$bconf->getName(), 'Imported bconf\'s name is not like ' . 'TestBconfAllFilters' . ' but ' . static::$bconf->getName());
    }

    /**
     * Tests the extension Mapping of the bconf
     * @depends test20_createEntity
     */
    public function test30_ExtensionMapping()
    {
        $extensionMapping = static::$bconf->getExtensionMapping();
        self::assertEquals(true, $extensionMapping->validate(), 'Extension-Mapping of the imported BCONF is not valid');
    }

    /**
     * Tests the validation of FPRMs and checks, if the expected errors are found
     * @depends test20_createEntity
     */
    public function test40_FprmValidators()
    {
        // checking the valid FPRMS
        $this->_createFprmTest('okf_html@local-html_customized.fprm', '', true);
        $this->_createFprmTest('okf_icml@local-icml_customized.fprm', '', true);
        $this->_createFprmTest('okf_idml@local-adobe_indesign_idml_documents_customized.fprm', '', true);
        $this->_createFprmTest('okf_itshtml5@local-standard_html5_customized.fprm', '', true);
        $this->_createFprmTest('okf_openxml@local-microsoft_office_document_customized.fprm', '', true);
        $this->_createFprmTest('okf_xml@local-generic_xml_customized.fprm', '', true);
        $this->_createFprmTest('okf_xmlstream@local-xml_stream_customized.fprm', '', true);
        // checking invalid PROPERTIES
        $this->_createFprmTest('okf_icml@invalid-properties.fprm', 'okf_icml@local-icml_customized', false, ['=true', 'skipThreshold.invalid']);
        $this->_createFprmTest('okf_idml@invalid-property-values.fprm', 'okf_idml@local-adobe_indesign_idml_documents_customized', false, ['maxAttributeSize.i=INVALID', 'ignoreCharacterKerning.b=INVALID']);
        $this->_createFprmTest('okf_openxml@invalid-property-values.fprm', 'okf_openxml@local-microsoft_office_document_customized', false, ['bPreferenceTranslateWordHeadersFooters.b=INVALID', 'bPreferenceAggressiveCleanup.b=INVALID', 'tsExcelExcludedColumns.i=INVALID']);
        // checking invalid XML & YAML with extended validation
        $this->_createFprmTest('okf_xml@invalid-xml.fprm', 'okf_xml@local-generic_xml_customized', false, ['Invalid XML']);
        // HINT: the extendedValidationErrors may depend on the longhorn version
        $this->_createFprmTest('okf_itshtml5@invalid-xml-values.fprm', 'okf_itshtml5@local-standard_html5_customized', true, [], false, ['org.w3c.its.ITSException', 'Invalid value', "'translate'"]);
        $this->_createFprmTest('okf_html@invalid-yaml.fprm', 'okf_html@local-html_customized', false, ['Invalid YAML']);
        // HINT: the extendedValidationErrors may depend on the longhorn version
        $this->_createFprmTest('okf_xmlstream@invalid-yaml-values.fprm', 'okf_xmlstream@local-xml_stream_customized', true, [], false, ['java.lang.ClassCastException', 'java.lang.String', 'cannot be cast to', 'java.lang.Boolean']);
    }

    /**
     * Test SRX validation and validation during upload processing
     * @depends test20_createEntity
     */
    public function test50_SrxValidators()
    {
        // source SRX
        $sourceSrx = static::$bconf->getSrx('source');
        $this->_createResourceFileTest($sourceSrx, true);
        $sourceSrx->setContent(static::api()->getFileContent('languages-invalid.srx'));
        $this->_createResourceFileTest($sourceSrx, false, ['Invalid XML']);
        // target SRX
        $targetSrx = static::$bconf->getSrx('target');
        $this->_createResourceFileTest($targetSrx, true);
        $targetSrx->setContent(static::api()->getFileContent('languages-invalid-rules.srx'));
        $this->_createResourceFileTest($targetSrx, true);
        // mimic upload and test the functionality
        // source SRX
        $contentBefore = static::$bconf->getSrx('source')->getContent();

        try {
            Segmentation::instance()->processUpload(static::$bconf, 'source', static::api()->getFile('languages-invalid.srx'), 'languages-invalid.srx');
            self::fail('Invalid SRX "languages-invalid.srx" could be uploaded although it is invalid');
        } catch (OkapiException $e) {
            $expectedErrorDetails = 'Invalid XML';
            self::assertEquals(true, str_contains($e->getExtra('details'), $expectedErrorDetails), 'Uplooaded Invalid SRX "languages-invalid.srx" had not expected error "' . $expectedErrorDetails . '"');
            self::assertEquals('E1390', $e->getErrorCode(), 'Uploaded Invalid SRX "languages-invalid.srx" lead to wrong exception "' . $e->getErrorCode() . '", expected: "E1390"');
            $contentAfter = static::$bconf->getSrx('source')->getContent();
            self::assertEquals(trim($contentBefore), trim($contentAfter), 'Uploaded Invalid SRX "languages-invalid.srx" lead to changed SRX of the BCONF');
        }
        // targetSRX
        // TODO FIXME: This Test cannot be executed since on the s1mittag always the source SRX is used to segment the file, so that these invalid rules are not detected
        // uncomment this test whenever this phenomenon is solved
        /*
        $contentBefore = static::$bconf->getSrx('target')->getContent();
        try {
            Segmentation::instance()->processUpload(static::$bconf, 'target', static::api()->getFile('languages-invalid-rules.srx'), 'languages-invalid-rules.srx');
            self::assertEquals(true, false, 'Invalid SRX "languages-invalid-rules.srx" could be uploaded although it is invalid');
        } catch (OkapiException $e){
            $expectedErrorDetails = 'java.util.regex.PatternSyntaxException';
            self::assertEquals(true, str_contains($e->getExtra('details'), $expectedErrorDetails), 'Uplooaded Invalid SRX "languages-invalid.srx" had not expected error "'.$expectedErrorDetails.'"');
            self::assertEquals('E1390', $e->getErrorCode(), 'Uplooaded Invalid SRX "languages-invalid-rules.srx" lead to wrong exception "'.$e->getErrorCode().'", expected: "E1390"');
            $contentAfter = static::$bconf->getSrx('target')->getContent();
            self::assertEquals(trim($contentBefore), trim($contentAfter), 'Uplooaded Invalid SRX "languages-invalid-rules.srx" lead to changed SRX of the BCONF');
        }
        */
    }

    /**
     * Test upload of valid and faulty Pipelines
     * @depends test20_createEntity
     */
    public function test55_PipelinesUpload()
    {
        $bconfId = self::$bconf->getId();

        static::api()->addFile('pln', static::api()->getFile('pipeline.pln'), 'application/octet-stream');
        $res = static::api()->post("editor/plugins_okapi_bconf/uploadpipeline?id=$bconfId");
        self::assertEquals(200, $res->getStatus());

        static::api()->addFile('pln', static::api()->getFile('pipeline_no_extraction_step.pln'), 'application/octet-stream');
        $res = static::api()->post("editor/plugins_okapi_bconf/uploadpipeline?id=$bconfId");
        self::assertEquals(500, $res->getStatus());
    }

    /**
     * Test Pipeline & Content validation
     * @depends test20_createEntity
     */
    public function test60_OtherValidators()
    {
        // invalid Pipeline
        $invalidPipeline = static::api()->getFileContent('pipeline-invalid.pln');
        $pipeline = new Pipeline(
            static::$bconf->getPipelinePath(),
            $invalidPipeline,
            (int) static::$bconf->getId()
        );
        $this->_createResourceFileTest($pipeline, false, ['invalid integer value', 'trimSrcLeadingWS.i=INVALID']);
        // invalid Content
        $invalidContent = file_get_contents(static::api()->getFile('content-invalid.json'));
        $content = new Content(
            static::$bconf->getContentPath(),
            $invalidContent,
            (int) static::$bconf->getId()
        );
        $this->_createResourceFileTest($content, false, ['no source SRX set', 'no step found']);
    }

    /**
     * Uploads a SRX and checks if it was changed, on file-base and in all places this is referenced
     * @depends test20_createEntity
     */
    public function test70_UploadSrx()
    {
        $result = $this->_uploadResourceFile('languages-changed.srx', 'editor/plugins_okapi_bconf/uploadsrx', 'srx', [
            'id' => (int) static::$bconf->getId(),
            'purpose' => 'source',
        ]);
        self::assertEquals(true, $result->success, 'Failed to upload changed SRX "languages-changed.srx" as new source SRX');
        // check update in pipeline
        $pipeline = new Pipeline(
            static::$bconf->getPipelinePath(),
            null,
            (int) static::$bconf->getId()
        );
        self::assertEquals('languages-changed.srx', $pipeline->getSrxFile('source'), 'Failed to change pipeline.pln for updated source SRX "languages-changed.srx"');
        // check update in content
        $content = new Content(static::$bconf->getContentPath(), null, (int) static::$bconf->getId());
        self::assertEquals('languages-changed.srx', $content->getSrxFile('source'), 'Failed to change content.json for updated source SRX "languages-changed.srx"');

        try {
            $srx = new Srx(static::$bconf->createPath('languages-changed.srx'));
            self::assertEquals(true, str_contains($srx->getContent(), 'JUSTACHANGEDSTRING'), 'The updated SRX "languages-changed.srx" did not contain the expected contents');
        } catch (Exception $e) {
            self::fail('Uploaded changed source SRX "languages-changed.srx" was not found in the BCONFs files [' . $e->getMessage() . ']');
        }
    }

    /**
     * Tests the saving of changed FPRMs for the 3 main FPRM types
     * @depends test20_createEntity
     */
    public function test80_ChangeFprm()
    {
        // this string is embedded in all the changed FPRMs
        $searchedString = 'JUSTACHANGEDSTRING';
        // changed YAML FPRM
        $this->_saveChangedFprmTest('okf_html_changed.fprm', 'okf_html@local-html_customized', Fprm::TYPE_YAML, $searchedString);
        // changed XML FPRM
        $this->_saveChangedFprmTest('okf_itshtml5_changed.fprm', 'okf_itshtml5@local-standard_html5_customized', Fprm::TYPE_XML, $searchedString);
        // changed PROPERTIES FPRM
        $this->_saveChangedFprmTest('okf_openxml_changed.fprm', 'okf_openxml@local-microsoft_office_document_customized', Fprm::TYPE_XPROPERTIES, $searchedString);
    }

    /**
     * Checks various invalid BCONFs to be uploaded
     */
    public function test90_InvalidBconfs()
    {
        $this->_uploadInvalidBconfTest(
            'invalid-pipeline',
            'all-customized-filters-invalid-pipeline.bconf',
            'E1415',
            ['Invalid Pipeline', 'invalid entries for the source or target segmentation srx file']
        );
        $this->_uploadInvalidBconfTest(
            'invalid-fprm-properties',
            'all-customized-filters-invalid-fprm-properties.bconf',
            'E1415',
            ['Invalid x-properties', 'Found invalid boolean value', 'Found invalid integer value', 'bPreferenceTranslatePowerpointNotes.b=aaaa', 'bPreferenceTranslatePowerpointMasters.b=bbbb', 'tsExcelExcludedColors.i=A', 'tsExcelExcludedColumns.i=B']
        );
        $this->_uploadInvalidBconfTest(
            'invalid-fprm-xml',
            'all-customized-filters-invalid-fprm-xml.bconf',
            'E1415',
            ['Invalid XML']
        );
        $this->_uploadInvalidBconfTest(
            'invalid-segmentation',
            'all-customized-filters-invalid-segmentation.bconf',
            'E1408',
            ['Failed to convert', 'for import with OKAPI', 'net.sf.okapi.common.exceptions.OkapiIOException', 'org.xml.sax.SAXParseException']
        );
        $this->_uploadInvalidBconfTest(
            'invalid-segmentation-rule',
            'all-customized-filters-invalid-segmentation-rule.bconf',
            'E1408',
            ['Failed to convert', 'for import with OKAPI', 'java.util.regex.PatternSyntaxException']
        );
    }

    /**
     * Creates a basic and extended test to test the FPRM validation
     * @throws OkapiException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_Exception
     */
    private function _createFprmTest(string $filename, string $identifier, bool $valid, array $validationErrors = [], bool $validExtended = null, array $extendedValidationErrors = [])
    {
        $fprmContent = static::api()->getFileContent($filename);
        $fprmPath = empty($identifier) ? static::$bconf->createPath($filename) : static::$bconf->createPath($identifier . '.fprm');
        $fprm = new Fprm($fprmPath, $fprmContent);
        $validValidation = $fprm->validate(false);
        self::assertEquals($valid, $validValidation, $filename . ' failed to validate to the expected state: ' . ($valid ? 'valid' : 'invalid'));
        if (! $validValidation && count($validationErrors) > 0) {
            $error = $fprm->getValidationError();
            // echo "\n\n FPRM $filename VALIDATION ERROR:\n$error\n";
            foreach ($validationErrors as $errorPart) {
                self::assertEquals(true, str_contains(strtolower($error), strtolower($errorPart)));
            }
        }
        // validate with real OKAPI processing
        if ($validExtended !== null) {
            $idata = Filters::parseIdentifier($identifier);
            $filterEntity = static::$bconf->findCustomFilterEntry($idata->type, $idata->id);
            if ($filterEntity == null) {
                self::fail('Could not find filter "' . $identifier . '" in tested BCONF');
            } else {
                $params = [
                    'id' => $filterEntity->getId(),
                    'type' => $fprm->getType(),
                ];
                $content = file_get_contents(static::api()->getFile($filename));
                $result = static::api()->postRawData('editor/plugins_okapi_bconffilter/savefprm', $content, $params);
                // result is to have two props: success & error. Error only, if success === false
                if ($validExtended) {
                    self::assertTrue((property_exists($result, 'success') && $result->success === true), 'Failed to save FPRM "' . $filename . '"');
                } else {
                    self::assertTrue((property_exists($result, 'success') && $result->success === false), 'Could save faulty FPRM "' . $filename . '"');
                    $error = strtolower($result->error);
                    foreach ($extendedValidationErrors as $errorPart) {
                        self::assertEquals(true, str_contains(strtolower($error), strtolower($errorPart)), 'Error "' . $error . '" did not contain expected part "' . $errorPart . '"');
                    }
                }
            }
        }
    }

    /**
     * Creates a resource-file test
     */
    private function _createResourceFileTest(ResourceFile $resourceFile, bool $valid, array $expectedErrors = [])
    {
        $resourceValid = $resourceFile->validate();
        self::assertEquals($valid, $resourceValid, basename($resourceFile->getPath()) . ' failed to validate to the expected state: ' . ($valid ? 'valid' : 'invalid'));
        if (! $resourceValid && count($expectedErrors) > 0) {
            $error = $resourceFile->getValidationError();
            foreach ($expectedErrors as $errorPart) {
                self::assertEquals(true, str_contains(strtolower($error), strtolower($errorPart)));
            }
        }
    }

    /**
     * Creates a upload-test that expects to upload invalid bconfs
     */
    private function _uploadInvalidBconfTest(string $bconfName, string $invalidBconfFile, string $expectedErrorCode, array $expectedErrors)
    {
        $bconf = static::getConfig()
            ->addBconf($bconfName, $invalidBconfFile)
            ->setNotToFailOnError();
        static::getConfig()->import($bconf);
        static::assertIsObject($bconf);
        if (! $bconf->hasUploadFailed()) {
            self::fail('Uploaded invalid BCONF "' . $invalidBconfFile . '" could be uploaded although it is invalid');
        } else {
            // check errorCode
            self::assertEquals($expectedErrorCode, $bconf->errorCode, 'Uploaded invalid BCONF "' . $invalidBconfFile . '" created different errorCode "' . $bconf->errorCode . '", expected: "' . $expectedErrorCode . '"');
            // check errors
            foreach ($expectedErrors as $errorPart) {
                self::assertEquals(true, str_contains(strtolower($bconf->error), strtolower($errorPart)));
            }
        }
    }

    /**
     * Creates a test that mimics the saving of a FPRM file
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_Exception
     */
    private function _saveChangedFprmTest(string $filename, string $identifier, string $fprmType, string $searchedString)
    {
        $idata = Filters::parseIdentifier($identifier);
        $filterEntity = static::$bconf->findCustomFilterEntry($idata->type, $idata->id);
        if ($filterEntity == null) {
            self::fail('Could not find filter "' . $identifier . '" in tested BCONF');
        } else {
            $params = [
                'id' => $filterEntity->getId(),
                'type' => $fprmType,
            ];
            $content = file_get_contents(static::api()->getFile($filename));
            $result = static::api()->postRawData('editor/plugins_okapi_bconffilter/savefprm', $content, $params);
            self::assertFalse(static::api()->isJsonResultError($result), 'Failed to save changed FPRM "' . $filename . '"');

            try {
                $fprm = new Fprm(static::$bconf->createPath($filterEntity->getFile()));
                self::assertEquals(true, str_contains($fprm->getContent(), $searchedString), 'The updated SRX "' . $filterEntity->getFile() . '" did not contain the expected contents');
            } catch (Exception $e) {
                self::fail('Can not find "' . $filterEntity->getFile() . '" in the BCONFs files [' . $e->getMessage() . ']');
            }
        }
    }

    /**
     * @return mixed
     */
    private function _uploadResourceFile(string $filename, string $endpoint, string $uploadName, array $uploadParams = [], string $uploadMime = 'application/octet-stream')
    {
        $input = new SplFileInfo(static::api()->getFile($filename));
        static::api()->addFile($uploadName, $input->getPathname(), $uploadMime);
        // Run as api test that if case runtimeOptions.plugins.Okapi.dataDir is missing it's created as webserver user
        $result = static::api()->postJson($endpoint, $uploadParams);

        return $this->_getFullResult($result);
    }

    /**
     * turns the multitype-result of the API to an object that also represents the result of a failed request
     * @return mixed|stdClass
     */
    private function _getFullResult($result)
    {
        if ($result === false) {
            $responseBody = static::api()->getLastResponse()->getBody();
            $result = json_decode($responseBody);
            $result->success = false;
        } elseif (! $result) {
            $result = new stdClass();
            $result->success = true;
        } else {
            $result->success = true;
        }

        return $result;
    }
}

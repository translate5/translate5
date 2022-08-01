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
class OkapiBconfFilterTest extends editor_Test_JsonTest {

    private static editor_Plugins_Okapi_Bconf_Entity $bconf;
    private static int $bconfId = 0;

    public static function setUpBeforeClass(): void {

        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::$api->login('testmanager');
        self::assertLogin('testmanager');

        $appState = self::$api->requestJson('editor/index/applicationstate');
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case');

        // Needed for localized error messages in Unit Test like ZfExtended_NoAccessException
        if(!Zend_Registry::isRegistered('Zend_Locale')){
            Zend_Registry::set('Zend_Locale', new Zend_Locale('en'));
        }
    }

    /**
     * Verifies that all file-stores (FPRMs, SRXs, testfiles) are valid (= all referenced files present)
     */
    public function test10_BconfFilterInventories() {
        $this->assertEquals(true, editor_Plugins_Okapi_Bconf_Filter_Okapi::instance()->validate());
        $this->assertEquals(true, editor_Plugins_Okapi_Bconf_Filter_Translate5::instance()->validate());
        $this->assertEquals(true, editor_Plugins_Okapi_Bconf_Segmentation_Translate5::instance()->validate());
        $this->assertEquals(true, editor_Plugins_Okapi_Bconf_Filters::instance()->validate());
    }

    /**
     * Just imports the BCONF we need for our tests
     */
    public function test20_ImportBconf() {
        $input = new SplFileInfo(self::$api->getFile('all-customized-filters.bconf'));
        $bconfName = 'OkapiBconfFilterTest'.time().'.bconf';
        self::$api->addFile('bconffile', $input->getPathname(), 'application/octet-stream');
        // Run as api test that if case runtimeOptions.plugins.Okapi.dataDir is missing it's created as webserver user
        $res = self::$api->requestJson('editor/plugins_okapi_bconf/uploadbconf', 'POST', [
            'name' => $bconfName,
        ]);
        self::assertEquals(true, $res?->success, 'uploadbconf did not respond with success:true for bconf '.$bconfName);
        self::$bconfId = $res->id;
        self::$bconf = new editor_Plugins_Okapi_Bconf_Entity();
        self::$bconf->load(self::$bconfId);
        self::assertEquals(self::$bconf->getName(), $bconfName, 'Imported bconf\'s name is not '.$bconfName.' but '.self::$bconf->getName());
    }

    /**
     * Tests the extension Mapping of the bconf
     */
    public function test30_ExtensionMapping() {
        $extensionMapping = self::$bconf->getExtensionMapping();
        self::assertEquals(true, $extensionMapping->validate(), 'Extension-Mapping of the imported BCONF is not valid');
    }

    /**
     * Tests the validation of FPRMs and checks, if the expected errors are found
     */
    public function test40_FprmValidators() {
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
        $this->_createFprmTest('okf_xmlstream@invalid-yaml-values.fprm', 'okf_xmlstream@local-xml_stream_customized', true, [], false, ['java.lang.ClassCastException', 'java.lang.String cannot be cast to class java.lang.Boolean']);
    }

    /**
     * Test SRX validation and validation during upload processing
     */
    public function test50_SrxValidators() {
        // source SRX
        $sourceSrx = self::$bconf->getSrx('source');
        $this->_createResourceFileTest($sourceSrx, true);
        $sourceSrx->setContent(self::$api->getFileContent('languages-invalid.srx'));
        $this->_createResourceFileTest($sourceSrx, false, ['Invalid XML']);
        // target SRX
        $targetSrx = self::$bconf->getSrx('target');
        $this->_createResourceFileTest($targetSrx, true);
        $targetSrx->setContent(self::$api->getFileContent('languages-invalid-rules.srx'));
        $this->_createResourceFileTest($targetSrx, true);
        // mimic upload and test the functionality
        // source SRX
        $contentBefore = self::$bconf->getSrx('source')->getContent();
        try {
            editor_Plugins_Okapi_Bconf_Segmentation::instance()->processUpload(self::$bconf, 'source', self::$api->getFile('languages-invalid.srx'), 'languages-invalid.srx');
            self::fail('Invalid SRX "languages-invalid.srx" could be uploaded although it is invalid');
        } catch (editor_Plugins_Okapi_Exception $e){
            $expectedErrorDetails = 'Invalid XML';
            self::assertEquals(true, str_contains($e->getExtra('details'), $expectedErrorDetails), 'Uplooaded Invalid SRX "languages-invalid.srx" had not expected error "'.$expectedErrorDetails.'"');
            self::assertEquals('E1390', $e->getErrorCode(), 'Uploaded Invalid SRX "languages-invalid.srx" lead to wrong exception "'.$e->getErrorCode().'", expected: "E1390"');
            $contentAfter = self::$bconf->getSrx('source')->getContent();
            self::assertEquals(trim($contentBefore), trim($contentAfter), 'Uploaded Invalid SRX "languages-invalid.srx" lead to changed SRX of the BCONF');
        }
        // ragetSRX
        $contentBefore = self::$bconf->getSrx('target')->getContent();
        try {
            editor_Plugins_Okapi_Bconf_Segmentation::instance()->processUpload(self::$bconf, 'target', self::$api->getFile('languages-invalid-rules.srx'), 'languages-invalid-rules.srx');
            self::assertEquals(true, false, 'Invalid SRX "languages-invalid-rules.srx" could be uploaded although it is invalid');
        } catch (editor_Plugins_Okapi_Exception $e){
            $expectedErrorDetails = 'java.util.regex.PatternSyntaxException';
            self::assertEquals(true, str_contains($e->getExtra('details'), $expectedErrorDetails), 'Uplooaded Invalid SRX "languages-invalid.srx" had not expected error "'.$expectedErrorDetails.'"');
            self::assertEquals('E1390', $e->getErrorCode(), 'Uplooaded Invalid SRX "languages-invalid-rules.srx" lead to wrong exception "'.$e->getErrorCode().'", expected: "E1390"');
            $contentAfter = self::$bconf->getSrx('target')->getContent();
            self::assertEquals(trim($contentBefore), trim($contentAfter), 'Uplooaded Invalid SRX "languages-invalid-rules.srx" lead to changed SRX of the BCONF');
        }
    }

    /**
     * Test Pipeline & Content validation
     */
    public function test50_OtherValidators() {
        // invalid Pipeline
        $invalidPipeline = self::$api->getFileContent('pipeline-invalid.pln');
        $pipeline = new editor_Plugins_Okapi_Bconf_Pipeline(self::$bconf->getPipelinePath(), $invalidPipeline, self::$bconf->getId());
        $this->_createResourceFileTest($pipeline, false, ['invalid integer value', 'trimSrcLeadingWS.i=INVALID']);
        // invalid Content
        $invalidContent = file_get_contents(self::$api->getFile('content-invalid.json'));
        $content = new editor_Plugins_Okapi_Bconf_Content(self::$bconf->getContentPath(), $invalidContent, self::$bconf->getId());
        $this->_createResourceFileTest($content, false, ['no source SRX set', 'no step found']);
    }

    /**
     * Last step: Cleanup
     */
    public function test90_RemoveImportedBconf() {
        $bconfDir = self::$bconf->getDataDirectory();
        self::$bconf->delete(); // delete record, which deletes directory as well
        self::assertDirectoryDoesNotExist($bconfDir);
    }

    /**
     * Checks various invalid BCONFs to be uploaded
     */
    public function test100_InvalidBconfs() {
        $this->_uploadInvalidBconfTest('all-customized-filters-invalid-pipeline.bconf', 'E1415', ['Invalid Pipeline', 'invalid entries for the source or target segmentation srx file']);
        $this->_uploadInvalidBconfTest('all-customized-filters-invalid-fprm-properties.bconf', 'E1415', ['Invalid x-properties', 'Found invalid boolean value', 'Found invalid integer value', 'bPreferenceTranslatePowerpointNotes.b=aaaa', 'bPreferenceTranslatePowerpointMasters.b=bbbb', 'tsExcelExcludedColors.i=A', 'tsExcelExcludedColumns.i=B']);
        $this->_uploadInvalidBconfTest('all-customized-filters-invalid-fprm-xml.bconf', 'E1415', ['Invalid XML']);
        $this->_uploadInvalidBconfTest('all-customized-filters-invalid-segmentation.bconf', 'E1408', ['Failed to convert', 'for import with OKAPI', 'net.sf.okapi.common.exceptions.OkapiIOException', 'org.xml.sax.SAXParseException']);
        $this->_uploadInvalidBconfTest('all-customized-filters-invalid-segmentation-rule.bconf', 'E1408', ['Failed to convert', 'for import with OKAPI', 'java.util.regex.PatternSyntaxException']);
    }

    /**
     * Creates a basic and extended test to test the FPRM validation
     * @param string $filename
     * @param string $identifier
     * @param bool $valid
     * @param array $validationErrors
     * @param bool|null $validExtended
     * @param array $extendedValidationErrors
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    private function _createFprmTest(string $filename, string $identifier, bool $valid, array $validationErrors=[], bool $validExtended=NULL, array $extendedValidationErrors=[]){
        $fprmContent = self::$api->getFileContent($filename);
        $fprmPath = empty($identifier) ? self::$bconf->createPath($filename) : self::$bconf->createPath($identifier.'.fprm');
        $fprm = new editor_Plugins_Okapi_Bconf_Filter_Fprm($fprmPath, $fprmContent);
        $validValidation = $fprm->validate(false);
        self::assertEquals($valid, $validValidation, $filename.' failed to validate to the expected state: '.($valid ? 'valid' : 'invalid'));
        if(!$validValidation && count($validationErrors) > 0){
            $error = $fprm->getValidationError();
            // echo "\n\n FPRM $filename VALIDATION ERROR:\n$error\n";
            foreach($validationErrors as $errorPart){
                self::assertEquals(true, str_contains(strtolower($error), strtolower($errorPart)));
            }
        }
        // validate with real OKAPI processing
        if($validExtended !== NULL){
            $okapiValidation = new editor_Plugins_Okapi_Bconf_Filter_FprmValidation(self::$bconf, $fprm);
            $validValidation = $okapiValidation->validateWithoutPacking();
            self::assertEquals($validExtended, $validValidation, $filename.' failed to validate with the extended validation to the expected state: '.($validExtended ? 'valid' : 'invalid'));
            if(!$validValidation && count($extendedValidationErrors) > 0){
                $error = $okapiValidation->getValidationError();
                // echo "\n\n FPRM $identifier EXTENDED VALIDATION ERROR:\n$error\n";
                foreach($extendedValidationErrors as $errorPart){
                    self::assertEquals(true, str_contains(strtolower($error), strtolower($errorPart)));
                }
            }
            if(!empty($identifier)){
                unset($fprm);
                // this tests, if after the the extended validation the fprm is back in the original state
                $originalContent = self::$api->getFileContent($identifier.'.fprm');
                $fprm = new editor_Plugins_Okapi_Bconf_Filter_Fprm(self::$bconf->createPath($identifier.'.fprm'));
                self::assertEquals(trim($originalContent), trim($fprm->getContent()), 'Extended validation of '.$identifier.'.fprm failed to revert the tested invalid content back to its original state');
            }
        }
    }

    /**
     * Creates a resource-file test
     * @param editor_Plugins_Okapi_Bconf_ResourceFile $resourceFile
     * @param bool $valid
     * @param array $expectedErrors
     */
    private function _createResourceFileTest(editor_Plugins_Okapi_Bconf_ResourceFile $resourceFile, bool $valid, array $expectedErrors=[]){
        $resourceValid = $resourceFile->validate();
        self::assertEquals($valid, $resourceValid, basename($resourceFile->getPath()).' failed to validate to the expected state: '.($valid ? 'valid' : 'invalid'));
        if(!$resourceValid && count($expectedErrors) > 0){
            $error = $resourceFile->getValidationError();
            foreach($expectedErrors as $errorPart){
                self::assertEquals(true, str_contains(strtolower($error), strtolower($errorPart)));
            }
        }
    }

    /**
     * Creates a upload-test that expects to upload invalid bconfs
     * @param string $invalidBconfFile
     * @param string $expectedErrorCode
     * @param array $expectedErrors
     */
    private function _uploadInvalidBconfTest(string $invalidBconfFile, string $expectedErrorCode, array $expectedErrors) {
        $tempName = pathinfo($invalidBconfFile, PATHINFO_FILENAME).time().'.bconf';
        $input = new SplFileInfo(self::$api->getFile($invalidBconfFile));
        self::$api->addFile('bconffile', $input->getPathname(), 'application/octet-stream');
        // Run as api test that if case runtimeOptions.plugins.Okapi.dataDir is missing it's created as webserver user
        $result = self::$api->requestJson('editor/plugins_okapi_bconf/uploadbconf', 'POST', [
            'name' => $tempName,
        ]);
        if($result !== false){
            self::fail('Uploaded invalid BCONF "'.$invalidBconfFile.'" could be uploaded although it is invalid');
        } else {
            $responseBody = self::$api->getLastResponse()->getBody();
            $response = json_decode($responseBody);
            self::assertEquals($expectedErrorCode, $response->errorCode, 'Uploaded invalid BCONF "'.$invalidBconfFile.'" created different errorCode "'.$response->errorCode.'", expected: "'.$expectedErrorCode.'"');
            $error = $response->errorMessage;
            foreach($expectedErrors as $errorPart){
                self::assertEquals(true, str_contains(strtolower($error), strtolower($errorPart)));
            }
        }
    }
}

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
 * This class processes an example file to validate a bconf
 * Therefore testfiles for all supported ::EXTENSIONS exist in data/testfiles
 * Be aware, that not all BCONFs can be validated this way
 */
final class editor_Plugins_Okapi_Bconf_Validation {

    /**
     * @var array
     */
    const EXTENSIONS = ['txt', 'xml', 'strings', 'csv', 'htm', 'html', 'sdlxliff', 'docx', 'odp', 'ods', 'odt', 'pptx', 'tbx', 'xlsx', 'idml'];

    /**
     * @var string
     */
    const TESTFILE_FOLDER = 'testfiles';

    /**
     * English / en-GB
     * @var int
     */
    const SOURCE_LANGUAGE = 5;

    /**
     * German / de-DE
     * @var int
     */
    const TARGET_LANGUAGE = 4;

    /**
     * @var string
     */
    private editor_Plugins_Okapi_Bconf_Entity $bconf;

    /**
     * @var bool
     */
    private bool $valid = true;

    /**
     * @var bool
     */
    private bool $testable = true;

    /**
     * @var string
     */
    private string $validationError;

    /**
     * @param editor_Plugins_Okapi_Bconf_Entity $bconf: The bconf that should be validated
     */
    public function __construct($bconf){
        $this->bconf = $bconf;
    }

    /**
     * Validates the bconf by processing an okapi project with it
     * @return bool
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function validate() : bool {
        $this->testable = true;
        $testfilePath = $this->getTestFilePath();
        // if the bconf supprted none of our extension we can not test it
        if(empty($testfilePath)){
            // if the bconf generally supports no extensions it is invalid
            if(count($this->bconf->getSupportedExtensions()) < 1){
                $this->validationError = 'The bconf has no extensions mapped';
                return false;
            }
            $this->validationError = 'The bconf could not be tested as none of the available testfiles are supported';
            $this->testable = false;
            return true;
        }
        return $this->process($testfilePath);
    }

    /**
     * Some bconfs can not be tested since we do not have a testfile to check the supported extensions
     * @return bool
     */
    public function wasTestable() : bool {
        return $this->testable;
    }

    /**
     * @return string|null
     * @throws ZfExtended_Exception
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    private function getTestFilePath() : ?string {
        foreach(self::EXTENSIONS as $extension){
            if($this->bconf->hasSupportFor($extension)){
                return editor_Plugins_Okapi_Init::getDataDir().self::TESTFILE_FOLDER.'/test.'.$extension;
            }
        }
        return NULL;
    }

    /**
     * Retrieves the error that caused the file to be invalid
     * @return string
     */
    public function getValidationError() : string {
        return $this->validationError;
    }

    /**
     * @param string $testfilePath
     * @return bool
     */
    private function process(string $testfilePath){
        $testDir = editor_Plugins_Okapi_Bconf_Entity::getUserDataDir().'/tmp';
        if(!is_dir($testDir)){
            @mkdir($testDir, 0777, true);
        }
        $dir = dirname($testfilePath);
        $manifestFile = sprintf(editor_Plugins_Okapi_Worker::MANIFEST_FILE, 'test');
        $testfile = basename($testfilePath);
        /* @var $api editor_Plugins_Okapi_Connector */
        $api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');
        /* @var $language editor_Models_Languages */
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        try {
            $api->createProject();
            $api->uploadOkapiConfig($this->bconf->getPath());
            $api->uploadInputFile($testfile, new SplFileInfo($testfilePath));
            $api->executeTask($language->loadLangRfc5646(self::SOURCE_LANGUAGE), $language->loadLangRfc5646(self::TARGET_LANGUAGE));
            $convertedFile = $api->downloadFile($testfile, $manifestFile, new SplFileInfo($testDir));
            // cleanup downloaded files
            unlink($convertedFile);
            unlink($testDir.'/'.$manifestFile);
        } catch (Exception $e){
            $this->validationError = 'Failed to convert '.$testfile.' for import with OKAPI ['.$e->getMessage().']';
            return false;
        } finally {
            $api->removeProject();
        }
        return true;
    }
}

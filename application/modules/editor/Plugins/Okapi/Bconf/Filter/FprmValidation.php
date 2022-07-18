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
 * This Class is Intended to test a FPRM file edited by the user
 * It tests filter, that are not X-Properties based and that we cannot validate otherwise
 * This class can only be used for already imported bconfs and will temporarily pack this bconf with the new filter
 * The passed FPRM filter must not already be flushed, otherwise the restoration of the original state is impossible
 * After validation, the filter will be flushed and the bconf packed, only toe update of the filter's hash in the DB is left to do. If you do not want this behaviour, use ::validateWithoutPacking
 */
final class editor_Plugins_Okapi_Bconf_Filter_FprmValidation extends editor_Plugins_Okapi_Bconf_Validation {

    /**
     * In case a custom filter has no extension mapped to it but we need to temporarily map an extension to it, we use this extension
     * It is pretty safe to assume, that this extension never occurs in the wild :-)
     */
    const TMP_MAPPING_EXTENSION = 'gqyzzvq';
    /**
     * @var string
     */
    private string $backupFilter;
    /**
     * @var string
     */
    private string $backupBconf;
    /**
     * @var string
     */
    private string $okapiType;
    /**
     * @var string
     */
    private ?string $tmpTestfile = NULL;
    /**
     * when we need to manipulate the mapping we have to revert it
     * @var boolean
     */
    private bool $mappingChanged = false;
    /**
     * @var editor_Plugins_Okapi_Bconf_Filter_Fprm
     */
    private editor_Plugins_Okapi_Bconf_Filter_Fprm $fprm;

    /**
     * @param editor_Plugins_Okapi_Bconf_Entity $bconf: The bconf the fprm to validate is contained in
     * @param editor_Plugins_Okapi_Bconf_Filter_Fprm $fprm: The fprm that should be validated
     */
    public function __construct(editor_Plugins_Okapi_Bconf_Entity $bconf, editor_Plugins_Okapi_Bconf_Filter_Fprm $fprm){
        $this->bconf = $bconf;
        $this->fprm = $fprm;
        $this->type = $fprm->getOkapiType();
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfValidation');
        if(!array_key_exists($this->type, editor_Plugins_Okapi_Bconf_Filters::GUIS)){
            // DEBUG
            if($this->doDebug){ error_log('FPRM VALIDATION ERROR: Can not validate fprm file '.$this->fprm->getFile().' because for okapi-type "'.$this->type.'" there is no GUI set'); }
            throw new ZfExtended_Exception('Can not validate fprm file '.$this->fprm->getFile().' because for okapi-type "'.$this->type.'" there is no GUI set');
        }
        if(count(editor_Plugins_Okapi_Bconf_Filters::GUIS[$this->type]['extensions']) < 1){
            // DEBUG
            if($this->doDebug){ error_log('FPRM VALIDATION ERROR: Can not validate fprm file '.$this->fprm->getFile().' because for okapi-type "'.$this->type.'" there are no testfiles in editor_Plugins_Okapi_Bconf_Filters::GUIS set'); }
            throw new ZfExtended_Exception('Can not validate fprm file '.$this->fprm->getFile().' because for okapi-type "'.$this->type.'" there are no testfiles in editor_Plugins_Okapi_Bconf_Filters::GUIS set');
        }
    }

    /**
     * Retrieves the file to test against
     * this may creates a temporary file that needs to be removed after validation
     * @return string|null
     * @throws ZfExtended_Exception
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    protected function getTestFilePath() : string {
        // existance already checked in constructor
        $testExtensions = editor_Plugins_Okapi_Bconf_Filters::GUIS[$this->type]['extensions'];
        $mappedExtensions = $this->bconf->getExtensionMapping()->findExtensionsForFilter($this->fprm->getIdentifier());
        // the easy case: we have one of the testable extensions mapped
        foreach($mappedExtensions as $extension){
            if(in_array($extension, $testExtensions)){
                // bingo, the needed file already exists
                // DEBUG
                if($this->doDebug){ error_log('FPRM VALIDATION: testfile: '.editor_Plugins_Okapi_Bconf_Filters::createTestfilePath('test.'.$extension)); }
                return editor_Plugins_Okapi_Bconf_Filters::createTestfilePath('test.'.$extension);
            }
        }
        if(count($mappedExtensions) < 1){
            // the hardest case: there are no extensions set for the edited filter
            $this->bconf->getExtensionMapping()->addFilter($this->fprm->getIdentifier(), [ self::TMP_MAPPING_EXTENSION ]); // this flushes the adjusted map !
            $this->mappingChanged = true;
            $this->tmpTestfile = editor_Plugins_Okapi_Bconf_Filters::createTestfilePath('fprmtest.'.self::TMP_MAPPING_EXTENSION);
        } else {
            // we simply take the first mapped extension and temporarily create a testfile with this extension
            $this->tmpTestfile = editor_Plugins_Okapi_Bconf_Filters::createTestfilePath('fprmtest.'.$mappedExtensions[0]);
        }
        $source = editor_Plugins_Okapi_Bconf_Filters::createTestfilePath('test.'.$testExtensions[0]);
        copy($source, $this->tmpTestfile);
        // DEBUG
        if($this->doDebug){ error_log('FPRM VALIDATION: temporary testfile: '.$this->tmpTestfile.($this->mappingChanged?', Mapping had to be changed':'')); }
        return $this->tmpTestfile;
    }

    /**
     * Validates a FPRM by processing an okapi project with it (in conjunction with the related bconf)
     * If the validation succeeds, the fprm is already be flushed and the bconf re-packed. Note, that the hash of the database-entry still must be updated !!
     * @return bool
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function validate() : bool {
        return $this->_validate(false);
    }
    /**
     * Validates a FPRM by processing an okapi project with it (in conjunction with the related bconf)
     * Does keep the bconf & fprm in it's original state
     * @return bool
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function validateWithoutPacking() : bool {
        return $this->_validate(true);
    }

    /**
     * @param bool $keepOriginals
     * @return bool
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    private function _validate(bool $keepOriginals) : bool {
        $testfilePath = $this->getTestFilePath();
        $filterPath = $this->fprm->getPath();
        $bconfPath = $this->bconf->getPath();
        $backupFilterPath = $filterPath.'.bu';
        $backupBconfPath = $bconfPath.'.bu';
        copy($filterPath, $backupFilterPath);
        copy($bconfPath, $backupBconfPath);
        // flush the fprm to the bconf folder & pack the bconf
        $this->fprm->flush();
        $this->bconf->pack();
        $valid = $this->process($testfilePath);
        if($keepOriginals || !$valid || $this->mappingChanged){
            // restore original bconf
            unlink($bconfPath);
            rename($backupBconfPath, $bconfPath);
            // restore original fprm. Only, if a valid fprm that was not mapped was sent and packing is wanted, we keep the changed fprm
            if($this->mappingChanged && $valid && !$keepOriginals){
                unlink($backupFilterPath);
            } else {
                unlink($filterPath);
                rename($backupFilterPath, $filterPath);
            }
        } else {
            // the normal case: just remove backups
            unlink($backupFilterPath);
            unlink($backupBconfPath);
        }
        // additional processing for changed mappings
        if($this->mappingChanged){
            $this->bconf->getExtensionMapping()->removeExtensions([ self::TMP_MAPPING_EXTENSION ]); // this flushes the reverted map !
            // a valid fprm will require another re-pack (to not have the adjusted mapping parsed in) if not originals shall be restored - which then already was done above
            if($valid && !$keepOriginals){
                $this->bconf->pack();
            }
        }
        if(!$valid){
            // dirty way to retrieve the error generated by okapi ...
            $parts = explode('[', $this->validationError);
            $okapiMsg = array_pop($parts);
            // DEBUG
            if($this->doDebug){ error_log('FPRM VALIDATION ERROR: Failed to validate '.$this->fprm->getFile().' ['.$okapiMsg); }
            $this->validationError = 'Failed to validate '.$this->fprm->getFile().' ['.$okapiMsg;
            return false;
        }
        // DEBUG
        if($this->doDebug){ error_log('FPRM VALIDATION SUCCESS: '.$this->fprm->getFile().' is valid'); }
        return true;
    }
}
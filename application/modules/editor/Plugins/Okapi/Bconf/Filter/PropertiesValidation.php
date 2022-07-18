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
 * Class validating a fprm file in the X-Properties format
 * This validates the given X-Properties file against the passed okapiType as reference
 * Reference-file are the default-fprms out of the okapi default inventory
 *
 * #v1
 * extractIsolatedStrings.b=false
 * codeFinderRules.rule0=</?([A-Z0-9a-z]*)\b[^>]*>
 * extractAllPairs.b=true
 * genericMetaRules=
 * codeFinderRules.count.i=1
 */
final class editor_Plugins_Okapi_Bconf_Filter_PropertiesValidation extends editor_Plugins_Okapi_Bconf_ResourceFile {

    protected string $mime = 'text/x-properties';

    /**
     * @var bool
     */
    private bool $needsRepair = false;

    /**
     * @var editor_Plugins_Okapi_Bconf_Parser_Properties
     */
    private editor_Plugins_Okapi_Bconf_Parser_Properties $props;

    /**
     * @var editor_Plugins_Okapi_Bconf_Parser_Properties
     */
    private editor_Plugins_Okapi_Bconf_Parser_Properties $referenceProps;

    /**
     * @param string $path
     * @param string|null $content
     * @throws ZfExtended_Exception
     */
    public function __construct(string $path, string $content=NULL){
        parent::__construct($path, $content);
        $identifier = editor_Plugins_Okapi_Bconf_Filters::createIdentifierFromPath($path);
        $idata = editor_Plugins_Okapi_Bconf_Filters::parseIdentifier($identifier);
        // try to get the default validation file
        $validationFile = editor_Plugins_Okapi_Bconf_Filters::instance()->getOkapiDefaultFilterPathById($idata->type);
        if(empty($validationFile)){
            $filters = editor_Plugins_Okapi_Bconf_Filter_Okapi::instance()->findFilter($idata->type);
            if(count($filters) > 0){
                $validationFile = editor_Plugins_Okapi_Bconf_Filter_Okapi::instance()->createFprmPath($filters[0]);
            }
        }
        if(empty($validationFile)){
            // DEBUG
            if($this->doDebug){ error_log('PROPERTIES VALIDATION ERROR: "'.$idata->type.'" seems no valid okapi-type'); }
            $this->validationError = '"'.$idata->type.'" seems no valid okapi-type';
        } else {
            $this->validationError = ''; // to avoid errors due to accessing unitialized vars ...
            $this->referenceProps = new editor_Plugins_Okapi_Bconf_Parser_Properties(file_get_contents($validationFile));
            if(!$this->referenceProps->isValid()){
                // DEBUG
                if($this->doDebug){ error_log('PROPERTIES VALIDATION ERROR: Invalid reference file "'.$validationFile.'": ('.$this->referenceProps->getErrorString(', ').')'); }
                throw new ZfExtended_Exception('Invalid reference file "'.$validationFile.'" ('.$this->referenceProps->getErrorString(', ').')');
            }
            $this->props = new editor_Plugins_Okapi_Bconf_Parser_Properties($this->content);
            if(!$this->props->isValid()){
                // DEBUG
                if($this->doDebug){ error_log('PROPERTIES VALIDATION ERROR: Invalid fprm "'.$path.'": ('.$this->props->getErrorString(', ').')'); }
                $this->validationError = trim($this->validationError.' '.$this->props->getErrorString("\n"));
            }
        }
    }
    /**
     * @return bool
     */
    public function hasToBeRepaired() : bool {
        return $this->needsRepair;
    }

    /**The file has an invalid value
     * Validates a FPRM based on it's type
     * We will ignore extra-values that may be in the FPRM compared to the reference
     * We will add missing values in comparision to the original file
     * @return bool
     */
    public function validate(bool $forImport=false) : bool {
        if(!$this->props->isValid()){
            return false;
        }
        $valid = true;
        $missingVals = [];
        foreach($this->referenceProps->getPropertyNames() as $varName){
            if(!$this->props->has($varName)){
                $missingVals[] = $varName;
            } else {
                try {
                    $this->referenceProps->set($varName, $this->props->get($varName));
                } catch(Exception $e) {
                    // highly improbable but who knows ...
                    // DEBUG
                    if($this->doDebug){ error_log('PROPERTIES VALIDATION PROBLEM: The file has an invalid value "'.$varName.'": '.$e->getMessage()); }
                    $this->validationError = trim($this->validationError."\n".' The file has an invalid value: '.$varName);
                    $valid = false;
                }
            }
        }
        // if there are additional vars we add them to our reference props
        $additionalVals = array_diff($this->props->getPropertyNames(), $this->referenceProps->getPropertyNames());
        if(count($additionalVals) > 0){
            if($this->doDebug){ error_log('PROPERTIES VALIDATION PROBLEM: The file has additional values compared to the reference: ('.implode(', ',$additionalVals ).')'); }
            foreach($additionalVals as $propName){
                $this->referenceProps->add($propName, $this->props->get($propName));
            }
            $this->needsRepair = true;
        }
        if(count($missingVals) > 0){
            // DEBUG
            if($this->doDebug){ error_log('PROPERTIES VALIDATION PROBLEM: The file has missing values compared to the reference: ('.implode(', ',$missingVals ).')'); }
            $this->validationError = trim($this->validationError."\n".'The file has missing values ('.implode(', ',$missingVals ).')');
            // SPECIAL: when importing, we silently ignore missing props, when validating edited FPRMs, we need to be more picky as this hints to an incomplete GUI (maybe due to rainbow updates)
            if(!$forImport){
                $valid = false;
            }
            $this->needsRepair = true;
        }
        if($this->needsRepair){
            $this->content = $this->referenceProps->unparse();
        }
        return $valid;
    }
}

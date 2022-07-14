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
 * Class representing a fprm file in the X-Properties format
 * This is a validation class that validates any X-Properties file against the passed okapiType
 *
 * A typical properties-file looks like this
 *
 * #v1
 * extractIsolatedStrings.b=false
 * codeFinderRules.rule0=</?([A-Z0-9a-z]*)\b[^>]*>
 * extractAllPairs.b=true
 * genericMetaRules=
 * codeFinderRules.count.i=1
 *
 * Types can be defined by appending (.i|.b|.f) to the variable-name, all variables without type are string
 * .i = integer, .b = boolean, .f = float
 */
final class editor_Plugins_Okapi_Bconf_Filter_XProperties extends editor_Plugins_Okapi_Bconf_ResourceFile {

    protected string $mime = 'text/x-properties';

    /**
     * @var array
     */
    private array $map = [];
    /**
     * @var array
     */
    private array $validationMap = [];
    /**
     * @var bool
     */
    private bool $needsRepair = false;

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
            if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: "'.$idata->type.'" seems no valid okapi-type'); }
            $this->validationError = '"'.$idata->type.'" seems no valid okapi-type';
        } else {
            $this->validationError = ''; // to avoid errors due to accessing unitialized vars ...
            $referenceContent = file_get_contents($validationFile);
            if(!$this->parseContent($referenceContent, $this->validationMap)){
                // DEBUG
                if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: Invalid reference file "'.$validationFile.'" ('.$this->validationError.')'); }
                throw new ZfExtended_Exception('Invalid reference file "'.$validationFile.'" ('.$this->validationError.')');
            }
            $this->parseContent($this->content, $this->map);
        }
    }
    /**
     * @return bool
     */
    public function hasToBeRepaired() : bool {
        return $this->needsRepair;
    }

    /**
     * Validates a FPRM based on it's type
     * We will ignore extra-values that may be in the FPRM compared to the reference
     * We will add missing values in comparision to the original file
     * @return bool
     */
    public function validate(bool $forImport=false) : bool {
        if($this->validationError != ''){
            return false;
        }
        $missingVals = [];
        $newMap = [];
        foreach($this->validationMap as $validationVar => $validationVal){
            if(!array_key_exists($validationVar, $this->map)){
                $newMap[$validationVar] = $validationVal;
                $missingVals[] = $validationVar;
            } else {
                $newMap[$validationVar] = $this->map[$validationVar];
            }
        }
        if(count($missingVals) > 0){
            $this->needsRepair = true;
            $this->map = $newMap;
            $this->content = $this->unparseContent($newMap);
            return true;
        } else if(count($missingVals) > 0){
            // DEBUG
            if($this->doDebug){ error_log('X-PROPERTIES VALIDATION PROBLEM: The file has missing values ('.implode(', ',$missingVals ).')'); }
            $this->validationError = trim($this->validationError.' The file has missing values ('.implode(', ',$missingVals ).')');
            unset($newMap);
            return false;
        }
        unset($newMap);
        return true;
    }

    /**
     * Parses a content-string and validates the lines for correct data-types.
     * Also creates a map of the parsed contents
     * @param string $content
     * @param array $map
     * @return bool
     */
    private function parseContent(string $content, array &$map) : bool {
        $content = rtrim(str_replace("\r", '', $content));
        $errors = [];
        $lines = explode("\n", $content);
        if(rtrim($lines[0]) != '#v1'){
            // DEBUG
            if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: The file has an invalid version header "'.rtrim($lines[0]).'"'); }
            $errors[] = 'The file has an invalid version header "'.rtrim($lines[0]).'"';
            return false;
        }
        foreach($lines as $line){
            if(!empty($line) && substr($line, 0, 1) != '#'){
                $pos = strpos($line, '=');
                if($pos !== false && $pos > 0){
                    $var = substr($line, 0, $pos);
                    $val = substr($line, ($pos + 1));
                    $type = (strlen($var) > 2) ? substr($var, -2) : NULL;
                    if($type === '.b'){
                        if($val != 'true' && $val != 'false'){
                            // DEBUG
                            if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: The file has an invalid boolean value ('.$var.'='.$val.')'); }
                            $errors[] = 'The file has an invalid boolean value ('.$var.'='.$val.')';
                        } else {
                            $map[$var] = ($val == 'true') ? true : false;
                        }

                    } else if($type === '.i'){
                        if(!preg_match("/^[0-9]+$/", $val)){
                            // DEBUG
                            if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: The file has an invalid integer value ('.$var.'='.$val.')'); }
                            $errors[] = 'The file has an invalid integer value ('.$var.'='.$val.')';
                        } else {
                            $map[$var] = intval($val);
                        }
                    } else if($type === '.f'){
                        if(!is_numeric($val)){
                            // DEBUG
                            if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: The file has an invalid float value ('.$var.'='.$val.')'); }
                            $errors[] = 'The file has an invalid float value ('.$var.'='.$val.')';
                        } else {
                            $map[$var] = floatval($val);
                        }
                    } else {
                        $map[$var] = $val;
                    }
                } else {
                    // DEBUG
                    if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: The file has an invalid line ('.$line.')'); }
                    $errors[] = 'The file has an invalid line ('.$line.')';
                }
            }
        }
        if(count($map) < 1){
            // DEBUG
            if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: The file contained no valid lines'); }
            $errors[] = 'The file contained no valid lines';
        }
        if(count($errors) > 0){
            // DEBUG
            if($this->doDebug){ error_log('X-PROPERTIES VALIDATION ERROR: "'.basename($this->path).'" is INVALID'); }
            $this->validationError = trim($this->validationError.' '.implode("\n", $errors));
            return false;
        }
        // DEBUG
        if($this->doDebug){ error_log('X-PROPERTIES VALIDATION SUCCESS: "'.basename($this->path).'" is valid'); }
        return true;
    }
    /**
     * Retrieves the content (maybe complemented with potentially missing values)
     * @return string
     */
    private function unparseContent(array $map) : string {
        $content = "#v1\n";
        foreach($map as $var => $val){
            if(is_bool($val)){
                $val = ($val) ? 'true' : 'false';
            } else if(is_numeric($val)){
                $val = strval($val);
            }
            $content .= $var.'='.$val."\n";
        }
        return trim($content);
    }
}

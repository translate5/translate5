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
 * Class Parsing the Properties-files used in the okapi eco-system
 * Note, that this parser might not parse all of the possible features of a properties file
 * A typical properties-fbased fprm has this structure:
 *
 * #v1
 * extractIsolatedStrings.b=false
 * codeFinderRules.rule0=</?([A-Z0-9a-z]*)\b[^>]*>
 * extractAllPairs.b=true
 * genericMetaRules=
 * codeFinderRules.count.i=1
 *
 * Types can be defined by appending (.i|.b) to the variable-name, all variables without type are string
 * .i = integer, .b = boolean
 *
 * Reference:
 * net.sf.okapi.common.ParametersString
 * net.sf.okapi.common.StringParameters
 * net.sf.okapi.common.BaseParameters
 *
 */
final class editor_Plugins_Okapi_Bconf_Parser_Properties {

    /**
     * @var array
     */
    private array $map = [];

    /**
     * @var array
     */
    private array $errors = [];

    /**
     * @param string $content
     */
    public function __construct(string $content){
        $this->parse($content);
    }

    /**
     * Returns a hashtable of the decoded properties
     * @return bool
     */
    public function getProperties() : array {
        return $this->map;
    }

    /**
     * Retrieves our property-names
     * @return array
     */
    public function getPropertyNames() : array {
        return array_keys($this->map);
    }

    /**
     * @return bool
     */
    public function isValid() : bool {
        return(count($this->errors) === 0);
    }

    /**
     * @return array
     */
    public function getErrors() : array {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getErrorString(string $seperator="\n") : string {
        return implode($seperator, $this->errors);
    }

    /**
     * Retrieves the type of a variable by it's name as defined in
     * See net.sf.okapi.common.ParametersString
     * @param $propName
     * @return string integer|boolean|string
     */
    public function getDataType($propName) : string {
        $type = (strlen($propName) > 2) ? substr($propName, -2) : NULL;
        if($type === '.b'){
            return 'boolean';
        }
        if($type === '.i'){
            return 'integer';
        }
        return 'string';
    }

    /**
     * @param string $propName
     * @return bool
     */
    public function has(string $propName) : bool {
        return array_key_exists($propName, $this->map);
    }

    /**
     * @param string $propName
     * @return string|bool|int
     * @throws ZfExtended_NotFoundException
     */
    public function get(string $propName){
        if($this->has($propName)){
            return $this->map[$propName];
        }
        throw new ZfExtended_NotFoundException('Property '.$propName.' not found');
    }

    /**
     * @param string $propName
     * @param string|bool|int $value
     * @throws ZfExtended_Exception
     * @throws ZfExtended_NotFoundException
     */
    public function set(string $propName, $value){
        if($this->has($propName) && $this->validateProp($propName, $value)){
            $this->map[$propName] = $value;
        }
        throw new ZfExtended_NotFoundException('Property '.$propName.' not found');
    }

    /**
     * @param string $propName
     * @param string|bool|int $value
     * @throws ZfExtended_Exception
     */
    public function add(string $propName, $value){
        if(!$this->has($propName) && $this->validateProp($propName, $value)){
            $this->map[$propName] = $value;
        }
        throw new ZfExtended_Exception('Property '.$propName.' already exists');
    }

    /**
     * Generates content out of our parsed map (which will clean all comments & empty lines)
     * @return string
     */
    public function unparse() : string {
        $content = "#v1";
        foreach($this->map as $varName => $val){
            $type = $this->getDataType($varName);
            if($type === 'boolean'){
                $val = ($val === true) ? 'true' : 'false';
            } else if($type === 'integer'){
                $val = strval($val);
            } else {
                $val = $this->escape($val);
            }
            $content .= "\n".$varName.'='.$val;
        }
        return $content;
    }

    /**
     * Parses a content-string and validates the lines for correct data-types.
     * and creates a map of the parsed contents
     * @param string $content
     */
    private function parse(string $content) {
        $content = rtrim(str_replace("\r", '', $content)); // for robustness
        $lines = explode("\n", $content);
        if(rtrim($lines[0]) != '#v1'){
            $this->errors[] = 'Invalid version header "'.rtrim($lines[0]).'"';
        }
        foreach($lines as $line){
            if(!empty($line) && substr($line, 0, 1) != '#'){
                $pos = strpos($line, '=');
                if($pos !== false && $pos > 0){
                    $varName = trim(substr($line, 0, $pos));
                    $val = substr($line, ($pos + 1));
                    $type = $this->getDataType($varName);
                    if($type === 'boolean'){
                        // boolean
                        $val = trim($val);
                        if($val != 'true' && $val != 'false'){
                            $this->errors[] = 'Found invalid boolean value ('.$varName.'='.$val.')';
                        } else {
                            $this->map[$varName] = ($val == 'true') ? true : false;
                        }
                    } else if($type === 'integer'){
                        // integer
                        $val = trim($val);
                        if(!preg_match("/^-?[0-9]+$/", $val)){
                            $this->errors[] = 'Found invalid integer value ('.$varName.'='.$val.')';
                        } else {
                            $this->map[$varName] = intval($val);
                        }
                    } else {
                        // strings are not trimmed but unescaped
                        $this->map[$varName] = $this->unescape($val);
                    }
                } else {
                    $this->errors[] = 'Found invalid line ('.$line.')';
                }
            }
        }
        if(count($this->map) < 1){
            $this->errors[] = 'Found no valid lines';
        }
    }

    /**
     * Escapes a String for usage in a properties-file
     * See net.sf.okapi.common.ParametersString
     * @param string $value
     * @return array|string|string[]
     */
    private function escape(string $value) {
        return str_replace(["\r", "\n"], ["$0d$", "$0a$"], $value);
	}

    /**
     * Unscapes a String for usage in a properties-file
     * See net.sf.okapi.common.ParametersString
     * @param string $value
     * @return array|string|string[]
     */
    private function unescape (string $value) {
        return str_replace(["$0d$", "$0a$"], ["\r", "\n"], $value);
    }

    /**
     * Validates, the value type matches the variable name
     * @param string $propName
     * @param string|bool|int $value
     * @throws ZfExtended_Exception
     */
    private function validateProp(string $propName, $value) : bool {
        $type = $this->getDataType($propName);
        if(($type === 'boolean' && !is_bool($value)) || ($type === 'integer' && !is_int($value)) || ($type === 'string' && !is_string($value))){
            throw new ZfExtended_Exception('Property '.$propName.' is not of the right type '.$type);
        }
        return true;
    }
}

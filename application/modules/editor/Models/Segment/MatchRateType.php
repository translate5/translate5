<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * MatchRateType String Class
 * - usable as a String to the translate5 representation of the matchratetype
 * - defines flexible converter methods for the several file types to be imported
 * - defines the match rate types usable in translate5 (flexible extensionable by plugins!?)
 */
class editor_Models_Segment_MatchRateType {
    /**
     * When the target was from a TM
     * @var string
     */
    const TYPE_TM = 'tm';
    
    /**
     * When the target was from a MT
     * @var string
     */
    const TYPE_MT = 'mt';
    
    /**
     * When the target was copied from source
     * @var string
     */
    const TYPE_SOURCE = 'source';
    
    /**
     * When the target was changed manually
     * @var string
     */
    const TYPE_INTERACTIVE = 'interactive';
    
    /**
     * When the target was auto-propagated (change alikes used)
     * @var string
     */
    const TYPE_AUTO_PROPAGATED = 'auto-propagated';
    
    /**
     * When the target was auto-aligned
     * @var string
     */
    const TYPE_AUTO_ALIGNED = 'auto-aligned';
    
    /**
     * When the target was in the same document
     * @var string
     */
    const TYPE_DOCUMENT_MATCH = 'document-match';
    
    /**
     * When the given matchRateType is unknown (no mapping value found)
     * @var string
     */
    const TYPE_UNKNOWN = 'unknown';
    
    /**
     * When in the segment no information was given
     * @var string
     */
    const TYPE_NONE = 'none';
    
    /**
     * When the matchrate was 0 and the target = empty
     * @var string
     */
    const TYPE_EMPTY = 'empty';
    
    /**
     * Uses as match rate prefix when the value comes from import
     * @var string
     */
    const PREFIX_IMPORT = 'import';
    
    /**
     * Uses as match rate prefix when the value was changed in translate5
     * @var string
     */
    const PREFIX_EDITED = 'edited';
    
    /**
     * internal map for import conversion 
     * @var array
     */
    protected $importMap = null;
    protected $importClosure = null;

    /**
     * Internal container of the match type fragments
     * @var array
     */
    protected $data = array(self::PREFIX_IMPORT, self::TYPE_UNKNOWN);
    
    /**
     * internal cache of valid types, derived from class constants
     * @var array
     */
    static protected $validTypes = array();
    
    /**
     * @var ZfExtended_Log
     */
    static protected $log;
    
    public function __construct() {
        self::initValidTypes();
        self::initLog();
    }
    
    public function init(string $initialValue) {
        $this->data = explode(';', $initialValue);
    }

    /**
     * caches the matchrate types in an internal static array for easy checking
     */
    static protected function initValidTypes() {
        if(!empty(self::$validTypes)) {
            return;
        }
        $class = new ReflectionClass(__CLASS__);
        $consts = $class->getConstants();
        foreach($consts as $const => $value) {
            if(substr($const, 0, 5) === 'TYPE_') {
                self::$validTypes[$const] = $value;
            }
        }
    }
    
    /**
     * inits the system logger
     */
    static protected function initLog() {
        if(empty(self::$log)) {
            self::$log = ZfExtended_Factory::get('ZfExtended_Log');
        }
    }
    
    /**
     * generates the matchrate type by imported segment data
     * @param editor_Models_Import_FileParser_SegmentAttributes $importedValue the plain value from 
     * @param mixed $mid segment mid for logging purposes only
     * @return editor_Models_Segment_MatchRateType
     */
    public function parseImport(editor_Models_Import_FileParser_SegmentAttributes $attributes, $mid){
        $importedValue = $attributes->matchRateType;
        $this->data = [self::PREFIX_IMPORT];
        
        if(!$attributes->isTranslated && $attributes->matchRate === 0) {
            $this->data[] = self::TYPE_EMPTY;
            return $this;
        }
        
        if(empty($importedValue) || $importedValue == self::PREFIX_IMPORT) {
            $this->data[] = self::TYPE_NONE;
            return $this;
        }
        
        $value = $this->useMap($importedValue);
        $value = $this->useCallback($value);
        
        if(empty($value) || $this->isValidType($value) === false) {
            //logs the info when a unknown matchrate type:
            self::$log->logError('The given matchrate type '.$value.' in Segment MID '.$mid.' is unknown!');
            $this->data[] = self::TYPE_UNKNOWN;
        }
        $this->data[] = $value;
        return $this;
    }
    
    /**
     * creates the match rate type string usable when changed by translate5 or its plugins
     * @param string $type
     * @param string $plugin
     * @return editor_Models_Segment_MatchRateType
     */
    public function initEdited(... $types) {
        //fallback when no type was given
        if(empty($types)) {
            $types = [self::TYPE_INTERACTIVE];
        }
        foreach($types as $type) {
            if(!$this->isValidType($type)) {
                array_unshift($types, self::TYPE_UNKNOWN);
                break;
            }
        }
        array_unshift($types, self::PREFIX_EDITED);
        $this->data = $types;
        return $this;
    }
    
    /**
     * Adds a valid type
     * @param string $type
     * @param boolean $unique optional, per default add only types not existing already
     */
    public function add(string $type, $unique = true) {
        if($this->isValidType($type) && (!$unique || ($unique && !in_array($type, $this->data)))) {
            $this->data[] = $type;
        }
    }
    
    /**
     * Sets a value based map to find the local value
     * first the map is applied, then the callback is called!
     */
    public function setImportMap(array $map) {
        $this->importMap = $map;
    }
    
    /**
     * Sets a closure to calculate the local value
     * first the map is applied, then the callback is called!
     * The closure should return null or empty string if value is invalid!
     */
    public function setImportClosure(Closure $callback) {
        $this->importClosure = $callback;
    }
    
    /**
     * converts the value map based
     * @param string $value
     */
    protected function useMap(string $value) {
        if(empty($this->importMap)) {
            return $value;
        }
        if(empty($this->importMap[$value])) {
            return null;
        }
        return $this->importMap[$value];
    }
    
    /**
     * converts the value closure based
     * @param string $value
     */
    protected function useCallback(string $value) {
        if(empty($this->importClosure) || !is_callable($this->importClosure)) {
            return $value;
        }
        return $this->importClosure($value);
    }
    
    /**
     * checks if the given type can be used in translate5 (without prefix!)
     * @param string $value
     * @return mixed returns the found const name, or false if type is invalid
     */
    protected function isValidType(string $type) {
        return array_search($type, self::$validTypes);
    }
    
    /**
     * returns if the whole type is valid or not
     * @return boolean
     */
    public function isValid() {
        $cnt = count($this->data);
        if($cnt <= 1) {
            return false;
        }
        $data = $this->data;
        $prefix = array_shift($data);
        if($prefix != self::PREFIX_EDITED && $prefix != self::PREFIX_IMPORT) {
            return false;
        }
        foreach($data as $oneType) {
            if(!$this->isValidType($oneType)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * returns true if is a EDITED type
     * @return boolean
     */
    public function isEdited() {
        return isset($this->data[0]) && $this->data[0] == self::PREFIX_EDITED;
    }
    
    /**
     * returns true if is a IMPORT type
     * @return boolean
     */
    public function isImported() {
        return isset($this->data[0]) && $this->data[0] == self::PREFIX_IMPORT;
    }
    
    /**
     * returns the string representation of this match rate type
     */
    public function __toString() {
        return join(';', $this->data);
    }
}
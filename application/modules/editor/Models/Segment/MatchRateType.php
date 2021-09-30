<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
     * When the target was from a termcollection
     * @var string
     */
    const TYPE_TERM_COLLECTION = 'termcollection';
    
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
     * @var string
     */
    const TYPE_MISSING_SOURCE_MRK = 'missing-source-mrk';
    
    /**
     * @var string
     */
    const TYPE_MISSING_TARGET_MRK = 'missing-target-mrk';
    
    /***
     *
     * @var string
     */
    const TYPE_INTERNAL_FUZZY_AVAILABLE = 'internal-fuzzy-available';
    
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
     * Uses as match rate prefix when the value was changed in translate5
     * @var string
     */
    const PREFIX_PRETRANSLATED = 'pretranslated';
    
    /**
     * Used as key for the flexible customMetaAttributes in segment attributes
     * @var string
     */
    const DATA_PREVIOUS_ORIGIN = 'previousOrigin';
    
    /**
     * Used as key for the flexible customMetaAttributes in segment attributes
     * @var string
     */
    const DATA_PREVIOUS_NAME = 'previousOriginName';
    
    /**
     * Defines the types to be considered as pretranslation sources
     * @var array
     */
    const PRETRANSLATION_TYPES = [self::TYPE_TM, self::TYPE_MT];
    /**
     * Defines the types to be considered as language resources and can be taken over from the matches panel
     * @var array
     */
    const RESOURCE_TYPES = [self::TYPE_TM, self::TYPE_MT, self::TYPE_TERM_COLLECTION];
    
    /***
     * All match rate types which are requiring an icon
     * @var array
     */
    const TYPES_WITH_ICONS = [
        self::TYPE_TM,
        self::TYPE_MT,
        self::TYPE_TERM_COLLECTION,
        self::TYPE_INTERNAL_FUZZY_AVAILABLE,
        self::TYPE_SOURCE,
        self::TYPE_AUTO_ALIGNED,
        self::TYPE_AUTO_PROPAGATED,
        self::TYPE_DOCUMENT_MATCH,
        self::TYPE_MISSING_SOURCE_MRK,
        self::TYPE_MISSING_TARGET_MRK,
        self::TYPE_INTERACTIVE,
        self::TYPE_UNKNOWN,
    ];
    
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
     * Error collector
     * @var ZfExtended_Logger_DataCollection
     */
    protected $errors = [];
    
    public function __construct() {
        self::initValidTypes();
        $this->errors = ZfExtended_Factory::get('ZfExtended_Logger_DataCollection', ['editor.import.matchratetype',[
            'E1193' => 'File "{file}" contains unknown matchrate types. See details.'
        ]]);
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
     * Returns true if the given type may be updated (either in the segment itself, or the whole segment into another TM)
     */
    static public function isUpdateable($type) {
        $type = explode(';', $type);
        if(in_array(self::TYPE_MISSING_SOURCE_MRK, $type)){
            return false;
        }
        if(in_array(self::TYPE_MISSING_TARGET_MRK, $type)){
            return false;
        }
        return true;
    }
    /**
     * Evaluates if a matchRateType reflects a pretranslation
     * @param string $type
     * @return boolean
     */
    public static function isTypePretranslated($type) {
        $type = explode(';', $type);
        return in_array(self::PREFIX_PRETRANSLATED, $type);
    }
    
    /**
     * Evaluates if a matchRateType reflects a user edit
     * @param string $type
     * @return boolean
     */
    public static function isTypeEdited($type) {
        $type = explode(';', $type);
        return in_array(self::PREFIX_EDITED, $type);
    }
    /**
     * retrieves if the matchRateType generally originates from a Machine Translation / MT
     * @param string $type
     * @return bool
     */
    public static function isTypeMT(string $type): bool {
        return in_array(self::TYPE_MT, explode(';', $type));
    }
    /**
     * returns true if the given matchRateType was imported or is pretranslated from a Machine Translation / MT
     * TODO / FIXME: the naming should better differntiate from ::isTypeMT
     * @param string $type
     * @return bool
     */
    public static function isFromMT(string $type): bool {
        return strpos($type, self::PREFIX_IMPORT.';'.self::TYPE_MT) === 0 || strpos($type, self::PREFIX_PRETRANSLATED.';'.self::TYPE_MT) === 0;
    }
    /**
     * retrieves if the matchRateType originates from a taken over Machine Translation / MT match
     * @param string $type
     * @return bool
     */
    public static function isEditedMT(string $type): bool {
        $types = explode(';', $type);
        return $types[0] == self::PREFIX_EDITED && in_array(self::TYPE_MT, $types);
    }
    /**
     * retrieves if the matchRateType generally originates from a Translation Memory / TM
     * @param string $type
     * @return bool
     */
    public static function isTypeTM(string $type): bool {
        return in_array(self::TYPE_TM, explode(';', $type));
    }
    /**
     * returns true if the given matchtype was imported or is pretranslated from a Translation Memory / TM
     * TODO / FIXME: the naming should better differntiate from ::isTypeTM
     * @param string $type
     * @return bool
     */
    public static function isFromTM(string $type): bool {
        return strpos($type, self::PREFIX_IMPORT.';'.self::TYPE_TM) === 0 || strpos($type, self::PREFIX_PRETRANSLATED.';'.self::TYPE_TM) === 0;
    }
    /**
     * retrieves if the matchRateType originates from a taken over Translation Memory / TM match
     * @param string $type
     * @return bool
     */
    public static function isEditedTM(string $type): bool {
        $types = explode(';', $type);
        return $types[0] == self::PREFIX_EDITED && in_array(self::TYPE_TM, $types);
    }
    /**
     * retrieves if the matchRateType results from a taken over (TM, MT, TermCollection) or pretranslated (TM, MT) Language Resource
     * @param string $type
     * @return bool
     */
    public static function isTypeLanguageResource(string $type): bool {
        $types = explode(';', $type);
        return (count(array_intersect($types, self::RESOURCE_TYPES)) > 0);
    }
    /**
     * generates the matchRateType type by imported segment data
     * @param editor_Models_Import_FileParser_SegmentAttributes $importedValue the plain value from
     * @param mixed $mid segment mid for logging purposes only
     * @return editor_Models_Segment_MatchRateType
     */
    public function parseImport(editor_Models_Import_FileParser_SegmentAttributes $attributes, $mid){
        $importedValue = $attributes->matchRateType;
        
        $this->data = [self::PREFIX_IMPORT];
        
        $previousOrigin = null;
        if(array_key_exists(self::DATA_PREVIOUS_ORIGIN, $attributes->customMetaAttributes)){
            $previousOrigin = strtoupper($attributes->customMetaAttributes[self::DATA_PREVIOUS_ORIGIN]);
            unset($attributes->customMetaAttributes[self::DATA_PREVIOUS_ORIGIN]);
        }
        $previousName = null;
        if(array_key_exists(self::DATA_PREVIOUS_NAME, $attributes->customMetaAttributes)){
            $previousName = $attributes->customMetaAttributes[self::DATA_PREVIOUS_NAME];
            unset($attributes->customMetaAttributes[self::DATA_PREVIOUS_NAME]);
        }
        
        if($importedValue == self::TYPE_MISSING_SOURCE_MRK || $importedValue == self::TYPE_MISSING_TARGET_MRK) {
            $this->data[] = $importedValue;
            return $this;
        }
        
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
            $this->errors->add('E1193', ['mid' => $mid, 'matchRateType' => $value]);
            $this->data[] = self::TYPE_UNKNOWN;
        }
        
        $this->data[] = $value;
        if(!is_null($previousOrigin)) {
            $this->data[] = $previousOrigin;
        }
        if(!is_null($previousName)) {
            $this->data[] = $previousName;
        }
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
        array_unshift($types, self::PREFIX_EDITED);
        $this->data = $types;
        return $this;
    }
    
    /**
     * creates the match rate type string usable when pretranslated by translate5 or its plugins
     * @param string $type
     * @param string $plugin
     * @return editor_Models_Segment_MatchRateType
     */
    public function initPretranslated(... $types) {
        //fallback when no type was given
        if(empty($types)) {
            $types = [self::TYPE_UNKNOWN];
        }
        else {
            $type = reset($types);
            if(!$this->isValidType($type)) {
                array_unshift($types, self::TYPE_UNKNOWN);
            }
        }
        array_unshift($types, self::PREFIX_PRETRANSLATED);
        $this->data = $types;
        return $this;
    }
    
    /**
     * Adds a valid type
     * @param string $type
     * @param bool $unique optional, per default add only types not existing already
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
     * returns true if the given type (as string parameter) is a pretranslation type (currently TM and MT)
     * @param string $type
     * @return bool
     */
    public function isPretranslationType(string $type): bool {
        return in_array($type, self::PRETRANSLATION_TYPES);
    }
    /**
     * returns the string representation of this match rate type
     */
    public function __toString() {
        return join(';', $this->data);
    }
    
    /**
     * logs the collected errors if any
     * @param string $fileName
     * @param editor_Models_Task $task
     */
    public function logErrors(string $fileName, editor_Models_Task $task) {
        $this->errors->warn('E1193', [
            'file' => $fileName,
            'task' => $task,
        ]);
    }
}
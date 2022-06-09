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
 * The master class for all Test Models
 * A test model usually defines how an entity of the REST API will be compared with captured data
 * All fields to compare must be defined in ::$compared and for those fields that need to be sanitized a sanitation can be provided
 * The sanitization in ::$sanitized is a assoc array where the key is the field-name and the value is a method name in editor_Test_Sanitizer
 * The ::$messageField defines a field to use for identifying the entity with auto-generated message texts
 */
abstract class editor_Test_Model_Abstract {
    
    /**
     * generates an instance of the given type
     * This expects, that a model class for the passed type exists, e.g. if you pass 'segment' a editor_Test_Model_Segment will be created
     * @param stdClass $data
     * @param string $type
     * @return editor_Test_Model_Abstract
     */
    public static function create(stdClass $data, string $type){
        $className = 'editor_Test_Model_'.ucfirst($type);
        return new $className($data);
    }
    /**
     * FIXME make a black list instead a whitelist here!!!
     * Defines the fields that are compared, all others are ignored
     * If the field does not exist in the passed data, it will be generated with NULL as value
     * @var string[]
     */
    protected $compared = [];
    /**
     * Defines the fields that are sanitized and the type of saintation applied (which will point to a method of editor_Test_Sanitizer)
     * Fields defined here must not appear in compared
     * entries are like 'field' => 'sanitizationtype'
     * If the field does not exist in the passed data, it will be generated with NULL as value
     * @var string[]
     */
    protected $sanitized = [];
    /**
     * This Field defines if this is a tree (as ExtJs uses them)
     * If set, the tree will be created recursively and the tree can be compared by comparing the root element or any branch can be compared as well
     * This field MUST NOT appear in $compared, otherwise the original data may be manipulated
     * @var boolean
     */
    protected $isTree = false;
    /**
     * Defines the fields of the root-node of a tree being sanitized.
     * ONLY the root node fields will be sanitized, this is to come around issues with root-nodes usually containing the task-ID in the text
     * @var array
     */
    protected $treeRootSanitized = [];
    /**
     * This defines a field that will be added to the default message to identify the model
     * This can be a field not added to the equation
     * @var string
     */
    protected $messageField = 'id';
    /**
     * 
     * @var stdClass
     */    
    private $_data;
    /**
     * 
     * @var string
     */
    private $_identification = null;

    /**
     * 
     * @param stdClass $data
     */
    public function __construct(stdClass $data){
        $this->_data = $data;
        if(!empty($this->messageField) && property_exists($data, $this->messageField)){
            $field = $this->messageField;
            $this->_identification = $data->$field;
        }
    }
    /**
     * dynamically adds a field that will be compared
     * @param string $field
     * @return editor_Test_Model_Abstract
     */
    public function addComparedField(string $field) : editor_Test_Model_Abstract {
        if(!in_array($field, $this->compared)){
            $this->compared[] = $field;
        }
        return $this;
    }
    /**
     * dynamically adds a sanitized field that will be compared
     * Keep in mind the passed sanitization must exist in editor_Test_Sanitizer
     * @param string $field
     * @param string $sanitizationName
     * @return editor_Test_Model_Abstract
     */
    public function addSanitizedField(string $field, string $sanitizationName) : editor_Test_Model_Abstract {
        $this->sanitized[$field] = $sanitizationName;
        return $this;
    }
    /**
     * Removes the field, either if a normal compared or a sanitized field
     * @param string $field
     * @return editor_Test_Model_Abstract
     */
    public function removeComparedField(string $field) : editor_Test_Model_Abstract {
        if(($idx = array_search($field, $this->compared)) !== false) {
            unset($this->compared[$idx]);
        }
        if(array_key_exists($field, $this->sanitized)){
            unset($this->sanitized[$field]);
        }
        return $this;
    }
    /**
     * Copies & sanitizes our data
     * @param stdClass $data
     * @param editor_Test_Model_Filter|null $treeFilter: If given, a passed tree data will be filtered according to the passed filter
     * @param bool $isRoot: internal prop to hint if an item is the root of a tree
     * @return stdClass
     */
    protected function copy(stdClass $data, editor_Test_Model_Filter $treeFilter=NULL, bool $isRoot=true) : stdClass {
        $result = new stdClass();
        // copy sanitized fields
        foreach($this->sanitized as $field => $functionName){
            if(property_exists($data, $field)){
                $result->$field = editor_Test_Sanitizer::$functionName($data->$field);
            } else {
                $result->$field = NULL;
            }
        }
        // copy unsanitized fields (if not already defined in $sanitized)
        foreach($this->compared as $field){
            if(!property_exists($result, $field)){
                if(property_exists($data, $field)){
                    $result->$field = $data->$field;
                } else {
                    $result->$field = NULL;
                }
            }
        }
        // additional sanitization of the Root field of a tree
        if($this->isTree && $isRoot && count($this->treeRootSanitized) > 0){
            foreach($this->treeRootSanitized as $field => $functionName){
                if(property_exists($data, $field)){
                    $result->$field = editor_Test_Sanitizer::$functionName($data->$field);
                } else {
                    $result->$field = NULL;
                }
            }
        }
        // recursive tree processing
        if($this->isTree && property_exists($data, 'children')){
            $result->children = [];
            foreach($data->children as $child){
                if($treeFilter === NULL || $treeFilter->matches($child)){
                    $result->children[] = $this->copy($child, $treeFilter, false);
                }
            }
        }

        //reorder the wanted data, so that it matches to the old order for easier comparsion with diff
        if(ZfExtended_Test_ApiHelper::isLegacyData() && $this instanceof editor_Test_Model_Segment) {
            $foo = [
                'segmentNrInTask' => null,
                'mid' => null,
                'userGuid' => null,
                'userName' => null,
                'editable' => null,
                'pretrans' => null,
                'matchRate' => null,
                'matchRateType' => null,
                'stateId' => null,
                'autoStateId' => null,
                'fileOrder' => null,
                'comments' => null,
                'workflowStepNr' => null,
                'workflowStep' => null,
                'source' => null,
                'sourceMd5' => null,
                'sourceToSort' => null,
                'target' => null,
                'targetMd5' => null,
                'targetToSort' => null,
                'targetEdit' => null,
                'targetEditToSort' => null,
                'metaCache' => null,
                'isWatched' => null,
                'isRepeated' => null,
            ];
            //fix the order of the associated array vs object then.
            $result = (array) $result;
            $result = (object) array_replace(array_intersect_key($foo, $result), $result);
        }
        return $result;
    }
    /**
     * Generates a default message
     * @param string $message
     * @return string
     */
    protected function getDefaultMessage(string $message) : string {
        if(!empty($message)){
            return $message;
        }
        $parts = explode('_', __CLASS__);
        $message = 'Comparing '.array_pop($parts);
        $message .= ($this->_identification == NULL) ? ' failed!' : ' "'.$this->_identification.'" failed!';
        return $message;
    }
    /**
     * Compares the Model with test data for the passed testcase
     * @param editor_Test_JsonTest $testCase
     * @param stdClass $expected
     * @param string $message
     * @param editor_Test_Model_Filter|null $treeFilter: If given, a passed tree data will be filtered according to the passed filter
     */
    public function compare(editor_Test_JsonTest $testCase, stdClass $expected, string $message='', editor_Test_Model_Filter $treeFilter=NULL){
        $this->compareExpectation($testCase, $expected, $message, $treeFilter);
    }
    /**
     * Since test-models can act as expectation or actual data we provide both directions of comparision (a test-model is expected to represent real API data though)
     * @param editor_Test_JsonTest $testCase
     * @param stdClass $expected
     * @param string $message
     * @param editor_Test_Model_Filter|null $treeFilter: If given, a passed tree data will be filtered according to the passed filter
     */
    public function compareExpectation(editor_Test_JsonTest $testCase, stdClass $expected, string $message='', editor_Test_Model_Filter $treeFilter=NULL){
        $testCase->assertEquals($this->copy($expected, $treeFilter), $this->copy($this->_data, $treeFilter), $message);
    }
    /**
     * Since test-models can act as expectation or actual data we provide both directions of comparision (a test-model is expected to represent real API data though)
     * @param editor_Test_JsonTest $testCase
     * @param stdClass $actual
     * @param string $message
     * @param editor_Test_Model_Filter|null $treeFilter: If given, a passed tree data will be filtered according to the passed filter
     */
    public function compareActual(editor_Test_JsonTest $testCase, stdClass $actual, string $message='', editor_Test_Model_Filter $treeFilter=NULL){
        $testCase->assertEquals($this->copy($this->_data, $treeFilter), $this->copy($actual, $treeFilter), $message);
    }
    /**
     * Retrieves the transformed data for comparision
     * @return stdClass
     */
    public function getComparableData(){
        return $this->copy($this->_data);
    }
}

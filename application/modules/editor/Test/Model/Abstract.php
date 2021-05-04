<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
     * Defines the fields that are compared, all others are ignored
     * If the field does not exist in the passed data, it will be generated with NULL as value
     * @var string[]
     */
    protected $compared = [];
    /**
     * Defines the fields that are sanitized and the type of saintation applied (which will point to a method of editor_Test_Sanitizer)
     * Fields defined here must not appear in _compared
     * entries are like 'field' => 'sanitizationtype'
     * If the field does not exist in the passed data, it will be generated with NULL as value
     * @var string[]
     */
    protected $sanitized = [];
    /**
     * This Field defines the children field for a tree (in ExtJs 'children')
     * If set, the tree will be created recursively and the tree can be compared by comparing the root element or any branch can be compared as well
     * This field MUST not appear in $compared, otherwise the original data may will be manipulated
     * @var array
     */
    protected $tree = null;
    /**
     * Further configuration for trees: usually the root node is the first child of the request-object's children array and has the property "root" set (ExtJS-speciality)
     * @var boolean
     */
    protected $firstChildIsRoot = false;
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
     * @return stdClass
     */
    protected function copy(stdClass $data) : stdClass {
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
        // recursive tree processing
        if(!empty($this->tree) && property_exists($data, $this->tree)){
            $children = $this->tree;
            $result->$children = [];
            foreach($data->$children as $child){
                $result->$children[] = $this->copy($child);
            }
        }
        return $result;
    }
    /**
     * Creates the comparable object out of the given data
     * @param stdClass $data
     * @return stdClass
     */
    protected function createComparableData(stdClass $data){
        if($this->tree != NULL && $this->firstChildIsRoot){
            $children = $this->tree;
            if(property_exists($data, $children) && is_array($data->$children) && count($data->$children) > 0){
                return $this->copy($data->$children[0]);
            } else {
                $result = new stdClass();
                $result->error = 'The passed data had no children in the root object';
                return $result;
            }
        }
        return $this->copy($data);
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
        $message = 'Comparing '.array_pop(explode('_', __CLASS__));
        $message .= ($this->_identification == NULL) ? ' failed!' : ' "'.$this->_identification.'" failed!';
        return $message;
    }
    /**
     * Compares the Model with test data for the passed testcase
     * @param editor_Test_JsonTest $testCase
     * @param stdClass $expected
     * @param string $message
     */
    public function compare(editor_Test_JsonTest $testCase, stdClass $expected, string $message=''){
        $this->compareExpectation($testCase, $expected, $message);
    }
    /**
     * Since test-models can act as expectation or actual data we provide both directions of comparision (a test-model is expected to represent real API data though)
     * @param editor_Test_JsonTest $testCase
     * @param stdClass $expected
     * @param string $message
     */
    public function compareExpectation(editor_Test_JsonTest $testCase, stdClass $expected, string $message=''){
        $testCase->assertEquals($this->createComparableData($expected), $this->createComparableData($this->_data), $message);
    }
    /**
     * Since test-models can act as expectation or actual data we provide both directions of comparision (a test-model is expected to represent real API data though)
     * @param editor_Test_JsonTest $testCase
     * @param stdClass $actual
     * @param string $message
     */
    public function compareActual(editor_Test_JsonTest $testCase, stdClass $actual, string $message=''){
        $testCase->assertEquals($this->createComparableData($this->_data), $this->createComparableData($actual), $message);
    }
    /**
     * Retrieves the transformed data for comparision
     * @return stdClass
     */
    public function getComparableData(){
        return $this->createComparableData($this->_data);
    }
}

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
 * Abstraction layer for API tests comparing REST-Data with stored JSON files
 * To achieve this, a Model-based architecture is used that filters & sanitizes both, the REST data & the JSON data before comparing them.
 * This solves problems with autoincrement values & other dynamic data
 * See editor_Test_Model_Abstract & descendants
 */
abstract class editor_Test_JsonTest extends editor_Test_ImportTest {

    /* Segment model specific API */
    
    /**
     * Adjuts the passed texts to clean up field tags for comparision
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    public function assertFieldTextEquals(string $expected, string $actual, string $message=''){
        return $this->assertEquals(
            editor_Test_Sanitizer::fieldtext($expected),
            editor_Test_Sanitizer::fieldtext($actual),
            $message);
    }
    /**
     * compares the given segment content to the content in the given assert file
     * @param string $fileToCompare
     * @param stdClass $segment
     * @param string $message
     * @param boolean $keepComments
     */
    public function assertSegmentEqualsJsonFile(string $fileToCompare, stdClass $segment, string $message='', bool $keepComments=true){
        $this->assertSegmentEqualsObject(static::api()->getFileContent($fileToCompare), $segment, $message, $keepComments);
    }
    /**
     * compares the given segment content with an expectation object
     * @param stdClass $expectedObj
     * @param stdClass $segment
     * @param string $message
     * @param boolean $keepComments
     */
    public function assertSegmentEqualsObject(stdClass $expectedObj, stdClass $segment, string $message='', bool $keepComments=true){
        $model = editor_Test_Model_Abstract::create($segment, 'segment');
        if(!$keepComments){
            $model->removeComparedField('comments');
        }
        $model->compare($this, $expectedObj, $message);
    }
    /**
     * Compares an array of segments with a file (which must contain those segments as json-array)
     * @param string $fileToCompare
     * @param stdClass[] $segments
     * @param string $message
     * @param boolean $keepComments
     */
    public function assertSegmentsEqualsJsonFile(string $fileToCompare, array $segments, string $message='', bool $keepComments=true){
        if(static::api()->isCapturing()) {
            foreach($segments as $idx => $segment) {
                $model = editor_Test_Model_Abstract::create($segment, 'segment');
                $segments[$idx] = $model->getComparableData();
            }
            // on capturing we disable assert existence
            static::api()->captureData($fileToCompare, $segments, encode: true);
        }
        $expectations = static::api()->getFileContent($fileToCompare);
        $numSegments = count($segments);
        $numExpectations = count($expectations);
        if($numSegments === $numExpectations) {

            for($i=0; $i < $numSegments; $i++){
                $msg = (empty($message)) ? '' : $message.' [Segment '.($i + 1).']';
                $this->assertSegmentEqualsObject($expectations[$i], $segments[$i], $msg, $keepComments);
            }
        } else {
            $this->assertEquals($numSegments, $numExpectations, $message.' [Number of segments does not match the expectations]');
        }
    }

    /***
     * Check if the languageresource tm result in the provided file is the same as the given tmResult.
     * In $tmResults non required data will be removed.
     * @param string $fileToCompare
     * @param array $tmResults
     * @param string $message
     */
    public function assertTmResultEqualsJsonFile(string $fileToCompare, array $tmResults, string $message){
        $expectations = static::api()->getFileContent($fileToCompare);
        $tmUnset = function ($in){
            unset($in->languageResourceid);
            unset($in->metaData);
        };
        foreach ($tmResults as &$res){
            $tmUnset($res);
        }
        $this->assertEquals($tmResults,$expectations ,$message);
    }
    
    /* Comment model specific API */
    
    /**
     * Compares an 2-dimensional array of comments with a file (which must contain those comments as json-array)
     * @param string $fileToCompare
     * @param stdClass[] $comments
     * @param string $message
     * @param boolean $removeDates
     */
    public function assertCommentsEqualsJsonFile(string $fileToCompare, array $comments, string $message='', bool $removeDates=false){
        $expectations = static::api()->getFileContent($fileToCompare);
        $numComments = count($comments);
        if($numComments != count($expectations)){
            $this->assertEquals($numComments, count($expectations), $message.' [Number of comments does not match the expectations]');
        } else {
            for($i=0; $i < $numComments; $i++){
                $msg = (empty($message)) ? '' : $message.' [Segment '.($i + 1).']';
                // the comments per segment are an array again ...
                $segmentComments = $comments[$i];
                $segmentExpectations = $expectations[$i];
                $numSegmentComments = count($segmentComments);
                if($numSegmentComments != count($segmentComments)){
                    $this->assertEquals($numComments, count($expectations), $message.' [Number of segment comments does not match the expectations for segment '.($i + 1).']');
                } else {
                    for($j=0; $j < $numSegmentComments; $j++){
                        $msg = (empty($message)) ? '' : $message.' [Segment '.($i + 1).', comment '.($j + 1).']';
                        $this->assertCommentEqualsObject($segmentExpectations[$j], $segmentComments[$j], $msg, $removeDates);
                    }
                }
            }
        }
    }
    /**
     * compares the given segment content with an expectation object
     * @param stdClass $expectedObj
     * @param stdClass $segment
     * @param string $message
     * @param boolean $keepComments
     */
    public function assertCommentEqualsObject(stdClass $expectedObj, stdClass $comment, string $message='', bool $removeDates=false){
        $model = editor_Test_Model_Abstract::create($comment, 'comment');
        if($removeDates){
            $model->removeComparedField('created')->removeComparedField('modified');
        }
        $model->compare($this, $expectedObj, $message);
    }
    
    /* General model specific API */
    
    /**
     * Compares a list of models of the given type/name with a list of expected models encoded as JSON array of objects in a file
     * @param string $modelName
     * @param string $fileToCompare
     * @param array $actualModels
     * @param string $message
     * @param editor_Test_Model_Filter|null $filter: If given, the expected & actual items will be filtered according to this filter
     */
    public function assertModelsEqualsJsonFile(string $modelName, string $fileToCompare, array $actualModels, string $message='', editor_Test_Model_Filter $filter=NULL){
        $expectedModels = static::api()->getFileContent($fileToCompare);
        $this->assertModelsEqualsObjects($modelName, $expectedModels, $actualModels, $message, $filter);
    }
    /**
     * Compares a model of the given type/name with an expected model encoded as JSON object in a file
     * @param string $modelName
     * @param string $fileToCompare
     * @param stdClass $actualModel
     * @param string $message
     * @param editor_Test_Model_Filter|null $treeFilter: If given, a passed tree data will be filtered according to the passed filter
     */
    public function assertModelEqualsJsonFile(string $modelName, string $fileToCompare, stdClass $actualModel, string $message='', editor_Test_Model_Filter $treeFilter=NULL){
        $this->assertModelEqualsObject($modelName, static::api()->getFileContent($fileToCompare), $actualModel, $message, $treeFilter);
    }
    
    /**
     * Compares a row of the given model/name with an expected model encoded as JSON object in a file. Both Objects must have a property "row"
     * @param string $modelName
     * @param string $fileToCompare
     * @param stdClass $actual
     * @param string $message
     */
    public function assertModelEqualsJsonFileRow(string $modelName, string $fileToCompare, stdClass $actual, string $message=''){
        $expected = static::api()->getFileContent($fileToCompare);
        $this->assertModelEqualsObject($modelName, $expected->row, $actual->row);
    }
    /**
     * Compares an expected with an actual model of the given type/name
     * @param string $modelName
     * @param stdClass $expectedModel
     * @param stdClass $actualModel
     * @param string $message
     * @param editor_Test_Model_Filter|null $treeFilter: If given, a passed tree data will be filtered according to the passed filter
     */
    public function assertModelEqualsObject(string $modelName, stdClass $expectedModel, stdClass $actualModel, string $message='', editor_Test_Model_Filter $treeFilter=NULL){
        $model = editor_Test_Model_Abstract::create($actualModel, $modelName);
        $model->compare($this, $expectedModel, $message, $treeFilter);
    }
    /**
     * Compares expected with actual models of the given type/name
     * @param string $modelName
     * @param array $expectedModels
     * @param array $actualModels
     * @param string $message
     * @param editor_Test_Model_Filter|null $filter: If given, the expected & actual items will be filtered according to this filter
     */
    public function assertModelsEqualsObjects(string $modelName, array $expectedModels, array $actualModels, string $message='', editor_Test_Model_Filter $filter=NULL){
        // if a filter was passed, we need to reduce the lists
        if($filter != NULL){
            $actualModels = $filter->apply($actualModels);
            $expectedModels = $filter->apply($expectedModels);
        }
        $numModels = count($actualModels);
        if($numModels != count($expectedModels)){
            $this->assertEquals($numModels, count($expectedModels), $message.' [Number of '.ucfirst($modelName).'s does not match the expectations]');
        } else {
            for($i=0; $i < $numModels; $i++){
                $msg = (empty($message)) ? '' : $message.' ['.ucfirst($modelName).' '.($i + 1).']';
                $this->assertModelEqualsObject($modelName, $expectedModels[$i], $actualModels[$i], $msg);
            }
        }
    }
    /**
     * Compares an actual stdClass objects with the decoded contents of a file
     * @param string $fileToCompare
     * @param stdClass $actualObject
     * @param string $message
     */
    public function assertObjectEqualsJsonFile(string $fileToCompare, stdClass $actualObject, string $message=''){
        $expectedObject = static::api()->getFileContent($fileToCompare);
        $this->assertEquals($expectedObject, $actualObject, $message);
    }
}
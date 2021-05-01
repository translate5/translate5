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

/**
 * Abstraction layer for performing API tests which involve comparing Segment Texts.
 * This solves the problem, that Tags in segment text are enriched with quality-id's in some cases that contain auto-increment id's and thus have to be stripped
 * Also, the attributes in tags may be in a different order because historically there have been different attribute orders for differen tags
 */
abstract class editor_Test_Segment extends \ZfExtended_Test_ApiTestcase {
    
    /**
     * These attributes will be deleted from all tags
     * @var array
     */
    protected static $attributesToDelete = [ 'data-t5qid', 'data-seq', 'data-usertrackingid', 'data-timestamp', 'data-tbxid' ];
    
    protected static $segmentFields = [ 'source', 'sourceEdit', 'target', 'targetEdit' ];
    
    /**
     * compares the given segment content to the content in the given assert file
     * @param string $fileToCompare
     * @param stdClass $segment
     * @param string $message
     * @param boolean $keepComments
     */
    public function assertSegmentEqualsJsonFile(string $fileToCompare, stdClass $segment, string $message='', bool $keepComments=true){
        $this->assertSegmentEqualsObject(self::$api->getFileContent($fileToCompare), $segment, $message, $keepComments);
    }
    /**
     * compares the given segment content with an expectation object
     * @param stdClass $expectedObj
     * @param stdClass $segment
     * @param string $message
     * @param boolean $keepComments
     */
    public function assertSegmentEqualsObject(stdClass $expectedObj, stdClass $segment, string $message='', bool $keepComments=true){
        $this->assertEquals(
            $this->cleanSegmentJson($expectedObj),
            $this->cleanSegmentObject($segment, $keepComments),
            $message);
    }
    /**
     * Compares an array of segments with a file (which must contain those segments as json-array)
     * @param string $fileToCompare
     * @param stdClass[] $segments
     * @param string $message
     * @param boolean $keepComments
     */
    public function assertSegmentsEqualsJsonFile(string $fileToCompare, array $segments, string $message='', bool $keepComments=true){
        $expectations = self::$api->getFileContent($fileToCompare);
        $numSegments = count($segments);
        if($numSegments != count($expectations)){
            $this->assertEquals($numSegments, count($expectations), $message.' [Number of segments does not match the expectations]');
        } else {
            for($i=0; $i < $numSegments; $i++){
                $msg = (empty($message)) ? '' : $message.' [Segment '.($i + 1).']';
                $this->assertSegmentEqualsObject($expectations[$i], $segments[$i], $msg, $keepComments);
            }
        }
    }
    /**
     * Cleans a segment originating from the T5 API
     * @param stdClass $segment
     * @param boolean $keepComments
     * @param boolean $keepId
     * @return stdClass
     */
    public function cleanSegmentObject(stdClass $segment, bool $keepComments=true, bool $keepId=false) : stdClass {
        if(!$keepId) {
            unset($segment->id);
        }
        unset($segment->fileId);
        unset($segment->taskGuid);
        unset($segment->timestamp);
        if(isset($segment->metaCache)) {
            $meta = json_decode($segment->metaCache, true);
            if(!empty($meta['siblingData'])) {
                $data = [];
                foreach($meta['siblingData'] as $sibling) {
                    $data['fakeSegId_'.$sibling['nr']] = $sibling;
                }
                ksort($data);
                $meta['siblingData'] = $data;
            }
            $segment->metaCache = json_encode($meta, JSON_FORCE_OBJECT);
        }
        if(!$keepComments){
            $segment->comments = NULL;
        } else if(!empty($segment->comments)) {
            $segment->comments = $this->_adjustComments($segment->comments);
        }
        foreach(self::$segmentFields as $field){
            if(property_exists($segment, $field)) {
                $segment->$field = $this->_adjustFieldText($segment->$field);
            }
        }
        return $segment;
    }
    /**
     * Cleans a segment originating from an comparision-file
     * @param stdClass $jsonSegment
     * @return stdClass
     */
    public function cleanSegmentJson(stdClass $jsonSegment) : stdClass {
        if(!empty($jsonSegment->comments)) {
            $jsonSegment->comments = $this->_adjustComments($jsonSegment->comments);
        }
        foreach(self::$segmentFields as $field){
            if(property_exists($jsonSegment, $field)) {
                $jsonSegment->$field = $this->_adjustFieldText($jsonSegment->$field);
            }
        }
        return $jsonSegment;
    }
    /**
     * Compares an 2-dimensional array of comments with a file (which must contain those comments as json-array)
     * @param string $fileToCompare
     * @param stdClass[] $comments
     * @param string $message
     * @param boolean $removeDates
     */
    public function assertCommentsEqualsJsonFile(string $fileToCompare, array $comments, string $message='', bool $removeDates=false){
        $expectations = self::$api->getFileContent($fileToCompare);
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
        $this->assertEquals(
            $this->cleanCommentJson($expectedObj),
            $this->cleanCommentObject($comment, $removeDates),
            $message);
    }
    /**
     * Cleans a comment originating from the T5 API
     * @param stdClass $comment
     * @param bool $removeDates
     * @return stdClass
     */
    public function cleanCommentObject(stdClass $comment, bool $removeDates=false) : stdClass {
        unset($comment->id);
        unset($comment->segmentId);
        unset($comment->taskGuid);
        if($removeDates) {
            unset($comment->modified);
            unset($comment->created);
        }
        return $comment;
    }
    /**
     * Cleans a comment originating from an comparision-file
     * @param stdClass $comment
     * @return stdClass
     */
    public function cleanCommentJson(stdClass $comment) : stdClass {
        return $comment;
    }
    
    /**
     * Adjuts the passed tests to clean up field tags for comparision
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    public function assertFieldTextEquals(string $expected, string $actual, string $message=''){
        return $this->assertEquals($this->_adjustFieldText($expected), $this->_adjustFieldText($actual), $message);
    }
    /**
     *
     * @param string $text
     * @return string
     */
    protected function _adjustFieldText(string $text){
        if(strip_tags($text) == $text){
            return $text;
        }
        return preg_replace_callback('~<([a-z]+[0-9]*)[^>]*>~', array($this, '_replaceFieldTags'), $text);
    }
    /**
     *
     * @param string $text
     * @return string
     */
    protected function _adjustComments(string $text){
        // <span class="modified">2019-08-27 17:44:36</span>
        return preg_replace('/<span class="modified">[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}</', '<span class="modified">NOT_TESTABLE<', $text);
    }
    /**
     *
     * @param array $matches
     * @return string
     */
    protected function _replaceFieldTags($matches){
        
        // error_log('REPLACE FIELD TAGS: '.print_r($matches));
        
        if(count($matches) > 1){
            $isSingle = (substr(trim(rtrim($matches[0], '>')), -1) == '/');
            $tag = ($isSingle) ? editor_Tag::unparse($matches[0]) : editor_Tag::unparse($matches[0].'</'.$matches[1].'>');
            if($tag == NULL){
                return $matches[0];
            }
            foreach(self::$attributesToDelete as $attrName){
                $tag->unsetAttribute($attrName);
            }
            if($isSingle){
                return $tag->render();
            }
            return $tag->start();
        }
        return $matches[0];
    }
}
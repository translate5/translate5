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
 * Abstraction layer for performing API tests which involve comparing Segment Texts.
 * This solves the problem, that Tags in segment text are enriched with quality-id's in some cases that contain auto-increment id's and thus have to be stripped
 * Also, the attributes in tags may be in a different order because historically there have been different attribute orders for differen tags
 */
abstract class editor_Test_SegmentTagsTest extends \ZfExtended_Test_ApiTestcase {

    /* Segment Tags helpers to easily create tests for segment tags */
    
    /**
     *
     * @var editor_Models_Task
     */
    protected static $testTask = NULL;
    /**
     *
     * @return editor_Segment_FieldTags
     */
    protected function createTags() : editor_Segment_FieldTags {
        $segmentId = 1234567;
        $segmentText = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod.'; // 80 characters
        return new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $segmentText, 'target', 'targetEdit');
    }
    /**
     *
     * @param editor_Segment_FieldTags $tags
     * @param string $expectedMarkup
     */
    protected function createTagsTest(editor_Segment_FieldTags $tags, string $expectedMarkup){
        // compare rendered Markup
        $this->assertEquals($expectedMarkup, $tags->render());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
        // unparse test
        $unparseTags = new editor_Segment_FieldTags($this->getTestTask(), $tags->getSegmentId(), $tags->getFieldText(), $tags->getField(), $tags->getDataField());
        $unparseTags->unparse($expectedMarkup);
        $this->assertEquals($expectedMarkup, $unparseTags->render());
    }
    /**
     *
     * @param int $segmentId
     * @param string $markup
     */
    protected function createDataTest($segmentId, $markup){
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $markup, 'target', 'targetEdit');
        // compare unparsed markup
        $this->assertEquals($markup, $tags->render());
        // compare field-texts vs stripped markup
        $this->assertEquals(editor_Segment_Tag::strip($markup), $tags->getFieldText());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
    }
    /**
     *
     * @param int $segmentId
     * @param string $original
     * @param string $markup
     */
    protected function createOriginalDataTest($segmentId, $original, $markup){
        $originalTags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $original, 'target', 'targetEdit');
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $markup, 'target', 'targetEdit');
        // compare unparsed markup
        $this->assertEquals($markup, $tags->render());
        // compare field-text original vs "sorted" markup
        $this->assertEquals($originalTags->getFieldText(), $tags->getFieldText());
        // compare field-text vs stripped markup
        $this->assertEquals(editor_Segment_Tag::strip($markup), $tags->getFieldText());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
    }
    /**
     *
     * @param int $segmentId
     * @param string $markup
     * @param string $compare
     */
    protected function createMqmDataTest($segmentId, $markup, $compare=null){
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $markup, 'target', 'targetEdit');
        // compare unparsed markup
        if($compare == null){
            $this->assertEquals($markup, $tags->render());
        } else {
            // if the markup cpontaines invalid mqm we may need a special compare markup
            $this->assertEquals($compare, $tags->render());
        }
        // compare field-texts vs stripped markup
        $this->assertEquals(editor_Segment_Tag::strip($markup), $tags->getFieldText());
        if($compare != null){
            $this->assertEquals(editor_Segment_Tag::strip($compare), $tags->getFieldText());
        }
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
    }
    /**
     * Retrieves a test-tak to init field-tags with
     * @return editor_Models_Task
     */
    protected function getTestTask() : editor_Models_Task {
        if(static::$testTask == NULL){
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->setId(1234);
            $task->setEntityVersion(280);
            $task->setTaskGuid('{c56eadf5-ca66-43ae-931f-a09ff22643ab}');
            $task->setTaskName('UNIT_TEST_TASK');
            $task->setForeignName('');
            $task->setSourceLang(5);
            $task->setTargetLang(4);
            $task->setRelaisLang(0);
            $task->setState('open');
            $task->setQmSubsegmentFlags('{"qmSubsegmentFlags":[{"text":"Accuracy","id":1,"children":[{"text":"Terminology","id":2},{"text":"Mistranslation","id":3},{"text":"Omission","id":4},{"text":"Untranslated","id":5},{"text":"Addition","id":6}]},{"text":"Fluency","id":7,"children":[{"text":"Content","id":8,"children":[{"text":"Register","id":9},{"text":"Style","id":10},{"text":"Inconsistency","id":11}]},{"text":"Mechanical","id":12,"children":[{"text":"Spelling","id":13},{"text":"Typography","id":14},{"text":"Grammar","id":15},{"text":"Locale violation","id":16}]},{"text":"Unintelligible","id":17}]},{"text":"Verity","id":18,"children":[{"text":"Completeness","id":19},{"text":"Legal requirements","id":20},{"text":"Locale applicability","id":21}]}],"severities":{"critical":"Critical","major":"Major","minor":"Minor"}}');
            $task->setTaskType('default');
            $task->setProjectId(1233);
            static::$testTask = $task;
        }
        return static::$testTask;
    }
}
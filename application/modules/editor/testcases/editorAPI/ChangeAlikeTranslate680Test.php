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
 * This test covers if the repetition editor can deal with tags inside of segments.
 * Details are described in TRANSLATE-680: Automatic substituations of tags for repetitions
 * Behaviour before implementing this issue:
 * Identical Segments with tags are not recognized by the repetition editor,
 *   since the tags are containing IDs which are preventing the recognition.
 * After implementing this feature:
 *   Tag Content and position does not matter, only the tag count must be
 *   equal in the segments (and the text of course) to be recognized as repetition.
 */
class ChangeAlikeTranslate680Test extends editor_Test_JsonTest {
    protected static $useSourceEditing = false;
    
    /**
     * the strings to be compared on testing change alike source matching
     * [0] = source
     * [1] = target
     * @var array
     */
    static protected $dummyData = array(
        //1. master segment, we assume that tag positioning was the same before editing
        //what is edited does not matter, since md5 hash is created before editing at all
        // no matching here, since this segment won't be in alikes result set
        ['This <b><br>is a</b> red house.','Dies <b>ist<br> ein</b> rotes Haus.'],
        
        //2.
        //full source match
        //target text different
        ['This <i><br>is a</i> red house.','Dies <i>ist</i> ein<br> grünes Haus.'],
            
        //3.
        //no full source match, since target tag count differs
        //target text different
        ['This <b><br>is a</b> red house.','Dies <b>ist</b> ein grünes Haus.'],
            
        //4.
        //full source match
        //target text different
        ['This <b><br>is a</b> red house.','Dies <b>ist<br> ein</b> grünes Haus.'],
            
        //5.
        //no source match
        //full target match
        ['This <b><br>is a</b> green house.','Dies <b>ist<br> ein</b> rotes Haus.'],
            
        //6.
        //no source match
        //full target match, with different source tags
        ['This is a<br> green house.','Dies <b>ist<br> ein</b> rotes Haus.'],
         
        //7.
        //source repetition since source tags are at the same place and target tag count equals
        //no target repetition since tags are at a different place and text is different
        ['This <br><b>is a</b> red house.','Dies <br><b>war ein</b> grünes Haus.'],
            
        //8.
        //source repetition, see above
        //no target repetition since tags are at a different place
        ['This <br><b>is a</b> red house.','Dies <br><b>war ein</b> rotes Haus.'],
            
        //9.
        //no source repetition since target tag count differs, although source tags are at the same place
        //no target repetition since tags and text is different
        ['This <br><b>is a</b> red house.', 'Dies <br><b>war ein grünes Haus.'],
            
        //10.
        //no source repetition, text differs
        //target repetition since tag structure and text is the same
        ['This <b><br>is a</b> green house.','Dies <br>ist<b> ein</b> rotes Haus.'],
            
        //11.
        //no source repetition, text differs, tags missing
        //target repetition since tag structure and text is the same, regardless of the different source tag structure
        ['This <br><br>is a green house.','Dies <br>ist<b> ein</b> rotes Haus.'],
            
        //12 no match at all, just to test tag less segments
        ['This is a green house.','Dies ist ein rotes Haus.'],
        ["\n","\n"],
        ['<br>','<br>'],
        ["\r",''],
        ['<hr>',''],
        ['<br>','<br><br>'], // no repetition of above sources with a single tag and testing the correct replacement of the tags
        //test deleting and adding whitespace tags via repetition should not influence other tags
        ['<p>Test wort</p>','<p>Test word</p>'],
        ['<b>Test wort</b>','<b>Test word</b>'],
        ['<p>Test wort2</p>','<p>Test word2</p>'],
        ['<b>Test wort2</b>','<b>Test word2</b>'],
    );
    
    /**
     * This are the expected segmentNrInTask for targetMatches with Source Editing
     * @var array
     */
    static protected $sourceMatch = [2, 4, 7, 8];
    
    /**
     * This are the expected values for targetMatches with Source Editing
     * key segmentNrInTask
     * value isMatch
     * @var array
     */
    static protected $targetMatch = [5, 6, 10, 11];
    
    /**
     * This are the expected values for targetMatches with Source Editing
     * key segmentNrInTask
     * value isMatch
     * @var array
     */
    static protected $targetMatchSE = [5,10];
    
    /*
     Idee war die Anzahl der Tags des Targets auch in den Hash des sources mit aufzunehmen,
        so dass keine WDHs gefunden werden wenn die Tag Anzahl unterschiedlich ist.
        NEU hier: Whitespace Tags gehören nicht zur Anzahl dazu!
        
        Logisch bedeutet das für Source Wiederholungen:
            Eine Wiederholung ist eine Wiederholung wenn,
                - der Source Text gleich ist
                - wenn die Source Tags an der gleichen Stelle stehen
                - Was es für Source Tags sind ist egal
                    → md5 hash auf segment mit neutralen tag placeholdern
                - Wenn die Anzahl der Target Tags ebenfalls gleich sind
                    → tag count des targets in den md5 hash des source mit rein
                    → Bei Projekten mit alternativen Targets den count weglassen, so dass md5 Spalte befüllt, auch wenn alikes nicht nutzbar
        
        Wie sieht das mit Target Wiederholungen aus?
            Eine Wiederholung ist eine Wiederholung wenn,
                - der Target Text gleich ist
                - wenn die Target Tags an der gleichen Stelle stehen (impliziert gleiche Anzahl im Target)
                - Die Anzahl / Struktur der Source Tags interessiert nicht
                - Was es für Tags sind ist egal
                    → md5 hash auf segment mit neutralen tag placeholdern
                    → Tags im Source interessieren hier nicht, da Inhalt komplett unterschiedlich sein kann und Source nicht modifiziert wird!
                
        Wie sieht es mit Source Editing aus:
            - Theoretisch dürften mit aktiviertem Source Editing ebenfalls keine Wiederholungen gefunden werden,
                in denen die Tag Anzahl im Source unterschiedlich ist, da die Source ebenfalls modifiziert wird.

                
                
        Für die relais md5 Spalte ist es prinzipiell ebenfalls Egal, da auch hier keine Wiederholungen genutzt werden könne, dennoch nehmen wir der Konsistenz wegen den gleichen Algorithmus als für die Source Splate
                
        Fragen:
        - Wieso hatten wir definiert, dass die Tags einer Wiederholung an der gleichen Stelle stehen müssen?
            → Immerhin ist ja der Tag Inhalt unerheblich, ist dann die Position soviel wichtiger?
            Antwort: Position spielt explizit eine Rolle,

     */
    
    
    public static function setUpBeforeClass():void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task =[
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'enableSourceEditing' => static::$useSourceEditing,
            'lockLocked' => 1,
        ];
        
        $appState = self::assertAppState();
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $api->addImportArray(self::$dummyData);
        $api->addFilePlain('taskConfig', 'runtimeOptions.import.fileparser.options.protectTags = 1', 'text/plain', 'task-config.ini');
        $api->import($task);

        $tests = [
            'runtimeOptions.alike.segmentMetaFields' => '[]', // no custom alike calculation may be set here
        ];
        self::$api->testConfig($tests);

        $task = $api->getTask();
   
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Test using changealikes by source match
     */
    public function testAlikeCalculation() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $this->assertCount(count(self::$dummyData), $segments);
        
        //test source editing
        $isSE = $this->api()->getTask()->enableSourceEditing;
        
        //test editing a prefilled segment
        $segToTest = $segments[0];
        //$this->assertEquals($this->dummyData[0]['sourceBeforeEdit'], $segToTest->source);
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        $alikes = array_map(function($item){return (array) $item;}, $alikes);
        
        $targetMatch = array_keys(array_filter(array_column($alikes, 'targetMatch', 'segmentNrInTask')));
        $sourceMatch = array_keys(array_filter(array_column($alikes, 'sourceMatch', 'segmentNrInTask')));
        
        $this->assertEquals(static::$sourceMatch, $sourceMatch, 'The Source Matches are not as expected');
        $this->assertEquals(static::$targetMatch, $targetMatch, 'The Target Matches are not as expected');
    }
    
    /**
     * @depends testAlikeCalculation
     */
    public function testEditing() {
        $isSE = static::$useSourceEditing;
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $segToTest = $segments[0];
        $edit = str_replace('Haus', 'Haus - edited', $segToTest->targetEdit);
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $edit, $segToTest->id);
        $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
            //edit source also, currently our test helper cant make this in one API call
        if($isSE) {
            $edit = str_replace('house', 'house - edited', $segToTest->sourceEdit);
            $segmentData = $this->api()->prepareSegmentPut('sourceEdit', $edit, $segToTest->id);
            $segment = $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        }
        
        //fetch alikes and assert correct segments found by segmentNrInTask
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        
        //save alikes
        $alikeIds = array_map(function($item){
            return $item->id;
        },$alikes);
        
        $alikePutData = [
            'duration' => 777, //faked duration value
            'alikes' => json_encode($alikeIds)
        ];
        //Alike Data is sent as plain HTTP request parameters not as JSON in data parameter!
        $resp = $this->api()->request('editor/alikesegment/'.$segToTest->id, 'PUT', $alikePutData);
        
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        foreach($segments as $segment) {
            $nr = $segment->segmentNrInTask;
            if(!in_array($segment->id, $alikeIds)) {
                continue;
            }
            preg_match_all('#<div.+?</div>#', $segment->target, $originalTags);
            preg_match_all('#<div.+?</div>#', $segment->targetEdit, $editedTags);
            $this->assertEquals($originalTags, $editedTags, 'Target segment (Nr. '.$nr.') tags were changed, that must not be!');
            $this->assertStringEndsWith('Haus - edited.', $segment->targetEdit, 'Target of segment Nr. '.$nr.' was not edited');
            
            if(!$isSE) {
                continue;
            }
            preg_match_all('#<div.+?</div>#', $segment->source, $originalTags);
            preg_match_all('#<div.+?</div>#', $segment->sourceEdit, $editedTags);
            $this->assertEquals($originalTags, $editedTags, 'Source segment (Nr. '.$nr.') tags were changed, that must not be!');
            $this->assertStringEndsWith('house - edited.', $segment->sourceEdit, 'Source of segment Nr. '.$nr.' was not edited');
        }
    }
    
    /**
     * See TRANSLATE-1442 and TRANSLATE-680 and TRANSLATE-1669
     */
    public function testTagOnlyReplacement() {
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $segToTest = $segments[12]; //segmentNrInTask 13
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segToTest->targetEdit.'Test', $segToTest->id);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //fetch alikes and assert correct segments found by segmentNrInTask
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        
        $alikeNrs = array_map(function($item){
            return $item->segmentNrInTask;
        },$alikes);
        $this->assertEquals([15], $alikeNrs, 'The found repetitions are not as expected!');
        
        //save repetitions
        $alikeIds = array_map(function($item){
            return $item->id;
        },$alikes);
            
        $alikePutData = [
            'duration' => 777, //faked duration value
            'alikes' => json_encode($alikeIds)
        ];
        //Alike Data is sent as plain HTTP request parameters not as JSON in data parameter!
        $this->api()->request('editor/alikesegment/'.$segToTest->id, 'PUT', $alikePutData);
            
        $segmentsAfterChange = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $this->assertSegmentsEqualsJsonFile('expectedSegments.json', $segmentsAfterChange, 'Imported segments are not as expected!');
        
        //seg 14 has seg 16 as alike, although the target tag count differs.
        // But thats ok since target of 16 is empty, and will be filled with one tag from the source on editing.
        // So after editing, tags are equal again.
        $segToTest = $segments[13]; //segmentNrInTask 14
        //fetch alikes and assert correct segments found by segmentNrInTask
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        $alikeNrs = array_map(function($item){
            return $item->segmentNrInTask;
        },$alikes);
        $this->assertEquals([16], $alikeNrs, 'The found repetitions are not as expected!');
    }
    
    /**
     * See TRANSLATE-1669
     * @depends testTagOnlyReplacement
     */
    public function testWhitespaceTagManipulation() {
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        
        /*
         * segmentNrInTask 18 - remove whitespace tag the other tags must remain in the alikes
         */
        $segToTest = $segments[17];
        $newTarget = preg_replace('/Test<.*>word/', 'Test Word', $segToTest->targetEdit);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $newTarget, $segToTest->id);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //fetch alikes and assert correct segments found by segmentNrInTask
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        
        $alikeNrs = array_column($alikes, 'segmentNrInTask');
        $this->assertEquals([19], $alikeNrs, 'The found repetitions are not as expected!');
        $alikeIds = array_column($alikes, 'id');
                
        $alikePutData = [
            'duration' => 777, //faked duration value
            'alikes' => json_encode($alikeIds)
        ];
        //Alike Data is sent as plain HTTP request parameters not as JSON in data parameter!
        $this->api()->request('editor/alikesegment/'.$segToTest->id, 'PUT', $alikePutData);
        
        /*
         * segmentNrInTask 20 - add whitespace tag the other tags must remain in the alikes
         */
        $segToTest = $segments[19];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segments[17]->target, $segToTest->id);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //fetch alikes and assert correct segments found by segmentNrInTask
        $alikes = $this->api()->requestJson('editor/alikesegment/'.$segToTest->id, 'GET');
        
        $alikeNrs = array_column($alikes, 'segmentNrInTask');
        $this->assertEquals([21], $alikeNrs, 'The found repetitions are not as expected!');
        $alikeIds = array_column($alikes, 'id');
        
        $alikePutData = [
            'duration' => 777, //faked duration value
            'alikes' => json_encode($alikeIds)
        ];
        
        //Alike Data is sent as plain HTTP request parameters not as JSON in data parameter!
        $this->api()->request('editor/alikesegment/'.$segToTest->id, 'PUT', $alikePutData);
                
        $segmentsAfterChange = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $this->assertSegmentsEqualsJsonFile('expectedSegmentsEditedWhitespace.json', $segmentsAfterChange, 'Imported segments are not as expected!');
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->cleanup && self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
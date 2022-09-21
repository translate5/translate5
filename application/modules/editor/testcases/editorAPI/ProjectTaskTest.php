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

/***
 * 1. Create project with 4 project tasks.
 * 2. Create term collection which will be assigned as default for all of the tasks.
 * 3. The term tagger will tag some of the segments in each project task.
 * 4. Compare the segment content after term tagging for each project task.
 *
 */
class ProjectTaskTest extends editor_Test_JsonTest {
    
    protected static $customerTest;
    protected static $sourceLangRfc = 'en';
    protected static $targetLangRfc = ['de','it','fr','mk'];
    
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_TermTagger_Bootstrap', $appState->pluginsLoaded, 'TermTagger must be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    /***
     * Create test customer, project tasks, language resources.
     * Run the task import and waith for importing
     */
    public function testSetupCustomerAndResources() {
        self::$customerTest = self::$api->postJson('editor/customer/',[
            'name'=>'API Testing::ResourcesLogCustomer',
            'number'=>uniqid('API Testing::ResourcesLogCustomer'),
        ]);
        
        $this->addTermCollection();
        $this->createProject();
        self::$api->getJson('editor/task/'.self::$api->getTask()->id.'/import');
        self::$api->checkProjectTasksStateLoop();
    }
    
    /***
     * Validate the basic project task values
     */
    public function testProjectTaskCreation() {
        self::$api->reloadProjectTasks();
        self::assertEquals(count(self::$api->getProjectTasks()), 4, 'The number of the project task is not as expected');
        $languages = self::$api->getLanguages();
        
        $getRfc = function($id) use ($languages){
            foreach ($languages as $lang){
                if($id == $lang->id){
                    return $lang->rfc5646;
                }
            }
            return '';
        };
        
        //validate the task target language
        foreach (self::$api->getProjectTasks() as $task){
            self::assertEquals($getRfc($task->sourceLang),self::$sourceLangRfc,'The project task does not match the expected source language');
            self::assertContains($getRfc($task->targetLang), self::$targetLangRfc, 'The task target language ('.$task->targetLang.') can not be found in the expected values.');
            self::assertEquals($task->taskType, self::$api::INITIAL_TASKTYPE_PROJECT_TASK, 'Project tasktype does not match the expected type.');
        }
    }
    /***
     * For each project task, check the segment content. Some of the segments are with terms.
     */
    public function testProjectTasksSegmentContent() {
        self::$api->reloadProjectTasks();
        foreach (self::$api->getProjectTasks() as $task){
            $this->checkProjectTaskSegments($task);
        }
    }
    
    /***
     * Check the segments content for the given task.
     * For this, first the task needs to be opened for editing. After the check the task will be set to open again.
     * @param stdClass $task
     */
    protected function checkProjectTaskSegments(stdClass $task){
        $project = $this->api()->getTask();
        //set internal current task for further processing
        $this->api()->setTask($task);

        error_log('Segments check for task ['.$task->taskName.']');
        //open the task for editing. This is the only way to load the segments via the api
        self::$api->putJson('editor/task/'.$task->id, ['userState' => 'edit', 'id' => $task->id]);

        $fileName = str_replace(['/','::'],'_',$task->taskName.'.json');
        $segments = self::$api->getSegments($fileName);
        // compare segments (this API will strip/adjust segment contents)
        $this->assertSegmentsEqualsJsonFile($fileName, $segments, 'Imported segments are not as expected in '.basename($fileName).'!');

        //close the task for editing
        self::$api->putJson('editor/task/'.$task->id, ['userState' => 'open', 'id' => $task->id]);

        //reset internal current task to the project
        $this->api()->setTask($project);
    }
    

    /***
     * Add the term collection resource
     */
    protected function addTermCollection() {
        $params=[];
        //create the resource 3 and import the file
        $params['name'] = 'API Testing::TermCollection_'.__CLASS__;
        $params['resourceId'] = 'editor_Services_TermCollection';
        $params['serviceType'] = 'editor_Services_TermCollection';
        $params['customerIds'] = [self::$customerTest->id];
        $params['customerUseAsDefaultIds'] = [self::$customerTest->id];
        $params['customerWriteAsDefaultIds'] = [];
        $params['serviceName'] ='TermCollection';
        $params['mergeTerms'] =false;
        
        self::$api->addResource($params, 'collection.tbx', true);
    }
    
    /***
     * Create project tasks.
     */
    protected function createProject(){
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>self::$customerTest->id,
            'autoStartImport'=>0,
            'edit100PercentMatch' => 0
        ];
        self::assertLogin('testmanager');
        
        $zipfile = self::$api->zipTestFiles('testfiles/','XLF-test.zip');
        self::$api->addImportFile($zipfile);
        
        self::$api->import($task,false,false);
        error_log('Task created. '.self::$api->getTask()->taskName);
    }
    
    public static function tearDownAfterClass(): void {
        
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');

        //close the task for editing
        self::$api->putJson('editor/task/'.$task->id, ['userState' => 'open', 'id' => $task->id]);
        
        //when removing the task, all task project will be removed
        self::$api->delete('editor/task/'.$task->id);
        
        //remove the created resources
        self::$api->removeResources();
        
        //remove the temp customer
        self::$api->delete('editor/customer/'.self::$customerTest->id);
    }
}

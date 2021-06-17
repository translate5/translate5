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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 * @deprecated TODO This class is not working anymore, the termtagger tests should be refactored to single tests called by our default testframework
 */
class editor_Test_Termtagger extends editor_Test_Termtagger_Abstract {
    /**
     *
     * @var string
     */
    protected static $workflow;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::setTestResources();
        self::setTaskValues();
        self::addTask();
        self::waitTaskReady();
        self::loadTaggedSegmentFromDb();
    }

    public function testPassedAssertionSource() {
        $a = self::$assertion.'Source';
        $this->$a();
    }

    public function testPassedAssertionTarget() {
        $a = self::$assertion.'Target';
        $this->$a();
    }

    protected function assertOutputEqualsSource() {
        self::$sourceTagged = $this->removeDataTbxIdFromString(self::$sourceTagged);
        self::$expectedSource = $this->removeDataTbxIdFromString(self::$expectedSource);

        $this->assertEquals(self::$expectedSource, self::$sourceTagged, 'The source content did not meet the expected result. <br><br><b>Expected source</b>: <br><br><pre>'.htmlentities(self::$expectedSource, ENT_HTML5, 'utf-8').'</pre><br><br><b>Retrieved source</b>: <br><br><pre>'.htmlentities(self::$sourceTagged, ENT_HTML5, 'utf-8').'</pre>');
    }

    protected function assertOutputEqualsTarget() {
        self::$targetTagged = $this->removeDataTbxIdFromString(self::$targetTagged);
        self::$expectedTarget = $this->removeDataTbxIdFromString(self::$expectedTarget);

        $this->assertEquals(self::$expectedTarget, self::$targetTagged, 'The target content did not meet the expected result. <br><br><b>Expected target</b>: <br><br><pre>'.htmlentities(self::$expectedTarget, ENT_HTML5, 'utf-8').'</pre><br><br><b>Retrieved target</b>: <br><br><pre>'.htmlentities(self::$targetTagged, ENT_HTML5, 'utf-8').'</pre>');
    }
    protected function removeDataTbxIdFromString($string) {
        return preg_replace('#data-tbxid="[^"]+"#', 'data-tbxid=""', $string);
    }

    protected static function setTaskValues() {
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        $s = $pm->db->select();
        $s->where('login = ?', ZfExtended_Models_User::SYSTEM_LOGIN);
        $pm->loadRowBySelect($s);
        self::$testTask->setPmGuid($pm->getUserGuid());
        self::$testTask->setPmName($pm->getUsernameLong());
        self::$testTask->setTaskName(self::$testfile->getFilename());
        self::$testTask->setTaskNr('');
        self::$testTask->setSourceLang(self::$sourceLangEntity['id']);
        self::$testTask->setTargetLang(self::$targetLangEntity['id']);
        self::$testTask->setOrderdate(date('Y-m-d H:i:s'));
        self::$testTask->setWordCount(0);
        self::$testTask->setEnableSourceEditing(1);
        self::$testTask->setEdit100PercentMatch(1);
        self::$testTask->setLockLocked(1);
        self::$testTask->setTerminologie(1);
        self::$testTask->createTaskGuidIfNeeded();
        self::$testTask->setCustomerId(ZfExtended_Factory::get('editor_Models_Customer')->loadByDefaultCustomer()->getId());
    }

    /**
     * executes the assertion-method specified in the testcase.xml
     */
    protected static function addTask() {
        $config = Zend_Registry::get('config');
        $workflowManager = ZfExtended_Factory::get('editor_Workflow_Manager');

        //init workflow id for the task
        self::$testTask->setWorkflow($config->runtimeOptions->workflow->initialWorkflow);
        self::$testTask->validate();

        $wfId = self::$testTask->getWorkflow();
        self::$workflow = $workflowManager->getCached($wfId);
        self::importFile();
        $workflowManager->initDefaultUserPrefs(self::$testTask);
        //reload because entityVersion was changed by above workflow and workflow manager calls
        self::$testTask->load(self::$testTask->getId());
        return self::$testTask;
    }

    protected static function waitTaskReady(){
        $count = 0;
        while ($count<100 && self::$testTask->getState() !=='open') {
            sleep(1);
            self::$testTask->load(self::$testTask->getId());
            $count++;
        }
        if($count>99){
            self::assertTrue(false,'Importing of testcase took to long. Something is wrong. Please check.');
        }
    }

    protected static function loadTaggedSegmentFromDb() {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->loadFirst(self::$testTask->getTaskGuid());
        self::$sourceTagged = $segment->getFieldOriginal('source');
        self::$targetTagged = $segment->getFieldEdited('target');
    }
    /**
     * imports the uploaded file
     * @throws Exception
     */
    protected static function importFile() {
        $importUpload = array(
            "importUpload" => array(
                "name" => self::$testfile->getFilename(),
                "type" => "application/xml",
                "tmp_name" => self::$testfile->getPathname(),
                "error" => 0,
                "size" => self::$testfile->getSize(),
                "options" => array(
                    "ignoreNoFile" => false,
                    "useByteString"=>true,
                    "magicFile"=> NULL,
                    "detectInfos"=> true),
                "validated"=>false,
                "received"=>false,
                "filtered"=> false,
                "validators"=>array("Zend_Validate_File_Upload")
            ),
            "importTbx" => array(
                "name" => self::$tbxFile->getFilename(),
                "type" => "application/xml",
                "tmp_name" => self::$tbxFile->getPathname(),
                "error" => 0,
                "size" => self::$tbxFile->getSize(),
                "options" => array(
                    "ignoreNoFile" => false,
                    "useByteString"=>true,
                    "magicFile"=> NULL,
                    "detectInfos"=> true),
                "validated"=>false,
                "received"=>false,
                "filtered"=> false,
                "validators"=>array("Zend_Validate_File_Upload")
            ),
        );
        Zend_Registry::set('offlineTestcase', true);

        $import = ZfExtended_Factory::get('editor_Models_Import');
        /* @var $import editor_Models_Import */
        $import->setUserInfos(self::$testTask->getPmGuid(),  self::$testTask->getPmName());

        $import->setLanguages(
                        self::$testTask->getSourceLang(),
                        self::$testTask->getTargetLang(),
                        self::$testTask->getRelaisLang(),
                        editor_Models_Languages::LANG_TYPE_ID);
        $import->setTask(self::$testTask);
        $upload = ZfExtended_Factory::get('editor_Models_Import_UploadProcessor');
        /* @var $upload editor_Models_Import_UploadProcessor */

        $upload->initDataProvider("testcase",$importUpload);
        $dp = $upload->getDataProvider();
        $import->import($dp);
        self::$workflow->getHandler()->doImport(self::$testTask,$import->getImportConfig());

        //run the queued import worker
        $workerModel = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $workerModel ZfExtended_Models_Worker */
        $workerModel->loadFirstOf('editor_Models_Import_Worker',self::$testTask->getTaskGuid());
        $worker = ZfExtended_Worker_Abstract::instanceByModel($workerModel);
        $worker && $worker->schedulePrepared();
    }
    public static function tearDownAfterClass(): void {
        parent::tearDownAfterClass();

        $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', array(self::$testTask));
        /* @var $remover editor_Models_Task_Remover */
        $remover->removeForced();
    }
}
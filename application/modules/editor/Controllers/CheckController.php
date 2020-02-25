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
 * This class checks if there are segments in the task, 
 * where the source has been changed by the bug 
 * TRANSLATE-683: repetition editor changes the source, even if it is non-editable
 * 
 */

/**
 * usage instruction:
 * 
 * Please be aware, that this check does NOT work for Star Transit and CSV-files, 
 * since they export the source-contents on export-time, too. If you need this 
 * check also for these file-types, please contact  MittagQI
 * 
 * Place this file in /application/modules/editor/Controllers
 * 
 * Since this script heavily uses translate5's export and import routines, we
 * recommend not to do any other exports or imports during using this script.
 * 
 * Deactivate the following plugins for faster procession time. 
 *  editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap
 *  editor_Plugins_NoMissingTargetTerminology_Bootstrap
 *  editor_Plugins_SegmentStatistics_Bootstrap
 *  editor_Plugins_ArchiveTaskBeforeDelete_Bootstrap
 * 
 * If you do not deactive them and get errors like 
 * "can not refresh parent row" or "deadlock", than increase 
 * the value for sleepTimeBeforeTaskDeletion
 * 
 * 
 * execute the following statement on your DB
 * INSERT INTO  `Zf_acl_rules` (
        `id` ,
        `module` ,
        `role` ,
        `resource` ,
        `right`
        )
        VALUES (
        NULL ,  'editor',  'basic',  'editor_check',  'all'
        );
 * be sure to delete this row from Zf_acl_rules again, when you are done
 * 
 * Configure the configuration section below.
 * 
 * Login to your application through the browser
 * 
 * Call http://application/editor/check/tasks
 * 
 * have a look to your error-log, when the script is done
 * 
 */
require_once "../application/modules/editor/Controllers/TaskController.php";
class editor_CheckController extends editor_TaskController {
    /**************************
     * configuration section
     **************************/
    
    /**
     *
     * @var array list all taskGUIDs, that should be checked
     */
    protected $tasks2check = array(
        '{25f991a3-1b0d-435b-af6e-97087ebf5725}',
        '{d888269a-ba2e-41da-b563-31a2b4ce858c}',
        '{61428516-5909-43d2-b40e-88f01b9324e4}',
        '{1339136d-751e-48ad-9553-50d443538035}'
        );
    /**
     *
     * @var boolean if false, for tasks with segments, 
     * where the source had been changed, the temporary check-tasks 
     * are not deleted after the check. In addition you will find
     * their taskGuids in the output
     */
    protected $deleteDifferingTasks = true;
    
    protected $sleepTimeBeforeTaskDeletion = 10;
    /**************************
     * configuration end
     **************************/   
    
    /**************************
     * do not change anything below here
     **************************/
    protected $tmpDir;
    protected $reviewDir;
    /**
     * @var editor_Models_Task
     */
    protected $existingTaskEntity;
    /**
     * @var editor_Models_Segment
     */
    protected $segmentExTask;
    /**
     * @var editor_Models_Segment
     */
    protected $segmentNewTask;
    
    protected $countWrongPerTask = 0;
    
    protected $outputCollection = array();
    protected $output = array();
    protected $taskGuids2Delete = array();
    /**
     */
    public function tasksAction() {
        $this->outputCollection[]="===========================================";
        $this->outputCollection[]="Beginn Auswertung veraenderte Quellsegmente";
        $this->outputCollection[]="Gelistet werden nur Tasks mit veraenderten Quellsegmenten";
        foreach ($this->tasks2check as $taskGuid) {
            $this->handleDirs();
            $this->output = array();
            $this->output[] = "\nTaskGUID: ".$taskGuid;
            $path2zip = $this->exportTask($taskGuid);
            $this->importTask($path2zip);
            $this->compareSource();
            if($this->countWrongPerTask === 0){
                $this->taskGuids2Delete[] = $this->entity->getTaskGuid();
                continue;
            }
            $this->countWrongPerTask = 0;
            $this->outputCollection[] = implode("\n", $this->output);
            if($this->deleteDifferingTasks){
                $this->taskGuids2Delete[] = $this->entity->getTaskGuid();
            }
        }
        $this->deleteTmpTasks();
        error_log(implode("\n", $this->outputCollection));
    }
    
    protected function deleteTmpTasks() {
        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $worker ZfExtended_Models_Worker */
        sleep($this->sleepTimeBeforeTaskDeletion);
        $wait = true;
        while($wait){
            if(!empty($worker->getListQueued($this->entity->getTaskGuid()))){
                sleep(1);
                continue;
            }
            $wait = false;
        }
        foreach ($this->taskGuids2Delete as $guid) {
            $this->entity->loadByTaskGuid($guid);
            $this->entity->delete();
        }
    }
    
    protected function compareSource() {
        $this->segmentExTask = ZfExtended_Factory::get('editor_Models_Segment');
        $segIdExTask = -1;
        $segIdNewTask = -1;
        $this->segmentNewTask = ZfExtended_Factory::get('editor_Models_Segment');
        $taskGuidEx = $this->existingTaskEntity->getTaskGuid();
        $taskGuidNew = $this->entity->getTaskGuid();
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        
        while($this->segmentExTask->loadNext($taskGuidEx,$segIdExTask)){
            $segIdExTask = $this->segmentExTask->getId();
            $this->segmentNewTask->loadNext($taskGuidNew,$segIdNewTask);
            $segIdNewTask = $this->segmentNewTask->getId();
            $sourceContentEx = $this->removeTagIds($this->segmentExTask->getFieldOriginal('source'));
            $sourceContentNew = $this->removeTagIds($this->segmentNewTask->getFieldOriginal('source'));
            if($sourceContentEx !== $sourceContentNew){
                $this->countWrongPerTask++;
                $file->load($this->segmentExTask->getFileId());
                $this->output[] = "Dateiname: ".$file->getFileName();
                $this->output[] = "Segment-mid: ".$this->segmentExTask->getMid();
                $this->output[] = "Segment-Nr im Editor: ".$this->segmentExTask->getSegmentNrInTask();
                $this->output[] = "Veraenderte Quelle im Editor: ".$sourceContentEx;
                $this->output[] = "Original Quelle beim Import: ".$sourceContentNew;
                $this->output[] = "Ggf. veraenderter Target im Editor: ".$this->segmentExTask->getFieldEdited('target');
                $this->output[] = "Original Target beim Import: ".$this->segmentExTask->getFieldOriginal('target');
                if(!$this->deleteDifferingTasks){
                    $this->output[] = "TaskGÚID des temporär angelegten Vergleichstasks: ".$this->segmentExTask->getFieldOriginal('target');
                }
            }
        }
    }
    
    protected function removeTagIds($source) {
        $search = array(
            //remove internal-tag ids, because they differ in each import
            '#(<[^>]*)id="[^"]*"([^>]*>)#',
            //remove termtags, because we do not tag the compared task
            //<div title="" class="term preferredTerm exact transNotFound" data-tbx>a</div>
            '#<div [^>]*class="term [^>]*>([^<>]*)</div>#'
        );
        $replace = array(
            '\\1\\2',
            '\\1'
        );
        
        return preg_replace($search,$replace , $source);
    }
    protected function handleDirs() {
        $this->tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'translate5CheckTasks'.
                DIRECTORY_SEPARATOR;
        $this->reviewDir = $this->tmpDir.'review'.DIRECTORY_SEPARATOR;
        if(!is_dir($this->tmpDir))
            mkdir($this->tmpDir);
        if(is_dir($this->reviewDir)){
            $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                    'Recursivedircleaner'
            );
            /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
            $recursivedircleaner->delete($this->reviewDir);
        }
        mkdir($this->reviewDir);
    }
    
    protected function exportTask($taskGuid) {
        $this->entity->loadByTaskGuid($taskGuid);
        $this->existingTaskEntity = clone $this->entity;
        $export = ZfExtended_Factory::get('editor_Models_Export');
        /* @var $export editor_Models_Export */
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        if(!$export->setTaskToExport($this->entity, false)){
            echo $translate->_(
                    'Derzeit läuft bereits ein Export für diesen Task. Bitte versuchen Sie es in einiger Zeit nochmals.');
            exit;
        }
        $export->_exportToFolder($this->reviewDir,true);
        $zipFile = $this->tmpDir.'export.zip';
        $filter = ZfExtended_Factory::get('Zend_Filter_Compress',array(
            array(
                    'adapter' => 'Zip',
                    'options' => array('archive' => $zipFile),
                )
            )
        );
        /* @var $filter Zend_Filter_Compress */
        if(!$filter->filter($this->reviewDir)){
            throw new Zend_Exception('Could not create export-zip of task '.$taskGuid.'.');
        }
        return $zipFile;
    }
    
    protected function fakePostparams($path2zip) {
        $this->setParam('format', 'jsontext');
        $this->setParam('taskName',  $this->entity->getTaskName());
        $this->setParam('taskNr',  $this->entity->getTaskNr());
        $this->setParam('sourceLang',  $this->entity->getSourceLang());
        $this->setParam('targetLang',  $this->entity->getTargetLang());
        $this->setParam('orderdate',  $this->entity->getOrderdate());
        $this->setParam('wordCount',  $this->entity->getWordCount());
        $this->setParam('lockLocked',  $this->entity->getLockLocked());
        $this->setParam('importTbx', array(
            'name'=>'',
            'type'=>'',
            'tmp_name'=>'',
            'error'=>4,
            'size'=>0
            ));
        $this->setParam('importUpload', array(
            'name'=>  basename($path2zip),
            'type'=>'application/zip',
            'tmp_name'=>$path2zip,
            'error'=>0,
            'size'=>  filesize($path2zip)
            ));
    }
    
    protected function prepareImport() {
        $this->entity->init();
        $this->data = $this->_getAllParams();
        settype($this->data['enableSourceEditing'], 'boolean');
        $this->data['pmGuid'] = $this->user->data->userGuid;
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        $pm->init((array)$this->user->data);
        $this->data['pmName'] = $pm->getUsernameLong();
        $this->processClientReferenceVersion();
        $this->convertToLanguageIds();
        $this->setDataInEntity();
        $this->entity->createTaskGuidIfNeeded();
        
        //init workflow id for the task
        $defaultWorkflow = $this->config->runtimeOptions->import->taskWorkflow;
        $this->entity->setWorkflow($this->workflowManager->getIdToClass($defaultWorkflow));
        $this->initWorkflow();
    }
    
    protected function doImport($path2zip) {
        $importInfo = array(
          "importUpload"=>array(
                'name'=> $this->entity->getTaskName(),
                'type'=>"application/zip",
                'tmp_name'=>$path2zip,
                'error'=>0,
                'size'=>  filesize($path2zip),
                'options' => array(
                    'ignoreNoFile' => false,
                    'useByteString' => true,
                    'magicFile' => NULL,
                    'detectInfos' => true,
                ),
                'validated'=>false,  
                'received'=>false,  
                'filtered'=>false,  
                'received'=>false,  
                'validators'=>array("Zend_Validate_File_Upload")
              )
            );
        $this->upload->initDataProvider('zip', $importInfo);
        $this->processUploadedFile();
        $this->workflow->doImport($this->entity);
        $this->workflowManager->initDefaultUserPrefs($this->entity);
        $this->entity->load($this->entity->getId());
    }
    
    protected function importTask($path2zip) {
        $this->fakePostparams($path2zip);
        $this->prepareImport();
        $this->doImport($path2zip);
    }
}
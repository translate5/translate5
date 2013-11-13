<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/* * #@+
 * @author Marc Mittag
 * @package translate5
 * @version 0.7
 *
 */

/**
 * Stellt Methoden bereit, die translate5 grundsätzlich als Stand Alone-Anwendung verfügbar machen
 *
 * @todo: Bei Entwicklung einer Serverapplication für translate5 getParams filtern
 */
class IndexController extends ZfExtended_Controllers_Action {
    /*
     * @var boolean projectImported Projekt ist importiert
     */
    protected $projectImported;

    public function init(){
        parent::init();
        $this->setProjectImported();
        $this->view->languageSelector();
    }
    public function indexAction() {}
    public function usageAction() {}
    public function testdataAction() {}
    public function sourceAction() {}
    public function newsletterAction() {}
    public function tasksAction() {
        /* @var $task editor_Models_Task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $this->view->tasks = $task->loadAll();
    }
    /**
     * @todo OBSOLETE
     */
    public function importAction() {
        $form = new ZfExtended_Zendoverwrites_Form('indexImport.ini');
        $this->view->form = $form;

        $form->getElement('sourceLang')->addMultiOptions($this->getAvailableLanguages());
        $form->getElement('targetLang')->addMultiOptions($this->getAvailableLanguages());
        
        
        //Relais Language is hardcoded set to en, because language setting is actualy not used. 
        //(Omitting Relais Language is no option, because this disables the Relais Feature)
        //$form->getElement('relaisLang')->addMultiOptions($this->getAvailableLanguages());
        //$form->getElement('targetLang')->setValue('es');

        if ($this->getRequest()->getParam('submit') && $form->isValid($this->_request->getParams())) {
            $this->processUploadedFile($this->getGuid(), $this->getGuid());
            return;
        }
        $config = Zend_Registry::get('config');
        if(empty($config->runtimeOptions->import->enableSourceEditing)) {
            $form->removeElement('enableSourceEditing');
        }
        $form->getElement('taskName')->setValue('Demo Projekt '.date('Y-m-d H:i'));
    }

    /**
     * Gibt alle verfügbaren Sprachen aufbereitet für die Select Anweisungen zurück.
     * @todo OBSOLETE
     */
    protected function getAvailableLanguages() {
        /* @var $langs editor_Models_Languages */
        $langs = ZfExtended_Factory::get('editor_Models_Languages');
        $langs = $langs->loadAll();
        $result = array(''=>'');
        foreach ($langs as $lang) {
            $result[$lang['rfc5646']] = $lang['langName'].' ('.$lang['rfc5646'].')';
        }
        return $result;
    }

    protected function processUploadedFile($taskGuid, $userGuid) {
        $upload = new Zend_File_Transfer_Adapter_Http();
        
        $uploaded = $upload->getFileInfo('importZip');
        $zip = $uploaded['importZip']['tmp_name'];
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($zip) != 'application/zip') {
            $this->view->form->getElement('importZip')->addError("Bitte eine Zip Datei auswählen.");
            $this->view->form->markAsError();
            return;
        }
        /* auskommentiert, da Serverabsturz bei inetsolutions
         * $config = Zend_Registry::get('config');
        $flagFile = $config->resources->cachemanager->zfExtended->backend->options->cache_dir.'/importRunning';
        while(file_exists($flagFile)){
            if(time()-filemtime($flagFile)>3600){
                unlink($flagFile);
            }
            sleep(1);
        }
        file_put_contents($flagFile, $this->getGuid());*/
        $p = (object) $this->_request->getParams();
        
        $import = ZfExtended_Factory::get('editor_Models_Import');
        /* @var $import editor_Models_Import */
        $import->setEdit100PercentMatches((bool) $p->edit100PercentMatches);
        $import->setUserInfos($userGuid, $p->userName);
        //Relais Language is hardcoded set to en, because language setting is actualy not used. 
        //(Omitting Relais Language is no option, because this disables the Relais Feature)
        $import->setLanguages($p->sourceLang, $p->targetLang,'en');
        $import->setTask($p->taskName,$taskGuid, $p);
        $dp = $this->getDataProvider($zip);
        
        try {
            $import->import($dp);
        }
        catch (Exception $e) {
        	$dp->handleImportException($e);
        	throw $e;
        }
        #auskommentiert, da Serverabsturz bei inetsolutions
        //if(file_exists($flagFile))unlink($flagFile);
        $this->view->taskGuid = $taskGuid;
        $this->_helper->viewRenderer('importSuccess');
    }

    public function exportAction() {
        $req = $this->getRequest();
        $taskGuid = $req->getParam('taskGuid');
        $diff = (boolean)$req->getParam('diff');
        if(empty($taskGuid)) {
            throw new Zend_Exception("Keine TaskGuid");
        }

        /* @var $export editor_Models_Export */
        $export = ZfExtended_Factory::get('editor_Models_Export');
        $export->loadTaskToExport($taskGuid, $diff);
        $zipFile = $export->exportToZip();

        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="exportdata.zip"');
        readfile($zipFile);
        // disable layout and view
    }

    public function deleteAction() {
        #return;
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $req = $this->getRequest();
        $taskGuid = $req->getParam('taskGuid');
        if(empty($taskGuid)) {
            throw new Zend_Exception("Keine TaskGuid");
        }
        /* @var $export editor_Models_Task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);
        $task->delete();
        $this->_redirect(APPLICATION_RUNDIR.'/index/tasks');
    }
    
    public function editAction() {
        throw new ZfExtended_NotFoundException();
    }
    
    /**
     * registers the needed data for editor usage in session
     * @param string $taskGuid
     * @param string $userGuid
     * @param string $userName
     */
    protected function registerSessionData(string $taskGuid, string $userGuid, string $userName) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $task->registerInSession();
        $sessionUser = new Zend_Session_Namespace('user');
        $sessionUser->userGuid = $userGuid;
        $sessionUser->userName = $userName;
    }

    protected function setProjectImported(){
        $db = Zend_Registry::get('db');
        $count = $db->fetchOne( 'SELECT COUNT(*) AS count FROM LEK_files' );
        $this->projectImported = ($count == 0)?false:true;
        $this->view->projectImported = $this->projectImported;
    }
    protected function getUri(){
        $prot = (empty($_SERVER['HTTPS'])) ? 'http://' : 'https://';
        return $prot . $_SERVER['SERVER_NAME'] . preg_replace('"/$"', '', $_SERVER['REQUEST_URI']);
    }

    protected function getGuid(){
        //from http://www.php.net/manual/en/function.uniqid.php#94959
        return sprintf( '{%04x%04x-%04x-%04x-%04x-%04x%04x%04x}',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    public function dbupdaterAction() {
        $sqlDirs = array(
                        APPLICATION_PATH.'/../docs',
                        APPLICATION_PATH.'/modules/editor/docs'
                        );
        
        //get SQL files on disk
        $filesToProcess = array();
        foreach($sqlDirs as $dir) {
            $files = scandir($dir);
            natcasesort($files);
            foreach($files as $file) {
                if($file == '.' || $file == '..'){
                    continue;
                }
                $info = pathinfo($file);
                if(empty($info['extension']) || $info['extension'] != 'sql'){
                    continue;
                }
                //simple filter for mssql
                if(strpos($info['basename'], 'mssql')!== false) {
                    continue;
                }
                $filesToProcess[] = str_replace(APPLICATION_PATH, '', $dir.DIRECTORY_SEPARATOR.$file);
            }
        }

        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $alreadyInDb = $dbAdapter->fetchAll($dbAdapter->select()->from('dbversion', 'name'), array(), Zend_Db::FETCH_COLUMN);

        $filesToProcess = array_diff($filesToProcess, $alreadyInDb);
        foreach($filesToProcess as $file) {
            $realfile = realpath(APPLICATION_PATH.$file);
            if(!file_exists($realfile)) {
                continue;
            }
            $sql = file_get_contents($realfile);
//FIXME query kommt nicht mit mehreren Statements zurecht. MultiQuery?
            //$dbAdapter->query($sql);
            $dbAdapter->insert('dbversion', array('name' => $file));
        }
        //get imported SQL files from DB
        echo '<h2>Already in DB</h2>';
        echo '<pre>';
        print_r($alreadyInDb);
        echo '</pre>';
        echo '<h2>New Imported in DB</h2>';
        echo '<pre>';
        print_r($filesToProcess);
        echo '</pre>';
        exit;
    }
    
    public function checkconfigAction() {
        echo "<h2>application agency loaded: </h2>".APPLICATION_AGENCY.'<br />';
        echo "<h2>loaded INI config files:</h2>".join("<br/>\n", ZfExtended_BaseIndex::getInstance()->applicationInis).'<br />';
        
        echo "<h2>tests</h2>";
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        exit;
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $test = $config->runtimeOptions->imageTag->fontFilePath;
        
        //@todo wegen openbasedir muss um die file_exists ein try catch bzw. dann um jeden einzelnen Test
        
        if(! file_exists($test)){
            echo 'ERROR "'.$test.'" does not exist!';
            return;
        }
        if(! is_file($test)){
            echo 'ERROR "'.$test.'" is not a File!';
            return;
        }
        if(! is_readable($test)){
            echo 'ERROR "'.$test.'" is not readable!';
            return;
        }
        echo 'OK '.$test.' is a readable File!<br />';

        $test = $config->runtimeOptions->termTagger->dir;
        if(! file_exists($test)){
            echo 'ERROR "'.$test.'" does not exist!';
            return;
        }
        if(! is_dir($test)){
            echo 'ERROR "'.$test.'" is not a Dir!';
            return;
        }
        if(! is_readable($test)){
            echo 'ERROR "'.$test.'" is not readable!';
            return;
        }
        echo 'OK '.$test.' is a readable File!<br />';
        exit;
    }

    public function impressumAction(){
        $this->view->impressum = true;
    }
}
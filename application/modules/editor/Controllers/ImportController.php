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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Dummy Index Controller
 */
class Editor_ImportController extends ZfExtended_Controllers_Action {
    /**
     * @var string GUID
     */
    protected $_taskGuid = NULL;
    /**
     * @var string GUID
     */
    protected $_userGuid = NULL;
    /**
     * @var string
     */
    protected $_userName = NULL;
    /**
     * @var array array(fileId => 'filePath',...)
     */
    protected $_filePaths = array();
    /**
     * @var integer aus der Tabelle LEK_languages
     */
    protected $_sourceLang = NULL;
    /**
     * @var integer aus der Tabelle LEK_languages
     */
    protected $_targetLang = NULL;
    /**
     * @var boolean legt für die aktuelle Fileparser-Instanz fest, ob 100-Matches
     *              editiert werden dürfen (true) oder nicht (false)
     *              Übergabe in URL: false wird bei Übergabe von 0 oder leer-String gesetzt, sonst true
     */
    public $_edit100PercentMatches = false;
    /**
     * @var string Ordner, unterhalb dessen die zu importierende Ordner- und Dateihierarchie liegt
     */
    protected $_importRootFolder = NULL;
    /**
     * @var array enthält alle images, die mit dem aktuellen Controllerdurchlauf erzeugt wurden als Values
     */
    protected $_imagesInTask = array();
    /**
     * führt den Import aller Dateien eines Task durch
     *
     * - mit Get-Parameter check=1: Prüft den Import aller Dateien und löscht im Anschluss alle Daten aus der Datenbank
     */
    public function indexAction() {
        $importer = ZfExtended_Factory::get('editor_Models_Import');
        /* @var $importer editor_Models_Import */
        $p = $this->getParams();
        $importer->setEdit100PercentMatches($p->editFullMatch);
        $importer->setUserInfos($p->userGuid, $p->userName);
        $importer->setLanguages($p->sourceLang, $p->targetLang, $p->relaisLang, $p->langType);
        $importer->createTask($p);
        $importer->setCheck($p->check);
        $dp = $this->getDataProvider($p);
        try {
            $importer->import($dp);
        }
        catch (Exception $e) {
            //task is not deleted on failure.
            //failures should not arise. If they do it is better to not delete the
            //task for better fixing of the bug
            $dp->handleImportException($e);
            throw $e;
        }
        if($p->check){
            Zend_Registry::set('errorCollect', true); // set here again, to display the errors
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            Zend_Registry::set('showErrorsInBrowser', true);
            $importer->deleteTask();
        }
    }

    /**
     * @param stdClass $params
     * @return editor_Models_Import_DataProvider_Abstract
     */
    protected function getDataProvider(stdClass $params) {
        return ZfExtended_Factory::get('editor_Models_Import_DataProvider_Directory', array($params->importRootFolder));        
    }
    
    /**
     * verarbeitet die per Get übergebenen Parameter für den Import
     */
    protected function getParams(){
        $result = new stdClass();
        $result->editFullMatch = (bool)$this->_getParam('edit100PercentMatches');
        $result->userGuid = urldecode($this->_getParam('userGuid'));
        $result->userName = urldecode($this->_getParam('userName'));
        $result->sourceLang = strtolower($this->_getParam('sourceLang'));
        $result->targetLang = strtolower($this->_getParam('targetLang'));
        $result->relaisLang = strtolower($this->_getParam('relaisLang'));
        $result->langType = strtolower($this->_getParam('languageType'));
        $result->enableSourceEditing = (bool)$this->_getParam('enableSourceEditing');
        if(empty($result->langType)) {
            $result->langType = strtolower($this->_session->runtimeOptions->import->languageType);
        }
        $folderBase64Encoded = $this->_getParam('folderBase64Encoded');
        $check = $this->_getParam('check');
        $result->check = ($check == 0?false:true);
        if(empty($folderBase64Encoded)){
            $result->importRootFolder = urldecode($this->_getParam('importRootFolder'));
        }
        else{
            $result->importRootFolder = urldecode(base64_decode($this->_getParam('importRootFolder')));
        }
        $result->pmGuid = urldecode($this->_getParam('pmGuid','{00000000-0000-0000-0000-000000000000}'));//set Default pmGuid, if not set
        $result->taskNr = urldecode($this->_getParam('taskNr','Default taskNr'));//set Default taskNr, if not set
        $result->wordCount = urldecode($this->_getParam('wordCount',0));//set Default wordCount, if not set
        $result->targetDeliveryDate = date("Y-m-d H:i:s",$this->_getParam('targetDeliveryDate',  time()));//set Default targetDeliveryDate, if not set
        $result->orderDate = date("Y-m-d H:i:s",$this->_getParam('orderDate',  time()));//set Default targetDeliveryDate, if not set

        $result->taskGuid = urldecode($this->_getParam('taskGuid'));
        $result->taskName = urldecode($this->_getParam('taskName','Default Task Name'));//set Default Task Name, if not set
        return $result;
    }

    public function generatesmalltagsAction() {
      set_time_limit(0);

      /* @var $single ImageTag_Single */
      $single = ZfExtended_Factory::get('ImageTag_Single');

      /* @var $left ImageTag_Left */
      $left = ZfExtended_Factory::get('ImageTag_Left');

      /* @var $right ImageTag_Right */
      $right = ZfExtended_Factory::get('ImageTag_Right');

      for($i = 1; $i <= 10; $i++) {
        $single->create('<'.$i.'/>');
        $left->create('<'.$i.'>');
        $right->create('<'.$i.'/>');

        $single->save($i);
        $left->save($i);
        $right->save($i);
      }

      exit;
    }
}

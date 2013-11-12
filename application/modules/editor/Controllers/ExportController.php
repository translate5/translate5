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
 * @package editor
 * @version 1.0
 *
 */

/**
 * 
 */
class Editor_ExportController extends ZfExtended_Controllers_Action {

    /**
     * @var string GUID
     *
     */
    protected $_taskGuid = NULL;

    /**
     * @var array array(fileId => 'filePath',...) Die Pfade der zu exportierenden Dateien
     *
     */
    protected $_filePaths = array();

    /**
     * @var array array(fileId => 'dirPath',...)
     *      Die Pfade der in $this->_exportRootFolder anzulegenden Verzeichnisse
     *      in einer Reihenfolge, so dass in der Hierarchie höhere Verzeichnisse
     *      niedrigere Array-Indizes haben
     *
     */
    protected $_dirPaths = array();

    /**
     * @var string Ordner, unterhalb dessen die zu importierende Ordner- und Dateihierarchie liegt
     *
     */
    protected $_exportRootFolder = NULL;

    /**
     * @var boolean wether or not to include a diff about the changes in the exported segments
     *
     */
    protected $_diff = false;

    /**
     * exports the task
     */
    public function indexAction() {
        $this->setGetVars();
        $task =  ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->_taskGuid);
        $export = ZfExtended_Factory::get('editor_Models_Export');
        /* @var $export editor_Models_Export */
        if(!$export->setTaskToExport($task, $this->_diff)){
            throw new Zend_Exception('export of task '.$this->_taskGuid.
                    ' already running!');
        }
        $export->exportToFolder($this->_exportRootFolder);
    }

    /**
     * Setzt die Klassenvariablen auf die per Get übergebenen Wert und validiert / filtert die Get-Werte
     *
     * @throws Zend_Exception 'Der übergebene importRootFolder '.$this->_importRootFolder.' existiert nicht.'
     * @throws Zend_Exception 'Die übergebene taskGuid '.$this->_taskGuid.' ist keine valide GUID.'
     */
    protected function setGetVars() {
        $diff = $this->_getParam('diff');
        $this->_diff = ($diff === 'true' || $diff === '1');
        $folderBase64Encoded = $this->_getParam('folderBase64Encoded');
        if ($folderBase64Encoded === 'true' or $folderBase64Encoded === '1') {
            $this->_exportRootFolder = urldecode(base64_decode($this->_getParam('exportRootFolder')));
        } else {
            $this->_exportRootFolder = urldecode($this->_getParam('exportRootFolder'));
        }

        if (!is_dir($this->_exportRootFolder)) {
            //throw new Zend_Exception('Der übergebene exportRootFolder '.$this->_exportRootFolder.' existiert nicht.');
            try {
                mkdir($this->_exportRootFolder, 0777, true);
            } catch (Exception $e) {
                throw new Zend_Exception('exportRootFolder ' . $this->_exportRootFolder . ' does not exist and is not possible to be created.');
            }
        }
        $this->_taskGuid = urldecode($this->_getParam('taskGuid'));
        $guidValidator = new ZfExtended_Validate_Guid();
        if (!$guidValidator->isValid($this->_taskGuid)) {
            throw new Zend_Exception('Die übergebene taskGuid ' . $this->_taskGuid . ' ist keine valide GUID.');
        }
    }

}


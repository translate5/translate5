<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Moses Connector
 */
class editor_Plugins_TmMtIntegration_Services_DummyFileTm_Connector extends editor_Plugins_TmMtIntegration_Services_ConnectorAbstract {

    protected $tm;
    protected $uploadedFile;

    public function __construct() {
        $eventManager = Zend_EventManager_StaticEventManager::getInstance();
        $eventManager->attach('editor_Plugins_TmMtIntegration_TmmtController', 'afterPostAction', array($this, 'handleAfterTmmtSaved'));
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::addTm()
     */
    public function addTm(string $filename, editor_Plugins_TmMtIntegration_Models_TmMt $tm){
        $this->uploadedFile = $filename;
        $this->tm = $tm;
        //do nothing here, since we need the entity ID to save the TM
        return true;
    }

    /**
     * in our dummy file TM the TM can only be saved after the TM is in the DB, since the ID is needed for the filename
     */
    public function handleAfterTmmtSaved() {
        move_uploaded_file($this->uploadedFile, $this->getTmFile($this->tm->getId()));
    }

    protected function getTmFile($id) {
        return APPLICATION_PATH.'/../data/dummyTm_'.$id;
    }

    public function synchronizeTmList() {
        //read file list
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::open()
     */
    public function open(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        error_log("Opened Tmmt ".$tmmt->getName().' - '.$tmmt->getResourceName());

    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::query()
     */
    public function query(string $queryString) {
        error_log("queried: ".$queryString);
        $result = array("foo", "bar");
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::search()
     */
    public function search(string $searchString) {
        return $this->query($searchString);
    }
}
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

/**
 * Controller for the Plugin TmMtIntegration configured Tmmt 
 */
class editor_Plugins_TmMtIntegration_TmmtController extends ZfExtended_RestController {

    const FILE_UPLOAD_NAME = 'tmUpload';
    
    protected $entityClass = 'editor_Plugins_TmMtIntegration_Models_TmMt';

    /**
     * @var editor_Plugins_TmMtIntegration_Models_TmMt
     */
    protected $entity;
    
    public function indexAction(){
        parent::indexAction();
    }
    
    public function postAction(){
        $this->entity->init();
        $this->data = $this->_getAllParams();
        $this->setDataInEntity($this->postBlacklist);
        
        error_log(print_r($this->entity->getDataObject(),1));
        $manager = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Services_Manager');
        /* @var $manager editor_Plugins_TmMtIntegration_Services_Manager */
        $resource = $manager->getResourceById($this->entity->getServiceType(), $this->entity->getResourceId());
        if($resource->getFilebased()) {
            $this->handleFileUpload($manager, $resource);
        }
      
        if($this->validate()){
            $this->entity->save();
            $this->view->rows = $this->entity->getDataObject();
            $this->view->success = true;
        }
    }
    
    protected function handleFileUpload(editor_Plugins_TmMtIntegration_Services_Manager $manager, editor_Plugins_TmMtIntegration_Models_Resource $resource) {
        $upload = new Zend_File_Transfer_Adapter_Http();
        $upload->isValid(self::FILE_UPLOAD_NAME);
        //mandatory upload file
        $importInfo = $upload->getFileInfo(self::FILE_UPLOAD_NAME);
        $connector = $manager->getConnector($this->entity->getServiceType(), $resource);
        /* @var $connector editor_Plugins_TmMtIntegration_Services_ConnectorAbstract */
        if(empty($importInfo['tmUpload']['size'])) {
            $this->uploadError('Die ausgewählte Datei war leer!');
        }
        if(!$connector->addTm($importInfo['tmUpload']['tmp_name'], $this->entity)) {
            $this->uploadError('Hochgeladene TM Datei konnte nicht hinzugefügt werden.');
        }
    }
    
    public function uploadError($msg) {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        $errors = array('tmUpload' => array($translate->_($msg)));
        $e = new ZfExtended_ValidateException(print_r($errors, 1));
        $e->setErrors($errors);
        throw $e;
    }
    
    public function deleteAction(){        
        $this->entityLoad();
        //$this->processClientReferenceVersion();
        $this->entity->delete();
    }
}
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

/***
 * Imports the language resource file into the language resource.
 */
class editor_Services_ImportWorker extends ZfExtended_Worker_Abstract {
    /***
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;
    
    public function init($taskGuid = NULL, $parameters = array()) {
        $this->behaviour->setConfig(['isMaintenanceScheduled' => true]);
        return parent::init($taskGuid, $parameters);


        $workerModel = $this->workerModel;
        $this->events->attach('editor_Models_Terminology_Import_TbxFileImport', 'afterTermEntrySave', function($progress) use($workerModel){
            $workerModel->updateProgress($progress);
        }, 0);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['languageResourceId'])){
            return false;
        }
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        return $this->doImport();
    }
    
    /**
     * Import languaeg resources file from the upload file
     * @return boolean
     */
    protected function doImport() {
        $params = $this->workerModel->getParameters();
        
        $this->languageResource=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResource editor_Models_LanguageResources_LanguageResource */
        $this->languageResource->load($params['languageResourceId']);
        
        $connector=$this->getConnector($this->languageResource);
        
        if(isset($params['addnew']) && $params['addnew']){
            $return = $connector->addTm($params['fileinfo'],$params);
        } else {
            $return = $connector->addAdditionalTm($params['fileinfo'],$params);
        }
        
        $this->updateLanguageResourceStatus($return);
        
        if(isset($params['fileinfo']['tmp_name']) && !empty($params['fileinfo']['tmp_name']) && file_exists($params['fileinfo']['tmp_name'])){
            //remove the file from the temp dir
            unlink($params['fileinfo']['tmp_name']);
        }
        
        return $return;
    }
    
    /**
     * Update language reources status so the resource is available again
     * @param bool $success
     */
    protected function updateLanguageResourceStatus($success) {
        if($success) {
            $this->languageResource->addSpecificData('status', editor_Services_Connector_FilebasedAbstract::STATUS_AVAILABLE);
        }
        else {
            $this->languageResource->addSpecificData('status', editor_Services_Connector_FilebasedAbstract::STATUS_ERROR);
        }
        $this->languageResource->save();
    }
    
    /***
     * Get the language resource connector
     *
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @return editor_Services_Connector
     */
    protected function getConnector($languageResource) {
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        
        return $serviceManager->getConnector($languageResource);
    }
    
}

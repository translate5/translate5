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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_Okapi_Worker extends editor_Models_Import_Worker_Abstract {
    

    /**
     * @var editor_Plugins_Okapi_Connector
     */
    protected $api;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        //if(empty($parameters['tmmtId'])) {
        //    return false;
        //}
        return true;
    } 
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $params = $this->workerModel->getParameters();
        $fileName=$params['fileName'];
        $okapiDir=$params['okapiDir'];
        $bconfFilePath=$params['bconfFilePath'];
        
        $this->api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');
        
        $this->api->setOkapiDir($okapiDir);
        
        $this->api->createProject();
        $this->api->uploadOkapiConfig($bconfFilePath);
        $this->api->uploadSourceFile($fileName);
        $this->api->executeTask();
        //$this->api->removeProject();

        return true;
    }
}

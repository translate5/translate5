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

/**
 * Defines a common interface for workflow actions
 */
class editor_Workflow_Actions_Abstract {
    /**
     * @var editor_Workflow_Actions_Config
     */
    protected editor_Workflow_Actions_Config $config;
    
    /**
     * @var ZfExtended_Logger
     */
    protected ZfExtended_Logger $log;
    
    
    public function init(editor_Workflow_Actions_Config $config) {
        $this->config = $config;
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.workflow.notification');
    }

    /**
     * returns the affected user by TUA or the authenticated one if there is no TUA
     * @return ZfExtended_Models_User
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function currentUser(): ZfExtended_Models_User {
        if(empty($this->config->newTua)) {
            return ZfExtended_Authentication::getInstance()->getUser();
        }
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->loadByGuid($this->config->newTua->getUserGuid());
        return $user;
    }
}
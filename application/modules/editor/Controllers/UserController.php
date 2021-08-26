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

class Editor_UserController extends ZfExtended_UserController {
    
    public function deleteAction() {
        //parent is calling load again, but nevermind, this should be bearable...
        $this->entityLoad();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $tasks = $task->loadListByPmGuid($this->entity->getUserGuid());
        if(empty($tasks)) {
            parent::deleteAction();
            return;
        }
        $taskGuids = array_column($tasks, 'taskGuid');
        
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1048' => 'The user can not be deleted, he is PM in one or more tasks.'
        ]);
        throw ZfExtended_Models_Entity_Conflict::createResponse('E1048', [
            'Der Benutzer kann nicht gelöscht werden, er ist PM in einer oder mehreren Aufgaben.'
        ],[
            'tasks' => join(', ', $taskGuids),
            'user' => $this->entity->getUserGuid(),
            'userLogin' => $this->entity->getLogin(),
            'userEmail' => $this->entity->getEmail(),
        ]);
    }
}
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
 */
class editor_Models_Logger_LanguageResources extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Logger_LanguageResources';
  
    
    /**
     * Sets the internal data from the given Event class
     * @param ZfExtended_Logger_Event $event
     */
    public function setFromEventAndLanguageResource(ZfExtended_Logger_Event $event, editor_Models_LanguageResources_LanguageResource $languageResource) {
        $this->setLanguageResourceId($languageResource->getId());
        $this->setEventCode($event->eventCode);
        $this->setLevel($event->level);
        $this->setDomain($event->domain);
        $this->setWorker($event->worker);
        $this->setMessage($event->message);
        $userGuid=$event->extra['userGuid'] ?? '';
        $userName=$event->extra['userName'] ?? '';
        $this->setAuthUserGuid($userGuid);
        $this->setAuthUser($userName);
        $this->setCreated($event->created);
    }
    
    /***
     * Load events by language resource id
     * @param integer $languageResourceId
     * @return array
     */
    public function loadByLanguageResourceId($languageResourceId){
        $s = $this->db->select();
        $s->where('languageResourceId = ?', $languageResourceId);
        return $this->loadFilterdCustom($s);
    }
    
    /**
     * loads all events to the given languageResource
     * sorts from newest to oldest
     * @param integer $languageResourceId
     * @return array
     */
    public function getTotalByLanguageResourceId($taskGuid) {
        $s = $this->db->select();
        $s->where('languageResourceId = ?', $taskGuid);
        return $this->computeTotalCount($s);;
    }
    
    /***
     * Get the count of events per language resource for the last 2 months. This will only count the errors and warnings
     *
     * @param array $languageResourcesIds
     * @return array
     */
    public function getLatesEventsCount(array $languageResourcesIds) {
        if(empty($languageResourcesIds)){
            return [];
        }
        $s = $this->db->select()
        ->from('LEK_languageresources_log',array('count(*) as logCount','languageResourceId'))
        ->where('languageResourceId IN(?)', $languageResourcesIds)
        ->where('level <= ?',ZfExtended_Logger::LEVEL_INFO)
        ->where('created >= NOW() - INTERVAL 2 month')
        ->group('languageResourceId');

        $result=$this->db->fetchAll($s)->toArray();
        return array_combine(array_column($result,'languageResourceId'),array_column($result,'logCount'));
    }
}

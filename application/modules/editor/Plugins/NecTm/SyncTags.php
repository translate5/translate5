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


class editor_Plugins_NecTm_SyncTags {
    /**
     * @var editor_Plugins_NecTm_HttpApi
     */
    protected $api;
    
    /**
     * @var editor_Plugins_NecTm_Service
     */
    protected $service;
    
    public function __construct(editor_Plugins_NecTm_Service $service) {
        $this->service = $service;
        $this->api = ZfExtended_Factory::get('editor_Plugins_NecTm_HttpApi');
    }
    
    /**
     * Performs the Tag-sync (= is not serviceResources-specific):
     * Queries NEC TM for all tags that can be accessed with the system credentials
     * in NEC TM. The existing tags are saved in the translate5 DB. Tags that 
     * already exist in translate5 DB, but do not exist any more in NEC TM, 
     * are removed from the DB and from all language resource associations.
     * @param bool $withMutex
     */
    public function synchronize($withMutex = true) {
        if($withMutex && !$this->mutex()) {
            return;
        }
        $tagsEntity = ZfExtended_Factory::get('editor_Models_Tags');
        /* @var $tagsEntity editor_Models_Tags */
        
        // all NEC-TM-tags that are available for us
        $allAvailable = $this->api->getAllTags();
        
        // When we have no list with the available tags, we cannot check anything.
        if(empty($allAvailable)) {
            return;
        }
        
        //TODO with PHP 7 use array_column and array_combine
        $allAvailableByID = [];
        foreach ($allAvailable as $availableTag) {
            $allAvailableByID[$availableTag->id] = $availableTag;
        }
        unset($allAvailable);
        
        // all tags that we stored from NEC-TM
        $allStored = $tagsEntity->loadByOrigin($this->service->getTagOrigin());
        foreach ($allStored as $storedTag) {
            $tagsEntity->load($storedTag);
            $tagId = $tagsEntity->getOriginalTagId();
            
            // if the locally stored tag does not exist on NEC-TM-Server - remove it locally!
            if(!array_key_exists($tagId, $allAvailableByID)) {
                $this->deleteStoredTag($tagsEntity);
                continue;
            }
            
            // if the ID is the same, but the name or type has changed: update the name locally
            if($tagsEntity->getLabel() != $allAvailableByID[$tagId]->name) {
                $tagsEntity->setLabel($allAvailableByID[$tagId]->name);
                $tagsEntity->save();
            }
            if($tagsEntity->getSpecificData('type') != $allAvailableByID[$tagId]->type) {
                $tagSpecificData  = array('type' => $allAvailableByID[$tagId]->type);
                $tagsEntity->setSpecificData($tagSpecificData);
                $tagsEntity->save();
            }
            
            //remove from list after processing locally
            //so the remaining in $allAvailableByID are new and must be added
            unset($allAvailableByID[$tagId]);
        }
        
        // Add those tags to the DB that are available, but not stored so far.
        foreach ($allAvailableByID as $necTmTag) {
            $this->addTagFromNEC($necTmTag);
        }
    }
    
    /**
     * Remove a NEC-TM-tag from the tags-table in our DB.
     * @param editor_Models_Tags $tagsEntity
     */
    protected function deleteStoredTag(editor_Models_Tags $tagsEntity) {
        $tagsEntity->delete();
    }
    
    /**
     * Add a NEC-TM-tag to the tags-table in our DB.
     * @param object $necTmTag
     */
    protected function addTagFromNEC($necTmTag) {
        $tagSpecificData  = array('type' => $necTmTag->type);
        $tagsEntity = ZfExtended_Factory::get('editor_Models_Tags');
        /* @var $tag editor_Models_Tags */
        $tagsEntity->setOrigin($this->service->getTagOrigin());
        $tagsEntity->setLabel($necTmTag->name);
        $tagsEntity->setOriginalTagId($necTmTag->id);
        $tagsEntity->setSpecificData($tagSpecificData);
        $tagsEntity->save();
    }
    
    /**
     * mutual exclusion: ensures that sync is running only once and once in every two minutes
     * @return boolean
     */
    protected function mutex() {
        $cache = Zend_Cache::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend());
        $backend = $cache->getBackend();
        /* @var $backend ZfExtended_Cache_MySQLMemoryBackend */
        $interval = 2 * 60; //2 minutes
        $key = 'editor_Plugins_NecTm_SyncTags::synchronize';
        
        //using cache backend as time based mutex to ensure that sync is not done more often as each 2 minutes:
        return $backend->updateIfOlderThen($key, $_SERVER['REQUEST_URI'], $interval);
    }
}

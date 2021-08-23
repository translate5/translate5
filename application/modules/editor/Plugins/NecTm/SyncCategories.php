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

class editor_Plugins_NecTm_SyncCategories {
    /**
     * @var editor_Plugins_NecTm_HttpApi
     */
    protected $api;
    
    public function __construct() {
        $this->api = ZfExtended_Factory::get('editor_Plugins_NecTm_HttpApi');
    }
    
    /**
     * Performs the categories-sync (= is not serviceResources-specific):
     * Queries NEC TM for all categories (= there: "tags") that can be accessed
     * with the system credentials in NEC TM. The existing NEC-TM-tags are saved
     * as categories in the translate5 DB. Categories that already exist in the
     * translate5 DB, but do not exist any more in NEC TM, are removed from the
     * DB and from all language resource associations.
     * @param bool $withMutex
     */
    public function synchronize($withMutex = true) {
        if ($withMutex && !$this->mutex()) {
            return;
        }
        $categoriesEntity = ZfExtended_Factory::get('editor_Models_Categories');
        /* @var $categoriesEntity editor_Models_Categories */
        
        // all NEC-TM-categories that are available for us
        $allAvailable = [];
        if ($this->api->getTags()) {
            $allAvailable = $this->api->getResult();
        }
        
        // When we have no list with the available categories, we cannot check anything.
        if(empty($allAvailable)) {
            return;
        }
        
        //TODO with PHP 7 use array_column and array_combine
        $allAvailableByID = [];
        foreach ($allAvailable as $availableCategory) {
            $allAvailableByID[$availableCategory->id] = $availableCategory;
        }
        unset($allAvailable);
        
        // all categories that we stored from NEC-TM
        $allStored = $categoriesEntity->loadByOrigin(editor_Plugins_NecTm_Service::CATEGORY_ORIGIN);
        foreach ($allStored as $storedCategory) {
            $categoriesEntity->load($storedCategory);
            $categoryId = $categoriesEntity->getOriginalCategoryId();
            
            // if the locally stored categories does not exist on NEC-TM-Server - remove it locally!
            if(!array_key_exists($categoryId, $allAvailableByID)) {
                $this->deleteStoredCategory($categoriesEntity);
                continue;
            }
            
            // if the ID is the same, but the name or type has changed: update the name locally
            if($categoriesEntity->getLabel() != $allAvailableByID[$categoryId]->name) {
                $categoriesEntity->setLabel($allAvailableByID[$categoryId]->name);
                $categoriesEntity->save();
            }
            if($categoriesEntity->getSpecificData('type') != $allAvailableByID[$categoryId]->type) {
                $categorySpecificData  = array('type' => $allAvailableByID[$categoryId]->type);
                $categoriesEntity->setSpecificData($categorySpecificData);
                $categoriesEntity->save();
            }
            
            //remove from list after processing locally
            //so the remaining in $allAvailableByID are new and must be added
            unset($allAvailableByID[$categoryId]);
        }
        
        // Add those categories to the DB that are available, but not stored so far.
        foreach ($allAvailableByID as $necTmCategory) {
            $this->addCategoryFromNEC($necTmCategory);
        }
    }
    
    /**
     * Remove a NEC-TM-category from the categories-table in our DB.
     * @param editor_Models_Categories $categoriesEntity
     */
    protected function deleteStoredCategory(editor_Models_Categories $categoriesEntity) {
        $categoriesEntity->delete();
    }
    
    /**
     * Add a NEC-TM-category to the categories-table in our DB.
     * @param object $necTmCategory
     */
    protected function addCategoryFromNEC($necTmCategory) {
        $categorySpecificData  = array('type' => $necTmCategory->type);
        $categoriesEntity = ZfExtended_Factory::get('editor_Models_Categories');
        /* @var $categoriesEntity editor_Models_Categories */
        $categoriesEntity->setOrigin(editor_Plugins_NecTm_Service::CATEGORY_ORIGIN);
        $categoriesEntity->setLabel($necTmCategory->name);
        $categoriesEntity->setOriginalCategoryId($necTmCategory->id);
        $categoriesEntity->setSpecificData($categorySpecificData);
        $categoriesEntity->save();
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
        $key = 'editor_Plugins_NecTm_SyncCategories::synchronize';
        
        //using cache backend as time based mutex to ensure that sync is not done more often as each 2 minutes:
        return $backend->updateIfOlderThen($key, $_SERVER['REQUEST_URI'], $interval);
    }
}

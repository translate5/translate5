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
     * performs the Tag-sync for all serviceResources
     * @param bool $withMutex
     */
    public function synchronize($withMutex = true) {
        if($withMutex && !$this->mutex()) {
            return;
        }
        foreach ($this->service->getResources() as $serviceResource) {
            /* @var $serviceResource editor_Models_LanguageResources_Resource */
            $this->syncOneServiceInstance($serviceResource);
        }
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
    
    /**
     * Queries NEC TM for all tags that can be accessed with the system credentials in NEC TM.
     * The existing tags are saved in the translate5 DB. Tags that already exist in translate5 DB,
     * but do not exist any more in NEC TM, are removed from the DB and from all language resource associations.
     * @param editor_Models_LanguageResources_Resource $serviceResource
     */
    protected function syncOneServiceInstance($serviceResource) {
        $languageResourceEntity = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResourceEntity editor_Models_LanguageResources_LanguageResource */
        
        // all NEC-TM-Tags that are available for us for this serviceResource
        $allAvailable = $this->api->getAllTags();
        $test = 3;
        // TODO...
    }
    
    /**
     * removes the language resource and notfies the PMs of associated tasks
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     */
    protected function deleteTagLocally(editor_Models_LanguageResources_LanguageResource $languageResource) {
        // TODO
    }
    
    /**
     * Add a GroupShare-TM to the LanguageResources for the given serviceResource; also save the resource languages.
     * @param object $necTmTag
     * @param editor_Models_LanguageResources_Resource $serviceResource
     */
    protected function addToTags($necTmTag, editor_Models_LanguageResources_Resource $serviceResource) {
        // TODO
    }
}

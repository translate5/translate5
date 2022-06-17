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
 * Seperate Holder of certain configurations to accompany editor_Plugins_SpellCheck_Worker_SpellCheckImport
 * 
 */
class editor_Plugins_SpellCheck_Configuration {
    
    /**
     * @var string
     */
    const SEGMENT_STATE_UNCHECKED = 'unchecked';

    /**
     * @var string
     */
    const SEGMENT_STATE_INPROGRESS = 'inprogress';

    /**
     * @var string
     */
    const SEGMENT_STATE_CHECKED = 'checked';

    /**
     * @var string
     */
    const SEGMENT_STATE_RECHECK = 'recheck';

    /**
     * @var string
     */
    const SEGMENT_STATE_DEFECT = 'defect';

    /**
     * Defines, how much segments can be processed in one worker call
     * @var integer
     */
    const IMPORT_SEGMENTS_PER_CALL = 1;

    /**
     * Defines the timeout in seconds how long a spell-check call with multiple segments may need
     *
     * @var integer
     */
    const IMPORT_TIMEOUT_REQUEST = 300;

    /**
     * Defines the timeout in seconds how long a single segment needs to be spell-checked
     *
     * @var integer
     */
    //const EDITOR_TIMEOUT_REQUEST = 180;

    /**
     * Logger Domain Import
     * @var string
     */
    const IMPORT_LOGGER_DOMAIN = 'editor.spellcheck.import';

    /**
     * Logger Domain Editing
     * @var string
     */
    const EDITOR_LOGGER_DOMAIN = 'editor.spellcheck.segmentediting';

    /**
     *
     * @var string
     */
    const DOWN_CACHE_KEY = 'SpellCheckDownList';

    /**
     *
     * @param array $offlineServers
     */
    public static function saveDownListToMemCache(array $offlineUrls) {
        $memCache = Zend_Cache::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend(), ['automatic_serialization' => true]);
        $memCache->save($offlineUrls, editor_Plugins_SpellCheck_Configuration::DOWN_CACHE_KEY);
    }

    /**
     * @var editor_Models_Task
     */
    private $task;

    /**
     * @var Zend_Cache_Core
     */
    private $memCache;

    /**
     * 
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task){
        $this->task = $task;
        $this->memCache = null;
    }

    /**
     * 
     * @param bool $isWorkerThread
     * @return int
     */
    public function getRequestTimeout(bool $isWorkerThread) : int {
        return self::IMPORT_TIMEOUT_REQUEST;
    }

    /**
     * 
     * @param string $processingType
     * @return string
     */
    public function getLoggerDomain(string  $processingType) : string {
        switch($processingType){
            case editor_Segment_Processing::IMPORT: return self::IMPORT_LOGGER_DOMAIN;
            case editor_Segment_Processing::EDIT:   return self::EDITOR_LOGGER_DOMAIN;
            default:                                return self::EDITOR_LOGGER_DOMAIN;
        }
    }
    /**
     * 
     * @return Zend_Cache_Core
     */
    public function getMemCache() : Zend_Cache_Core {
        if($this->memCache == null){
            $this->memCache = Zend_Cache::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend(), ['automatic_serialization' => true]);
        }
        return $this->memCache;
    }
    /**
     *
     * @return array
     */
    public function getAvailableResourceSlots($resourcePool) {
        $config = Zend_Registry::get('config');
        $url = $config->runtimeOptions->plugins->SpellCheck->languagetool->url;
        switch ($resourcePool) {
            case 'gui':
                $return = $url->gui->toArray();
                break;
                
            case 'import':
                $return = $url->import->toArray();
                break;
                
            case 'default':
            default:
                $return = $url->default->toArray();
                break;
        }
        //remove not available spellcheckers from configured list
        $downList = $this->getMemCache()->load(self::DOWN_CACHE_KEY);
        if(!empty($downList) && is_array($downList)) {
            $return = array_diff($return, $downList);
        }
        // no slots for this resourcePool defined
        if (empty($return) && $resourcePool != 'default') {
            // calculate slot from default resourcePool
            return $this->getAvailableResourceSlots('default');
        }
        return $return;
    }
    /**
     * disables the given slot (URL) via memcache.
     * @param string $url
     */
    public function disableResourceSlot(string $url) : void {
        $list = $this->getMemCache()->load(self::DOWN_CACHE_KEY);
        if(!$list || !is_array($list)) {
            $list = [];
        }
        $list[] = $url;
        $this->getMemCache()->save($list, self::DOWN_CACHE_KEY);
    }
}

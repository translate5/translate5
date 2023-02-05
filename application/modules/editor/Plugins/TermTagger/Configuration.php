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
 * Seperate Holder of certain configurations regarding the termtagging
 * to accompany editor_Plugins_TermTagger_SegmentProcessor and editor_Plugins_TermTagger_Worker_TermTaggerImport
 */
class editor_Plugins_TermTagger_Configuration {
    
    /**
     * @var string
     */
    const SEGMENT_STATE_UNTAGGED = 'untagged';
    /**
     * @var string
     */
    const SEGMENT_STATE_INPROGRESS = 'inprogress';
    /**
     * @var string
     */
    const SEGMENT_STATE_TAGGED = 'tagged';
    /**
     * @var string
     */
    const SEGMENT_STATE_DEFECT = 'defect';
    /**
     * @var string
     */
    const SEGMENT_STATE_RETAG = 'retag';
    /**
     * @var string
     */
    const SEGMENT_STATE_OVERSIZE = 'oversized';
    /**
     * @var string
     */
    const SEGMENT_STATE_IGNORE = 'ignore';
    
    //const SEGMENT_STATE_TARGETNOTFOUND = 'targetnotfound';
    /**
     * Defines, how much segments can be processed in one worker call
     * @var integer
     */
    const IMPORT_SEGMENTS_PER_CALL = 5;
    /**
     * Defines the timeout in seconds how long a termtag call with multiple segments may need
     * @var integer
     */
    const IMPORT_TIMEOUT_REQUEST = 300;
    /**
     * Defines the timeout in seconds how long a single segment needs to be tagged
     * @var integer
     */
    const EDITOR_TIMEOUT_REQUEST = 180;
    /**
     * Defines the timeout in seconds how long the upload and parse request of a TBX may need
     * @var integer
     */
    const TIMEOUT_TBXIMPORT = 600;
    /**
     * Logger Domain Import
     * @var string
     */
    const IMPORT_LOGGER_DOMAIN = 'editor.terminology.import';
    /**
     * Logger Domain Editing
     * @var string
     */
    const EDITOR_LOGGER_DOMAIN = 'editor.terminology.segmentediting';
    /**
     * Logger Domain Manual Analysis
     * @var string
     */
    const ANALYSIS_LOGGER_DOMAIN = 'editor.terminology.analysis';
    /**
     * 
     * @var string
     */
    const DOWN_CACHE_KEY = 'TermTaggerDownList';
    
    /**
     * 
     * @param array $offlineServers
     */
    public static function saveDownListToMemCache(array $offlineUrls) {
        $memCache = Zend_Cache::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend(), ['automatic_serialization' => true]);
        $memCache->save($offlineUrls, editor_Plugins_TermTagger_Configuration::DOWN_CACHE_KEY);
    }

    /**
     *
     * @param string $processingType
     * @return string
     */
    public static function getLoggerDomain(string $processingType) : string {
        switch($processingType){

            case editor_Segment_Processing::IMPORT:
                return self::IMPORT_LOGGER_DOMAIN;

            case editor_Segment_Processing::ANALYSIS:
            case editor_Segment_Processing::RETAG:
            case editor_Segment_Processing::TAGTERMS:
                return self::ANALYSIS_LOGGER_DOMAIN;

            case editor_Segment_Processing::EDIT:
            default:
                return self::EDITOR_LOGGER_DOMAIN;
        }
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
         if($isWorkerThread){
            return self::IMPORT_TIMEOUT_REQUEST;
        }
        return self::EDITOR_TIMEOUT_REQUEST;
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
        $url = $config->runtimeOptions->termTagger->url;
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
        //remove not available termtaggers from configured list
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

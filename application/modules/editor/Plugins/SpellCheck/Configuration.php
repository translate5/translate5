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
 * Separate holder of certain configurations to accompany editor_Plugins_SpellCheck_Worker_Import
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
     * Defines, how much segments can be processed per one worker call
     *
     * @var integer
     */
    const IMPORT_SEGMENTS_PER_CALL = 5;

    /**
     * Defines the timeout in seconds how long a spell-check call with multiple segments may need
     *
     * @var integer
     */
    const IMPORT_TIMEOUT_REQUEST = 300;

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
     * Cache key to point to the list of LanguageTool connectors which are down
     *
     * @var string
     */
    const DOWN_CACHE_KEY = 'SpellCheckDownList';

    /**
     * Memcache instance
     *
     * @var Zend_Cache_Core
     */
    private $memCache = null;

    /**
     * Get request timeout
     *
     * @param bool $isWorkerThread
     * @return int
     */
    public function getRequestTimeout(bool $isWorkerThread) : int {
        return self::IMPORT_TIMEOUT_REQUEST;
    }

    /**
     * Get logger domain
     *
     * @param string $processingType
     * @return string
     */
    public function getLoggerDomain(string  $processingType) : string {
        switch ($processingType) {
            case editor_Segment_Processing::IMPORT: return self::IMPORT_LOGGER_DOMAIN;
            case editor_Segment_Processing::EDIT:   return self::EDITOR_LOGGER_DOMAIN;
            default:                                return self::EDITOR_LOGGER_DOMAIN;
        }
    }

    /**
     * Get memcache instance
     *
     * @return Zend_Cache_Core
     */
    public function getMemCache() : Zend_Cache_Core {
        return $this->memCache ?? $this->memCache = Zend_Cache
            ::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend(), ['automatic_serialization' => true]);
    }

    /**
     * Save to cache list of LanguageTool spots which are offline
     *
     * @param array $offlineSpots
     */
    public static function saveDownListToMemCache(array $offlineSpots) {
        Zend_Cache
            ::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend(), ['automatic_serialization' => true])
            ->save($offlineSpots, self::DOWN_CACHE_KEY);
    }

    /**
     * Get array of available resource slots for the given $resourcePool
     *
     * @return array
     */
    public function getAvailableResourceSlots($resourcePool) {

        // Get config
        $config = Zend_Registry::get('config');

        // Get declared LanguageTool slots, grouped by resource pools
        $url = $config->runtimeOptions->plugins->SpellCheck->languagetool->url;

        // Get slot(s) declared for given $resourcePool
        switch ($resourcePool) {
            case 'gui'   :   $declared = [$url->gui];              break;
            case 'import':   $declared = $url->import->toArray();  break;
            case 'default':
            default:         $declared = $url->default->toArray(); break;
        }

        // Get list of offline slots
        $offline = $this->getMemCache()->load(self::DOWN_CACHE_KEY);

        // Get online slots by deducting offline from declared
        $online = array_diff($declared, is_array($offline) ? $offline : []);

        // If given $resourcePool is not 'default' and no online slots detected
        if (empty($online) && $resourcePool != 'default') {

            // Pick slots for 'default'-resourcePool
            return $this->getAvailableResourceSlots('default');
        }

        // Return online slots array (can be empty)
        return $online;
    }

    /**
     * Append slot (URL) to the list of down slots to be able to skip it further
     *
     * @param string $url
     */
    public function disableResourceSlot(string $url) : void {

        // Get current list of down slots
        $list = $this->getMemCache()->load(self::DOWN_CACHE_KEY);

        // Make sure it's an array
        if (!$list || !is_array($list)) {
            $list = [];
        }

        // Append $url arg to the list
        $list []= $url;

        // Save list back to memcache
        $this->getMemCache()->save($list, self::DOWN_CACHE_KEY);
    }
}

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

/**
 * Segment Helper Class
 */
class editor_Models_Segment_Utility {
    /**
     * @var array
     */
    protected $stateFlags;
    
    /**
     * @var array
     */
    protected $qualityFlags;
    
    public function __construct() {
        $config = Zend_Registry::get('config');
        $this->stateFlags = $config->runtimeOptions->segments->stateFlags->toArray();
        $this->qualityFlags = $config->runtimeOptions->segments->qualityFlags->toArray();
    }
    
    /**
     * returns the configured value to the given state id
     * @param string $stateId
     * @return string
     */
    public function convertStateId($stateId) {
        if(empty($stateId)) {
            return '';
        }
        if(isset($this->stateFlags[$stateId])){
            return $this->stateFlags[$stateId];
        }
        return 'Unknown State '.$stateId;
    }
    
    /**
     * converts the semicolon separated qmId string into an associative array
     * key => qmId
     * value => configured String in the config for this id
     * @param string $qmIds
     * @return array
     */
    public function convertQmIds($qmIds) {
        if(empty($qmIds)) {
            return array();
        }
        $qmIds = trim($qmIds, ';');
        $qmIds = explode(';', $qmIds);
        $result = array();
        foreach($qmIds as $qmId) {
            if(isset($this->qualityFlags[$qmId])){
                $result[$qmId] = $this->qualityFlags[$qmId];
                continue;
            }
            $result[$qmId] = 'Unknown Qm Id '.$qmId;
        }
        return $result;
    }
}
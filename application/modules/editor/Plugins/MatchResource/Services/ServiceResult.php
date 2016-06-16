<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Container class for one single service result
 * Main Intention of this class, provide a unified response format for the different services.
 */
class editor_Plugins_MatchResource_Services_ServiceResult {
    protected $defaultSource = '';
    protected $defaultMatchrate;
    
    protected $results = [];
    protected $lastAdded;
    
    /**
     * @var editor_Plugins_MatchResource_Models_TmMt
     */
    protected $tmmt;
    
    /**
     * Total results, needed for paging
     * @var integer
     */
    protected $total = null;
    
    /**
     * A default source text for the results and a defaultMatchrate can be set
     * The default values are the used as initial value for new added result sets
     * @param string $defaultSource
     * @param number $defaultMatchrate
     */
    public function __construct($defaultSource = '', $defaultMatchrate = 0) {
        $this->defaultMatchrate = $defaultMatchrate;
        $this->defaultSource = $defaultSource;
    }
    
    /**
     * Optional, sets a default source text to be used foreach added result
     * @param string $defaultSource
     */
    public function setDefaultSource(string $defaultSource) {
        $this->defaultSource = $defaultSource;
    }
    
    /**
     * Set the source field for the last added result
     * @param string $source
     */
    public function setSource($source) {
        $this->lastAdded->source = $source;
    }
    
    /**
     * sets the resultlist count total which should be send to the server
     * How the total is calculated, depends on the service.
     * @param integer $total
     */
    public function setTotal($total) {
        $this->total = $total;
    }
    
    /**
     * Set the source field for the last added result
     * @param string $source
     */
    public function setAttributes($attributes) {
        $this->lastAdded->attributes = $attributes;
    }
    
    /**
     * Adds a new result set to the result list. Only target and $matchrate are mandatory.
     * All additonal data can be provided by 
     * 
     * @param string $target
     * @param float $matchrate
     */
    public function addResult($target, $matchrate = 0) {
        $result = new stdClass();
        $result->target = $target;
        $result->matchrate = $matchrate;
        $result->source = $this->defaultSource;
        $result->attributes = null;
        $result->tmmtid = $this->tmmt->getId();
        $this->results[] = $result;
        $this->lastAdded = $result;
        return $result;
    }
    
    /**
     * returns the stored total value
     * @return integer
     */
    public function getTotal() {
        if(is_null($this->total)){
            return count($this->results);
        }
        return $this->total;
    }
    
    /**
     * returns a plain array of result objects
     * @return [stdClass]
     */
    public function getResult() {
        return $this->results;
    }
    
    /**
     * @param editor_Plugins_MatchResource_Models_TmMt $tmmt
     */
    public function setTmmt(editor_Plugins_MatchResource_Models_TmMt $tmmt){
        $this->tmmt = $tmmt;
    }
}
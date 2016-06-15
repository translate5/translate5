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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Abstract Base Connector
 */
abstract class editor_Plugins_TmMtIntegration_Services_ConnectorAbstract {
    /**
     * @var editor_Plugins_TmMtIntegration_Models_TmMt
     */
    protected $tmmt;
    
    /**
     * Container for the connector results
     * @var editor_Plugins_TmMtIntegration_Services_ServiceResult
     */
    protected $resultList;

    /**
     * initialises the internal result list
     */
    public function __construct() {
        $this->resultList = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Services_ServiceResult');
    }
    
    /**
     * Just for logging the called methods
     * @param string $msg
     */
    protected function log($method, $msg = '') {
        error_log($method." Tmmt ".$this->tmmt->getName().' - '.$this->tmmt->getServiceName().$msg);
    }
    
    /**
     * Link this Connector Instance to the given Tmmt and its resource
     * @param editor_Plugins_TmMtIntegration_Models_TmMt $tmmt
     */
    public function connectTo(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        $this->tmmt = $tmmt;
        $this->resultList->setTmmt($tmmt);
    }

    /**
     * Adds the given file to the underlying system
     * @param string $filename
     * @return boolean
     */
    public function addTm(string $filename) {
        //to be implemented if needed
        $this->log(__METHOD__, ' filename '.$filename);
    }

    /**
     * Opens the with connectTo given TM on the configured Resource (on task open, not on each request)
     * @param editor_Plugins_TmMtIntegration_Models_TmMt $tmmt
     */
    public function open() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
    
    /**
     * Updates translations in the connected service
     * @param editor_Models_Segment $segment
     */
    public function update(editor_Models_Segment $segment) {
        //to be implemented if needed
        $this->log(__METHOD__, ' segment '.$segment->getId());
    }

    /**
     * Closes the connected TM on the configured Resource (on task close, not after each request)
     */
    public function close() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
    
    /**
     * Deletes the connected TM on the configured Resource
     */
    public function delete() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }

    /**
     * makes a tm / mt / file query to find a match / translation
     * returns an array with stdObjects, each stdObject contains the fields: 
     * 
     * @param editor_Models_Segment $segment
     * @return editor_Plugins_TmMtIntegration_Services_ServiceResult
     */
    abstract public function query(editor_Models_Segment $segment);

    /**
     * returns the original or edited source content to be queried, depending on source edit
     * @param editor_Models_Segment $segment
     * @return string
     */
    protected function getQueryString(editor_Models_Segment $segment) {
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($segment->getTaskGuid());
        $source = editor_Models_SegmentField::TYPE_SOURCE;
        $sourceMeta = $sfm->getByName($source);
        $isSourceEdit = ($sourceMeta !== false && $sourceMeta->editable == 1);
        return $isSourceEdit ? $segment->getFieldEdited($source) : $segment->getFieldOriginal($source);
    }
    
    /**
     * makes a tm / mt / file concordance search
     * @param string $queryString
     * @param string $field
     * @return editor_Plugins_TmMtIntegration_Services_ServiceResult
     */
    abstract public function search(string $searchString, $field = 'source');
    
    /**
     * 
     * @param integer $page
     * @param integer $offset
     * @param integer $limit
     */
    public function setPaging($page, $offset, $limit = 20) {
        //to be implemented if needed
    }
}
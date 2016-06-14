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
    protected $resource;
    
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
        /* @var $this->resultList editor_Plugins_TmMtIntegration_Services_ServiceResult */
    }
    
    public function connectTo(editor_Plugins_TmMtIntegration_Models_Resource $resource) {
        $this->resource = $resource;
    }

    /**
     * Adds the given file to the underlying system
     * @param string $filename
     * @param editor_Plugins_TmMtIntegration_Models_TmMt $tm
     * @return boolean
     */
    abstract public function addTm(string $filename, editor_Plugins_TmMtIntegration_Models_TmMt $tm);

    abstract public function synchronizeTmList();

    /**
     * Opens the desired TM on the configured Resource (on task open, not on each request)
     * @param editor_Plugins_TmMtIntegration_Models_TmMt $tmmt
     */
    abstract public function open(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt);

    /**
     * Closes the desired TM on the configured Resource (on task close, not after each request)
     * @param editor_Plugins_TmMtIntegration_Models_TmMt $tmmt
     */
    abstract public function close(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt);

    /**
     * opens a tm for a concrete query / search request, called before each request
     * @param string $queryString
     * @return array
     */
    abstract public function openForQuery(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt);

    /**
     * makes a tm / mt / file query to find a match / translation
     * returns an array with stdObjects, each stdObject contains the fields: 
     * 
     * 
     * @param string $queryString
     * @return editor_Plugins_TmMtIntegration_Services_ServiceResult
     */
    abstract public function query(string $queryString);

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
        //per default do nothing
    }
}
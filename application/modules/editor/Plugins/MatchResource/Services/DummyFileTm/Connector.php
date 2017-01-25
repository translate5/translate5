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
 * Moses Connector
 *
 * The dummy CSV file must be:
 * , separated
 * " as enclosure
 * "" as escape
 * This should be the CSV defaults.
 * The first column must be an id, the second the source and the theird column the target values. Other columns are ignored.
 */
class editor_Plugins_MatchResource_Services_DummyFileTm_Connector extends editor_Plugins_MatchResource_Services_ConnectorAbstract {

    protected $tm;
    protected $uploadedFile;

    /**
     * Paging information for search requests
     * @var integer
     */
    protected $page;
    protected $offset;
    protected $limit;
    
    /**
     * internal variable to count search results
     * @var integer
     */
    protected $searchCount = 0;

    public function __construct() {
        $eventManager = Zend_EventManager_StaticEventManager::getInstance();
        $eventManager->attach('editor_Plugins_MatchResource_TmmtController', 'afterPostAction', array($this, 'handleAfterTmmtSaved'));
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::addTm()
     */
    public function addTm(string $filename){
        $this->uploadedFile = $filename;
        //do nothing here, since we need the entity ID to save the TM
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::getTm()
     */
    public function getTm(& $mime) {
        $file = new SplFileInfo($this->getTmFile($this->tmmt->getId()));
        if(!$file->isFile() || !$file->isReadable()) {
            throw new ZfExtended_NotFoundException('requested TM file for dummy TM with the tmmtId '.$this->tmmt->getId().' not found!');
        }
        $mime = 'application/csv';
        return file_get_contents($file);
    }

    /**
     * in our dummy file TM the TM can only be saved after the TM is in the DB, since the ID is needed for the filename
     */
    public function handleAfterTmmtSaved() {
        move_uploaded_file($this->uploadedFile, $this->getTmFile($this->tmmt->getId()));
    }

    protected function getTmFile($id) {
        return APPLICATION_PATH.'/../data/dummyTm_'.$id;
    }

    public function update(editor_Models_Segment $segment) {
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        $messages->addError('This is just to inform you, that the TM is not updated and the udpate handler is only for demonstration invoked.');
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryString($segment);
        return $this->loopData($segment->stripTags($queryString));
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::search()
     */
    public function search(string $searchString, $field = 'source') {
        $this->searchCount = 0;
        return $this->loopData($searchString, $field);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::setPaging()
     */
    public function setPaging($page, $offset, $limit = 20) {
        $this->page = (int) $page;
        $this->offset = (int) $offset;
        $this->limit = (int) $limit;
        if(empty($this->limit)) {
            $this->limit = 20;
        }
    }
    
    /**
     * loops through the dummy data and performs a match / search 
     * 
     * @param string $queryString
     * @param string $field
     * @throws ZfExtended_NotFoundException
     * @return editor_Plugins_MatchResource_Services_ServiceResult
     */
    protected function loopData(string $queryString, string $field = null) {
        if(stripos($this->tmmt->getName(), 'slow') !== false) {
            sleep(rand(5, 15));
        }
        
        $file = new SplFileInfo($this->getTmFile($this->tmmt->getId()));
        if(!$file->isFile() || !$file->isReadable()) {
            throw new ZfExtended_NotFoundException('requested TM file for dummy TM with the tmmtId '.$this->tmmt->getId().' not found!');
        }
        $file = $file->openFile();

        $result = array();
        $i = 0;
        while($line = $file->fgetcsv(",", '"', '"')) {
            if($i++ == 0 || empty($line) || empty($line[0]) || empty($line[1])){
                continue;
            }

            //simulate match query
            if(empty($field)) {
                $this->makeMatch($queryString, $line[1], $line[2]);
                continue;
            }
            
            $this->makeSearch($queryString, $line[1], $line[2], $field == 'source');
        }
        
        if($this->searchCount > 0) {
            //to simulate the OpenTM2 paging behaviour we don't deliver the real total to the GUI
            // but offset + limit + 1 if there are more available results.
            // the last page contains then the real total to end paging in the GUI
            //
            //relevant algorithms are:
            // - get data from storage with limit + 1 to see if there are more results
            //   → but send only limit * results to the GUI not limit + 1
            // - if count(results) <= limit, that means we are on the last page.
            // - for total count we use just offset + count(results) and thats it 
            $this->resultList->setTotal(min($this->searchCount, $this->limit + $this->offset + 1));
        }

        return $this->resultList;
    }
    
    /**
     * performs a MT match
     * @param string $queryString
     * @param string $source
     * @param string $target
     */
    protected function makeMatch($queryString, $source, $target) {
        $queryString = strip_tags($queryString);
        $source = strip_tags($source);
        $target = strip_tags($target);
        
        similar_text($queryString, $source, $percent);
        if($percent < 80) {//FIXME why we need this cheking here ?
            return;
        }
        $this->resultList->addResult($target, $percent);
        $this->resultList->setSource($source);
        $this->resultList->setAttributes('Attributes: can be empty when service does not provide attributes. If not empty, then already preformatted for tooltipping!');
    }
    
    /**
     * performs a MT search with paging
     * @param string $queryString
     * @param string $source
     * @param string $target
     * @param boolean $isSource
     * @param integer $idx
     */
    protected function makeSearch($queryString, $source, $target, $isSource) {
        $isSearchHit = stripos($isSource ? $source : $target, $queryString) !== false;
        
        if(! $isSearchHit) {
            return;
        }
        
        if($this->searchCount >= $this->offset && $this->searchCount < ($this->offset + $this->limit)) {
            $this->resultList->addResult(strip_tags($target));
            $this->resultList->setSource(strip_tags($source));
        }
        //inc count over all search results for total count
        $this->searchCount++;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::delete()
     */
    public function delete() {
        $file = new SplFileInfo($this->getTmFile($this->tmmt->getId()));
        if($file->isFile()) {
            unlink($file);
        }
    }
}
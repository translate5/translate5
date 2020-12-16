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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * FIXME reactivate me for pretranslation and analysis tests!
 *
 * The dummy CSV file must be:
 * , separated
 * " as enclosure
 * "" as escape
 * This should be the CSV defaults.
 * The first column must be an id, the second the source and the theird column the target values. Other columns are ignored.
 */
class editor_Services_DummyFileTm_Connector extends editor_Services_Connector_FilebasedAbstract {

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
        $eventManager->attach('editor_Languageresourceinstance', 'afterPostAction', array($this, 'handleAfterLanguageResourceSaved'));
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo,array $params=null){
        $this->uploadedFile = $fileinfo['tmp_name'];
        //do nothing here, since we need the entity ID to save the TM
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::addAdditionalTm()
     */
    public function addAdditionalTm(array $fileinfo = null,array $params=null){
        
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::getTm()
     */
    public function getTm(& $mime) {
        $file = new SplFileInfo($this->getTmFile($this->languageResource->getId()));
        if(!$file->isFile() || !$file->isReadable()) {
            throw new ZfExtended_NotFoundException('requested TM file for dummy TM with the languageResourceId '.$this->languageResource->getId().' not found!');
        }
        $mime = 'application/csv';
        return file_get_contents($file);
    }

    /**
     * in our dummy file TM the TM can only be saved after the TM is in the DB, since the ID is needed for the filename
     */
    public function handleAfterLanguageResourceSaved() {
        move_uploaded_file($this->uploadedFile, $this->getTmFile($this->languageResource->getId()));
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
     * @see editor_Services_Connector_FilebasedAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryStringAndSetAsDefault($segment);
        return $this->loopData($this->tagHandler->prepareQuery($queryString));
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        $this->searchCount = 0;
        return $this->loopData($searchString, $field);
    }
    
    /**
     * loops through the dummy data and performs a match / search
     *
     * @param string $queryString
     * @param string $field
     * @throws ZfExtended_NotFoundException
     * @return editor_Services_ServiceResult
     */
    protected function loopData(string $queryString, string $field = null) {
        if(stripos($this->languageResource->getName(), 'slow') !== false) {
            sleep(rand(5, 15));
        }
        
        $file = new SplFileInfo($this->getTmFile($this->languageResource->getId()));
        if(!$file->isFile() || !$file->isReadable()) {
            throw new ZfExtended_NotFoundException('requested TM file for dummy TM with the languageResourceId '.$this->languageResource->getId().' not found!');
        }
        $file = $file->openFile();

        $result = array();
        $i = 0;
        while($line = $file->fgetcsv(",", '"', '"')) {
            if($i++ == 0 || empty($line) || empty($line[0]) || empty($line[1]) || empty($line[2])){
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
            $this->resultList->setNextOffset(min($this->searchCount, $this->limit + $this->offset + 1));
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
        $source = $this->tagHandler->restoreInResult($source);
        $target = $this->tagHandler->restoreInResult($target);
        $percent = 0;
        similar_text($queryString, $source, $percent);
        if($percent < 80) {
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
     * @param bool $isSource
     * @param int $idx
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
     * @see editor_Services_Connector_FilebasedAbstract::delete()
     */
    public function delete() {
        $file = new SplFileInfo($this->getTmFile($this->languageResource->getId()));
        if($file->isFile()) {
            unlink($file);
        }
    }
    public function getValidFiletypes()
    {}
    
    public function getValidExportTypes()
    {}

    public function getStatus(editor_Models_LanguageResources_Resource $resource)
    {}

    public function translate(string $searchString){
        return $this->search($searchString);
    }

}
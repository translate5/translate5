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
    
    /**
     * @var editor_Services_DummyFileTm_Db
     */
    protected $db;

    public function __construct() {
        $this->db = new editor_Services_DummyFileTm_Db();
        parent::__construct();
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo = null, array $params=null){
        if(empty($fileinfo)) {
            return true;
        }
        
        $import = ZfExtended_Factory::get('editor_Services_DummyFileTm_ImportTmx');
        /* @var $import editor_Services_DummyFileTm_ImportTmx */
        
        //TODO register another callback for the languages to be set in the language resource, so that the languages are automatically determined by the TMX content
        $import->import(new SplFileInfo($fileinfo['tmp_name']), $this->languageResource, function($oneSegment){
            //TODO prevent duplicates in DB. How???
            $this->db->insert($oneSegment);
        });
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::addAdditionalTm()
     */
    public function addAdditionalTm(array $fileinfo = null,array $params=null){
        //TODO
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::getTm()
     */
    public function getTm($mime) {
        $target = strtolower($this->languageResource->getTargetLangCode());
        $source = strtolower($this->languageResource->getSourceLangCode());
        $result = ['<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE "tmx14.dtd">
<tmx version="1.4">
<header adminlang="'.$target.'" creationid="kk" srclang="'.$source.'"></header><body>'];

        $rowSet = $this->db->fetchAll($this->db->select()->where('languageResourceId = ?', $this->languageResource->getId()));
        foreach($rowSet as $row) {
            //simulate match query
            $result[] = '<tu tuid="'.$row['mid'].'"><tuv xml:lang="'.$source.'"><seg>'.htmlentities($row['source'], ENT_XML1).'</seg></tuv>';
            $result[] = '<tuv xml:lang="'.$target.'"><seg>'.htmlentities($row['target'], ENT_XML1).'</seg></tuv></tu>';
        }
        $result[] = '</body></tmx>';
        return join("\n", $result);
    }

    public function update(editor_Models_Segment $segment) {
        $source = $this->tagHandler->prepareQuery($this->getQueryString($segment));
        $target = $this->tagHandler->prepareQuery($segment->getTargetEdit());

        $s = $this->db->select()->where('source = ?', $source);
        if($this->isInternalFuzzy()) {
            $s->where('internalFuzzy = 1');
        }
        $row = $this->db->fetchRow($s);
        if($row) {
            $row->target = $target;
        }
        else {
            $row = $this->db->createRow([
                'languageResourceId' => $this->languageResource->getId(),
                'mid' => $segment->getMid(),
                'internalFuzzy' => (int) $this->isInternalFuzzy(),
                'source' => $source,
                'target' => $target,
            ]);
        }
        $row->save();
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
        
        $rowSet = $this->db->fetchAll($this->db->select()->where('languageResourceId = ?', $this->languageResource->getId()));
        foreach($rowSet as $row) {
            //simulate match query
            if(empty($field)) {
                $this->makeMatch($queryString, $row['source'], $row['target']);
                continue;
            }
            //TODO just copied from old filebased stuff, search could be refactored to real database based search instead of loop through all data
            $this->makeSearch($queryString, $row['source'], $row['target'], $field == 'source');
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
        $where = [
            'languageResourceId = ?' => $this->languageResource->getId()
        ];
        if($this->isInternalFuzzy()) {
            $where['target like ?'] = 'translate5-unique-id[%';
            $where['internalFuzzy = ?'] = 1;
        }
        $this->db->delete($where);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypes()
     */
    public function getValidFiletypes() {
        return [
            'TMX' => ['text/xml'],
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypeForExport()
     */
    public function getValidExportTypes() {
        return [
            'TMX' => 'text/xml',
        ];
    }

    public function getStatus(editor_Models_LanguageResources_Resource $resource) {
        return editor_Services_Connector_Abstract::STATUS_AVAILABLE;
    }

    public function translate(string $searchString){
        return $this->search($searchString);
    }

    /***
     * Download and save the existing tm with "fuzzy" name. The new fuzzy connector will be returned.
     * @param int $analysisId
     * @throws ZfExtended_NotFoundException
     * @return editor_Services_Connector_Abstract
     */
    public function initForFuzzyAnalysis($analysisId) {
        $this->isInternalFuzzy = true;

        $fuzzyLanguageResource = clone $this->languageResource;
        /* @var $fuzzyLanguageResource editor_Models_LanguageResources_LanguageResource  */

        //visualized name:
        $fuzzyLanguageResourceName = $this->renderFuzzyLanguageResourceName($this->languageResource->getName(), $analysisId);
        $fuzzyLanguageResource->setName($fuzzyLanguageResourceName);

        $connector = ZfExtended_Factory::get(get_class($this));
        /* @var $connector editor_Services_Connector */
        $connector->connectTo($fuzzyLanguageResource,$this->languageResource->getSourceLang(),$this->languageResource->getTargetLang());
        // copy the current config (for task specific config)
        $connector->setConfig($this->getConfig());
        // copy the worker user guid
        $connector->setWorkerUserGuid($this->getWorkerUserGuid());
        $connector->isInternalFuzzy = true;
        return $connector;
    }

}
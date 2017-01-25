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
 * OpenTM2 Connector
 */
class editor_Plugins_MatchResource_Services_OpenTM2_Connector extends editor_Plugins_MatchResource_Services_ConnectorAbstract {

    /**
     * @var editor_Plugins_MatchResource_Services_OpenTM2_HttpApi
     */
    protected $api;
    
    /**
     * Paging information for search requests
     * @var integer
     */
    protected $page;   // XXX
    protected $offset; // XXX
    protected $limit;  // XXX
    
    /**
     * internal variable to count search results
     * @var integer
     */
    protected $searchCount = 0; // XXX

    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::connectTo()
     */
    public function connectTo(editor_Plugins_MatchResource_Models_TmMt $tmmt) {
        parent::connectTo($tmmt);
        $class = 'editor_Plugins_MatchResource_Services_OpenTM2_HttpApi';
        $this->api = ZfExtended_Factory::get($class, [$tmmt]);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::open()
     */
    public function open() {
        $this->api->open();
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::open()
     */
    public function close() {
        $this->api->close();
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::addTm()
     */
    public function addTm(string $filename) {
        //$filename is the real file path of the temp uploaded file on the disk!
        //$this->tmmt->getFileName() is the original filename of the uploaded file
        $this->api->import($filename);
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

    protected function getTmFile($id) {
        return APPLICATION_PATH.'/../data/dummyTm_'.$id;
    }

    public function update(editor_Models_Segment $segment) {
        $this->api->update($segment);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        error_log(__FUNCTION__);return;
        $queryString = $this->getQueryString($segment);
        return $this->loopData($segment->stripTags($queryString));
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::search()
     */
    public function search(string $searchString, $field = 'source') {
        error_log(__FUNCTION__);return;
        $this->searchCount = 0;
        return $this->loopData($searchString, $field);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::setPaging()
     */
    public function XXXsetPaging($page, $offset, $limit = 20) {
        $this->page = (int) $page;
        $this->offset = (int) $offset;
        $this->limit = (int) $limit;
        if(empty($this->limit)) {
            $this->limit = 20;
        }
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::delete()
     */
    public function XXXdelete() {
        $file = new SplFileInfo($this->getTmFile($this->tmmt->getId()));
        if($file->isFile()) {
            unlink($file);
        }
    }
}
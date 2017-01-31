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
        
        //FIXME hier die Fehler abfangen wenn es ein TM mit dem selben Namen bereits gibt!
        
        //FIXME hier den Zeichen Filter von Gerhard einbauen
        $this->tmmt->setFileName($this->tmmt->getName());
        
        //$filename is the real file path of the temp uploaded file on the disk!
        //$this->tmmt->getFileName() is the original filename of the uploaded file
        if($this->api->createMemory($this->tmmt->getName(), $this->tmmt->getSourceLangRfc5646(), file_get_contents($filename))){
            return true;
        }
        $this->handleOpenTm2Error('MatchResource Plugin - could not add TM to OpenTM2'." TMMT: \n");
        return false;
        
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::addTm()
     */
    public function addAdditionalTm(string $filename) {
        //FIXME refactor to streaming (for huge files) if possible by underlying HTTP client
        if($this->api->importMemory(file_get_contents($filename))) {
            return true;
        }
        $this->handleOpenTm2Error('MatchResource Plugin - could not add TMX data to OpenTM2'." TMMT: \n");
        return false;
    }
    
    protected function handleOpenTm2Error($logMsg) {
        $errors = $this->api->getErrors();
        
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        $msg = 'Von OpenTM2 gemeldeter Fehler';
        $messages->addError($msg, 'MatchResource', null, $errors);
        
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $data  = print_r($this->tmmt->getDataObject(),1);
        $data .= " \nError\n".print_r($errors,1);
        $log->logError($logMsg, $data);
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
        
        //FIXME implement segmentsUpdateable check from LEK_matchresource_taskassoc
        // → in Init event handler better?
        
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        if($this->api->update($segment)) {
            $messages->addNotice('Segment im TM aktualisiert!', 'MatchResource');
            return;
        }
        $errors = $this->api->getErrors();
        //$messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */

        $msg = 'Das Segment konnte nicht ins TM gespeichert werden! Bitte kontaktieren Sie Ihren Administrator! <br />Gemeldete Fehler:';
        $messages->addError($msg, 'MatchResource', null, $errors);
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $msg = 'MatchResource Plugin - could not save segment to TM'." TMMT: \n";
        $data  = print_r($this->tmmt->getDataObject(),1);
        $data .= " \nError\n".print_r($errors,1);
        $log->logError($msg, $data);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($segment->getFileId());
        
        $queryString = $segment->stripTags($this->getQueryString($segment));
        
        if($this->api->lookup($segment, $queryString, $file->getFileName())){
            $result = $this->api->getResult();
            if((int)$result->NumOfFoundProposals === 0){
                return $this->resultList; 
            }
            foreach($result->FoundProposals as $found) {
                $meta = new stdClass();
                $this->resultList->addResult($found->Target, $found->Fuzzyness, $this->getMetaData($found));
                $this->resultList->setSource($found->Source);
            }
            
            $this->resultList->setTotal($result->NumOfFoundProposals);
            return $this->resultList; 
        }
        throw new ZfExtended_Exception('Errors in receiving data from OpenTM2: '.print_r($this->api->getErrors(),1));
    }
    
    /**
     * Helper function to get the metadata which should be shown in the GUI out of a single result
     * @param stdClass $found
     * @return stdClass
     */
    protected function getMetaData($found) {
        $nameToShow = [
            "DocumentName",
            "DocumentShortName",
            "Type", 
            "Match",
            "Author",
            "DateTime",
            "Markup",
            "Context",
            "AddInfo",
        ];
        $result = [];
        foreach($nameToShow as $name) {
            if(property_exists($found, $name)) {
                $item = new stdClass();
                $item->name = $name;
                $item->value = $found->{$name};
                $result[] = $item;
            }
        }
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::search()
     */
    public function search(string $searchString, $field = 'source') {
        $field = ucfirst($field);
        //FIXME for paging see Dummy TM connector, there is a hint about OpenTM2
        
        if($this->api->search($searchString, ucfirst($field))){
            $result = $this->api->getResult();
            $found = $result->FoundProposal;
            
            if(empty($found->{$field})){
                return $this->resultList; 
            }
            //[NextSearchPosition] => 
    
            $this->resultList->addResult($found->Target);
            $this->resultList->setSource($found->Source);
            
            //FIXME Total Calculation and paging logic differs completly from that what we need!
            $this->resultList->setTotal(1);
            return $this->resultList; 
        }
        throw new ZfExtended_Exception('Errors in receiving data from OpenTM2: '.print_r($this->api->getErrors(),1));
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
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_ConnectorAbstract::delete()
     */
    public function delete() {
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        $messages->addNotice('Implement remote DELETE call!', 'MatchResource');
        //FIXME
        return true;
    }
}
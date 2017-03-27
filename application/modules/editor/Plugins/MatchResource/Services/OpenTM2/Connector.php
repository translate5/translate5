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
class editor_Plugins_MatchResource_Services_OpenTM2_Connector extends editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract {

    /**
     * @var editor_Plugins_MatchResource_Services_OpenTM2_HttpApi
     */
    protected $api;
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::connectTo()
     */
    public function connectTo(editor_Plugins_MatchResource_Models_TmMt $tmmt) {
        parent::connectTo($tmmt);
        $class = 'editor_Plugins_MatchResource_Services_OpenTM2_HttpApi';
        $this->api = ZfExtended_Factory::get($class, [$tmmt]);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::open()
     */
    public function open() {
        //This call is not necessary, since TMs are opened automatically.
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::open()
     */
    public function close() {
    /*
     * This call deactivated, since openTM2 has a access time based garbage collection
     * If we close a TM and another Task still uses this TM this bad for performance,
     *  since the next request to the TM has to reopen it
     */
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo = null) {
        $sourceLang = $this->tmmt->getSourceLangRfc5646(); 
        
        //to ensure that we get unique TMs Names although of the above stripped content, 
        // we add the TMMT ID and a prefix which can be configured per each translate5 instance 
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $prefix = $config->runtimeOptions->plugins->MatchResource->opentm2->tmprefix;
        if(!empty($prefix)) {
            $prefix .= '-';
        }
        $name = $prefix.'ID'.$this->tmmt->getId().'-'.$this->filterName($this->tmmt->getName());
        $this->tmmt->setFileName($name);
        
        $noFile = empty($fileinfo);
        $tmxUpload = !$noFile && $fileinfo['type'] == 'application/xml' && preg_match('/\.tmx$/', $fileinfo['name']);
        
        if($noFile || $tmxUpload) {
            if($this->api->createEmptyMemory($name, $sourceLang)){
                $this->tmmt->setFileName($this->api->getResult()->name);
                //if initial upload is a TMX file, we have to import it. 
                if($tmxUpload) {
                    return $this->addAdditionalTm($fileinfo);
                }
                return true;
            }
            $this->handleOpenTm2Error('MatchResource Plugin - could not create TM in OpenTM2'." TMMT: \n");
            return false;
        }
        
        //initial upload is a TM file
        if($this->api->createMemory($name, $sourceLang, file_get_contents($fileinfo['tmp_name']))){
            $this->tmmt->setFileName($this->api->getResult()->name);
            return true;
        }
        $this->handleOpenTm2Error('MatchResource Plugin - could not create prefilled TM in OpenTM2'." TMMT: \n");
        return false;
        
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_Connector_Abstract::addAdditionalTm()
     */
    public function addAdditionalTm(array $fileinfo = null) {
        //FIXME refactor to streaming (for huge files) if possible by underlying HTTP client
        if($this->api->importMemory(file_get_contents($fileinfo['tmp_name']))) {
            return true;
        }
        $this->handleOpenTm2Error('MatchResource Plugin - could not add TMX data to OpenTM2'." TMMT: \n");
        return false;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::getValidFiletypes()
     */
    public function getValidFiletypes() {
        return [
            'TM' => 'application/zip',
            'TMX' => 'application/xml',
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::getTm()
     */
    public function getTm($mime) {
        if($this->api->get($mime)) {
            return $this->api->getResult();
        }
        $this->throwBadGateway();
    }

    public function update(editor_Models_Segment $segment) {
        $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $internalTag editor_Models_Segment_InternalTag */
        
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($segment->getFileId());
        
        $source = $internalTag->toXliff($this->getQueryString($segment));
        $target = $internalTag->toXliff($segment->getTargetEdit());
        
        if($this->api->update($source, $target, $segment, $file->getFileName())) {
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
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($segment->getFileId());
        
        //Although we take the source fields from the OpenTM2 answer below
        // we have to set the default source here to fill the be added internal tags 
        $this->resultList->setDefaultSource($this->getQueryString($segment));
        
        $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $internalTag editor_Models_Segment_InternalTag */
        
        //$map is returned by reference
        $queryString = $internalTag->toXliff($this->getQueryString($segment), true, $map);
        
        if($this->api->lookup($segment, $queryString, $file->getFileName())){
            $result = $this->api->getResult();
            if((int)$result->NumOfFoundProposals === 0){
                return $this->resultList; 
            }
            foreach($result->results as $found) {
                $meta = new stdClass();
                $target = $internalTag->reapply2dMap($found->target, $map);
                $this->resultList->addResult($target, $found->matchRate, $this->getMetaData($found));
                $source = $internalTag->reapply2dMap($found->source, $map);
                $this->resultList->setSource($source);
            }
            
            return $this->resultList; 
        }
        $this->throwBadGateway();
    }
    
    /**
     * Helper function to get the metadata which should be shown in the GUI out of a single result
     * @param stdClass $found
     * @return stdClass
     */
    protected function getMetaData($found) {
        $nameToShow = [
            "documentName",
            "documentShortName",
            "type", 
            "matchType",
            "author",
            "timestamp",
            "markupTable",
            "context",
            "additionalInfo",
        ];
        $result = [];
        foreach($nameToShow as $name) {
            if(property_exists($found, $name)) {
                $item = new stdClass();
                $item->name = $name;
                $item->value = $found->{$name};
                if($name == 'timestamp') {
                    $item->value = date('Y-m-d H:i:s T', strtotime($item->value));
                }
                $result[] = $item;
            }
        }
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        if($this->api->search($searchString, $field, $offset)){
            $result = $this->api->getResult();
            
            if(empty($result) || empty($result->results)){
                $this->resultList->setNextOffset(null);
                return $this->resultList; 
            }
            $this->resultList->setNextOffset($result->NewSearchPosition);
            $results = $result->results;
            
            $highlight = function($haystack, $doit) use ($searchString) {
                if(!$doit){
                    return $haystack;
                }
                return preg_replace('/('.preg_quote($searchString, '/').')/i', '<span class="highlight">\1</span>', $haystack);
            };
            
            //$found->{$field}
            //[NextSearchPosition] =>
            foreach($results as $result) {
                $this->resultList->addResult($highlight(strip_tags($result->target), $field === 'target'));
                $this->resultList->setSource($highlight(strip_tags($result->source), $field === 'source'));
            }
            
            return $this->resultList; 
        }
        $this->throwBadGateway();
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract::delete()
     */
    public function delete() {
        if(!$this->api->delete()) {
            $this->throwBadGateway();
        }
    }
    
    /**
     * Throws a ZfExtended_BadGateway exception containing the underlying errors
     * @throws ZfExtended_BadGateway
     */
    protected function throwBadGateway() {
        $e = new ZfExtended_BadGateway('Die angefragte OpenTM2 Instanz meldete folgenden Fehler:');
        $e->setOrigin('MatchResource OpenTM2');
        $e->setErrors($this->api->getErrors());
        throw $e;
    }
    
    /**
     * In difference to $this->throwBadGateway this method generates an 400 error 
     *   which shows additional error information in the frontend
     *   
     * @param string $logMsg
     */
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
     * Replaces not allowed characters with "_" in memory names
     * @param string $name
     * @return string
     */
    protected function filterName($name){
        //since we are getting Problems on the OpenTM2 side with non ascii characters in the filenames,
        // we strip them all. See also OPENTM2-13.
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        return preg_replace('/[^a-zA-Z0-9 _-]/', '_', $name);
        //original not allowed string list: 
        //return str_replace("\\/:?*|<>", '_', $name);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        $name = $this->tmmt->getFileName();
        if(empty($name)) {
            $moreInfo = 'The internal stored filename is invalid';
            return self::STATUS_NOCONNECTION;
        }
        
        try {
            $apiResult = $this->api->status();
        }catch (ZfExtended_BadGateway $e){
            $moreInfo = $e->getMessage();
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError($moreInfo, $this->tmmt->getResource()->getUrl());
            return self::STATUS_NOCONNECTION;
        }
        
        if($apiResult) {
            $status = $this->api->getResult()->tmxImportStatus;
            switch($status) {
                case 'available':
                    return self::STATUS_AVAILABLE;
                case 'import':
                    $moreInfo = 'TMX wird importiert, TM kann trotzdem benutzt werden';
                    return self::STATUS_IMPORT;
                case 'error':
                case 'failed':
                    $moreInfo = $this->api->getResult()->ErrorMsg;
                    return self::STATUS_ERROR;
            }
            $moreInfo = 'original OpenTM2 status '.$status;
            return self::STATUS_UNKNOWN;
        }
        
        //a 404 response from the status call means: 
        // - OpenTM2 is online
        // - the requested TM is currently not loaded, so there is no info about the existence
        // - So we display the STATUS_NOT_LOADED instead
        if($this->api->getResponse()->getStatus() == 404) {
            $moreInfo = 'Die Ressource ist generell verfügbar, stellt aber keine Informationen über das angefragte TM bereit, da dies nicht geladen ist.';
            return self::STATUS_NOT_LOADED;
        }
        
        $moreInfo = join("<br/>\n", array_map(function($item) {
            return $item->type.': '.$item->error;
        }, $this->api->getErrors()));
            
        return self::STATUS_NOCONNECTION;
    }
}
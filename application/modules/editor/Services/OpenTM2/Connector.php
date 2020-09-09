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
 * OpenTM2 Connector
 */
class editor_Services_OpenTM2_Connector extends editor_Services_Connector_FilebasedAbstract {

    /**
     * @var editor_Services_OpenTM2_HttpApi
     */
    protected $api;
    
    /***
     * Filename by file id cache
     * @var array
     */
    public $fileNameCache=array();
    
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser;
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::connectTo()
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource, $sourceLang, $targetLang) {
        parent::connectTo($languageResource, $sourceLang, $targetLang);
        $class = 'editor_Services_OpenTM2_HttpApi';
        $this->api = ZfExtended_Factory::get($class, [$languageResource]);
        $this->xmlparser= ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $parser editor_Models_Import_FileParser_XmlParser */
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::open()
     */
    public function open() {
        //This call is not necessary, since this resource is opened automatically.
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::close()
     */
    public function close() {
        //This call is not necessary, since this resource is closed automatically.
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo = null,array $params=null) {
        $sourceLang = $this->languageResource->getSourceLangRfc5646(); 

        //to ensure that we get unique TMs Names although of the above stripped content, 
        // we add the LanguageResource ID and a prefix which can be configured per each translate5 instance 
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $prefix = $config->runtimeOptions->LanguageResources->opentm2->tmprefix;
        if(!empty($prefix)) {
            $prefix .= '-';
        }
        $name = $prefix.'ID'.$this->languageResource->getId().'-'.$this->filterName($this->languageResource->getName());
        
        $this->languageResource->addSpecificData('fileName',$name);
        $this->languageResource->save();
        
        // If we are adding a TMX file as LanguageResource, we must create an empty memory first.
        $validFileTypes = $this->getValidFiletypes();
        if(empty($validFileTypes['TMX'])){
            throw new ZfExtended_NotFoundException('OpenTM2: Cannot addTm for TMX-file; valid file types are missing.');
        }
        $noFile = empty($fileinfo);
        $tmxUpload = !$noFile && in_array($fileinfo['type'], $validFileTypes['TMX']) && preg_match('/\.tmx$/', $fileinfo['name']);
        if($noFile || $tmxUpload) {
            if($this->api->createEmptyMemory($name, $sourceLang)){
                $this->languageResource->addSpecificData('fileName',$this->api->getResult()->name);
                $this->languageResource->save(); //saving it here makes the TM available even when the TMX import was crashed
                //if initial upload is a TMX file, we have to import it. 
                if($tmxUpload) {
                    return $this->addAdditionalTm($fileinfo);
                }
                return true;
            }
            $this->handleOpenTm2Error('LanguageResources - could not create TM in OpenTM2'." LanguageResource: \n");
            return false;
        }
        
        //initial upload is a TM file
        if($this->api->createMemory($name, $sourceLang, file_get_contents($fileinfo['tmp_name']))){
            $this->languageResource->addSpecificData('fileName',$this->api->getResult()->name);
            return true;
        }
        $this->handleOpenTm2Error('LanguageResources - could not create prefilled TM in OpenTM2'." LanguageResource: \n");
        return false;
        
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::addAdditionalTm()
     */
    public function addAdditionalTm(array $fileinfo = null,array $params=null){
        //FIXME refactor to streaming (for huge files) if possible by underlying HTTP client
        if($this->api->importMemory(file_get_contents($fileinfo['tmp_name']))) {
            return true;
        }
        $this->handleOpenTm2Error('LanguageResources - could not add TMX data to OpenTM2'." LanguageResource: \n");
        return false;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypes()
     */
    public function getValidFiletypes() {
        return [
            'TM' => ['application/zip'],
            'TMX' => ['application/xml','text/xml'],
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypeForExport()
     */
    public function getValidExportTypes() {
        return [
            'TM' => 'application/zip',
            'TMX' => 'application/xml',
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::getTm()
     */
    public function getTm($mime) {
        if($this->api->get($mime)) {
            return $this->api->getResult();
        }
        $this->throwBadGateway();
    }

    public function update(editor_Models_Segment $segment) {
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($segment->getFileId());
        
        $source= $this->prepareSegmentContent($this->getQueryString($segment));
        $target= $this->prepareSegmentContent($segment->getTargetEdit());
        if($this->api->update($source, $target, $segment, $file->getFileName())) {
            return;
        }
        
        $errors = $this->api->getErrors();
        //$messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */

        $msg = 'Das Segment konnte nicht ins TM gespeichert werden! Bitte kontaktieren Sie Ihren Administrator! <br />Gemeldete Fehler:';
        $messages->addError($msg, 'core', null, $errors);
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $msg = 'LanguageResources - could not save segment to TM'." LanguageResource: \n";
        $data  = print_r($this->languageResource->getDataObject(),1);
        $data .= " \nSegment\n".print_r($segment->getDataObject(),1);
        $data .= " \nError\n".print_r($errors,1);
        $log->logError($msg, $data);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        
        if(!isset($this->fileNameCache[$segment->getFileId()])){
            $file = ZfExtended_Factory::get('editor_Models_File');
            /* @var $file editor_Models_File */
            $file->load($segment->getFileId());
            $this->fileNameCache[$segment->getFileId()]=$file->getFileName();
            
        }
        
        $fileName=$this->fileNameCache[$segment->getFileId()];
        
        $queryString = $this->getQueryString($segment);
        
        //if source is empty, OpenTM2 will return an error, therefore we just return an empty list
        if(empty($queryString) && $queryString !== "0") {
            return $this->resultList;
        }
        
        //Although we take the source fields from the OpenTM2 answer below
        // we have to set the default source here to fill the be added internal tags 
        $this->resultList->setDefaultSource($queryString);
        
        $queryString = $this->restoreWhitespaceForQuery($queryString);
        
        //$map is set by reference
        $map = [];
        $queryString = $this->internalTag->toXliffPaired($queryString, true, $map);
        $mapCount = count($map);
        
        //we have to use the XML parser to restore whitespace, otherwise protectWhitespace would destroy the tags
        $xmlParser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlParser editor_Models_Import_FileParser_XmlParser */
        
        $this->shortTagIdent = $mapCount + 1;
        $xmlParser->registerOther(function($textNode, $key) use ($xmlParser){
            //for communication with OpenTM2 we assume that the segment content is XML/XLIFF therefore we assume xmlBased here 
            $textNode = $this->whitespaceHelper->protectWhitespace($textNode, true); 
            $textNode = $this->whitespaceTagReplacer($textNode);
            $xmlParser->replaceChunk($key, $textNode);
        });
        
        if($this->api->lookup($segment,$queryString, $fileName)){
            $result = $this->api->getResult();
            if((int)$result->NumOfFoundProposals === 0){
                return $this->resultList; 
            }
            foreach($result->results as $found) {
                
                $this->validateInternalTags($found, $segment);
                
                try {
                    //since protectWhitespace should run on plain text nodes we have to call it before the internal tags are reapplied,
                    // since then the text contains xliff tags and the xliff tags should not contain affected whitespace
                    $target = $xmlParser->parse($found->target);
                    $target = $this->internalTag->reapply2dMap($target, $map);
                    $target = $this->replaceAdditionalTags($target, $mapCount);
                    $calcMatchRate=$this->calculateMatchRate($found->matchRate, $this->getMetaData($found),$segment, $fileName);
                    $this->resultList->addResult($target, $calcMatchRate, $this->getMetaData($found));
                    
                    //about whitespace see target
                    $source = $xmlParser->parse($found->source);
                    $source = $this->internalTag->reapply2dMap($source, $map);
                    $source = $this->replaceAdditionalTags($source, $mapCount);
                    $this->resultList->setSource($source);
                } catch (editor_Models_Import_FileParser_InvalidXMLException $e) {
                    //the source has invalid xml -> remove all tags from the result, and reduce the matchrate by 2%
                    $matchrate=$this->reduceMatchrate($found->matchRate,2);
                    $found->target=strip_tags($found->target);
                    $this->resultList->addResult($found->target, $matchrate, $this->getMetaData($found));
                }

                try {
                    //about whitespace see target
                    $source = $xmlParser->parse($found->source);
                    $source = $this->internalTag->reapply2dMap($source, $map);
                    $source = $this->replaceAdditionalTags($source, $mapCount);
                    $this->resultList->setSource($source);
                    
                } catch (editor_Models_Import_FileParser_InvalidXMLException $e) {
                    
                    //the source has invalid xml -> remove all tags
                    $this->resultList->setSource(strip_tags($found->source));
                }

            }
            return $this->getResultListGrouped();
        }
        $this->throwBadGateway();
    }
    
    /**
     * replace additional tags from the TM to internal tags which are ignored in the frontend then
     * @param string $segment
     * @param int $mapCount used as start number for the short tag numbering
     * @return string
     */
    protected function replaceAdditionalTags($segment, $mapCount) {
        $shortTagNr = $mapCount;
        return preg_replace_callback('#<(x|ex|bx|g|/g)[^>]*>#', function() use (&$shortTagNr) {
            return $this->internalTag->makeAdditionalHtmlTag($shortTagNr++);
        }, $segment);
    }

    /**
     * Checks OpenTM2 result on valid segments: <it> ,<ph>,<bpt> and <ept> are invalid since they can not handled by the replaceAdditionalTags method
     * @param string $segmentContent
     */
    protected function validateInternalTags($result, editor_Models_Segment $seg) {
        //just concat source and target to check both:
        if(preg_match('#<(it|ph|ept|bpt)[^>]*>#', $result->source.$result->target)) {
            $this->xmlparser->registerElement('opentm2result > it,opentm2result > ph,opentm2result > ept,opentm2result > bpt',null, function($tag, $key, $opener){
                $this->xmlparser->replaceChunk($opener['openerKey'],'',$opener['isSingle'] ? 1 : $key-$opener['openerKey']);
            });
            $result->source=$this->replaceInvalidTags($result->source);
            $result->target=$this->replaceInvalidTags($result->target);
            //the invalid tags are removed, reduce the matchrate by 2 percent
            $result->matchRate=$this->reduceMatchrate($result->matchRate,2);
        }
    }
    
    /***
     * Replace the invalid tags with empty content
     * 
     * @param string $content
     * @return string
     */
    protected function replaceInvalidTags($content){
        //surround the content with tmp tags(used later as selectors)
        $content='<opentm2result>'.$content.'</opentm2result>';
        
        //parse the content
        $content=$this->xmlparser->parse($content);
        
        //remove the helper tags
        $content=strtr($content,array(
            '<opentm2result>'=>'',
            '</opentm2result>'=>''
        ));
        return $content;
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
     * @see editor_Services_Connector_FilebasedAbstract::search()
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
            
            //$found->{$field}
            //[NextSearchPosition] =>
            foreach($results as $result) {
                $this->resultList->addResult($this->highlight($searchString, strip_tags($result->target), $field == 'target'));
                $this->resultList->setSource($this->highlight($searchString, strip_tags($result->source), $field == 'source'));
            }
            
            return $this->resultList; 
        }
        $this->throwBadGateway();
    }
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        //return empty result when no search string
        if(empty($searchString) && $searchString !== "0") {
            return $this->resultList;
        }
        
        $this->resultList->setDefaultSource($searchString);
        
        //$map is returned by reference
        $searchString = $this->internalTag->toXliffPaired($searchString, true, $map);
        $mapCount = count($map);
        
        //create dummy segment so we can use the lookup
        $dummySegment=ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $dummySegment editor_Models_Segment */
        $dummySegment->init();
        
        if($this->api->lookup($dummySegment,$searchString, 'source')){
            $result = $this->api->getResult();
            if((int)$result->NumOfFoundProposals === 0){
                return $this->resultList;
            }
            foreach($result->results as $found) {
                
                $this->validateInternalTags($found, $dummySegment);
                
                $target = $this->internalTag->reapply2dMap($found->target, $map);
                $target = $this->replaceAdditionalTags($target, $mapCount);
                
                $calcMatchRate=$this->calculateMatchRate($found->matchRate, $this->getMetaData($found),$dummySegment,'InstantTranslate');
                
                $this->resultList->addResult($target, $calcMatchRate, $this->getMetaData($found));
                
                $source = $this->internalTag->reapply2dMap($found->source, $map);
                $source = $this->replaceAdditionalTags($source, $mapCount);
                $this->resultList->setSource($source);
            }
            return $this->resultList;
        }
        $this->throwBadGateway();
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::delete()
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
        $e->setDomain('LanguageResources');
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
        $messages->addError($msg, 'core', null, $errors);
        
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $data  = print_r($this->languageResource->getDataObject(),1);
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
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        if(empty($this->languageResource)){
            //TODO use the resource. Ping action!
        }
        $name = $this->languageResource->getSpecificData('fileName');
        
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
            $log->logError($moreInfo, $this->languageResource->getResource()->getUrl());
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
        
        //lets check the internal state before we just print the 404 default:
        $status = $this->languageResource->getSpecificData('status') ?? '';
        if($status == self::STATUS_IMPORT) {
            $moreInfo = 'TM wird noch importiert und ist daher auch noch nicht nutzbar.';
            //FIXME thats not 100% correct here, since when it was crashed while the import it may stay on status import
            return self::STATUS_IMPORT; 
        }
        
        //Warning: this evaluates to "available" in the GUI, see the following explanation:
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
    
    /***
     * Calculate the new matchrate value.
     * Check if the current match is of type context-match or exact-exact match
     * 
     * @param int $matchRate
     * @param array $metaData
     * @param editor_Models_Segment $segment
     * @param string $filename
     * 
     * @return integer
     */
    protected function calculateMatchRate($matchRate,$metaData,$segment,$filename){
        
        if($matchRate<100){
            return $matchRate;
        }
        
        $isExacExac=false;
        $isContext=false;
        foreach ($metaData as $data){
            
            //exact-exact match
            if($data->name=="documentName" && $data->value==$filename){
                $isExacExac=true;
            }
            
            //context metch
            if($data->name=="context" && $data->value==$segment->getMid()){
                $isContext=true;
            }
        }
        
        if($isExacExac && $isContext){
            return self::CONTEXT_MATCH_VALUE;
        }
        
        if($isExacExac){
            return self::EXACT_EXACT_MATCH_VALUE;
        }
        
        return $matchRate;
    }
    
    /***
     * Download and save the existing tm with "fuzzy" name. The new fuzzy connector will be returned.
     * @param int $analysisId
     * @throws ZfExtended_NotFoundException
     * @return editor_Services_Connector_Abstract
     */
    public function initForFuzzyAnalysis($analysisId) {
        $mime="TM";
        $this->isInternalFuzzy = true;
        $validExportTypes = $this->getValidExportTypes();
        
        if(empty($validExportTypes[$mime])){
            throw new ZfExtended_NotFoundException('Can not download in format '.$mime);
        }
        $data = $this->getTm($validExportTypes[$mime]);
        
        $fuzzyFileName = $this->renderFuzzyLanguageResourceName($this->languageResource->getSpecificData('fileName'), $analysisId);
        $this->api->createMemory($fuzzyFileName, $this->languageResource->getSourceLangRfc5646(), $data);
        
        $fuzzyLanguageResource = clone $this->languageResource;
        /* @var $fuzzyLanguageResource editor_Models_LanguageResources_LanguageResource  */
        
        //visualized name:
        $fuzzyLanguageResourceName = $this->renderFuzzyLanguageResourceName($this->languageResource->getName(), $analysisId);
        $fuzzyLanguageResource->setName($fuzzyLanguageResourceName);
        $fuzzyLanguageResource->addSpecificData('fileName', $fuzzyFileName);
        $fuzzyLanguageResource->setId(null);
        
        $connector = ZfExtended_Factory::get(get_class($this));
        /* @var $connector editor_Services_Connector */
        $connector->connectTo($fuzzyLanguageResource,$this->languageResource->getSourceLang(),$this->languageResource->getTargetLang());
        $connector->isInternalFuzzy = true;
        return $connector;
    }
    
    /***
     * Get the result list where the >=100 matches with the same target are grouped as 1 match.
     * @return editor_Services_ServiceResult|number
     */
    public function getResultListGrouped() {
        $allResults=$this->resultList->getResult();
        if(empty($allResults)){
            return $this->resultList;
        }
        
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $showMultiple100PercentMatches = $config->runtimeOptions->LanguageResources->opentm2->showMultiple100PercentMatches;
        
        $other=array();
        $differentTargetResult=array();
        $document=array();
        $target=null;
        $resultlist=$this->resultList;
        //filter and collect the results
        //all 100>= matches with same target will be collected
        //all <100 mathes will be collected
        //all documentName and documentShortName will be collected from matches >=100
        $filterArray = array_filter($allResults, function ($var) use(&$other,&$document,&$target,&$differentTargetResult,$resultlist,$showMultiple100PercentMatches) {
            //collect lower then 100 matches to separate array
            if($var->matchrate<100){
                $other[]=$var;
                return false;
            }
            //set the compare target
            if(!isset($target)){
                $target=$var->target;
            }
            
            //is with same target or show multiple id disabled collect >=100 match for later sorting
            if($var->target==$target || !$showMultiple100PercentMatches){
                $document[]=array(
                    'documentName'=>$resultlist->getMetaValue($var->metaData, 'documentName'),
                    'documentShortName'=>$resultlist->getMetaValue($var->metaData, 'documentShortName'),
                );
                return true;
            }
            //collect different target result 
            $differentTargetResult[]=$var;
            return false;
        });
        
        //sort by highes matchrate from the >=100 match results, when same matchrate sort by timestamp
        usort($filterArray,function($item1,$item2) use($resultlist){
            if ($item1->matchrate == $item2->matchrate){
                return date($resultlist->getMetaValue($item1->metaData, 'timestamp'))<date($resultlist->getMetaValue($item2->metaData, 'timestamp')) ? 1 : -1;
            }
            return ($item1->matchrate < $item2->matchrate) ? 1 : -1;
        });
        
        if(!empty($filterArray)){
            //get the highest >=100 match, and apply the documentName and documentShrotName from all >=100 matches
            $filterArray=$filterArray[0];
            foreach ($filterArray->metaData as $md) {
                if($md->name=='documentName'){
                    $md->value=implode(';',array_column($document, 'documentName'));
                }
                if($md->name=='documentShortName'){
                    $md->value=implode(';',array_column($document, 'documentShortName'));
                }
            }
        }

        //if it is single result, init it as array
        if(!is_array($filterArray)){
            $filterArray=[$filterArray];
        }
        
        //merge all available results
        $result=array_merge($filterArray,$differentTargetResult);
        $result=array_merge($result,$other);
        
        $this->resultList->resetResult();
        $this->resultList->setResults($result);
        return $this->resultList;
    }
    
    /***
     * Reduce the given matchrate to given percent.
     * It is used when unsupported tags are found in the response result, and those tags are removed.
     * @param integer $matchrate
     * @param integer $reducePercent
     * @return number
     */
    protected function reduceMatchrate($matchrate,$reducePercent) {
        //reset higher matches than 100% to 100% match
        if($matchrate>100){
            $matchrate=100;
        }
        //if the matchrate is higher than 0, reduce it by $reducePercent %
        if($matchrate>0){
            $matchrate=$matchrate - ($matchrate*($reducePercent/100));
            $matchrate=round($matchrate);
        }
        
        return $matchrate;
    }
}

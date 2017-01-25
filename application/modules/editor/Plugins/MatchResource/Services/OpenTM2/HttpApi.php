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
 * OpenTM2 HTTP Connection API
 */
class editor_Plugins_MatchResource_Services_OpenTM2_HttpApi {
    /**
     * @var editor_Plugins_MatchResource_Models_TmMt
     */
    protected $tmmt;
    
    public function __construct(editor_Plugins_MatchResource_Models_TmMt $tmmt) {
        $this->tmmt = $tmmt;
    }
    
    /**
     * This method imports a memory using the internal memory files provided in a ZIP package.
     */
    public function importFromPackage() {
        //In:{ "Method":"importFromPackage", "Memory":"MyTestMemory", "Files":"C:/FileArea/MyTestMemory.ZIP" }  
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
    }

    /**
     * This method deletes a memory.
     */
    public function delete() {
        //In:{ "Method":"delete", "Memory":"MyTestMemory" }  
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
    }
    
    /**
     * This method creates a new memory.
     */
    public function create() {
        //In:{ "Method":"create", "Memory":"MyTestMemory", "SourceLanguage":"en-US" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
    }
    
    /**
     * FIXME is a create statement needed before?
     * This method imports a memory from a TMX file.
     */
    public function import($tmxFile) {
        //In:{ "Method":"import", "Memory":"MyTestMemory", "TMXFile":"C:/FileArea/MyTstMemory.TMX" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
        
        error_log(__METHOD__."Do nothing at the moment!");
        return;
        $json = $this->json(__FUNCTION__);
        $json->TMXFile = 'foobar';
    }

    /**
     * This method opens a memory.
     * Note: This method is not required as memories are automatically opened when they are accessed for the first time.
     * @param string $memory
     */
    public function open() {
        //In:{ "Method":"open", "Memory":"MyTestMemory" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"" }
        $this->request($this->json(__FUNCTION__));
    }
    
    /**
     * This method closes a memory.
     * Note: This method is not required as memories are automatically closed when the service ends or the memory is being deleted.
     */
    public function close() {
        //In:{ "Method":"close", "Memory":"MyTestMemory" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
        $this->request($this->json(__FUNCTION__));
    }

    /**
     * This method does a memory lookup based on the provided search criteria
     * Note: This method returns a list of found memory proposals.
     */
    public function lookup() {
        //FIXME wenn es sich bestätigt dass der Lookup ohne Memory auskommt, dann aus dem JSON entfernen, da memory per default mit bei
        //In:{ "Method":"lookup", "SearchCriteria":{ "Source": "This is the source text", "Segment": "231", "DocumentName":"Anothertest.txt", "SourceLanguage":"en-US", "TargetLanguage":"de-de", "Markup":"EQFHTML3", "Context":"" } } 
        //Out: { "ReturnValue":0, "ErrorMsg":"", "NumOfFoundProposals": 2, "FoundProposals":                  [ { "Source": "This is the source text", "Target": "This is the translated text", "Segment": 231, "ID":"identifier", "DocumentName":"Anothertest.txt",                     "DocumentShortName":"ANOTHERT.001", "SourceLanguage":"en-US", "TargetLanguage":"de-de", "Type":"Manual", "Match":"Exact", "Author":"A.Nonymous",                     "DateTime":"20161013T152948Z", "Fuzziness":100, "Markup":"EQFHTML3", "Context":null, "AddInfo":null }, { "Source": "This is the source text", "Target": "This is another translated text", "Segment": 15, "ID":"identifier", "DocumentName":"OtherDoc.txt", "DocumentShortName":"OTHERD.001", "SourceLanguage":"en-US", "TargetLanguage":"de-de", "Type":"Manual", "Match":"Exact", "Author":"", "DateTime":"20161004T110214Z", "Fuzziness":100, "Markup":"EQFHTML3", "Context":"", "AddInfo":"" } ]  } 
    }

    /**
     * This method searches the given search string in the proposals contained in a memory. The function returns one proposal per request. The caller has to provide the search position returned by a previous call or an empty search position to start the search at the begin of the memory.
     * Note: Provide the returned search position NewSearchPosition as SearchPosition on subsequenet calls to do a sequential search of the memory.
     */
    public function search() {
        //In:{ "Method":"search", "Memory": "TestMemory", "SearchString": "this is the search string", "Search":"Source", "SearchPosition":"" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"", "NewSearchPosition":"254:43", "FoundProposal": { "Source": "This is the source text", "Target": "This is the translated text", "Segment": 231, "ID":"identifier", "DocumentName":"Anothertest.txt", "DocumentShortName":"ANOTHERT.001", "SourceLanguage":"en-US", "TargetLanguage":"de-de", "Type":"Manual", "Match":"Exact", "Author":"A.Nonymous", "DateTime":"20161013T152948Z", "Fuzziness":100, "Markup":"EQFHTML3", "Context":"", "AddInfo":"" }  } 
    }

    /**
     * This method updates (or adds) a memory proposal in the memory.
     * Note: This method updates an existing proposal when a proposal with the same key information (source text, language, segment number, and document name) exists.
     */
    public function update(editor_Models_Segment $segment) {
        /* 
         * In:{ "Method":"update", "Memory": "TestMemory", "Proposal": {
         *  "Source": "This is the source text", 
         *  "Target": "This is the translated text", 
         *  "Segment":231,
         *  "DocumentName":"Anothertest.txt", 
         *  "SourceLanguage":"en-US", 
         *  "TargetLanguage":"de-de", 
         *  "Type":"Manual", 
         *  "Author":"A.Nonymous", 
         *  "DateTime":"20161013T152948Z", 
         *  "Markup":"EQFHTML3", 
         *  "Context":"", 
         *  "AddInfo":"" }  } 
         */
        //Out: { "ReturnValue":0, "ErrorMsg":"" }
        $json = $this->json(__FUNCTION__);
        $json->Source = $segment->stripTags($segment->getSource());
        $json->Target = $segment->stripTags($segment->getTargetEdit());
        
        //$json->Segment = $segment->getSegmentNrInTask(); FIXME zuwas?
        //$json->DocumentName FIXME zuwas?
        $json->Author = $segment->getUserName();
        $json->DateTime = $this->nowDate();
        
        $json->Type = "Manual";
        $json->Markup = "OTMHTM32";
        
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        
        $lang->load($this->tmmt->getSourceLang());
        $json->SourceLanguage = $lang->getRfc5646();
        
        $lang->load($this->tmmt->getTargetLang());
        $json->TargetLanguage = $lang->getRfc5646();
        
        $this->request($json);
    }
    
    /**
     * Creates a stdClass Object which is later converted to JSON for communication
     * @param string $method a method is always needed in the request JSON
     * @param string $memory optional, if given this is added as memory to the JSON
     * @return stdClass;
     */
    protected function json(string $method, $memory = null) {
        $result = new stdClass();
        $result->method = $method;
        if(!empty($memory)) {
            $result->memory = $memory;
        }
        return $result;
    }
    
    /**
     * Prepare and send the request to OpenTM2
     * @param stdClass $json
     */
    protected function request(stdClass $json) {
         $json->Memory = $this->tmmt->getName();
         
         $http = new Zend_Http_Client();
         $http->setUri($this->tmmt->getResource()->getUrl());
         $http->setRawData(json_encode($json), 'application/json');
         $response = $http->request('PUT');
         error_log("sent: ".print_r($json,1));
         error_log("getHeaders: ".print_r($response->getHeaders(),1));
         error_log("getRawBody: ".print_r($response->getRawBody(),1));
         error_log("getMessage: ".print_r($response->getMessage(),1));
         error_log("getBody: ".print_r($response->getBody(),1));
         //$http->setFileUpload($filename, $formname);
    }
    
    /**
     * returns the current time stamp in the expected format for OpenTM2
     */
    protected function nowDate() {
        return gmdate('Ymd\THis\Z');
    }
}
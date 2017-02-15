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
    
    /**
     * @var Zend_Http_Response
     */
    protected $response;
    
    /**
     * @var stdClass
     */
    protected $result;
    
    protected $error = array();
    
    public function __construct(editor_Plugins_MatchResource_Models_TmMt $tmmt) {
        $this->tmmt = $tmmt;
    }
    
    /**
     * This method creates a new memory.
     */
    public function createEmptyMemory($memory, $sourceLanguage) {
        $data = new stdClass();
        $data->name = $memory;
        $data->sourceLang = $sourceLanguage;
        
        $http = $this->getHttp();
        $http->setRawData(json_encode($data), 'application/json');
        $res = $http->request('POST');
        return $this->processResponse($res);
    }
    
    /**
     * This method creates a new memory with TM file
     * FIXME change this method when OpenTM2 can deal with multipart uploads
     */
    public function createMemory($memory, $sourceLanguage, $tmData) {
        $data = new stdClass();
        $data->name = $memory;
        $data->sourceLang = $sourceLanguage;
        
        $http = $this->getHttp();
        $http->setRawData(json_encode($data), 'application/json');
        $res = $http->request('POST');
        return $this->processResponse($res);
    }
    
    /**
     * This method imports a memory from a TMX file.
     */
    public function importMemory($tmData) {
        //In:{ "Method":"import", "Memory":"MyTestMemory", "TMXFile":"C:/FileArea/MyTstMemory.TMX" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
        
        $data = new stdClass();
        $data->tmxData = base64_encode($tmData);

        $http = $this->getHttpWithMemory('/import');
        $http->setConfig(['timeout' => 120]);
        $http->setRawData(json_encode($data), 'application/json');
        
        $res = $http->request('POST');
        return $this->processResponse($res);
    }
    
    
    /**
     * This method deletes a memory.
     * FIXME Currently old style API
     */
    public function delete() {
        return $this->request($this->json(__FUNCTION__));
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs)
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttp($urlSuffix = '') {
        $url = rtrim($this->tmmt->getResource()->getUrl(), '/');
        $urlSuffix = ltrim($urlSuffix, '/');
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $http->setUri($url.'/'.$urlSuffix);
        return $http;
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the Memory Name + additional URL parts
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttpWithMemory($urlSuffix = '') {
        return $this->getHttp(urlencode($this->tmmt->getFileName()).'/'.ltrim($urlSuffix, '/'));
    }
    
    
    //
    // Old style API (still not finished!):
    //
    
    
    /**
     * This method imports a memory using the internal memory files provided in a ZIP package.
     */
    public function importFromPackage() {
        //In:{ "Method":"importFromPackage", "Memory":"MyTestMemory", "Files":"C:/FileArea/MyTestMemory.ZIP" }  
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
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
        
        /*
         * This call deactivated, since openTM2 has a access time based garbage collection
         * If we close a TM and another Task still uses this TM this bad for performance,
         *  since the next request to the TM has to reopen it
         *  
         * FIXME put this information into confluence 
         */
        
        //In:{ "Method":"close", "Memory":"MyTestMemory" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
        //$this->request($this->json(__FUNCTION__));
    }

    /**
     * This method does a memory lookup based on the provided search criteria
     * Note: This method returns a list of found memory proposals.
     * 
     * @param editor_Models_Segment $segment The plain segment for meta data
     * @param string $queryString the querystring, with converted tags for OpenTM2
     * @return boolean
     */
    public function lookup(editor_Models_Segment $segment, string $queryString, string $filename) {
        //In:{ "Method":"lookup", "SearchCriteria":{ "Source": "This is the source text", "Segment": "231", "DocumentName":"Anothertest.txt", "SourceLanguage":"en-US", "TargetLanguage":"de-de", "Markup":"EQFHTML3", "Context":"" } } 
        //Out: { "ReturnValue":0, "ErrorMsg":"", "NumOfFoundProposals": 2, "FoundProposals":                  [ { "Source": "This is the source text", "Target": "This is the translated text", "Segment": 231, "ID":"identifier", "DocumentName":"Anothertest.txt",                     "DocumentShortName":"ANOTHERT.001", "SourceLanguage":"en-US", "TargetLanguage":"de-de", "Type":"Manual", "Match":"Exact", "Author":"A.Nonymous",                     "DateTime":"20161013T152948Z", "Fuzziness":100, "Markup":"EQFHTML3", "Context":null, "AddInfo":null }, { "Source": "This is the source text", "Target": "This is another translated text", "Segment": 15, "ID":"identifier", "DocumentName":"OtherDoc.txt", "DocumentShortName":"OTHERD.001", "SourceLanguage":"en-US", "TargetLanguage":"de-de", "Type":"Manual", "Match":"Exact", "Author":"", "DateTime":"20161004T110214Z", "Fuzziness":100, "Markup":"EQFHTML3", "Context":"", "AddInfo":"" } ]  }
        $json = $this->json(__FUNCTION__);
        
        //FIXME die Erkenntnisse hinter den einzelnen Feldern ins Confluence
        $json->SearchCriteria = [
                "Source" => $queryString,
                "Segment" => '', //FIXME can be used after implementing TRANSLATE-793
                "DocumentName" => $filename, //FIXME für Doku: Pfade möglich mit Backslash, aber bei uns komplette Pfade im FileTree JSON, daher vorerst nur Dateinamen
                "SourceLanguage" => $this->tmmt->getSourceLangRfc5646(),
                "TargetLanguage" => $this->tmmt->getTargetLangRfc5646(),
                //"SourceLanguage" => 'en-UK',
                //"TargetLanguage" => 'de-DE',
                "Markup" => "OTMXUXLF", //
                "Context" => $segment->getMid()// hier MID (Context war gedacht für die Keys (Dialog Nummer) bei übersetzbaren strings in Software)
        ];
        
        //FIXME Das hier ist das Resultat mit offenen Fragen:
        /*
{
	"ReturnValue": 0,
	"ErrorMsg": "",
	"NumOfFoundProposals": 1,
	"FoundProposals": [{
		"Source": "Installation and Configuration",
		"Target": "Installation und Konfiguration",
		"Segment": 0,
		"ID": 0,
		"DocumentName": "",
		"DocumentShortName": "",
		"SourceLanguage": "en-GB",
		"TargetLanguage": "de-CH",
		"Type": "Manual", 
		"Match": "ExactSameDoc", //FIXME Wie gehen wir mit dieser Info um? → attributes → doku!
		"Author": "THOMAS LAURIA", //FIXME we mappen wir dieses und die nachfolgenden Felder auf unser Frontend? → attributes → doku!
		"DateTime": "20170127T150423Z",
		"Fuzzyness": 100,
		"Markup": "OTMHTM32",
		"Context": "",
		"AddInfo": ""
	}]  //FIXME welcher dieser Meta Infos wollen / können wir anzeigen?
}
         */
        
        return $this->request($json);
    }

    /**
     * This method searches the given search string in the proposals contained in a memory. The function returns one proposal per request. The caller has to provide the search position returned by a previous call or an empty search position to start the search at the begin of the memory.
     * Note: Provide the returned search position NewSearchPosition as SearchPosition on subsequenet calls to do a sequential search of the memory.
     */
    public function search($queryString, $field, $searchPosition = '') {
        //In:{ "Method":"search", "Memory": "TestMemory", "SearchString": "this is the search string", "Search":"Source", "SearchPosition":"" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"", "NewSearchPosition":"254:43", "FoundProposal": { "Source": "This is the source text", "Target": "This is the translated text", "Segment": 231, "ID":"identifier", "DocumentName":"Anothertest.txt", "DocumentShortName":"ANOTHERT.001", "SourceLanguage":"en-US", "TargetLanguage":"de-de", "Type":"Manual", "Match":"Exact", "Author":"A.Nonymous", "DateTime":"20161013T152948Z", "Fuzziness":100, "Markup":"EQFHTML3", "Context":"", "AddInfo":"" }  }
        
        //Old OpenTM2 Interface:
        $json = $this->json(__FUNCTION__);
        $json->SearchString = $queryString;
        $json->Search = $field;
        $json->Markup = "OTMXUXLF";
        $json->SearchPosition = $searchPosition;
        return $this->request($json);
        
        //NEW more restlike interface, is already working.
        $data = new stdClass();
        $data->searchString = $queryString;
        $data->searchType = $field;
        $data->searchPosition = null;
        
        $http = $this->getHttpWithMemory('concordancesearch');
        $http->setRawData(json_encode($data), 'application/json');
        $res = $http->request('POST');
        
        //FIXME REST Error Handling!
    }

    /**
     * This method updates (or adds) a memory proposal in the memory.
     * Note: This method updates an existing proposal when a proposal with the same key information (source text, language, segment number, and document name) exists.
     * 
     * @param editor_Models_Segment $segment
     * @return boolean
     */
    public function update(string $source, string $target, editor_Models_Segment $segment, $filename) {
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
        
        $json->Source = $source;
        $json->Target = $target;
        
        //$json->Segment = $segment->getSegmentNrInTask(); FIXME TRANSLATE-793 must be implemented first, since this is not segment in task, but segment in file
        $json->DocumentName = $filename;
        $json->Author = $segment->getUserName();
        $json->DateTime = $this->nowDate();
        
        $json->Type = "Manual";
        $json->Markup = "OTMXUXLF"; //fixed markup table for our XLIFF subset
        
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        
        $lang->load($this->tmmt->getSourceLang());
        $json->SourceLanguage = $lang->getRfc5646();
        
        $lang->load($this->tmmt->getTargetLang());
        $json->TargetLanguage = $lang->getRfc5646();
        
        return $this->request($json);
    }
    
    /**
     * returns the found errors
     */
    public function getErrors() {
        return $this->error;
    }

    /**
     * returns the raw response
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * returns the decoded JSON result
     */
    public function getResult() {
        return $this->result;
    }
    
    /**
     * Creates a stdClass Object which is later converted to JSON for communication
     * @param string $method a method is always needed in the request JSON
     * @param string $memory optional, if given this is added as memory to the JSON
     * @return stdClass;
     */
    protected function json(string $method) {
        $result = new stdClass();
        $result->Method = $method;
        return $result;
    }
    
    /**
     * Prepare and send the request to OpenTM2
     * @param stdClass $json
     * @return boolean true on success, false on failure
     */
    protected function request(stdClass $json) {
        $json->Memory = $this->tmmt->getFileName();
        
        $url = $this->tmmt->getResource()->getUrl();
        
        //create request
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $http->setUri($url);
        $json = json_encode($json);
        $http->setRawData($json, 'application/json');
        $response = $http->request('PUT');
        
        //$http->setFileUpload($filename, $formname);
        return $this->processResponse($response);
    }
    
    /**
     * parses and processes the response of OpenTM2, and handles the errors
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response) {
        $this->response = $response;
        
        //check for HTTP State (REST errors)
        if($response->getStatus() != 200) {
            $error = new stdClass();
            $error->type = 'HTTP';
            $error->error = $response->getStatus();
            $this->error[] = $error;
        }
        $result = json_decode(trim($response->getBody()));
        
        //check for JSON errors
        if(json_last_error() > 0){
            $error = new stdClass();
            $error->type = 'JSON';
            $error->error = json_last_error_msg();
            $this->error[] = $error;
            return false;
        }
        
        $this->result = $result;
        
        //check for error messages from body
        if(!empty($result->ReturnValue) && $result->ReturnValue > 0) {
            $error = new stdClass();
            $error->type = 'Error Nr. '.$result->ReturnValue;
            $error->error = $result->ErrorMsg;
            $this->error[] = $error;
        }
        
        return empty($this->error);
    }
    
    /**
     * Prepare and send the request to OpenTM2
     * @param stdClass $json
     */
    protected function rest($data, $memory = null, $action = 'POST', $URLSuffix = '') {
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
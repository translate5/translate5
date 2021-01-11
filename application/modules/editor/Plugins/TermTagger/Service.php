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

/**
 * Service Class of Plugin "TermTagger"
 */
class editor_Plugins_TermTagger_Service {
    /**
     * The timeout for connections is fix, the request timeout depends on the request type and comes from the config
     * @var integer
     */
    const CONNECT_TIMEOUT = 10;
    
    /**
     * @var ZfExtended_Logger
     */
    protected $log;
    
    /**
     * contains the HTTP status of the last request
     * @var integer
     */
    protected $lastStatus;
    
    /**
     *
     * @var Zend_Config
     */
    protected $config;
    
    /**
     * @var editor_Models_Segment_TermTag
     */
    protected $termTagHelper;
    
    /**
     * @var editor_Models_Segment_TermTagTrackChange
     */
    protected $termTagTrackChangeHelper;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTagHelper;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $generalTrackChangesHelper;
    
    /**
     * @var integer
     */
    protected $openTimeout;
    
    /**
     * @var integer
     */
    protected $tagTimeout;
    
    /**
     * Two corresponding array to hold replaced tags.
     * Tags must be replaced in every text-element before send to the TermTagger-Server,
     * because TermTagger can not handle with already TermTagged-text.
     */
    private $replacedTagsNeedles = array();
    private $replacedTagsReplacements = array();
    
    /**
     * Container for segment data needed before and after tagging
     * @var array
     */
    private $segments = array();
    
    /**
     * Holds a counter for replacedTags to make needles unic
     * @var integer
    */
    private $replaceCounter = 1;
    
    
    /**
     * @param string $logDomain the domain to be used for the internal logger instance
     * @param integer $tagTimeout the timeout to be used for tagging
     * @param integer $openTbxTimeout the timeout to be used for opening a TBX
     */
    public function __construct(string $logDomain, int $tagTimeout, int $openTbxTimeout) {
        $this->tagTimeout = $tagTimeout;
        $this->openTimeout = $openTbxTimeout;
        $this->log = Zend_Registry::get('logger')->cloneMe($logDomain);
        $config = Zend_Registry::get('config');
        $this->config = $config->runtimeOptions->termTagger;
        $this->termTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        $this->internalTagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->termTagTrackChangeHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTagTrackChange');
        $this->generalTrackChangesHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
    }
    
    /**
     * returns the configured TermTagger URLs
     * @return array
     */
    public function getConfiguredUrls() {
        return $this->config->url->toArray();
    }
    
    /**
     * returns the HTTP Status of the last request
     * @return integer
     */
    public function getLastStatus() {
        return (int) $this->lastStatus;
    }
    
    /**
     * returns true if the last request was HTTP state 2**
     * @return boolean
     */
    public function wasSuccessfull() {
        $stat = $this->getLastStatus();
        return $stat >= 200 && $stat < 300;
    }
    
    /**
     * Checks if there is a TermTagger-server behind $url.
     * @param string $url url of the TermTagger-Server
     * @return boolean true if there is a TermTagger-Server behind $url
     */
    public function testServerUrl(string $url, &$version = null) {
        $httpClient = $this->getHttpClient($url.'/termTagger');
        $httpClient->setHeaders('accept', 'text/html');
        try {
            $response = $this->sendRequest($httpClient, $httpClient::GET);
        }
        catch(editor_Plugins_TermTagger_Exception_TimeOut $e) {
            return true; // the request URL is probably a termtagger which can not respond due it is processing data
        }
        catch(editor_Plugins_TermTagger_Exception_Down $e) {
            return false;
        }
        catch(editor_Plugins_TermTagger_Exception_Request $e) {
            return false;
        }
        
        $version = $response->getBody();
        // $url is OK if status == 200 AND string 'de.folt.models.applicationmodel.termtagger.TermTaggerRestServer' is in the response-body
        return $response && $this->wasSuccessfull() && strpos($response->getBody(), 'de.folt.models.applicationmodel.termtagger.TermTaggerRestServer') !== false;
    }
    
    /**
     * If no $tbxHash given, checks if the TermTagger-Sever behind $url is alive.
     * If $tbxHash is given, check if Server has loaded the tbx-file with the id $tbxHash.
     *
     * @param string $url url of the TermTagger-Server
     * @param string tbxHash unique id for a tbx-file
     *
     * @return boolean True if ping was succesfull
     */
    public function ping(string $url, $tbxHash = false) {
        $httpClient = $this->getHttpClient($url.'/termTagger/tbxFile/'.$tbxHash);
        $httpClient->setConfig([
            'timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => $this->tagTimeout //for pinging we just the same timeout as for tagging
        ]);
        $response = $this->sendRequest($httpClient, $httpClient::HEAD);
        return ($response && (($tbxHash !== false && $this->wasSuccessfull()) || ($tbxHash === false && $this->getLastStatus() == 404)));
    }
    
    
    /**
     * Load a tbx-file $tbxFilePath into the TermTagger-server behind $url where $tbxHash is a unic id for this tbx-file
     *
     * @param string $url url of the TermTagger-Server
     * @param string $tbxHash TBX hash
     * @param string $tbxData TBX data
     * @throws editor_Plugins_TermTagger_Exception_Open
     * @throws editor_Plugins_TermTagger_Exception_Request
     * @return Zend_Http_Response
     */
    public function open(string $url, string $tbxHash, string $tbxData) {
        if(empty($tbxHash)) {
            //Could not load TBX into TermTagger: TBX hash is empty.
            throw new editor_Plugins_TermTagger_Exception_Open('E1116', [
                'termTaggerUrl' => $url,
            ]);
        }
        
        // get default- and additional- (if any) -options for server-communication
        $serverCommunication = new stdClass();
        $serverCommunication->tbxFile = $tbxHash;
        $serverCommunication->tbxdata = $tbxData;
        
        // send request to TermTagger-server
        $httpClient = $this->getHttpClient($url.'/termTagger/tbxFile/');
        $httpClient->setConfig([
            'timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => $this->openTimeout
        ]);
        $httpClient->setRawData(json_encode($serverCommunication), 'application/json');
        $response = $this->sendRequest($httpClient, $httpClient::POST);
        $success = $this->wasSuccessfull();
        if($success && $response = $this->decodeServiceResult($response)) {
            return $response;
        }
        $data = [
            'httpStatus' => $this->getLastStatus(),
            'termTaggerUrl' => $httpClient->getUri(true),
            'plainServerResponse' => print_r($response, true),
            'requestedData' => $serverCommunication,
        ];
        $errorCode = $success ? 'E1118' : 'E1117';
        //E1117: Could not load TBX into TermTagger: TermTagger HTTP result was not successful!
        //E1118: Could not load TBX into TermTagger: TermTagger HTTP result could not be decoded!'
        throw new editor_Plugins_TermTagger_Exception_Open($errorCode, $data);
    }
    
    /**
     * send request method with unified logging
     * @param Zend_Http_Client $client
     * @param string $method
     * @throws editor_Plugins_TermTagger_Exception_Request
     * @return Zend_Http_Response
     */
    protected function sendRequest(Zend_Http_Client $client, $method) {
        $this->lastStatus = false;
        $start = microtime(true);
        try {
            $extraData = [
                'httpMethod' => $method,
                'termTaggerUrl' => $client->getUri(true),
            ];
            $result = $client->request($method);
            if(ZfExtended_Debug::hasLevel('plugin', 'TermTagger')) {
                $rand = rand();
                error_log("TermTagger Duration (id: $rand): ".(microtime(true) - $start).'s');
                error_log("TermTagger Request (id: $rand): ".print_r($client->getLastRequest(),1));
                error_log("TermTagger Answer (to id $rand): ".print_r($result->getRawBody(),1));
            }
            $this->lastStatus = $result->getStatus();
            return $result;
        } catch(ZfExtended_Zendoverwrites_Http_Exception_TimeOut $httpException) {
            //if the error is one of the following, we have a request timeout
            //ERROR Zend_Http_Client_Adapter_Exception: E9999 - Read timed out after 10 seconds
            throw new editor_Plugins_TermTagger_Exception_TimeOut('E1240', $extraData, $httpException);
        } catch(ZfExtended_Zendoverwrites_Http_Exception_Down $httpException) {
            //if the error is one of the following, we have a connection problem
            //ERROR Zend_Http_Client_Adapter_Exception: E9999 - Unable to Connect to tcp://localhost:8080. Error #111: Connection refused
            //ERROR Zend_Http_Client_Adapter_Exception: E9999 - Unable to Connect to tcp://michgibtesdefinitivnichtalsdomain.com:8080. Error #0: php_network_getaddresses: getaddrinfo failed: Name or service not known

            //the following IP is not routed, so it trigers a timeout on connection connect, which must result in "Unable to connect" too and not in a request timeout below
            //ERROR Zend_Http_Client_Adapter_Exception: E9999 - Unable to Connect to tcp://10.255.255.1:8080. Error #111: Connection refused
            throw new editor_Plugins_TermTagger_Exception_Down('E1129', $extraData, $httpException);
        } catch(ZfExtended_Zendoverwrites_Http_Exception_NoResponse $httpException) {
            //This error points to an crash of the termtagger, so we can log additional data here
            throw new editor_Plugins_TermTagger_Exception_Request('E1130', $extraData, $httpException);
        } catch(Exception $httpException) {
            //Error in communication with TermTagger
            throw new editor_Plugins_TermTagger_Exception_Request('E1119', $extraData, $httpException);
        }
    }
    
    /**
     * instances a Zend_Http_Client Object, sets the desired URI and returns it
     * @param string $uri
     * @return Zend_Http_Client
     */
    protected function getHttpClient($uri) {
        $client = ZfExtended_Factory::get('Zend_Http_Client');
        $client->setUri($uri);
        return $client;
    }
    
    /**
     * TermTaggs segment-text(s) in $data on TermTagger-server $url
     *
     * @param string $url
     * @param editor_Plugins_TermTagger_Service_ServerCommunication $data
     *
     * @return Zend_Http_Response or null on error
     */
    public function tagterms($url, editor_Plugins_TermTagger_Service_ServerCommunication $data) {
        $data = $this->encodeSegments($data);
        
        //test term tagger errors, start a dummy netcat server in the commandline: nc -l -p 8080
        // if the request was received in the commandline, just kill nc to simulate a termtagger crash.
        //$url = 'http://michgibtesdefinitivnichtalsdomain.com:8080'; // this is the nc dummy URL then.
        //$url = 'http://localhost:8080'; // this is the nc dummy URL then.
        $httpClient = $this->getHttpClient($url.'/termTagger/termTag/');
        
        $httpClient->setRawData(json_encode($data), 'application/json');
        $httpClient->setConfig([
            'timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => $this->tagTimeout
        ]);
        $response = $this->sendRequest($httpClient, $httpClient::POST);
        
        if(!$this->wasSuccessfull()) {
            //TermTagger returns an error on tagging segments.
            throw new editor_Plugins_TermTagger_Exception_Malfunction('E1120', [
                'httpStatus' => $this->getLastStatus(),
                'termTaggerUrl' => $httpClient->getUri(true),
                'plainServerResponse' => print_r($response->getBody(), true),
                'requestedData' => $data,
            ]);
        }
        $response = $this->decodeServiceResult($response);
        if (!$response) {
            //processing tagterms TermTagger result could not be decoded.
            throw new editor_Plugins_TermTagger_Exception_Request('E1121', [
                'httpStatus' => $this->getLastStatus(),
                'termTaggerUrl' => $httpClient->getUri(true),
                'plainServerResponse' => print_r($response->getBody(), true),
                'requestedData' => $data,
            ]);
        }
        
        return $this->decodeSegments($response, $data);
    }
    
    /**
     * replaces our internal tags with a img place holder, since the termtagger can not deal with our tags, but with imgs
     * @param editor_Plugins_TermTagger_Service_ServerCommunication $data
     * @return editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private function encodeSegments(editor_Plugins_TermTagger_Service_ServerCommunication $data) {
        foreach ($data->segments as & $segment) {
            $segment->source = $this->encodeSegment($segment, 'source');
            $segment->target = $this->encodeSegment($segment, 'target');
        }
        
        return $data;
    }

    /**
     * restores our internal tags from the delivered img tags
     *
     * @param stdClass $data
     * @param editor_Plugins_TermTagger_Service_ServerCommunication $requests
     * @return stdClass
     */
    private function decodeSegments(stdClass $data, editor_Plugins_TermTagger_Service_ServerCommunication $request) {
        foreach ($data->segments as & $segment) {
            $segment->source = $this->decodeSegment($segment, 'source', $request);
            $segment->target = $this->decodeSegment($segment, 'target', $request);
        }
        return $data;
    }
    
    private function encodeSegment($segment, $field) {
        $trackChangeTag = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        /* @var $trackChangeTag editor_Models_Segment_TrackChangeTag */
        
        $text = $segment->$field;
        $matchContentRegExp = '/<div[^>]+class="(open|close|single).*?".*?\/div>/is';
        
        preg_match_all($matchContentRegExp, $text, $tempMatches);
        
        foreach ($tempMatches[0] as $match) {
            $needle = '<img class="content-tag" src="'.$this->replaceCounter++.'" alt="TaggingError" />';
            $this->replacedTagsNeedles[] = $needle;
            $this->replacedTagsReplacements[] = $match;
            
            $text = str_replace($match, $needle, $text);
        }
        $text = preg_replace('/<div[^>]+>/is', '', $text);
        $text = preg_replace('/<\/div>/', '', $text);
        
        //protecting trackChanges del tags
        $text = $trackChangeTag->protect($text);
        //store the text with track changes
        $trackChangeTag->textWithTrackChanges = $text;
        $this->segments[$field.'-'.$segment->id] = $trackChangeTag; //we have to store one instance per segment since it contains specific data for recreation
        
        // Now remove the stored TrackChange-Nodes from the text for termtagging (with the general helper to keep the original tags inside the specific instance)
        return $this->generalTrackChangesHelper->removeTrackChanges($text);
    }
    
    private function decodeSegment($segment, $field, editor_Plugins_TermTagger_Service_ServerCommunication $request) {
        $text = $segment->$field;
        if(empty($text) && $text !== '0') {
            return $text;
        }
        //fix TRANSLATE-713
        $text = str_replace('term-STAT_NOT_FOUND', 'term STAT_NOT_FOUND', $text);
        
        //remerge trackchanges and terms - FIXME dont do it if there are no INS/DEL!
        $trackChangeTag = $this->segments[$field.'-'.$segment->id];
        
        //error_log(print_r($trackChangeTag,1));
        //error_log($text);
        $text = $this->termTagTrackChangeHelper->mergeTermsAndTrackChanges($text, $trackChangeTag->textWithTrackChanges);
        //check if content is valid XML, or if textual content has changed
        $oldFlagValue = libxml_use_internal_errors(true);
        // delete tags and internal tags are masked, thats ok for the check here
        $invalidXml = ! @simplexml_load_string('<container>'.$text.'</container>');
        libxml_use_internal_errors($oldFlagValue);
        $textNotEqual = strip_tags($text) !== strip_tags($segment->$field);
        if($invalidXml || $textNotEqual) {
            $this->log->warn('E1132', 'Conflict in merging terminology and track changes: "{type}".', [
                'type' => ($invalidXml?'Invalid XML,':'').($textNotEqual?' text changed by merge':''),
                'task' => $request->task,
                'segmentId' => $segment->id,
                'inputFromBrowser' => $trackChangeTag->unprotect($trackChangeTag->textWithTrackChanges),
                'termTaggerResult' => $segment->$field,
                'mergedResult' => $text,
            ]);
        }
        //error_log($text);
        $text = $trackChangeTag->unprotect($text);
        //error_log($text);
        
        if (empty($this->replacedTagsNeedles)) {
            return $text;
        }
        
        $text = preg_replace('"&lt;img class=&quot;content-tag&quot; src=&quot;(\d+)&quot; alt=&quot;TaggingError&quot; /&gt;"', '<img class="content-tag" src="\\1" alt="TaggingError" />', $text);
        $text = str_replace($this->replacedTagsNeedles, $this->replacedTagsReplacements, $text);
        
        return $text;
    }
    
    /**
     * decodes the TermTagger JSON and logs an error if data can not be processed
     * @param Zend_Http_Response $result
     * @return stdClass or null on error
     */
    private function decodeServiceResult(Zend_Http_Response $result = null) {
        if(empty($result)) {
            return null;
        }
    
        $data = json_decode($result->getBody());
        if(!empty($data)) {
            if(!empty($data->error)) {
                $this->log->error('E1133', 'TermTagger reports error "{error}".', [
                    'error' => print_r($data,1),
                ]);
            }
            return $data;
        }
        $this->log->error('E1134', 'TermTagger produces invalid JSON: "{jsonError}".', [
            'jsonError' => json_last_error_msg(),
            'jsonBody' => $result->getBody(),
        ]);
        return null;
    }
    
}


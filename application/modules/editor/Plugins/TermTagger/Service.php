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
     * @var ZfExtended_Log
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
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTagHelper;
    
    
    /**
     * Two corresponding array to hold replaced tags.
     * Tags must be replaced in every text-element before send to the TermTagger-Server,
     * because TermTagger can not handle with already TermTagged-text.
     */
    private $replacedTagsNeedles = array();
    private $replacedTagsReplacements = array();
    
    /**
     * Holds a counter for replacedTags to make needles unic
     * @var integer
    */
    private $replaceCounter = 1;
    
    /**
     * Arrays for handling the TrackChange-Nodes.
     * TrackChange-Nodes must be replaced in every text-element before send to the TermTagger-Server,
     * because TermTagger can not handle text with TrackChange-Nodes.
     */
    private $arrTrackChangeNodes = array();
    
    
    
    public function __construct() {
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
        $config = Zend_Registry::get('config');
        $this->config = $config->runtimeOptions->termTagger;
        $this->termTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        $this->internalTagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
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
     * 
     * @param url $url url of the TermTagger-Server
     * 
     * @return boolean true if there is a TermTagger-Server behind $url 
     */
    public function testServerUrl(string $url, &$version = null) {
        $httpClient = $this->getHttpClient($url.'/termTagger');
        $httpClient->setHeaders('accept', 'text/html');
        try {
            $response = $this->sendRequest($httpClient, $httpClient::GET);
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
        $response = $this->sendRequest($httpClient, $httpClient::HEAD);
        return ($response && (($tbxHash !== false && $this->wasSuccessfull()) || ($tbxHash === false && $this->getLastStatus() == 404)));
    }
    
    
    /**
     * Load a tbx-file $tbxFilePath to the TermTagger-server behind $url where $tbxHash is a unic id for this tbx-file
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
            throw new editor_Plugins_TermTagger_Exception_Open('TBX hash is empty!');
        }
        
        return $this->_open($url, $tbxHash, $tbxData);
    }
    
    /**
     * sends an open request to the termtagger
     * @param string $url
     * @param string $tbxHash
     * @param string $tbxData
     * @param array $moreParams
     * @throws editor_Plugins_TermTagger_Exception_Open
     * @throws editor_Plugins_TermTagger_Exception_Request
     * @return Zend_Http_Response
     */
    private function _open($url, $tbxHash, $tbxData, $moreParams = array()) {
        // get default- and additional- (if any) -options for server-communication
        $serverCommunication = new stdClass();
        $serverCommunication->tbxFile = $tbxHash;
        foreach ($moreParams as $key => $value) {
            $serverCommunication->$key = $value;
        }
        $serverCommunication->tbxdata = $tbxData;
        
        // send request to TermTagger-server
        $httpClient = $this->getHttpClient($url.'/termTagger/tbxFile/');
        $httpClient->setConfig(array('timeout' => (integer)$this->config->timeOut->tbxParsing));
        $httpClient->setRawData(json_encode($serverCommunication), 'application/json');
        $response = $this->sendRequest($httpClient, $httpClient::POST);
        if(!$this->wasSuccessfull()) {
            $msg = 'TermTagger HTTP Status was: '.$this->getLastStatus();
            $msg .= "\n URL: ".$httpClient->getUri(true);
            $this->log->logError('INFO: Opening a TBX in termtagger '.$url.' was NOT successfull!', $msg."\n\nMore details in error log!\n\n");
            $msg .= "\n\nPlain Server Response: ".print_r($response,true);
            $msg .= "\n\nRequested Data: ".print_r($serverCommunication,true);
            error_log($msg);
            throw new editor_Plugins_TermTagger_Exception_Open('TermTagger HTTP Result was not successfull!');
        }
        
        $response = $this->decodeServiceResult($response);
        if (!$response) {
            $msg = 'Could not decode TermTagger result!';
            $msg .= "\n URL: ".$httpClient->getUri(true);
            $this->log->logError('INFO: Opening a TBX in termtagger '.$url.' has PERHAPS failed!', $msg."\n\nMore details in error log!\n\n");
            $msg .= "\n\nPlain Server Response: ".print_r($response,true);
            $msg .= "\n\nRequested Data: ".print_r($serverCommunication,true);
            error_log($msg);
            throw new editor_Plugins_TermTagger_Exception_Open('TermTagger HTTP Result could not be decoded!');
        }
        return $response;
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
        try {
            $result = $client->request($method);
            if(ZfExtended_Debug::hasLevel('plugin', 'TermTagger')) {
                $rand = rand();
                error_log("TermTagger Request (id: $rand): ".print_r($client->getLastRequest(),1));
                error_log("TermTagger Answer (to id $rand): ".print_r($result->getRawBody(),1));
            }
            $this->lastStatus = $result->getStatus();
            return $result;
        } catch(Exception $httpException) {
            //logging the send data is irrelevant here, since we are logging communication errors, not termtagger server errors!
            $msg = 'Method: '.$method.'; URL was: '.$client->getUri(true).'; Message was: '.$httpException->getMessage();
            throw new editor_Plugins_TermTagger_Exception_Request($msg);
        }
    }
    
    /**
     * instances a Zend_Http_Client Object, sets the desired URI and returns it
     * @param string $uri
     * @return Zend_Http_Client
     */
    protected function getHttpClient($uri) {
        $client = new Zend_Http_Client();
        $client->setUri($uri);
        return $client;
    }
    
    /**
     * TermTaggs segment-text(s) in $data on TermTagger-server $url 
     * 
     * @param unknown $url
     * @param editor_Plugins_TermTagger_Service_ServerCommunication $data
     * 
     * @return Zend_Http_Response or null on error
     */
    public function tagterms($url, editor_Plugins_TermTagger_Service_ServerCommunication $data) {
        
        $data = $this->encodeSegments($data);
        
        $httpClient = $this->getHttpClient($url.'/termTagger/termTag/');
        $httpClient->setRawData(json_encode($data), 'application/json');
        $httpClient->setConfig(array('timeout' => (integer)$this->config->timeOut->segmentTagging));
        $response = $this->sendRequest($httpClient, $httpClient::POST);
        
        if(!$this->wasSuccessfull()) {
            $msg = 'TermTagger HTTP Status was: '.$this->getLastStatus();
            $msg .= "\n URL: ".$httpClient->getUri(true)."\n\nRequested Data: ";
            $msg .= print_r($data,true)."\n\nPlain Server Response: ";
            $msg .= print_r($response,true);
            throw new editor_Plugins_TermTagger_Exception_Malfunction($msg);
        }
        
        $response = $this->decodeServiceResult($response);
        if (!$response) {
            //processing tagterms 
            throw new editor_Plugins_TermTagger_Exception_Request('TermTagger : Error on decodeServiceResult');
        }
        
        $response = $this->decodeSegments($response);
        
        return $response;
    }
    
    /**
     * replaces our internal tags with a img place holder, since the termtagger can not deal with our tags, but with imgs
     * @param editor_Plugins_TermTagger_Service_ServerCommunication $data
     * @return editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private function encodeSegments(editor_Plugins_TermTagger_Service_ServerCommunication $data) {
        foreach ($data->segments as & $segment) {
            $segment->source = $this->encodeText($segment->source, $segment->id);
            $segment->target = $this->encodeText($segment->target, $segment->id);
        }
        
        return $data;
    }

    /**
     * restores our internal tags from the delivered img tags
     * 
     * @param stdClass $data
     * @return stdClass
     */
    private function decodeSegments(stdClass $data) {
        foreach ($data->segments as & $segment) {
            $segment->source = $this->decodeText($segment->source, $segment->id);
            $segment->target = $this->decodeText($segment->target, $segment->id);
        }
        return $data;
    }
    
    private function encodeText($text, $segmentId) {
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
        
        $text = $this->encodeTrackChanges($text, $segmentId);
        
        return $text;
    }
    
    private function decodeText($text, $segmentId) {
        //fix TRANSLATE-713
        $text = str_replace('term-STAT_NOT_FOUND', 'term STAT_NOT_FOUND', $text);
        
        if (empty($this->replacedTagsNeedles) && empty($this->arrTrackChangeNodes)) {
            return $text;
        }
        
        $text = $this->decodeTrackChanges($text, $segmentId);
        
        $text = preg_replace('"&lt;img class=&quot;content-tag&quot; src=&quot;(\d+)&quot; alt=&quot;TaggingError&quot; /&gt;"', '<img class="content-tag" src="\\1" alt="TaggingError" />', $text);
        $text = str_replace($this->replacedTagsNeedles, $this->replacedTagsReplacements, $text);
        
        return $text;
    }
    
    private function encodeTrackChanges($text, $segmentId) {
        // We will need to assign the found TrackChange-Nodes to the original text later. 
        // So we have to remember which text the found TrackChange-Nodes belong to!
        $cleanText = $this->internalTagHelper->removeTrackChanges($text);
        $cleanText = $this->termTagHelper->remove($cleanText);
        $textKey = $segmentId . '-' . md5($cleanText);
        
        $text = $this->internalTagHelper->protect($text);
        
        // Fetch the TrackChangesin the text:
        $this->arrTrackChangeNodes[$textKey] = array();
        
        // - DEL
        $matchTrackChangesDELRegExp = '/<del[^>]*>.*?<\/del>/i';
        preg_match_all($matchTrackChangesDELRegExp, $text, $tempMatchesTrackChangesDEL, PREG_OFFSET_CAPTURE);
        foreach ($tempMatchesTrackChangesDEL[0] as $match) {
            $this->arrTrackChangeNodes[$textKey][$match[1]] = $match[0];
        }
        //- INS
        $matchTrackChangesINSRegExp = '/<\/?ins[^>]*>/i';
        preg_match_all($matchTrackChangesINSRegExp, $text, $tempMatchesTrackChangesINS, PREG_OFFSET_CAPTURE);
        foreach ($tempMatchesTrackChangesINS[0] as $match) {
            $this->arrTrackChangeNodes[$textKey][$match[1]] = $match[0];
        }
        ksort($this->arrTrackChangeNodes[$textKey]);
        
        $text = $this->internalTagHelper->unprotect($text);
        
        // Return the text without the TrackChanges.
        return $this->internalTagHelper->removeTrackChanges($text);
    }
    
    private function decodeTrackChanges($text, $segmentId) {
        // If we don't have any information about the TrackChange-Nodes for the original text,
        // we cannot restore them. (We also don't know if there weren't any, if so.)
        // So, this array might be empty, but we need this information!
        $cleanText = $this->internalTagHelper->removeTrackChanges($text);
        $cleanText = $this->termTagHelper->remove($cleanText);
        $textKey = $segmentId . '-' . md5($cleanText);
        if (!array_key_exists($textKey, $this->arrTrackChangeNodes)) {
            //throw new ZfExtended_Exception('Decoding TrackChanges failed because there is no information about the original version.');
            error_log($textKey . 'Decoding TrackChanges failed because there is no information about the original version: ' . $cleanText);
            return $text;
        }
        $arrTrackChangeNodesInText = $this->arrTrackChangeNodes[$textKey];
        
        $text = $this->internalTagHelper->protect($text);
        
        // Fetch the TermTags in the text:
        $arrTermTagsInText = array();
        $matchTermTagsRegExp= '/<\/?div[^>]*>/i';
        preg_match_all($matchTermTagsRegExp, $text, $tempMatchesTermTags, PREG_OFFSET_CAPTURE);
        foreach ($tempMatchesTermTags[0] as $match) {
            $arrTermTagsInText[$match[1]] = $match[0];
        }
        ksort($arrTermTagsInText);
        
        $trackChangeNodeStatus= null;
        $pos = 0;
        while ($pos < strlen($text)) {
            $posAtTheBeginningOfThisStep = $pos;
            $openingTrackChangeNode = null;
            $closingTrackChangeNode = null;
            // If there is a termTag in the text at this position, we need to:
            if(array_key_exists($pos, $arrTermTagsInText)) {
                // get all needed items related to the current $pos before positions in the text/arrays change
                $termTagInText = $arrTermTagsInText[$pos];
                if ($trackChangeNodeStatus == 'open') {
                    $openingTrackChangeNode = $this->getThresholdItemInArray($arrTrackChangeNodesInText, $pos, 'before');
                    $closingTrackChangeNode = $this->getThresholdItemInArray($arrTrackChangeNodesInText, $pos, 'next');
                }
                // - close the current TrackChange-Node in case we are in the midst of one:
                if ($closingTrackChangeNode != null) {
                    $length = strlen($closingTrackChangeNode);
                    $arrTrackChangeNodesInText = $this->increaseKeysInArray($arrTrackChangeNodesInText, $length, $pos);
                    $arrTermTagsInText = $this->increaseKeysInArray($arrTermTagsInText, $length, $pos+1);
                    $text = substr($text, 0, $pos) . $closingTrackChangeNode. substr($text, $pos);
                    $pos += $length;
                }
                // - increase the following positions of the found TrackChange-Nodes by the length of the found termTag.
                $length = strlen($termTagInText);
                $arrTrackChangeNodesInText = $this->increaseKeysInArray($arrTrackChangeNodesInText, $length, $pos);
                $pos += $length;
                // - re-open the current TrackChange-Node in case we are in the midst of one:
                if ($openingTrackChangeNode != null) {
                    $length = strlen($openingTrackChangeNode);
                    $arrTrackChangeNodesInText = $this->increaseKeysInArray($arrTrackChangeNodesInText, $length, $pos);
                    $arrTermTagsInText = $this->increaseKeysInArray($arrTermTagsInText, $length, $pos+1);
                    $text = substr($text, 0, $pos) . $openingTrackChangeNode. substr($text, $pos);
                    $pos += $length;
                }
            }
            // If there is a TrackChange-Node in the text at this position, we need to:
            if(array_key_exists($pos, $arrTrackChangeNodesInText)) {
                // get all needed items related to the current $pos before positions in the text/arrays change
                $trackChangeNodeInText = $arrTrackChangeNodesInText[$pos];
                $length = strlen($trackChangeNodeInText);
                // - increase the following positions of the found TermTags by the length of the found TrackChange-Node.
                $arrTermTagsInText = $this->increaseKeysInArray($arrTermTagsInText, $length, $pos);
                // - re-enter the TrackChange-Node here
                $text = substr($text, 0, $pos) . $trackChangeNodeInText. substr($text, $pos);
                $pos += $length;
                // - set the status of the current TrackChange-Node (open/close):
                //   (but only when opening and closing tags are handled extra, thus not for "<del>...</del>") 
                $matchTrackChangesDELRegExp = '/<del[^>]*>.*?<\/del>/i';
                if (!preg_match_all($matchTrackChangesDELRegExp, $trackChangeNodeInText)) {
                    $trackChangeNodeStatus = ($trackChangeNodeStatus == 'open') ? 'close' : 'open'; // start was null and the first step must go to 'open'
                }
            }
            if ($pos == $posAtTheBeginningOfThisStep) { // if we increase $pos after it has already been increased in the current step we will skip the current $pos
                $pos++;
            }
        }
        
        $text = $this->internalTagHelper->unprotect($text);
        
        return $text;
    }
    /**
     * Returns the array-item of the key that is after or before/at the given threshold.
     * @param array $arr
     * @param number $threshold
     * @param number $direction
     * @return array
     */
    private static function getThresholdItemInArray ($arr, $threshold, $direction) {
        if ($direction == 'next') {
            end($arr);
            while(key($arr) > $threshold) prev($arr);   // set internal pointer to position before $threshold
            return next($arr);                          // return the item after that position.
        }
        if(array_key_exists($threshold, $arr)) {        // If there IS an item at the threshold's position
            return $arr[$threshold];                    // return that one.
        }
        while(key($arr) < $threshold) next($arr);       // set internal pointer to position after $threshold
            return prev($arr);                          // return the item before that position.
    }
    /**
     * Returns a "new version" of the given array with keys increased by the given number.
     * Increases only those keys that are higher than the given threshold. 
     * @param array $arr
     * @param number $number
     * @param number $threshold
     * @return array
     */
    private static function increaseKeysInArray ($arr, $number, $threshold) {
        $arrOldValues = array_values($arr);
        $arrOldKeys = array_keys($arr);
        $arrNewKeys = array_map(function($oldKey) use ($number, $threshold) {
            if ($oldKey < $threshold) {
                return $oldKey;
            } else {
                return $oldKey + $number;
            }
        }, $arrOldKeys);
        return array_combine($arrNewKeys, $arrOldValues);
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
                $this->log->logError(__CLASS__.' decoded TermTagger Result but with following Error from TermTagger: ', print_r($data,1));
            }
            return $data;
        }
        $msg = "Original TermTagger Result was: \n".$result->getBody()."\n JSON decode error was: ";
        if (function_exists('json_last_error_msg')) {
            $msg .= json_last_error_msg();
        } else {
            static $errors = array(
                            JSON_ERROR_NONE             => null,
                            JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
                            JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
                            JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
                            JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
                            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
            );
            $error = json_last_error();
            $msg .=  array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
        }
        $this->log->logError(__CLASS__.' cannot json_decode TermTagger Result!', $msg);
        return null;
    }
    
}

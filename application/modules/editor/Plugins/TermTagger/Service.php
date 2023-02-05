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

use MittagQI\Translate5\Service\DockerMultiService;

/**
 * Service Class of Plugin "TermTagger"
 */
final class editor_Plugins_TermTagger_Service extends DockerMultiService {

    /**
     * The timeout for connections is fix, the request timeout depends on the request type and comes from the config
     * @var integer
     */
    const CONNECT_TIMEOUT = 10;

    /**
     * Timeout used for test-pings
     */
    const DEFAULT_TAG_TIMEOUT = 10;

    /**
     * contains the HTTP status of the last request
     * @var integer
     */
    protected $lastStatus;

    /**
     * returns the configured TermTagger URLs
     * @return array
     */
    public function getConfiguredUrls() {
        return $this->config->runtimeOptions->termTagger->url->toArray();
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
     * @param string $url
     * @param mixed|null $version
     * @return bool: true if there is a TermTagger-Server behind $url
     * @throws Zend_Http_Client_Exception
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
            'request_timeout' => self::DEFAULT_TAG_TIMEOUT
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
            'request_timeout' => editor_Plugins_TermTagger_Configuration::TIMEOUT_TBXIMPORT
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
     * Requests the termtagger with the given service-url and the passed segment-data (wich has to be be encoded)
     * @param string $serviceUrl
     * @param editor_Plugins_TermTagger_Service_Data $serviceData
     * @param ZfExtended_Logger $logger
     * @param int $requestTimeout
     * @return stdClass|null
     * @throws Zend_Http_Client_Exception
     * @throws editor_Plugins_TermTagger_Exception_Request
     */
    public function tagTerms(string $serviceUrl, editor_Plugins_TermTagger_Service_Data $serviceData, ZfExtended_Logger $logger, int $requestTimeout): ?stdClass
    {
        //test term tagger errors, start a dummy netcat server in the commandline: nc -l -p 8080
        // if the request was received in the commandline, just kill nc to simulate a termtagger crash.
        //$serviceUrl = 'http://michgibtesdefinitivnichtalsdomain.com:8080'; // this is the nc dummy URL then.
        //$serviceUrl = 'http://localhost:8080'; // this is the nc dummy URL then.
        $httpClient = $this->getHttpClient($serviceUrl.'/termTagger/termTag/');
        $httpClient->setRawData(json_encode($serviceData), 'application/json');
        $httpClient->setConfig([
            'timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => $requestTimeout
        ]);
        $response = $this->sendRequest($httpClient, $httpClient::POST);

        $response = $this->decodeServiceResult($logger, $response);
        if (!$response) {
            //processing tagterms TermTagger result could not be decoded.
            throw new editor_Plugins_TermTagger_Exception_Request('E1121', [
                'httpStatus' => $this->getLastStatus(),
                'termTaggerUrl' => $httpClient->getUri(true),
                'plainServerResponse' => print_r($response->getBody(), true),
                'requestedData' => $serviceData,
            ]);
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
    private function sendRequest(Zend_Http_Client $client, $method) {
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
    private function getHttpClient($uri) {
        $client = ZfExtended_Factory::get('Zend_Http_Client');
        $client->setUri($uri);
        return $client;
    }

    /**
     * decodes the TermTagger JSON and logs an error if data can not be processed
     * @param ZfExtended_Logger $logger
     * @param Zend_Http_Response|null $result
     * @return stdClass|null
     */
    private function decodeServiceResult(ZfExtended_Logger $logger, Zend_Http_Response $result = null): ?stdClass
    {
        if(empty($result)) {
            return null;
        }
        $data = json_decode($result->getBody());
        if(!empty($data)) {
            if(!empty($data->error)) {
                $logger->error('E1133', 'TermTagger reports error "{error}".', [
                    'error' => print_r($data,1),
                ]);
            }
            return $data;
        }
        $logger->error('E1134', 'TermTagger produces invalid JSON: "{jsonError}".', [
            'jsonError' => json_last_error_msg(),
            'jsonBody' => $result->getBody(),
        ]);
        return null;
    }
}


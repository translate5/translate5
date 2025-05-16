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

namespace MittagQI\Translate5\Plugins\TermTagger;

use Exception;
use MittagQI\Translate5\Plugins\TermTagger\Exception\DownException;
use MittagQI\Translate5\Plugins\TermTagger\Exception\NoResponseException;
use MittagQI\Translate5\Plugins\TermTagger\Exception\OpenException;
use MittagQI\Translate5\Plugins\TermTagger\Exception\RequestException;
use MittagQI\Translate5\Plugins\TermTagger\Exception\TimeOutException;
use MittagQI\Translate5\Plugins\TermTagger\Service\ServiceData;
use MittagQI\Translate5\PooledService\AbstractPooledService;
use ReflectionException;
use stdClass;
use Throwable;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use Zend_Http_Response;
use ZfExtended_Debug;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_Zendoverwrites_Http_Exception_Down;
use ZfExtended_Zendoverwrites_Http_Exception_NoResponse;
use ZfExtended_Zendoverwrites_Http_Exception_TimeOut;

/**
 * Service Class of Plugin "TermTagger"
 */
final class Service extends AbstractPooledService
{
    public const SERVICE_ID = 'termtagger';

    /**
     * The timeout for connections is fix, the request timeout depends on the request type and comes from the config
     * @var integer
     */
    public const CONNECT_TIMEOUT = 10;

    /**
     * Timeout used for test-pings
     */
    public const DEFAULT_TAG_TIMEOUT = 10;

    private const TBX_BASE_URL_PATH = '/termTagger/tbxFile/';

    private const DEFAULT_URL = 'http://termtagger.:9001';

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.termTagger.url.default',
        'type' => 'list',
        'url' => self::DEFAULT_URL,
    ];

    protected array $guiConfigurationConfig = [
        'name' => 'runtimeOptions.termTagger.url.gui',
        'type' => 'list',
        'url' => self::DEFAULT_URL,
    ];

    protected array $importConfigurationConfig = [
        'name' => 'runtimeOptions.termTagger.url.import',
        'type' => 'list',
        'url' => self::DEFAULT_URL,
    ];

    protected array $testConfigs = [
        // this leads to the application-db configs being copied to the test-DB
        'runtimeOptions.termTagger.url.gui' => null,
        'runtimeOptions.termTagger.url.import' => null,
        'runtimeOptions.termTagger.url.default' => null,
    ];

    protected bool $persistentConnections = false;

    /**
     * contains the HTTP status of the last request
     * @var integer
     */
    protected $lastStatus;

    public function getServiceId(): string
    {
        return self::SERVICE_ID;
    }

    /**
     * @return array{
     *     success: bool,
     *     version: string|null
     * }
     * @throws Zend_Http_Client_Exception
     * @throws ReflectionException
     */
    protected function checkServiceUrl(string $url): array
    {
        $result = [
            'success' => false,
            'version' => null,
        ];
        $httpClient = $this->getHttpClient(rtrim($url, '/') . '/termTagger');
        $httpClient->setHeaders('accept', 'text/html');

        try {
            $response = $this->sendRequest($httpClient, $httpClient::GET);
        } catch (TimeOutException) {
            // the request URL is probably a termtagger which can not respond because it is processing data
            $result['success'] = true;

            return $result;
        } catch (Throwable) {
            return $result; // all other ecxceptione are regarded as service not functioning properly
        }
        if ($response) {
            $result['success'] = (
                $this->wasSuccessfull()
                && str_contains($response->getBody(), 'de.folt.models.applicationmodel.termtagger.TermTaggerRestServer')
            );
            // will be markup like <html> <title>TermTagger Version Information</title><body><h1>TermTagger
            // Version Information</h1><h2>TermTagger REST Server</h2><b>Version:</b> 0.16<br />
            //<b>Class: </b>de.folt.models.applicationmodel.termtagger.TermTaggerRestServer<br />
            //<b>Compile Date: </b>Thu Jan 26 17:42:24 UTC 2023<hr><h2>TermTagger:</h2><b>Version:</b> 9.01<br />
            //<b>Class: </b>de.folt.models.applicationmodel.termtagger.XliffTermTagger<br />
            //<b>Compile Date: </b>Mon Jun 10 18:33:52 UTC 2019<hr><h2>OpenTMS Version: </h2>0.2.1</body></html>
            $parts = explode('Version:', $response->getBody());
            $parts = (count($parts) > 1) ? explode('<br', $parts[1]) : [];
            $result['version'] = (! empty($parts)) ? trim(strip_tags($parts[0])) : null;
        }

        return $result;
    }

    /**
     * returns the HTTP Status of the last request
     * @return integer
     */
    public function getLastStatus()
    {
        return (int) $this->lastStatus;
    }

    /**
     * returns true if the last request was HTTP state 2**
     * @return boolean
     */
    public function wasSuccessfull()
    {
        $stat = $this->getLastStatus();

        return $stat >= 200 && $stat < 300;
    }

    /**
     * If no $tbxHash given, checks if the TermTagger-Sever behind $url is alive.
     * If $tbxHash is given, check if Server has loaded the tbx-file with the id $tbxHash.
     *
     * @param string $url url of the TermTagger-Server
     * @return bool True if ping was succesfull
     * @throws DownException
     * @throws NoResponseException
     * @throws ReflectionException
     * @throws RequestException
     * @throws TimeOutException
     * @throws Zend_Http_Client_Exception
     */
    public function ping(string $url, string $tbxHash = ''): bool
    {
        $httpClient = $this->getHttpClient($url . self::TBX_BASE_URL_PATH . $tbxHash, $tbxHash);
        $httpClient->setConfig([
            'timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => self::DEFAULT_TAG_TIMEOUT,
        ]);
        $this->applyPersistentConnections($httpClient);

        $response = $this->sendRequest($httpClient, $httpClient::HEAD);

        $hasHash = strlen($tbxHash) > 0;

        return $response
            && (($hasHash && $this->wasSuccessfull())
                || (! $hasHash && $this->getLastStatus() == 404));
    }

    /**
     * Load a tbx-file $tbxFilePath into the TermTagger-server behind $url where $tbxHash is a unic id for this tbx-file
     * @throws DownException
     * @throws NoResponseException
     * @throws OpenException
     * @throws ReflectionException
     * @throws RequestException
     * @throws TimeOutException
     * @throws Zend_Http_Client_Exception
     */
    public function loadTBX(string $url, string $tbxHash, string $tbxData, ZfExtended_Logger $logger): stdClass
    {
        if (empty($tbxHash)) {
            //Could not load TBX into TermTagger: TBX hash is empty.
            throw new OpenException('E1116', [
                'termTaggerUrl' => $url,
            ]);
        }

        // get default- and additional- (if any) -options for server-communication
        $serviceData = new stdClass();
        $serviceData->tbxFile = $tbxHash;
        $serviceData->tbxdata = $tbxData;

        // send request to TermTagger-server
        $httpClient = $this->getHttpClient($url . self::TBX_BASE_URL_PATH, $tbxHash);
        $httpClient->setConfig([
            'timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => Configuration::TIMEOUT_TBXIMPORT,
        ]);
        $httpClient->setRawData(json_encode($serviceData), 'application/json');
        $response = $this->sendRequest($httpClient, $httpClient::POST);
        $decodedResponse = $this->decodeServiceResult($logger, $response);
        if ($this->wasSuccessfull() && $decodedResponse !== null) {
            return $decodedResponse;
        }

        if ($this->isEmptyResponse($response)) {
            $error = 'NO_RESPONSE';
        } else {
            $error = $decodedResponse->error ?? 'UNKNOWN';
        }

        $logData = $this->getLogExtraData($response, $httpClient, $serviceData);

        throw match ($error) {
            //NoResponseException → wrong name for NO_TBX_FILE and TBX_ID_ERROR but correct semantic, triggers delay
            'NO_TBX_FILE', 'NO_RESPONSE', 'TBX_ID_ERROR' => new NoResponseException('E1719', $logData),
            //E1117: Could not load TBX into TermTagger: TermTagger HTTP result was not successful!
            'TERM_TAG_TBX_PROBLEM' => new OpenException('E1117', $logData),
            //E1118: Could not load TBX into TermTagger: TermTagger HTTP result could not be decoded!
            // not really happened anymore
            default => new OpenException('E1118', $logData),
        };
    }

    /**
     * Unloads (Deletes) a TBX in the Termtagger
     * This is useful, to free up memory & lowers the probability, the Termtagger runs into memory-leaks
     * @throws DownException
     * @throws NoResponseException
     * @throws RequestException
     * @throws TimeOutException
     * @throws Zend_Http_Client_Exception
     * @throws ReflectionException
     */
    public function unloadTBX(string $url, string $tbxHash): void
    {
        if (empty($tbxHash)) {
            return;
        }
        $httpClient = $this->getHttpClient($url . self::TBX_BASE_URL_PATH . $tbxHash, $tbxHash);
        $httpClient->setConfig([
            'timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => self::DEFAULT_TAG_TIMEOUT,
        ]);
        $this->applyPersistentConnections($httpClient);

        $this->sendRequest($httpClient, $httpClient::DELETE);
    }

    /**
     * Requests the termtagger with the given service-url and the passed segment-data (wich has to be be encoded)
     * @throws DownException
     * @throws NoResponseException
     * @throws RequestException
     * @throws TimeOutException
     * @throws Zend_Http_Client_Exception
     * @throws ReflectionException
     */
    public function tagTerms(
        string $serviceUrl,
        ServiceData $serviceData,
        ZfExtended_Logger $logger,
        int $requestTimeout
    ): ?stdClass {
        //test term tagger errors, start a dummy netcat server in the commandline: nc -l -p 8080
        // if the request was received in the commandline, just kill nc to simulate a termtagger crash.
        //$serviceUrl = 'http://michgibtesdefinitivnichtalsdomain.com:8080'; // this is the nc dummy URL then.
        //$serviceUrl = 'http://localhost:8080'; // this is the nc dummy URL then.
        $httpClient = $this->getHttpClient($serviceUrl . '/termTagger/termTag/', $serviceData->tbxFile);
        $httpClient->setRawData(json_encode($serviceData), 'application/json');
        $httpClient->setConfig([
            'timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => $requestTimeout,
        ]);
        $this->applyPersistentConnections($httpClient);
        $httpResponse = $this->sendRequest($httpClient, $httpClient::POST);
        $response = $this->decodeServiceResult($logger, $httpResponse);
        if ($this->isEmptyResponse($httpResponse) || ! $response) {
            //processing terms from the TermTagger result could not be decoded.
            throw new RequestException(
                'E1121',
                $this->getLogExtraData($httpResponse, $httpClient, $serviceData)
            );
        }

        return $response;
    }

    private function isEmptyResponse(?Zend_Http_Response $response): bool
    {
        //if we use a dedicated proxy before the termtaggers,
        // we may get a 502 or 429 if the termtagger is not responding, so we fake that here
        return $response === null || in_array((int) $response->getStatus(), [429, 502], true);
    }

    /**
     * send request method with unified logging
     * @return Zend_Http_Response
     * @throws DownException
     * @throws NoResponseException
     * @throws RequestException
     * @throws TimeOutException
     */
    private function sendRequest(Zend_Http_Client $client, $method)
    {
        $this->lastStatus = false;
        $start = microtime(true);

        $extraData = [
            'httpMethod' => $method,
            'termTaggerUrl' => $client->getUri(true),
        ];

        try {
            // use to trigger a NoResponse Exception to trigger a worker-delay
            // throw new ZfExtended_Zendoverwrites_Http_Exception_NoResponse('E1130', $extraData);

            $result = $client->request($method);
            if (ZfExtended_Debug::hasLevel('plugin', 'TermTagger')) {
                $rand = rand();
                error_log("TermTagger Duration (id: $rand): " . (microtime(true) - $start) . 's');
                error_log("TermTagger Request (id: $rand): " . print_r($client->getLastRequest(), 1));
                error_log("TermTagger Answer (to id $rand): " . print_r($result->getRawBody(), 1));
            }
            $this->lastStatus = $result->getStatus();

            return $result;
        } catch (ZfExtended_Zendoverwrites_Http_Exception_TimeOut $httpException) {
            //if the error is one of the following, we have a request timeout
            //ERROR Zend_Http_Client_Adapter_Exception: E9999 - Read timed out after 10 seconds
            throw new TimeOutException('E1240', $extraData, $httpException);
        } catch (ZfExtended_Zendoverwrites_Http_Exception_Down $httpException) {
            //if the error is one of the following, we have a connection problem
            //ERROR Zend_Http_Client_Adapter_Exception: E9999 - Unable to Connect to tcp://localhost:8080. Error #111: Connection refused
            //ERROR Zend_Http_Client_Adapter_Exception: E9999 - Unable to Connect to tcp://michgibtesdefinitivnichtalsdomain.com:8080. Error #0: php_network_getaddresses: getaddrinfo failed: Name or service not known
            //the following IP is not routed, so it trigers a timeout on connection connect, which must result in "Unable to connect" too and not in a request timeout below
            //ERROR Zend_Http_Client_Adapter_Exception: E9999 - Unable to Connect to tcp://10.255.255.1:8080. Error #111: Connection refused
            throw new DownException('E1129', $extraData, $httpException);
        } catch (ZfExtended_Zendoverwrites_Http_Exception_NoResponse $httpException) {
            // This error points to the termtagger not responding due to too many requests
            throw new NoResponseException('E1130', $extraData, $httpException);
        } catch (Exception $httpException) {
            //Error in communication with TermTagger
            throw new RequestException('E1119', $extraData, $httpException);
        }
    }

    /**
     * instances a Zend_Http_Client Object, sets the desired URI and returns it
     * @throws Zend_Http_Client_Exception
     * @throws ReflectionException
     */
    private function getHttpClient(string $uri, string $tbxHash = null): Zend_Http_Client
    {
        $client = ZfExtended_Factory::get(Zend_Http_Client::class);
        $client->setUri($uri);
        if ($tbxHash !== null) {
            $client->setHeaders('x-tbxid', $tbxHash);
        }

        return $client;
    }

    /**
     * decodes the TermTagger JSON and logs an error if data can not be processed
     */
    private function decodeServiceResult(ZfExtended_Logger $logger, Zend_Http_Response $result = null): ?stdClass
    {
        if (empty($result)) {
            return null;
        }
        $data = json_decode($result->getBody());
        if (! empty($data)) {
            if (! empty($data->error)) {
                $logger->error('E1133', 'TermTagger reports error "{error}".', [
                    'error' => print_r($data, 1),
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

    public function setPersistentConnections(bool $persistent): void
    {
        $this->persistentConnections = $persistent;
    }

    /**
     * CRUCIAL: with persistent connections we need DNS Pinning - talk to the concrete IPs
     *  instead the hostname (providing different IPs) - but this must come from outside!
     * TODO not tested if we could use all calls with persistent connections - then this should go into getHttpClient
     * @throws Zend_Http_Client_Exception
     */
    private function applyPersistentConnections(Zend_Http_Client $client): void
    {
        if ($this->persistentConnections) {
            $client->setConfig([
                'keepalive' => true,
                'persistent' => true,
            ]);
            $client->setHeaders('Connection', 'keep-alive');
        }
    }

    private function getLogExtraData(
        Zend_Http_Response|null $response,
        Zend_Http_Client $httpClient,
        stdClass|ServiceData $serviceData
    ): array {
        if ($response === null) {
            $logBody = 'Response was null';
        } elseif (mb_strlen($response->getBody()) > 1024) {
            $logBody = mb_substr($response->getBody(), 0, 1024) . ' > CUT AFTER 1024 CHARS';
        } else {
            $logBody = $response->getBody();
        }

        if ($serviceData instanceof stdClass && mb_strlen($serviceData->tbxdata) > 1024) {
            $serviceData->tbxdata = mb_substr($serviceData->tbxdata, 0, 1024) . ' > CUT AFTER 1024 CHARS';
        }

        return [
            'httpStatus' => $this->getLastStatus(),
            'termTaggerUrl' => $httpClient->getUri(true),
            'plainServerResponse' => $logBody,
            'plainServerResponseHeaders' => $response?->getHeaders(),
            'requestedData' => $serviceData,
        ];
    }
}

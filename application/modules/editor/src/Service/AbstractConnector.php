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

namespace MittagQI\Translate5\Service;

use MittagQI\ZfExtended\Zendoverwrites\Http\JsonClient;
use MittagQI\ZfExtended\Zendoverwrites\Http\JsonResponse;
use Throwable;
use Zend_Http_Client_Exception;
use ZfExtended_DbConfig_Type_CoreTypes as CoreTypes;
use ZfExtended_Exception;
use ZfExtended_Utils;
use ZfExtended_Zendoverwrites_Http_Exception_InvalidResponse;

/**
 * Connects to a service that needs Authentication
 * Provides a Http Client and handles the result status
 * Accompanies an AbstractAuthenticatedService
 * Usually this connects to a Json service
 */
abstract class AbstractConnector
{
    /**
     * The charset-header to use
     */
    public const ACCEPT_CHARSET = 'UTF-8';

    /**
     * The accept-header to use
     */
    public const ACCEPT_HEADER = 'application/json; charset=utf-8';

    /**
     * The default matchrate used if not configurable
     */
    public const DEFAULT_MATCHRATE = 70;

    /**
     * The base Endpint of the API
     * This can be used to add a general path prefixed for all requests
     * IMPORTANT: This path-fragment must be terminated by '/' on both sites
     */
    public const BASE_ENDPOINT = '/';

    /**
     * An assoc array that at least represents the config-value of the authentication
     * The layout is expected to be Zf_configuration.name => Zf_configuration.type / 'runtimeOptions.plugins.MyService.authKey' => 'string'
     */
    protected array $connectorConfig;

    /**
     * This is the config representing the authentication
     * It MUST be present as key in ::$connectorConfig and must be defined in inheriting classes
     */
    protected string $authorizationConfigKey;

    /**
     * This is the config representing the default matchrate
     * It MUST be present as key in ::$connectorConfig if it is set (otherwise the static default matchrate will be taken)
     */
    protected string $matchrateConfigKey;

    /**
     * The Name of the Authorization Header field
     */
    protected string $authorizationHeaderName = 'Authorization';

    /**
     * If the Auth is a Bearer-Authentication, set this to 'Bearer'
     */
    protected string $authorizationHeaderBearer = '';

    protected AbstractAuthenticatedService $service;

    protected string $url;

    /**
     * may hold additional props to configure the JsonClient
     */
    protected array $httpClientConfig = [];

    public function __construct(AbstractAuthenticatedService $service, string $url = null)
    {
        $this->service = $service;
        $url ??= $service->getServiceUrl();

        if ($url === null) {
            throw new ZfExtended_Exception(get_class($this) . ' cannot be instaniated without a proper service-url');
        }

        $this->url = rtrim($url, '/');
    }

    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs) and authorization
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_Exception
     */
    public function createClient(string $apiEndpointPath = '', array $clientConfig = []): JsonClient
    {
        $clientConfig = array_merge($this->httpClientConfig, $clientConfig);
        $client = new JsonClient(ZfExtended_Utils::combinePathes($this->url, static::BASE_ENDPOINT, $apiEndpointPath), $clientConfig);
        $client->setHeaders('Accept-charset', static::ACCEPT_CHARSET);
        $client->setHeaders('Accept', static::ACCEPT_HEADER);
        $this->addAuthorization($client);

        return $client;
    }

    public function getService(): AbstractAuthenticatedService
    {
        return $this->service;
    }

    /**
     * The base URL of the service
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * The base URL for all API-calls
     */
    public function getApiUrl(): string
    {
        return rtrim($this->url . static::BASE_ENDPOINT, '/');
    }

    /**
     * Retrieves the authorization key, usually a string
     * @throws ZfExtended_Exception
     */
    protected function getAuthorizationKey(): mixed
    {
        return $this->service->getConfigValue($this->authorizationConfigKey, $this->connectorConfig[$this->authorizationConfigKey]);
    }

    /**
     * Retrieves the default matchrate (needed by language-resources)
     * @throws ZfExtended_Exception
     */
    public function getDefaultMatchrate(): int
    {
        if (isset($this->matchrateConfigKey)) {
            return intval($this->service->getConfigValue($this->matchrateConfigKey, CoreTypes::TYPE_INTEGER));
        }

        return static::DEFAULT_MATCHRATE;
    }

    /**
     * Retrieves, if the connector is configured
     */
    public function isConfigured(): bool
    {
        return $this->service->hasConfigurations(array_keys($this->connectorConfig));
    }

    /**
     * Checks, if everything is configured & the Connector can successfully connect & request the API
     * Does not throw any exceptions
     */
    public function isAvailable(): bool
    {
        try {
            if ($this->service->isConfigured() && $this->isConfigured()) {
                $response = $this->createStatusResponse();

                return ! $response->hasError();
            }

            return false;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Base implementation for a test-request
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Zendoverwrites_Http_Exception_InvalidResponse
     */
    protected function createStatusResponse(): JsonResponse
    {
        $client = $this->createClient();

        return $client->requestJson();
    }

    /**
     * Adds the authorization to the client
     * This usually must be overwritten in extending classes
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_Exception
     */
    protected function addAuthorization(JsonClient $client): void
    {
        $bearer = empty($this->authorizationHeaderBearer) ? '' : $this->authorizationHeaderBearer . ' ';
        $client->setHeaders($this->authorizationHeaderName, $bearer . $this->getAuthorizationKey());
    }
}

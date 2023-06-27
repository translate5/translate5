<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Service;

use editor_Services_Connector_Exception;
use MittagQI\ZfExtended\Zendoverwrites\Http\JsonClient;
use MittagQI\ZfExtended\Zendoverwrites\Http\JsonResponse;
use stdClass;
use Throwable;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use ZfExtended_Debug;
use ZfExtended_ErrorCodeException;
use ZfExtended_Exception;
use ZfExtended_Utils;
use ZfExtended_Zendoverwrites_Http_Exception_Down;
use ZfExtended_Zendoverwrites_Http_Exception_InvalidResponse;
use ZfExtended_Zendoverwrites_Http_Exception_NoResponse;
use ZfExtended_Zendoverwrites_Http_Exception_TimeOut;

/**
 * Represents a Single Request on a Service
 * Must be Instantiated with a AbstractConnector
 * This class creates a throwaway-instance for a single request and provides any persistance nor the capabilities to fetch multiple times
 * Note, that it will be possible to add data and params at the same time
 */
abstract class AbstractRequest
{
    /**
     * Configured via inheritance
     */
    const ENDPOINT = 'MUST BE DEFINED IN INHERITING CLASSES';

    /**
     * Configured via inheritance
     * If an entity-ID is configured, this can add additional pathes like /ENDPOINT/entityId/ENTITY_ENDPOINT
     */
    const ENTITY_ENDPOINT = '';

    /**
     * Configured via inheritance
     */
    const METHOD = Zend_Http_Client::GET;

    /**
     * Configured via inheritance
     * May holds additional configs for the request
     * @var array
     */
    protected array $config = [];

    /**
     * Configured via inheritance
     * This Exception will be thrown in case of connection errors
     * It MUST inherit from ZfExtended_ErrorCodeException
     * @var string
     */
    protected string $exceptionClass = editor_Services_Connector_Exception::class;

    /**
     * Configured via inheritance
     * If set, an exception will be thrown if the entityId is missing
     * @var bool
     */
    protected bool $entityIdRequired = false;

    /**
     * Optionally configured via inheritance
     * If set, an Exception will be thrown if the result format does not match
     * The possible values are any|string|object|array
     * TODO FIXME: as soon as we use PHP 8.1 turn this into an Enum
     * @var string
     */
    protected string $expectedDataFormat = 'any';

    /**
     * Optionally configured via inheritance
     * If set, an exception will be thrown if the result is empty
     * @var bool
     */
    protected bool $allowEmptyResult = true;

    /**
     * optionally Configured via inheritance
     * Holds the params to be sent / being sent
     * Th predefine params, they can be given in this prop,
     * to ensure, params MUST be set when using the request, they can be added with a value of null. null as a param-value will lead to an exception (whereas the empty string or int 0 is allowed)
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Holds the data that may is sent to the server
     * @var mixed|null
     */
    protected mixed $data = null;

    /**
     * A suffix (typically an entity ID) that will be appended to the Endpoint
     * @var string
     */
    protected string $entityId = '';

    /**
     * Holds the extra-data sent when an exception occurs
     * @var array
     */
    protected array $extraData;

    /**
     * Holds a custom service name
     * @var string
     */
    protected string $serviceName;

    /**
     * Holds the fetched result
     * @var JsonResponse
     */
    protected JsonResponse $response;

    /**
     * @var bool
     */
    protected bool $doDebug = false;

    /**
     * @var bool: keeps the fetched state
     */
    private bool $fetched = false;

    /**
     * @param AbstractConnector $connector : The connector to fetch the request
     * @param bool $isInstantTranslate : May leads to different params when fetching the data or different exception handling
     */
    public function __construct(protected AbstractConnector $connector, protected bool $isInstantTranslate = false)
    {
        $this->doDebug = ZfExtended_Debug::hasLevel('core', 'ServiceRequest');
    }

    /**
     * @param mixed $entityId
     * @return $this
     * @throws ZfExtended_Exception
     */
    public function setEntityId(mixed $entityId): AbstractRequest
    {
        $this->entityId = strval($entityId);
        if (strlen($this->entityId) < 1) {
            throw new ZfExtended_Exception('Service-Request entityId cannot be empty if set');
        }
        return $this;
    }

    /**
     * Sets the data to be sent with the request. This will be sent as raw json encoded string
     * The data is expected to be unencoded
     * @param mixed $data
     * @return $this
     */
    public function setData(mixed $data): AbstractRequest
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Adds/Sets multiple params as assoc array
     * @param array $params
     * @return $this
     * @throws ZfExtended_Exception
     */
    public function setParams(array $params): AbstractRequest
    {
        foreach ($params as $name => $value) {
            $this->addParam($name, $value);
        }
        return $this;
    }

    /**
     * Adds/Sets a single param
     * @param string $name
     * @param mixed $value
     * @return $this
     * @throws ZfExtended_Exception
     */
    public function addParam(string $name, mixed $value): AbstractRequest
    {
        if ($value === null) {
            throw new ZfExtended_Exception('Service-Request Parameters must not be null');
        }
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Fetches the request. After this call, a Response will be available, otherwise a non-processed exception is thrown
     * Can be called explicitly to provide a customized service-name and/or extra-data for potential exceptions
     * @param string|null $serviceName
     * @param array $extraData
     * @return $this
     * @throws Throwable
     * @throws ZfExtended_Exception
     */
    final public function fetch(string $serviceName = null, array $extraData = []): AbstractRequest
    {
        if ($this->fetched) {
            throw new ZfExtended_Exception('Service-Request can not be fetched multiple times');
        }
        $this->fetched = true;

        if ($this->entityIdRequired && $this->entityId === '') {
            throw new ZfExtended_Exception('The Service-Request needs an entityId to be set');
        }

        if ($serviceName !== null) {
            $this->serviceName = $serviceName;
        }
        $this->extraData = $extraData;

        // create client, request data, catch any exception & process it.
        // We also transform some legacy exceptions to proper connector exceptions
        $exception = null;
        try {
            // crate client
            $client = $this->createClient();
            // add params
            $params = $this->createParams();
            if (!empty($params)) {
                if (static::METHOD === Zend_Http_Client::POST) {
                    $client->setParameterPost($params);
                } else {
                    $client->setParameterGet($params);
                }
            }
            if ($this->doDebug) {
                $debug =
                    "\n----------\n"
                    . 'ServiceRequest by ' . get_class($this) . '::fetch to: ' . $client->getUri(true) . "\n    method: " . static::METHOD;
                if (!empty($params)) {
                    $debug .= "\n    params: ";
                    foreach ($params as $param => $value) {
                        $debug .= "\n        $param: " . (is_array($value) ? implode(', ', $value) : $value);
                    }
                }
                if (!empty($this->data)) {
                    $debug .= "\n    data: " . json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }
                error_log($debug);
            }
            // request JSON
            $this->response = $client->requestJson(static::METHOD, $this->data);

        } catch (ZfExtended_Zendoverwrites_Http_Exception_Down $e) {

            // E1311 Could not connect to {service}: server not reachable
            $exception = $this->castException($e, 'E1311');

        } catch (ZfExtended_Zendoverwrites_Http_Exception_TimeOut $e) {

            //'E1312' => 'Could not connect to {service}: timeuse ZfExtended_Zendoverwrites_Http_Exception;out on connection to server',
            $exception = $this->castException($e, 'E1312');

        } catch (ZfExtended_Zendoverwrites_Http_Exception_NoResponse $e) {
            //'E1370' => 'Empty response from {service}'
            $exception = $this->castException($e, 'E1370');

        } catch (ZfExtended_Zendoverwrites_Http_Exception_InvalidResponse $e) {

            // E1537: Request to service {service}: Invalid response.
            $exception = $this->castException($e, 'E1537');

        } catch (ZfExtended_ErrorCodeException $e) {
            // any other Error-coded exception will be converted to "our" class
            $exception = $this->castException($e, $e->getErrorCode());

        } catch (Throwable $e) {
            // any unknow Exceptions will become a E1313 with the original message as error
            // only when no response was created this is likely a coding-error and we want to see the original exception
            $exception = isset($this->response) ? $this->castException($e, 'E1313', $e->getMessage()) : $e;
        }
        // No response: unrecoverable service error that can not be intercepted
        if (!isset($this->response)) {
            if ($this->doDebug) {
                error_log("\n    FAILED WITHOUT RESPONSE: " . $this->traceException($exception));
                error_log("\n==========\n");
            }
            throw $exception;
        }
        // response had error
        if (!$exception && $this->response->hasError()) {
            $exception = $this->createException('E1313');
        }
        // empty result
        if (!$exception && !$this->allowEmptyResult && $this->response->isEmpty()) {
            $exception = $this->createException('E1370');
        }
        // check response data type
        if (!$exception && $this->expectedDataFormat !== 'any') {
            if ($this->expectedDataFormat === 'object' && !$this->response->hasDataObject()) {
                $exception = $this->createException('E1537', 'Response was no object');
            } else if ($this->expectedDataFormat === 'array' && !$this->response->hasDataArray()) {
                $exception = $this->createException('E1537', 'Response was no array');
            } else if ($this->expectedDataFormat === 'string' && !is_string($this->response->getData())) {
                $exception = $this->createException('E1537', 'Response was no string');
            }
        }
        // additional validations of the result
        if (!$exception) {
            $exception = $this->validateResult();
        }
        // processing in extending classes. this may "absorbs" the exception maybe just created
        if ($exception != null) {
            $exception = $this->interceptException($exception);
        }
        if ($this->doDebug) {
            if ($exception != null) {
                error_log("\n    FAILED: " . $this->traceException($exception));
            }
            if (!$this->response->hasData()) {
                error_log("\n    EMPTY RESPONSE!");
            } else if ($this->response->hasDataObject() || $this->response->hasDataArray()) {
                error_log("\n    RESPONSE:\n" . json_encode($this->response->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
            } else {
                $data = $this->response->getData();
                error_log("\n    RESPONSE: " . (is_string($data) ? '"' . $data . '"' : strval($data)));
            }
            error_log("\n==========\n");
        }
        // throw the transformed exception
        if ($exception != null) {
            throw $exception;
        }
        return $this;
    }

    /**
     * Retrieves if a fetch was successful
     * Note, that this not neccessarily returned any data
     * @return bool
     * @throws Throwable
     * @throws ZfExtended_Exception
     */
    final public function wasSuccessful(): bool
    {
        $this->_fetch();
        return !$this->response->hasError();
    }

    /**
     * Checks wether the response contained data
     * @return bool
     * @throws Throwable
     * @throws ZfExtended_Exception
     */
    final public function hasResult(): bool
    {
        $this->_fetch();
        return $this->response->hasData();
    }

    /**
     * @return mixed
     * @throws Throwable
     * @throws ZfExtended_Exception
     */
    final public function getResult(): mixed
    {
        $this->_fetch();
        if ($this->response->hasData()) {
            return $this->processResult($this->response->getData());
        }
        return null;
    }

    /**
     * @return JsonResponse
     * @throws Throwable
     * @throws ZfExtended_Exception
     */
    final public function getResponse(): JsonResponse
    {
        $this->_fetch();
        return $this->response;
    }

    /**
     * Creates a client from our connector
     * @return JsonClient
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_Exception
     */
    protected function createClient(): JsonClient
    {
        $client = $this->connector->createClient($this->createEndpoint());
        if (!empty($this->config)) {
            $client->setConfig($this->config);
        }
        return $client;
    }

    /**
     * Creates the endpoint to instantiate the client with
     * @return string
     */
    protected function createEndpoint(): string
    {
        if ($this->entityId !== '') {
            return ZfExtended_Utils::combinePathes('/', static::ENDPOINT, $this->entityId, static::ENTITY_ENDPOINT);
        }
        return (strlen(static::ENDPOINT) === 0) ? '' : ZfExtended_Utils::combinePathes('/', static::ENDPOINT);
    }

    /**
     * Creates the parameters, validates them against the existing that may need to be set
     * @return array
     * @throws ZfExtended_Exception
     */
    protected function createParams(): array
    {
        // validate
        foreach ($this->params as $name => $value) {
            if ($value === null) {
                // not set required params lead to an exception
                throw $this->createException('E1536', null, ['paramname' => $name]);
            }
        }
        return $this->params;
    }

    /**
     * Can be used to implement result transformations
     * @param mixed $result
     * @return mixed
     */
    protected function processResult(mixed $result): mixed
    {
        return $result;
    }

    /**
     * can be used to additionally validate the result for further errors e.g. that are hidden in the JSON response
     * If an Exception should be thrown, it must be returned
     * For Inheritance low -> heigh, call the parent function first !
     * @return ZfExtended_ErrorCodeException|null
     */
    protected function validateResult(): ?ZfExtended_ErrorCodeException
    {
        return null;
    }

    /**
     * Can be used to intercept any thrown exception if a response was received
     * The passed exception will be of the type defined in $this->exceptionClass !! This simply cannot be annotated nor typed ... so it is "at least" a ZfExtended_ErrorCodeException
     * If no exception shall be thrown (the exception should be intercepted), null has to be returned
     * @param ZfExtended_ErrorCodeException $exception
     * @return ZfExtended_ErrorCodeException|null
     */
    protected function interceptException(ZfExtended_ErrorCodeException $exception): ?ZfExtended_ErrorCodeException
    {
        return $exception;
    }

    /**
     * Helper to create an exception of our desired class
     * @param string $ecode
     * @param string|null $errorMessage
     * @param array $extraData
     * @return ZfExtended_ErrorCodeException
     */
    final protected function createException(string $ecode, string $errorMessage = null, array $extraData = []): ZfExtended_ErrorCodeException
    {
        return new $this->exceptionClass($ecode, $this->createExtraData($errorMessage, $extraData));
    }

    /**
     * Helper to cast an exception to a different type / ecode
     * This will change Code & Message & add data for all Error-Coded Exceptions,
     * otherwise it creates a new ZfExtended_ErrorCodeException with the passed Throwable added as $previous
     * @param Throwable $exception
     * @param string $newErrorCode
     * @param string|null $errorMessage
     * @param array $extraData
     * @return ZfExtended_ErrorCodeException
     */
    final protected function castException(Throwable $exception, string $newEcode, string $errorMessage = null): ZfExtended_ErrorCodeException
    {
        // do not cast for no reason
        if (get_class($exception) === $this->exceptionClass && $exception->getErrorCode() === $newEcode) {
            return $exception;
        }
        // a "real" cast only if the thrown exception already is error-coded
        if (is_a($exception, ZfExtended_ErrorCodeException::class)) {
            /* @var ZfExtended_ErrorCodeException $exception */
            // we merge the "current" extra-data over the "old" to have the chance to override stuff. Therefore we cannot use ->createException
            $extraData = array_merge($exception->getErrors(), $this->createExtraData($errorMessage));
            return new $this->exceptionClass($newEcode, $extraData);
        }
        // make sure $previous always represents the first ...
        $previous = ($exception->getPrevious() === null) ? $exception : $exception->getPrevious();
        return new $this->exceptionClass($newEcode, $this->createExtraData($errorMessage), $previous);
    }

    /**
     * Creates the extra-data to be included in any exception
     * @param string|null $message
     * @param array $extraData
     * @return array
     */
    protected function createExtraData(string $message = null, array $extraData = []): array
    {
        $extraData = array_merge($this->extraData, $extraData);
        $extraData['service'] = $this->getServiceName();
        if ($message !== null) {
            $extraData['message'] = $message;
        }
        if (isset($this->response)) {
            $extraData['error'] = $this->createResponseError($this->response);
        }
        return $extraData;
    }

    /**
     * Creates the error-object from the response
     * @param JsonResponse $response
     * @return stdClass
     */
    protected function createResponseError(JsonResponse $response): stdClass
    {
        if ($response->hasError()) {
            return $response->getError();
        }
        return $response->createError();
    }

    /**
     * Retrieves the service-name to be included in any Exception
     * @return string
     */
    protected function getServiceName(): string
    {
        if (isset($this->serviceName)) {
            return $this->serviceName;
        }
        return $this->connector->getService()->getName();
    }

    /**
     * Used to fetch the request if not yet fetched explicitly
     * @return void
     * @throws Throwable
     * @throws ZfExtended_Exception
     */
    private function _fetch(): void
    {
        if (!$this->fetched) {
            $this->fetch();
        }
    }

    /**
     * Traces the passed exception for debug-output
     * @param Throwable $exception
     * @return string
     */
    private function traceException(Throwable $exception): string
    {
        if (is_a($exception, ZfExtended_ErrorCodeException::class)) {
            $trace = $exception->getErrorCode() . ' | ' . $exception->getMessage();
            $errors = $exception->getErrors();
            if (!empty($errors)) {
                foreach ($errors as $error => $value) {
                    $trace .= "\n        $error: ";
                    if (!is_object($value)) {
                        $trace .= strval($value);
                    } else if ($value instanceof stdClass) {
                        $trace .= json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } else {
                        $trace .= '(Object) ' . get_class($value);
                    }
                }
            }
            return $trace;
        }
        return $exception->getMessage();
    }
}
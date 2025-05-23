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

use MittagQI\Translate5\Integration\Google\Enum\Format;

class editor_Services_Google_Connector extends editor_Services_Connector_Abstract
{
    use editor_Services_Connector_BatchTrait;

    protected const TAG_HANDLER_CONFIG_PART = 'google';

    protected editor_Services_Google_ApiWrapper $api;

    /**
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct()
    {
        parent::__construct();
        $this->defaultMatchRate = $this->config->runtimeOptions->LanguageResources->google->matchrate;
        $this->batchQueryBuffer = 50;

        editor_Services_Connector_Exception::addCodes([
            'E1319' => '{service} authorization failed. Please supply a valid API Key.',
            'E1320' => '{service} daily limit exceeded.',
        ]);

        ZfExtended_Logger::addDuplicatesByMessage('E1319', 'E1320');
    }

    public function connectTo(
        editor_Models_LanguageResources_LanguageResource $languageResource,
        $sourceLang,
        $targetLang,
        $config = null
    ) {
        parent::connectTo($languageResource, $sourceLang, $targetLang, $config);
        $this->api = ZfExtended_Factory::get('editor_Services_Google_ApiWrapper', [$languageResource->getResource()]);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment)
    {
        $queryString = $this->getQueryStringAndSetAsDefault($segment);
        $success = $this->api->translate(
            $this->tagHandler->prepareQuery($queryString),
            $this->languageResource->getSourceLangCode(),
            $this->languageResource->getTargetLangCode(),
            $this->getFormat()
        );

        if ($success === false) {
            throw $this->createConnectorException();
        }

        $result = $this->api->getResult();

        if (empty($result)) {
            return $this->resultList;
        }

        $this->resultList->addResult(
            $this->tagHandler->restoreInResult($result['text'] ?? ""),
            $this->defaultMatchRate
        );

        return $this->resultList;
    }

    /**
     * Search the resource for available translation. Where the source text is in resource source language and the
     * received results are in the resource target language
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString)
    {
        if (empty($searchString) && $searchString !== "0") {
            return $this->resultList;
        }

        $success = $this->api->translate(
            $searchString,
            $this->languageResource->getSourceLangCode(),
            $this->languageResource->getTargetLangCode(),
            $this->getFormat()
        );

        if ($success === false) {
            throw $this->createConnectorException();
        }

        $result = $this->api->getResult();

        if (empty($result)) {
            return $this->resultList;
        }

        $this->resultList->addResult($result['text'] ?? "", $this->defaultMatchRate);

        return $this->resultList;
    }

    public function languages(): array
    {
        //if empty api wrapper
        if (empty($this->api)) {
            $this->api = ZfExtended_Factory::get('editor_Services_Google_ApiWrapper', [$this->resource]);
        }

        return $this->api->getLanguages();
    }

    /**
     * @see editor_Services_Connector_BatchTrait::batchSearch()
     */
    protected function batchSearch(array $queryStrings, string $sourceLang, string $targetLang): bool
    {
        $success = $this->api->translateBatch($queryStrings, $sourceLang, $targetLang, $this->getFormat());

        if ($success === false) {
            throw $this->createConnectorException();
        }

        return $success;
    }

    /**
     * @see editor_Services_Connector_BatchTrait::processBatchResult()
     */
    protected function processBatchResult($segmentResults)
    {
        $this->resultList->addResult(
            $this->tagHandler->restoreInResult($segmentResults['text'] ?? ''),
            $this->defaultMatchRate
        );
    }

    protected function getResponseData(): mixed
    {
        return $this->api->getResult();
    }

    /**
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(
        editor_Models_LanguageResources_Resource $resource,
        editor_Models_LanguageResources_LanguageResource $languageResource = null,
    ): string {
        $this->lastStatusInfo = '';
        $languages = $this->languages();
        if (empty($languages)) {
            //we also log below errors
            $e = $this->createConnectorException();
            $this->logger->exception($e);

            if ($e->getErrorCode() === 'E1319') {
                $this->lastStatusInfo = 'Anmeldung bei Google Translate fehlgeschlagen. Bitte verwenden Sie einen gültigen API Key.';

                return self::STATUS_NOVALIDLICENSE;
            }
            if ($e->getErrorCode() === 'E1320') {
                $this->lastStatusInfo = 'Das Nutzungslimit wurde erreicht.';

                return self::STATUS_QUOTA_EXCEEDED;
            }
            $this->lastStatusInfo = 'Keine Verbindung zu Google Translate!';
        } else {
            return self::STATUS_AVAILABLE;
        }

        return self::STATUS_NOCONNECTION;
    }

    /**
     * Creates a service connector exception
     */
    protected function createConnectorException(): editor_Services_Connector_Exception
    {
        $badRequestException = $this->api->getError();
        $msg = $badRequestException->getMessage();
        if (stripos($msg, 'Daily Limit Exceeded') !== false) {
            $ecode = 'E1320'; //'Google Translate quota exceeded. The character limit has been reached.',
        } elseif (stripos($msg, 'API key not valid. Please pass a valid API key.') !== false) {
            $ecode = 'E1319'; //'Google Translate authorization failed. Please supply a valid API Key.',
        } elseif (strpos($msg, 'cURL error') !== false) {
            $ecode = 'E1311'; //server not reachable
        } else {
            $ecode = 'E1313'; //'The queried {service} returns an error: {error}'
        }
        $data = [
            'service' => $this->getResource()->getName(),
            'languageResource' => $this->languageResource ?? '',
            'error' => $msg,
        ];

        return new editor_Services_Connector_Exception($ecode, $data, $badRequestException);
    }

    protected function getResourceName(): string
    {
        return 'Google Translate';
    }

    protected function getFormat(): Format
    {
        $format = (string) $this->config->runtimeOptions->LanguageResources->google->format;

        return Format::tryFrom($format) ?? Format::Html;
    }
}

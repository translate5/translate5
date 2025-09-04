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

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource;
use editor_Services_Connector_Exception;
use editor_Services_Connector_TagHandler_Abstract as TagHandler;
use editor_Services_OpenTM2_Connector;
use editor_Services_OpenTM2_HttpApi;
use editor_Services_ServiceResult;
use MittagQI\Translate5\ContentProtection\T5memory\T5NTag;
use MittagQI\Translate5\LanguageResource\Adapter\TagsProcessing\TagHandlerFactory;
use MittagQI\Translate5\T5Memory\Api\Response\Response;
use MittagQI\Translate5\T5Memory\ContentProtection\QueryStringGuesser;
use MittagQI\Translate5\T5Memory\DTO\FuzzyMatchDTO;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Logger;

class FuzzySearchService
{
    public function __construct(
        private readonly ReorganizeService $reorganizeService,
        private readonly RetryService $retryService,
        private readonly TagHandlerFactory $tagHandlerFactory,
        private readonly ZfExtended_Logger $logger,
        private readonly QueryStringGuesser $queryStringGuesser,
    ) {
    }

    public static function create(): self
    {
        return new self(
            ReorganizeService::create(),
            RetryService::create(),
            TagHandlerFactory::create(),
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.fuzzy-search'),
            QueryStringGuesser::create(),
        );
    }

    public function query(
        editor_Models_LanguageResources_LanguageResource $languageResource,
        string $queryString,
        string $context,
        string $fileName,
        callable $calculateMatchRate,
        Zend_Config $config,
        bool $isInternalFuzzy,
    ): editor_Services_ServiceResult {
        $resultList = new editor_Services_ServiceResult();
        $resultList->setLanguageResource($languageResource);

        //if source is empty, t5memory will return an error, therefore we just return an empty list
        if (empty($queryString) && $queryString !== '0') {
            return $resultList;
        }

        if ($languageResource->isConversionStarted()) {
            return $resultList;
        }

        //Although we take the source fields from the t5memory answer below
        // we have to set the default source here to fill the be added internal tags
        $resultList->setDefaultSource($queryString);

        $matches = $this->queryTms(
            $languageResource,
            $queryString,
            $context,
            $fileName,
            $calculateMatchRate,
            $config,
            $isInternalFuzzy,
        );

        foreach ($matches as $match) {
            $resultList->addResult(
                $match->target,
                $match->matchrate,
                $match->metaData,
                $match->rawTarget,
                $match->timestamp
            );
            $resultList->setSource($match->source);
        }

        return $resultList;
    }

    /**
     * @return iterable<FuzzyMatchDTO>
     */
    private function queryTms(
        editor_Models_LanguageResources_LanguageResource $languageResource,
        string $queryString,
        string $context,
        string $fileName,
        callable $calculateMatchRate,
        Zend_Config $config,
        bool $isInternalFuzzy,
    ): iterable {
        $tagHandler = $this->tagHandlerFactory->createTagHandler(
            editor_Services_OpenTM2_Connector::TAG_HANDLER_CONFIG_PART,
            [
                'gTagPairing' => false,
                TagHandler::OPTION_KEEP_WHITESPACE_TAGS => $this->isSendingWhitespaceAsTagEnabled($config),
            ],
            $config,
        );
        $tagHandler->setLanguages((int) $languageResource->getSourceLang(), (int) $languageResource->getSourceLang());

        $query = $tagHandler->prepareQuery($queryString);

        $hasProtectedContent = (bool) preg_match(T5NTag::fullTagRegex(), $query);

        foreach ($languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            $tmName = $memory['filename'];

            $matches = $this->queryTm(
                $languageResource,
                $tmName,
                $query,
                $context,
                $fileName,
                $tagHandler,
                $calculateMatchRate,
                $hasProtectedContent,
                $config,
                $isInternalFuzzy,
            );

            foreach ($matches as $match) {
                yield $match;
            }
        }
    }

    /**
     * @return iterable<FuzzyMatchDTO>
     */
    private function queryTm(
        editor_Models_LanguageResources_LanguageResource $languageResource,
        string $tmName,
        string $query,
        string $context,
        string $fileName,
        TagHandler $tagHandler,
        callable $calculateMatchRate,
        bool $hasProtectedContent,
        Zend_Config $config,
        bool $isInternalFuzzy,
    ): iterable {
        $result = $this->lookup(
            $languageResource,
            $tmName,
            $context,
            $query,
            $fileName,
            $config,
            $isInternalFuzzy
        );

        if ($result === null) {
            return yield from [];
        }

        $iterator = $this->iterateThroughMatches(
            $result->results,
            $languageResource,
            $tmName,
            $context,
            $query,
            $fileName,
            $hasProtectedContent,
            $config,
            $isInternalFuzzy
        );

        foreach ($iterator as $found) {
            $target = $tagHandler->restoreInResult($found->target, false);
            $hasTargetErrors = $tagHandler->hasRestoreErrors();

            $source = $tagHandler->restoreInResult($found->source);
            $hasSourceErrors = $tagHandler->hasRestoreErrors();

            if ($hasTargetErrors || $hasSourceErrors) {
                //the source has invalid xml -> remove all tags from the result, and reduce the matchrate by 2%
                $found->matchRate = $this->reduceMatchrate($found->matchRate, 2);
            }

            $metaData = $this->getMetaData($found);
            $matchRate = $calculateMatchRate(
                $found->matchRate,
                $metaData,
                $fileName
            );
            $metaDataAssoc = array_column($metaData, 'value', 'name');
            $timestamp = 0;

            if (! empty($metaDataAssoc['timestamp'])) {
                $timestamp = (int) strtotime($metaDataAssoc['timestamp']);
            }

            yield new FuzzyMatchDTO(
                source: $source,
                target: $target,
                matchrate: $matchRate,
                metaData: $metaData,
                rawTarget: $found->target,
                timestamp: $timestamp,
            );
        }
    }

    private function iterateThroughMatches(
        array $matches,
        editor_Models_LanguageResources_LanguageResource $languageResource,
        string $tmName,
        string $context,
        string $query,
        string $fileName,
        bool $hasProtectedContent,
        Zend_Config $config,
        bool $isInternalFuzzy,
    ): iterable {
        $hundredMatchFound = false;

        foreach ($matches as $found) {
            if ($found->matchRate >= 100) {
                $hundredMatchFound = true;
            }

            if (
                ! $hundredMatchFound
                && $hasProtectedContent
                && $found->matchRate < 100
                && $found->matchRate > 50
            ) {
                $betterMatches = $this->lookForBetterMatches(
                    $found,
                    $languageResource,
                    $tmName,
                    $context,
                    $query,
                    $fileName,
                    $config,
                    $isInternalFuzzy
                );

                foreach ($betterMatches as $betterMatch) {
                    $hundredMatchFound = $hundredMatchFound || $betterMatch->matchRate >= 100;

                    $betterMatch->guessed = true;

                    yield $betterMatch;
                }
            }

            yield $found;
        }
    }

    private function lookForBetterMatches(
        object $match,
        editor_Models_LanguageResources_LanguageResource $languageResource,
        string $tmName,
        string $context,
        string $query,
        string $fileName,
        Zend_Config $config,
        bool $isInternalFuzzy,
    ): iterable {
        $tunedQuery = $this->queryStringGuesser->filterExtraTags(
            $query,
            $match->source
        );

        $result = $this->lookup(
            $languageResource,
            $tmName,
            $context,
            $tunedQuery,
            $fileName,
            $config,
            $isInternalFuzzy
        );

        if ($result === null) {
            return yield from [];
        }

        foreach ($result->results as $found) {
            if ($found->matchRate > $match->matchRate) {
                yield $found;
            }
        }
    }

    private function lookup(
        editor_Models_LanguageResources_LanguageResource $languageResource,
        string $tmName,
        string $context,
        string $query,
        string $fileName,
        Zend_Config $config,
        bool $isInternalFuzzy,
    ): ?object {
        if ($this->reorganizeService->isReorganizingAtTheMoment($languageResource, $tmName)) {
            if (! $this->retryService->canWaitLongTaskFinish()) {
                return null;
            }

            $this->reorganizeService->waitReorganizeFinished(
                $languageResource,
                $tmName,
            );
        }

        // TODO move fuzzy search to \MittagQI\Translate5\T5Memory\Api\T5MemoryApi
        $api = new editor_Services_OpenTM2_HttpApi();
        $api->setLanguageResource($languageResource);

        $successful = $api->lookup($query, $context, $fileName, $tmName);

        $response = Response::fromContentAndStatus(
            $api->getResponse() ? $api->getResponse()->getBody() : '{}',
            $api->getResponse() ? $api->getResponse()->getStatus() : 200,
        );

        $saveDifferentTargetsForSameSource = (bool) $config
            ->runtimeOptions
            ->LanguageResources
            ->t5memory
            ->saveDifferentTargetsForSameSource;
        $reorganizeOptions = new ReorganizeOptions($saveDifferentTargetsForSameSource);

        if ($this->reorganizeService->needsReorganizing(
            $response,
            $languageResource,
            $tmName,
        )) {
            $this->reorganizeService->reorganizeTm($languageResource, $tmName, $reorganizeOptions, $isInternalFuzzy);
            $successful = $api->lookup($query, $context, $fileName, $tmName);
        }

        if (! $successful && $this->isLockingTimeoutOccurred($api->getError())) {
            $lookup = fn () => $api->lookup($query, $context, $fileName, $tmName)
                ? [WaitCallState::Done, true]
                : [WaitCallState::Retry, false];

            $successful = (bool) $this->retryService->callAwaiting($lookup);
        }

        if (! $successful) {
            $this->logger->exception($this->getBadGatewayException($api, $languageResource, $tmName));

            return null;
        }

        // In case we have at least one successful lookup, we reset the reorganize attempts
        $this->reorganizeService->resetReorganizeAttempts($languageResource, $isInternalFuzzy);

        $result = $api->getResult();

        if ((int) ($result->NumOfFoundProposals ?? 0) === 0) {
            return null;
        }

        return $result;
    }

    private function isLockingTimeoutOccurred(?object $error): bool
    {
        if (null === $error) {
            return false;
        }

        return $error->returnValue === 506;
    }

    protected function isSendingWhitespaceAsTagEnabled(Zend_Config $config): bool
    {
        return (bool) $config->runtimeOptions->LanguageResources?->t5memory?->sendWhitespaceAsTag;
    }

    private function getMetaData(object $found): array
    {
        $nameToShow = [
            'segmentId',
            'documentName',
            'matchType',
            'author',
            'timestamp',
            'context',
            'additionalInfo',
            'internalKey',
            'sourceLang',
            'targetLang',
            'guessed',
        ];
        $result = [];

        foreach ($nameToShow as $name) {
            if (property_exists($found, $name)) {
                $item = new \stdClass();
                $item->name = $name;

                $item->value = match ($name) {
                    'timestamp' => date('Y-m-d H:i:s T', strtotime($found->{$name})),
                    'guessed' => 'Some content was unprotected to get a better match',
                    default => $found->{$name}
                };

                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Reduce the given matchrate to given percent.
     * It is used when unsupported tags are found in the response result, and those tags are removed.
     * @param integer $matchrate
     * @param integer $reducePercent
     * @return number
     */
    protected function reduceMatchrate($matchrate, $reducePercent)
    {
        //reset higher matches than 100% to 100% match
        //if the matchrate is higher than 0, reduce it by $reducePercent %
        return max(0, min($matchrate, 100) - $reducePercent);
    }

    private function getBadGatewayException(
        editor_Services_OpenTM2_HttpApi $api,
        editor_Models_LanguageResources_LanguageResource $languageResource,
        string $tmName = '',
    ): editor_Services_Connector_Exception {
        $ecode = 'E1313';
        $error = $api->getError();
        $data = [
            'service' => $languageResource->getResource()->getName(),
            'languageResource' => $this->languageResource ?? '',
            'tmName' => $tmName,
            'error' => $error,
            'request' => $api->request,
            'response' => $api->getResponse()->getBody(),
        ];

        $api->request = null;

        if (strpos($error->error ?? '', 'needs to be organized') !== false) {
            $ecode = 'E1314';
            $data['tm'] = $languageResource->getName();
        }

        if (strpos($error->error ?? '', 'too many open translation memory databases') !== false) {
            $ecode = 'E1333';
        }

        return new editor_Services_Connector_Exception($ecode, $data);
    }
}

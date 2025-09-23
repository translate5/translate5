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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Connector_Exception;
use Exception;
use GuzzleHttp\Exception\RequestException;
use MittagQI\Translate5\ContentProtection\T5memory\T5NTag;
use MittagQI\Translate5\T5Memory\Api\Contract\FuzzyInterface;
use MittagQI\Translate5\T5Memory\Api\Response\FuzzySearchResponse;
use MittagQI\Translate5\T5Memory\Api\Response\MatchDTO;
use MittagQI\Translate5\T5Memory\Api\SegmentLengthValidator;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\ContentProtection\QueryStringGuesser;
use MittagQI\Translate5\T5Memory\DTO\FuzzyMatchDTO;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use MittagQI\Translate5\T5Memory\Exception\ReorganizeException;
use Psr\Http\Client\RequestExceptionInterface;
use Throwable;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Logger;

class FuzzySearchService
{
    public function __construct(
        private readonly ReorganizeService $reorganizeService,
        private readonly RetryService $retryService,
        private readonly ZfExtended_Logger $logger,
        private readonly QueryStringGuesser $queryStringGuesser,
        private readonly FuzzyInterface $api,
        private readonly PersistenceService $persistenceService,
        private readonly TagHandlerProvider $tagHandlerProvider,
        private readonly SegmentLengthValidator $segmentLengthValidator,
    ) {
    }

    public static function create(): self
    {
        return new self(
            ReorganizeService::create(),
            RetryService::create(),
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.fuzzy-search'),
            QueryStringGuesser::create(),
            T5MemoryApi::create(),
            PersistenceService::create(),
            new TagHandlerProvider(),
            SegmentLengthValidator::create(),
        );
    }

    /**
     * @return iterable<FuzzyMatchDTO>
     */
    public function query(
        LanguageResource $languageResource,
        string $queryString,
        string $context,
        string $fileName,
        callable $calculateMatchRate,
        Zend_Config $config,
        bool $isInternalFuzzy,
    ): iterable {
        // if source is empty, t5memory will return an error, therefore we just return an empty list
        if (empty($queryString) && $queryString !== '0') {
            return yield from [];
        }

        if ($languageResource->isConversionStarted()) {
            return yield from [];
        }

        $tagHandler = $this->tagHandlerProvider->getTagHandler(
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getSourceLang(),
            $config,
        );

        $query = $tagHandler->prepareQuery($queryString);

        if (! $this->segmentLengthValidator->isValid($query)) {
            $this->logger->warn(
                'E1742',
                'Segment too long for queries in t5memory',
                [
                    'languageResource' => $languageResource,
                    'queryString' => $queryString,
                ]
            );

            return yield from [];
        }

        $hasProtectedContent = (bool) preg_match(T5NTag::fullTagRegex(), $query);

        [$responses, $hasSkippedTms] = $this->lookup(
            $languageResource,
            array_column(
                $languageResource->getSpecificData('memories', parseAsArray: true),
                'filename'
            ),
            $query,
            $context,
            $fileName,
            $config,
            $isInternalFuzzy,
        );

        $matches = [];

        foreach ($responses as $response) {
            $matches[] = $response->getMatches();
        }

        $matches = array_merge(...$matches);

        $iterator = $this->iterateThroughMatches(
            $matches,
            $languageResource,
            $context,
            $query,
            $fileName,
            $config,
            $hasProtectedContent,
            $isInternalFuzzy,
            $hasSkippedTms,
        );

        foreach ($iterator as $found) {
            $target = $tagHandler->restoreInResult($found->target, false);
            $hasTargetErrors = $tagHandler->hasRestoreErrors();

            $source = $tagHandler->restoreInResult($found->source);
            $hasSourceErrors = $tagHandler->hasRestoreErrors();

            $matchRate = $found->matchRate;
            if ($hasTargetErrors || $hasSourceErrors) {
                //the source has invalid xml -> remove all tags from the result, and reduce the matchrate by 2%
                $matchRate = $this->reduceMatchRate($matchRate, 2);
            }

            $metaData = $this->getMetaData($found);
            $matchRate = $calculateMatchRate(
                $matchRate,
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

    /**
     * @param MatchDTO[] $matches
     * @return iterable<MatchDTO>
     */
    private function iterateThroughMatches(
        array $matches,
        LanguageResource $languageResource,
        string $context,
        string $query,
        string $fileName,
        Zend_Config $config,
        bool $hasProtectedContent,
        bool $isInternalFuzzy,
        bool $hasSkippedTms,
    ): iterable {
        $hundredMatchFound = false;

        foreach ($matches as $found) {
            if ($found->matchRate >= 100) {
                $hundredMatchFound = true;
            }
        }

        foreach ($matches as $found) {
            if (
                ! $hundredMatchFound
                && $hasProtectedContent
                && $found->matchRate < 100
                && $found->matchRate > 50
            ) {
                $betterMatches = $this->lookForBetterMatches(
                    $found,
                    $languageResource,
                    $context,
                    $query,
                    $fileName,
                    $config,
                    $isInternalFuzzy,
                    $hasSkippedTms,
                );

                foreach ($betterMatches as $betterMatch) {
                    $hundredMatchFound = $hundredMatchFound || $betterMatch->matchRate >= 100;

                    // not optimal mark is set by lookForBetterMatches as it has additional lookup call inside
                    yield $betterMatch;
                }
            }

            yield $hasSkippedTms ? $found->makeNotOptimal() : $found;
        }
    }

    private function lookForBetterMatches(
        MatchDTO $match,
        LanguageResource $languageResource,
        string $context,
        string $query,
        string $fileName,
        Zend_Config $config,
        bool $isInternalFuzzy,
        bool $hasSkippedTms,
    ): iterable {
        $tunedQuery = $this->queryStringGuesser->filterExtraTags($query, $match->source);

        if ($tunedQuery === $query) {
            return yield from [];
        }

        [$responses, $hasSkippedTms] = $this->lookup(
            $languageResource,
            array_column(
                $languageResource->getSpecificData('memories', parseAsArray: true),
                'filename'
            ),
            $tunedQuery,
            $context,
            $fileName,
            $config,
            $isInternalFuzzy,
            $hasSkippedTms,
        );

        if (empty($responses)) {
            return yield from [];
        }

        foreach ($responses as $response) {
            foreach ($response->getMatches() as $found) {
                if ($found->matchRate > $match->matchRate) {
                    // we mark it as not optimal here because some tms may be skipped in current lookup
                    yield $hasSkippedTms ? $found->makeGuessed()->makeNotOptimal() : $found->makeGuessed();
                }
            }
        }
    }

    private function getQueryableMemories(LanguageResource $languageResource, array $memories): array
    {
        $tms = [];
        foreach ($memories as $memory) {
            if ($this->reorganizeService->isReorganizingAtTheMoment($languageResource, $memory)) {
                if (! $this->retryService->canWaitLongTaskFinish()) {
                    continue;
                }

                $this->reorganizeService->waitReorganizeFinished(
                    $languageResource,
                    $memory,
                );
            }

            $tms[] = $memory;
        }

        return $tms;
    }

    /**
     * @return array{FuzzySearchResponse[], bool}
     */
    private function lookup(
        LanguageResource $languageResource,
        array $memories,
        string $query,
        string $context,
        string $fileName,
        Zend_Config $config,
        bool $isInternalFuzzy,
        bool $hasSkippedTms = false,
    ): array {
        $count = count($memories);
        $memories = $this->getQueryableMemories($languageResource, $memories);
        $memories = array_map(
            fn ($memory) => $this->persistenceService->addTmPrefix($memory),
            $memories
        );

        $hasSkippedTms = $hasSkippedTms || count($memories) < $count;

        ['responses' => $responses, 'failures' => $failures] = $this->api->fuzzyParallel(
            $languageResource->getResource()->getUrl(),
            $memories,
            $languageResource->getSourceLangCode(),
            $languageResource->getTargetLangCode(),
            $query,
            $context,
            $fileName,
        );

        $retryTms = [];
        $saveDifferentTargetsForSameSource = (bool) $config
            ->runtimeOptions
            ->LanguageResources
            ->t5memory
            ->saveDifferentTargetsForSameSource;
        $reorganizeOptions = new ReorganizeOptions($saveDifferentTargetsForSameSource);

        foreach ($responses as $tmName => $response) {
            if ($this->reorganizeService->needsReorganizing($response, $languageResource, $tmName)) {
                unset($responses[$tmName]);

                try {
                    $this->reorganizeService->startReorganize(
                        $languageResource,
                        $tmName,
                        $reorganizeOptions,
                        $isInternalFuzzy
                    );

                    $retryTms[] = $tmName;
                } catch (ReorganizeException $e) {
                    $failures[$tmName] = $e;
                }

                continue;
            }

            $this->reorganizeService->resetReorganizeAttempts($languageResource, $tmName, $isInternalFuzzy);

            if ($this->isLockingTimeoutOccurred($response)) {
                $lookup = function () use (
                    $languageResource,
                    $tmName,
                    $query,
                    $context,
                    $fileName,
                ) {
                    $response = $this->api->fuzzy(
                        $languageResource->getResource()->getUrl(),
                        $tmName,
                        $languageResource->getSourceLangCode(),
                        $languageResource->getTargetLangCode(),
                        $query,
                        $context,
                        $fileName,
                    );

                    return $this->isLockingTimeoutOccurred($response)
                        ? [WaitCallState::Retry, null]
                        : [WaitCallState::Done, $response];
                };

                try {
                    $response = $this->retryService->callAwaiting($lookup);

                    if (null !== $response) {
                        $responses[$tmName] = $response;

                        continue;
                    }

                    $failures[$tmName] = new Exception('Retry failed after locking timeout');
                } catch (Throwable $e) {
                    $failures[$tmName] = $e;
                }
            }
        }

        foreach ($failures as $tmName => $failure) {
            $this->logger->exception($this->getBadGatewayException($failure, $languageResource, $tmName));
        }

        if (! empty($retryTms)) {
            [$retryResponses, $hasSkippedTms] = $this->lookup(
                $languageResource,
                $retryTms,
                $query,
                $context,
                $fileName,
                $config,
                $isInternalFuzzy,
                $hasSkippedTms,
            );

            foreach ($retryResponses as $tmName => $response) {
                $responses[$tmName] = $response;
            }
        }

        return [$responses, $hasSkippedTms];
    }

    private function isLockingTimeoutOccurred(FuzzySearchResponse $response): bool
    {
        return $response->getCode() === 506;
    }

    private function getMetaData(MatchDTO $found): array
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
        ];
        $result = [];

        foreach ($nameToShow as $name) {
            if (property_exists($found, $name)) {
                $item = new \stdClass();
                $item->name = $name;

                $item->value = match ($name) {
                    'timestamp' => date('Y-m-d H:i:s T', strtotime($found->{$name})),
                    default => $found->{$name}
                };

                $result[] = $item;
            }
        }

        if ($found->guessed) {
            $item = new \stdClass();
            $item->name = 'Guessed';
            $item->value = 'Some content was unprotected to get a better match';

            $result[] = $item;
        }

        if ($found->possiblyNotOptimal) {
            $item = new \stdClass();
            $item->name = 'Possibly not optimal';
            $item->value = 'Language resource is currently being processed. Better matches might be available.';

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Reduce the given match-rate to given percent.
     * It is used when unsupported tags are found in the response result, and those tags are removed.
     */
    protected function reduceMatchRate(int $matchRate, int $reducePercent): int
    {
        return max(0, min($matchRate, 100) - $reducePercent);
    }

    private function getBadGatewayException(
        Throwable $e,
        LanguageResource $languageResource,
        string $tmName,
    ): editor_Services_Connector_Exception {
        $ecode = 'E1313';

        $request = null;
        $response = null;

        if ($e instanceof RequestExceptionInterface) {
            $request = $e->getRequest();
        }

        if ($e instanceof RequestException) {
            $response = $e->getResponse();
        }

        if ($request?->getBody()->isSeekable()) {
            $request->getBody()->rewind();
        }

        if ($response?->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        $data = [
            'service' => $languageResource->getResource()->getName(),
            'languageResource' => $languageResource,
            'tmName' => $tmName,
            'error' => $e->getMessage(),
            'uri' => $request?->getUri() ?? $languageResource->getResource()->getUrl(),
            'request' => $request?->getBody()->getContents() ?? '',
            'response' => $response?->getBody()->getContents() ?? '',
        ];

        if (str_contains($data['response'], 'needs to be organized')) {
            $ecode = 'E1314';
            $data['tm'] = $languageResource->getName();
        }

        if (str_contains($data['response'], 'too many open translation memory databases')) {
            $ecode = 'E1333';
        }

        return new editor_Services_Connector_Exception($ecode, $data, $e);
    }
}

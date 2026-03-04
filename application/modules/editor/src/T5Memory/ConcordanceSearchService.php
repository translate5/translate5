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
use editor_Services_ServiceResult;
use Exception;
use GuzzleHttp\Exception\RequestException;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\Response\ConcordanceSearchCountResponse;
use MittagQI\Translate5\T5Memory\Api\Response\ConcordanceSearchResponse;
use MittagQI\Translate5\T5Memory\Api\Response\FindDTO;
use MittagQI\Translate5\T5Memory\Api\SegmentLengthValidator;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\Contract\TagHandlerProviderInterface;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use MittagQI\Translate5\T5Memory\Exception\ReorganizeException;
use MittagQI\Translate5\T5Memory\TagHandler\PassThroughTagHandlerProvider;
use MittagQI\Translate5\T5Memory\TagHandler\TagHandlerProvider;
use Psr\Http\Client\RequestExceptionInterface;
use Throwable;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Logger;

class ConcordanceSearchService
{
    private const CONCORDANCE_SEARCH_NUM_RESULTS = 1;

    public function __construct(
        private readonly ReorganizeService $reorganizeService,
        private readonly RetryService $retryService,
        private readonly ZfExtended_Logger $logger,
        private readonly PersistenceService $persistenceService,
        private readonly TagHandlerProviderInterface $tagHandlerProvider,
        private readonly SegmentLengthValidator $segmentLengthValidator,
        private readonly StatusService $statusService,
        private readonly T5MemoryApi $t5MemoryApi,
        private readonly EmptyMemoryCheck $emptyMemoryCheck,
    ) {
        \editor_Services_Connector_Exception::addCodes([
            'E1314' => 'The queried T5Memory TM "{tm}" is corrupt and must be reorganized before usage!',
            'E1333' => 'The queried T5Memory server has to many open TMs!',
            'E1377' => 'Memory status: {status}. Please try again in a while.',
            'E1742' => 'Segment too long for queries in t5memory',
        ]);
    }

    public static function create(): self
    {
        return new self(
            ReorganizeService::create(),
            RetryService::create(),
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.concordance-search'),
            PersistenceService::create(),
            TagHandlerProvider::create(),
            SegmentLengthValidator::create(),
            StatusService::create(),
            T5MemoryApi::create(),
            EmptyMemoryCheck::create(),
        );
    }

    public static function createWithTagHandlerProvider(TagHandlerProviderInterface $tagHandlerProvider): self
    {
        return new self(
            ReorganizeService::create(),
            RetryService::create(),
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.concordance-search'),
            PersistenceService::create(),
            $tagHandlerProvider,
            SegmentLengthValidator::create(),
            StatusService::create(),
            T5MemoryApi::create(),
            EmptyMemoryCheck::create(),
        );
    }

    public static function createForDeleteSegmentService(): self
    {
        return new self(
            ReorganizeService::create(),
            RetryService::create(),
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.concordance-search'),
            PersistenceService::create(),
            PassThroughTagHandlerProvider::create(),
            SegmentLengthValidator::create(),
            StatusService::create(),
            T5MemoryApi::create(),
            EmptyMemoryCheck::create(),
        );
    }

    public function countSegments(LanguageResource $languageResource, SearchDTO $searchDTO, Zend_Config $config): int
    {
        $memories = $languageResource->getSpecificData('memories', parseAsArray: true);

        if (empty($memories)) {
            return 0;
        }

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        $responses = $this->fetchCounts(
            $languageResource,
            array_column($memories, 'filename'),
            $searchDTO,
            $config,
        );

        $amount = 0;

        foreach ($responses as $tmName => $response) {
            // currently reorganizing tm can't be here as it is filtered before in self::getQueryableMemories
            if (! $response->successful()) {
                throw $this->getBadGatewayException(
                    new Exception($response->getErrorMessage() ?? 'Unknown error during concordance search count'),
                    $languageResource,
                    $tmName,
                    $response->getBody(),
                );
            }

            $amount += $response->getNumOfFoundSegments();
        }

        return $amount;
    }

    /**
     * @throws editor_Services_Connector_Exception
     */
    public function query(
        LanguageResource $languageResource,
        SearchDTO $searchDTO,
        ?string $offset,
        Zend_Config $config,
        int $amountOfResults = self::CONCORDANCE_SEARCH_NUM_RESULTS,
    ): editor_Services_ServiceResult {
        $offsetTmId = null;
        $recordKey = null;
        $targetKey = null;
        $tmOffset = null;

        if (null !== $offset) {
            @[$offsetTmId, $recordKey, $targetKey] = explode(':', $offset);
        }

        if (null !== $offset && (null === $recordKey || null === $targetKey)) {
            throw new \editor_Services_Connector_Exception('E1565', compact('offset'));
        }

        if (null !== $recordKey && null !== $targetKey) {
            $tmOffset = $recordKey . ':' . $targetKey;
        }

        $resultList = new editor_Services_ServiceResult();
        $resultList->setLanguageResource($languageResource);

        if (! $this->segmentLengthValidator->isValid($searchDTO->source)) {
            $this->logger->info(
                'E1742',
                'Segment too long for queries in t5memory',
                [
                    'languageResource' => $languageResource,
                    'queryString' => $searchDTO->source,
                ]
            );

            return $resultList;
        }

        $results = [];
        $resultsCount = 0;

        $memories = $languageResource->getSpecificData('memories', parseAsArray: true);

        if (empty($memories)) {
            return $resultList;
        }

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);
        $ids = array_column($memories, 'id');

        foreach ($memories as ['filename' => $tmName, 'id' => $id]) {
            // check if current memory was searched through in prev request
            if ('' !== $offsetTmId && $id < $offsetTmId) {
                continue;
            }

            [$finds, $newSearchPosition] = $this->queryTm(
                $languageResource,
                $tmName,
                $searchDTO,
                $tmOffset,
                $amountOfResults,
                $config,
            );

            if (empty($finds)) {
                // Reset the TM offset to start from the beginning of the next TM
                $tmOffset = null;

                continue;
            }

            $results[] = array_map(fn (FindDTO $find) => $find->withPartId($id), $finds);
            $resultsCount += count($finds);
            $nextOffset = $newSearchPosition ? ($id . ':' . $newSearchPosition) : null;

            // If there is no search position in the result, and there is next memory
            // we need to set the next offset to the next memory
            if (! $nextOffset && $id < max($ids)) {
                $filtered = array_filter($ids, static fn ($num) => $num > $id);
                $nextOffset = reset($filtered) . ':1:1';
            }

            $resultList->setNextOffset($nextOffset);

            // if we get enough results then response them
            /** @var int $resultsCount */
            if ($amountOfResults <= $resultsCount) {
                break;
            }
        }

        $results = array_merge(...$results);

        if (empty($results)) {
            $resultList->setNextOffset(null);

            return $resultList;
        }

        $tagHandler = $this->tagHandlerProvider->getTagHandler(
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getTargetLang(),
            $config,
        );

        /** @var FindDTO $result */
        foreach ($results as $result) {
            // we need to prepare query for each result to properly set tag handler state, otherwise we can't restore tags in result properly
            // because source will be empty in tag handler
            $tagHandler->prepareQuery($result->source);
            $resultList->addResult(
                $tagHandler->restoreInResult($result->target, false),
                0,
                $this->getMetaData($result)
            );
            $resultList->setSource($tagHandler->restoreInResult($result->source));
        }

        return $resultList;
    }

    /**
     * @return array{FindDTO[], string|null} Tuple of found segments and next offset
     * @throws ReorganizeException
     * @throws editor_Services_Connector_Exception
     */
    public function queryTm(
        LanguageResource $languageResource,
        string $tmName,
        SearchDTO $searchDTO,
        ?string $tmOffset,
        int $amountOfResults,
        Zend_Config $config,
    ): array {
        $this->assertMemoryAvailable($languageResource, $tmName);

        $segmentIdsGenerated = $this->areSegmentIdsGenerated($languageResource, $tmName);

        if (! $segmentIdsGenerated && ! $this->emptyMemoryCheck->isMemoryEmpty($languageResource, $tmName)) {
            $this->reorganizeTm($languageResource, $tmName, $config);
        }

        $response = $this->searchAwaiting($languageResource, $tmName, $searchDTO, $tmOffset, $amountOfResults);

        if ($this->reorganizeService->needsReorganizing($response, $languageResource, $tmName)) {
            $this->reorganizeTm($languageResource, $tmName, $config);

            $response = $this->searchAwaiting($languageResource, $tmName, $searchDTO, $tmOffset, $amountOfResults);
        }

        if (! $response->successful()) {
            throw $this->getBadGatewayException(
                new Exception($response->getErrorMessage() ?? 'Unknown error during concordance search'),
                $languageResource,
                $tmName,
                $response->getBody(),
            );
        }

        // In case we have at least one successful search, we reset the reorganize attempts
        $this->reorganizeService->resetReorganizeAttempts($languageResource, $tmName);

        $finds = $response->getFinds();

        if (empty($finds)) {
            return [[], null];
        }

        $this->checkFinds($finds, $languageResource, $tmName, $config);

        return [$finds, $response->getNewSearchPosition()];
    }

    /**
     * @param array<string> $memories
     * @return array<string, ConcordanceSearchCountResponse>
     *
     * @throws editor_Services_Connector_Exception
     */
    private function fetchCounts(
        LanguageResource $languageResource,
        array $memories,
        SearchDTO $searchDTO,
        Zend_Config $config,
    ): array {
        $memories = $this->getQueryableMemories($languageResource, $memories);

        ['responses' => $responses, 'failures' => $failures] = $this->t5MemoryApi->concordanceSearchCountParallel(
            $languageResource->getResource()->getUrl(),
            $memories,
            $searchDTO,
        );

        foreach ($responses as $tmName => $response) {
            // currently reorganizing tm can't be here as it is filtered before in self::getQueryableMemories
            if ($this->reorganizeService->needsReorganizing($response, $languageResource, $tmName)) {
                unset($responses[$tmName]);

                try {
                    $this->reorganizeTm($languageResource, $tmName, $config);

                    $retryTms[] = $tmName;
                } catch (ReorganizeException $e) {
                    $failures[$tmName] = $e;
                }

                continue;
            }

            $this->reorganizeService->resetReorganizeAttempts($languageResource, $tmName);

            if (! $response->isLockingTimeoutOccurred()) {
                continue;
            }

            try {
                $responses[$tmName] = $this->countAwaiting($languageResource, $tmName, $searchDTO);
            } catch (Throwable $e) {
                $failures[$tmName] = $e;
            }
        }

        foreach ($failures as $tmName => $failure) {
            $this->logger->exception($this->getBadGatewayException($failure, $languageResource, $tmName));
        }

        if (! empty($failure)) {
            throw $this->getBadGatewayException(
                new Exception('Count cannot be performed when some TMs are failing'),
                $languageResource,
                implode(', ', array_keys($failures))
            );
        }

        if (! empty($retryTms)) {
            $retryResponses = $this->fetchCounts(
                $languageResource,
                $retryTms,
                $searchDTO,
                $config,
            );

            foreach ($retryResponses as $tmName => $response) {
                $responses[$tmName] = $response;
            }
        }

        return $responses;
    }

    /**
     * @throws ReorganizeException
     */
    private function reorganizeTm(
        LanguageResource $languageResource,
        string $tmName,
        Zend_Config $config,
    ): void {
        $reorganizeOptions = new ReorganizeOptions(
            TmxFilterOptions::fromConfig($config),
        );

        if ($this->retryService->canWaitLongTaskFinish()) {
            $this->reorganizeService->reorganizeTm(
                $languageResource,
                $tmName,
                $reorganizeOptions,
            );

            return;
        }

        $this->reorganizeService->startReorganize(
            $languageResource,
            $tmName,
            $reorganizeOptions,
        );

        throw new \editor_Services_Connector_Exception('E1377', [
            'languageResource' => $languageResource,
            'status' => LanguageResourceStatus::REORGANIZE_IN_PROGRESS,
        ]);
    }

    private function assertMemoryAvailable(LanguageResource $languageResource, string $memoryName): void
    {
        $status = $this->statusService->getStatus($languageResource, $memoryName);

        if ($status !== LanguageResourceStatus::AVAILABLE) {
            throw new \editor_Services_Connector_Exception('E1377', [
                'languageResource' => $languageResource,
                'status' => $status,
            ]);
        }
    }

    private function areSegmentIdsGenerated(LanguageResource $languageResource, string $tmName): bool
    {
        try {
            $response = $this->t5MemoryApi->getSegmentIndex(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName),
            );
        } catch (Throwable $e) {
            throw $this->getBadGatewayException(
                $e,
                $languageResource,
                $tmName
            );
        }

        if (! $response->successful()) {
            return false;
        }

        if (null === $response->getSegmentIndex()) {
            // This means memory is not loaded into RAM
            // So call for a fake segment to force t5memory to load memory into RAM and call for status again
            $this->t5MemoryApi->getEntry($languageResource->getResource()->getUrl(), $tmName, '7:1');

            $response = $this->t5MemoryApi->getSegmentIndex(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName),
            );
        }

        return $response->getSegmentIndex() > 0;
    }

    /**
     * @throws editor_Services_Connector_Exception
     */
    private function countAwaiting(
        LanguageResource $languageResource,
        string $tmName,
        SearchDTO $searchDTO,
    ): ConcordanceSearchCountResponse {
        $count = function () use (
            $languageResource,
            $tmName,
            $searchDTO,
        ) {
            $response = $this->t5MemoryApi->concordanceSearchCount(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName),
                $searchDTO,
            );

            return $response->isLockingTimeoutOccurred()
                ? [WaitCallState::Retry, null]
                : [WaitCallState::Done, $response];
        };

        $response = $this->retryService->callAwaiting($count);

        if (null === $response) {
            throw new Exception('Retry failed after locking timeout');
        }

        return $response;
    }

    /**
     * @throws editor_Services_Connector_Exception
     */
    private function searchAwaiting(
        LanguageResource $languageResource,
        string $tmName,
        SearchDTO $searchDTO,
        ?string $tmOffset,
        int $amountOfResults,
    ): ConcordanceSearchResponse {
        $lookup = function () use (
            $languageResource,
            $tmName,
            $searchDTO,
            $tmOffset,
            $amountOfResults,
        ) {
            $response = $this->t5MemoryApi->concordanceSearch(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName),
                $searchDTO,
                $tmOffset,
                $amountOfResults,
            );

            return $response->isLockingTimeoutOccurred()
                ? [WaitCallState::Retry, null]
                : [WaitCallState::Done, $response];
        };

        try {
            $response = $this->retryService->callAwaiting($lookup);
        } catch (Throwable $e) {
            throw $this->getBadGatewayException(
                $e,
                $languageResource,
                $tmName
            );
        }

        if (null === $response) {
            throw $this->getBadGatewayException(
                new Exception('Retry failed after locking timeout'),
                $languageResource,
                $tmName
            );
        }

        return $response;
    }

    private function getMetaData(FindDTO $found): array
    {
        $nameToShow = [
            'segmentId',
            'documentName',
            'author',
            'timestamp',
            'context',
            'additionalInfo',
            'internalKey',
            'sourceLang',
            'targetLang',
            'partId',
        ];
        $result = [];

        foreach ($nameToShow as $name) {
            if (property_exists($found, $name)) {
                $item = new \stdClass();
                $item->name = $name;

                $item->value = match ($name) {
                    // if date before unix time provided in TMX t5memory will not save time at all.
                    // and later that will result in lots of issues on our side.
                    // so we preserve segments but mark them as old as who knows how valid they actually are
                    'timestamp' => date('Y-m-d H:i:s T', strtotime($found->{$name}) ?: 0),
                    default => $found->{$name}
                };

                $result[] = $item;
            }
        }

        return $result;
    }

    private function getBadGatewayException(
        Throwable $e,
        LanguageResource $languageResource,
        string $tmName,
        ?array $responseBody = null,
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
            'response' => $response?->getBody()->getContents() ?? ($responseBody ? json_encode($responseBody) : ''),
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

    /**
     * @param FindDTO[] $finds
     */
    private function checkFinds(
        array $finds,
        LanguageResource $languageResource,
        mixed $tmName,
        Zend_Config $config,
    ): void {
        foreach ($finds as $find) {
            if ($find->segmentId === 0) {
                $this->reorganizeTm($languageResource, $tmName, $config);

                return;
            }
        }
    }

    /**
     * @param string[] $memories
     *
     * @return string[]
     * @throws editor_Services_Connector_Exception
     */
    private function getQueryableMemories(LanguageResource $languageResource, array $memories): array
    {
        $tms = [];
        foreach ($memories as $memory) {
            if ($this->reorganizeService->isReorganizingAtTheMoment($languageResource, $memory)) {
                if (! $this->retryService->canWaitLongTaskFinish()) {
                    throw new \editor_Services_Connector_Exception('E1377', [
                        'languageResource' => $languageResource,
                        'status' => LanguageResourceStatus::REORGANIZE_IN_PROGRESS,
                    ]);
                }

                $this->reorganizeService->waitReorganizeFinished(
                    $languageResource,
                    $memory,
                );
            }

            $tms[] = $this->persistenceService->addTmPrefix($memory);
        }

        return $tms;
    }
}

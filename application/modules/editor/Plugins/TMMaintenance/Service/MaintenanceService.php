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

namespace MittagQI\Translate5\Plugins\TMMaintenance\Service;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as SegmentModel;
use editor_Services_OpenTM2_Connector as T5MemoryConnector;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagService;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagServiceInterface;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\Plugins\TMMaintenance\Exception\BatchDeleteException;
use MittagQI\Translate5\Plugins\TMMaintenance\Overwrites\T5MemoryXliff;
use MittagQI\Translate5\T5Memory\Api\Response\Response as ApiResponse;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\ReorganizeService;
use MittagQI\Translate5\T5Memory\UpdateRetryService;

/**
 * This is a temporary service partially copying functionality from the OpenTM2Connector
 */
class MaintenanceService extends \editor_Services_Connector_Abstract
{
    private const CONCORDANCE_SEARCH_NUM_RESULTS = 1;

    protected const TAG_HANDLER_CONFIG_PART = 't5memory';

    private \editor_Services_OpenTM2_HttpApi $api;

    /**
     *  Is the connector generally able to support internal Tags for the translate-API
     * @var bool
     */
    protected $internalTagSupport = true;

    private T5MemoryConnector $t5MemoryConnector;

    private readonly ConvertT5MemoryTagServiceInterface $tmConversionService;

    private readonly ReorganizeService $reorganizeService;

    private readonly UpdateRetryService $updateRetryService;

    private readonly PersistenceService $persistenceService;

    private readonly T5MemoryApi $t5MemoryApi;

    public function __construct()
    {
        \editor_Services_Connector_Exception::addCodes([
            'E1314' => 'The queried OpenTM2 TM "{tm}" is corrupt and must be reorganized before usage!',
            'E1333' => 'The queried OpenTM2 server has to many open TMs!',
            'E1306' => 'Could not save segment to TM',
            'E1688' => 'Could not delete segment',
            'E1377' => 'Memory status: {status}. Please try again in a while.',
            'E1616' => 'T5Memory server version serving the selected memory is not supported',
            'E1611' => 't5memory: Requested segment not found. Probably it was deleted.',
            'E1612' => 't5memory: Found segment id differs from the requested one, ' .
                'probably it was deleted or edited meanwhile. Try to refresh your search.',
        ]);

        \ZfExtended_Logger::addDuplicatesByEcode('E1333', 'E1306', 'E1314');
        $this->t5MemoryConnector = new T5MemoryConnector();
        $this->tmConversionService = ConvertT5MemoryTagService::create();
        $this->reorganizeService = ReorganizeService::create();
        $this->updateRetryService = UpdateRetryService::create();
        $this->persistenceService = PersistenceService::create();
        $this->t5MemoryApi = T5MemoryApi::create();

        parent::__construct();
    }

    public function connectTo(
        LanguageResource $languageResource,
        $sourceLang,
        $targetLang,
        $config = null,
    ): void {
        $this->api = \ZfExtended_Factory::get('editor_Services_OpenTM2_HttpApi');
        $this->api->setLanguageResource($languageResource);

        $this->t5MemoryConnector->connectTo($languageResource, $sourceLang, $targetLang, $config);
        parent::connectTo($languageResource, $sourceLang, $targetLang, $config);
    }

    protected function createTagHandler(array $params = []): \editor_Services_Connector_TagHandler_Abstract
    {
        return new T5MemoryXliff([
            'gTagPairing' => false,
        ]);
    }

    public function updateTranslation(string $source, string $target, string $tmName = '')
    {
        // Not used
    }

    /**
     * Create a segment in t5memory
     */
    public function createSegment(
        string $source,
        string $target,
        string $userName,
        string $context,
        int $timestamp,
        string $fileName,
    ): void {
        $source = $this->tagHandler->prepareQuery($source);
        $this->tagHandler->setInputTagMap($this->tagHandler->getTagMap());
        $target = $this->tagHandler->prepareQuery($target, false);

        $memoryName = $this->persistenceService->getWritableMemory($this->languageResource);

        $this->assertMemoryAvailable($memoryName);

        $this->updateSegmentInMemory(
            $source,
            $target,
            $userName,
            $context,
            $timestamp,
            $fileName,
            $memoryName,
        );
    }

    /**
     * Update method was designed to work with SegmentModel context
     * so this method was added to be able to update a memory entry without an SegmentModel
     */
    public function updateSegment(
        int $memoryId,
        int $segmentId,
        int $segmentRecordKey,
        int $segmentTargetKey,
        string $source,
        string $target,
        string $userName,
        string $context,
        int $timestamp,
        string $fileName,
    ): void {
        $memoryName = $this->getMemoryNameById($memoryId);

        $this->assertMemoryAvailable($memoryName);

        $successful = $this->api->getEntry($memoryName, $segmentRecordKey, $segmentTargetKey);

        if (! $successful) {
            throw new \editor_Services_Connector_Exception('E1611');
        }

        $result = $this->api->getResult();

        if ($segmentId !== $result->segmentId) {
            throw new \editor_Services_Connector_Exception('E1612');
        }

        $this->deleteEntry($memoryId, $segmentId, $segmentRecordKey, $segmentTargetKey);

        $source = $this->tagHandler->prepareQuery($source);
        $this->tagHandler->setInputTagMap($this->tagHandler->getTagMap());
        $target = $this->tagHandler->prepareQuery($target, false);

        $this->updateSegmentInMemory(
            $source,
            $target,
            $userName,
            $context,
            $timestamp,
            $fileName,
            $memoryName,
        );
    }

    public function deleteEntry(int $memoryId, int $segmentId, int $recordKey, int $targetKey): void
    {
        $memoryName = $this->getMemoryNameById($memoryId);

        $this->assertMemoryAvailable($memoryName);

        $successful = $this->api->deleteEntry($memoryName, $segmentId, $recordKey, $targetKey);

        $response = ApiResponse::fromContentAndStatus(
            $this->api->getResponse()->getBody(),
            $this->api->getResponse()->getStatus(),
        );

        if (
            ! $successful
            && $this->reorganizeService->needsReorganizing($response, $this->languageResource, $memoryName)
        ) {
            $saveDifferentTargetsForSameSource = (bool) $this->config
                ->runtimeOptions
                ->LanguageResources
                ->t5memory
                ->saveDifferentTargetsForSameSource;
            $reorganizeOptions = new ReorganizeOptions($saveDifferentTargetsForSameSource);

            $this->reorganizeService->reorganizeTm($this->languageResource, $memoryName, $reorganizeOptions);

            $successful = $this->api->deleteEntry($memoryName, $segmentId, $recordKey, $targetKey);
        }

        if (! $successful && $this->isLockingTimeoutOccurred($this->api->getError())) {
            $retries = 0;

            while ($retries < $this->getMaxRequestRetries() && ! $successful) {
                sleep($this->getRetryDelaySeconds());
                $retries++;

                $successful = $this->api->deleteEntry($memoryName, $segmentId, $recordKey, $targetKey);
            }
        }

        if (! $successful) {
            throw new \editor_Services_Connector_Exception('E1688', [
                'languageResource' => $this->languageResource,
                'error' => $this->api->getError(),
            ]);
        }
    }

    public function deleteBatch(SearchDTO $dto): bool
    {
        try {
            TuBatchDeleteWorker::queueWorker($this->languageResource, $dto);

            $this->languageResource->setStatus(LanguageResourceStatus::REORGANIZE_IN_PROGRESS);
            $this->languageResource->save();
        } catch (BatchDeleteException $e) {
            $this->logger->error(
                'E1688',
                'Could not schedule batch delete worker',
                [
                    'languageResource' => $this->languageResource,
                    'error' => $e->getMessage(),
                ]
            );

            throw new \editor_Services_Connector_Exception('E1688');
        }

        return true;
    }

    /**
     * Fuzzy search
     *
     * {@inheritDoc}
     */
    public function query(SegmentModel $segment): \editor_Services_ServiceResult
    {
        // TODO not used
        return new \editor_Services_ServiceResult();
    }

    /**
     * Concordance search
     *
     * {@inheritDoc}
     */
    public function concordanceSearch(
        string $searchString,
        string $field,
        ?string $offset,
        SearchDTO $searchDTO,
        int $amountOfResults = self::CONCORDANCE_SEARCH_NUM_RESULTS,
    ): \editor_Services_ServiceResult {
        $offsetTmId = null;
        $recordKey = null;
        $targetKey = null;
        $tmOffset = null;

        if (null !== $offset) {
            @[$offsetTmId, $recordKey, $targetKey] = explode(':', (string) $offset);
        }

        if ('' !== $offsetTmId && null === $recordKey && null === $targetKey) {
            throw new \editor_Services_Connector_Exception('E1565', compact('offset'));
        }

        if (null !== $recordKey && null !== $targetKey) {
            $tmOffset = $recordKey . ':' . $targetKey;
        }

        $isSource = $field === 'source';

        $resultList = new \editor_Services_ServiceResult();
        $resultList->setLanguageResource($this->languageResource);

        $results = [];
        $resultsCount = 0;

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);
        $saveDifferentTargetsForSameSource = (bool) $this->config
            ->runtimeOptions
            ->LanguageResources
            ->t5memory
            ->saveDifferentTargetsForSameSource;
        $reorganizeOptions = new ReorganizeOptions($saveDifferentTargetsForSameSource);

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);
        $ids = array_column($memories, 'id');

        foreach ($memories as ['filename' => $tmName, 'id' => $id]) {
            // check if current memory was searched through in prev request
            if ('' !== $offsetTmId && $id < $offsetTmId) {
                continue;
            }

            $this->assertMemoryAvailable($tmName);

            $segmentIdsGenerated = $this->areSegmentIdsGenerated($tmName);
            $successful = $this->api->search($tmName, $tmOffset, $amountOfResults, $searchDTO);

            $response = ApiResponse::fromContentAndStatus(
                $this->api->getResponse()->getBody(),
                $this->api->getResponse()->getStatus(),
            );

            if (
                (! $segmentIdsGenerated && ! $this->isMemoryEmpty($this->languageResource, $tmName))
                || $this->reorganizeService->needsReorganizing($response, $this->languageResource, $tmName)
            ) {
                $this->reorganizeService->reorganizeTm($this->languageResource, $tmName, $reorganizeOptions);

                $successful = $this->api->search($tmName, $tmOffset, $amountOfResults, $searchDTO);
            }

            if (! $successful && $this->isLockingTimeoutOccurred($this->api->getError())) {
                $retries = 0;

                while ($retries < $this->getMaxRequestRetries() && ! $successful) {
                    sleep($this->getRetryDelaySeconds());
                    $retries++;

                    $successful = $this->api->search($tmName, $tmOffset, $amountOfResults, $searchDTO);
                }
            }

            if (! $successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            // In case we have at least one successful search, we reset the reorganize attempts
            $this->reorganizeService->resetReorganizeAttempts($this->languageResource, $tmName);

            /** @var ?object{results: array, NewSearchPosition: string} $result */
            $result = $this->api->getResult();

            if (empty($result) || empty($result->results)) {
                // Reset the TM offset to start from the beginning of the next TM
                $tmOffset = null;

                continue;
            }

            if (in_array(0, array_column($result->results, 'segmentId'), true)) {
                $this->reorganizeService->reorganizeTm($this->languageResource, $tmName, $reorganizeOptions);
            }

            $data = array_map(
                static function ($item) use ($id) {
                    $item->internalKey = $id . ':' . $item->internalKey;

                    return $item;
                },
                $result->results
            );
            $results[] = $data;
            $resultsCount += count($result->results);
            $nextOffset = $result->NewSearchPosition ? ($id . ':' . $result->NewSearchPosition) : null;

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

        $this->tagHandler->setQuerySegment($searchString);

        foreach ($results as $result) {
            $resultList->addResult(
                $this->tagHandler->restoreInResult($result->target, $isSource),
                0,
                $this->getMetaData($result)
            );
            $resultList->setSource($this->tagHandler->restoreInResult($result->source, $isSource));
        }

        return $resultList;
    }

    private function isMemoryEmpty(LanguageResource $languageResource, string $tmName): bool
    {
        $response = $this->t5MemoryApi->downloadTmx(
            $languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName),
            1
        );

        foreach ($response as $stream) {
            $content = $stream->getContents();

            if (str_contains($content, '<tu ')) {
                return false;
            }
        }

        return true;
    }

    public function countSegments(SearchDTO $searchDTO): int
    {
        $amount = 0;
        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);
        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        foreach ($memories as ['filename' => $tmName]) {
            $this->assertMemoryAvailable($tmName);

            $successful = $this->api->search($tmName, '', 0, $searchDTO);

            if (! $successful && $this->isLockingTimeoutOccurred($this->api->getError())) {
                $retries = 0;

                while ($retries < $this->getMaxRequestRetries() && ! $successful) {
                    sleep($this->getRetryDelaySeconds());
                    $retries++;

                    $successful = $this->api->search($tmName, '', 0, $searchDTO);
                }
            }

            $result = $this->api->getResult();

            if (empty($result->NumOfFoundSegments)) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            $amount += $result->NumOfFoundSegments;
        }

        return $amount;
    }

    /***
     * Search the resource for available translation. Where the source text is in
     * resource source language and the received results are in the resource target language
     *
     * {@inheritDoc}
     */
    public function translate(string $searchString)
    {
        // TODO not used
    }

    public function getStatus(
        \editor_Models_LanguageResources_Resource $resource,
        LanguageResource $languageResource = null,
        ?string $tmName = null,
    ): string {
        if ($this->languageResource->isConversionStarted()) {
            return LanguageResourceStatus::CONVERTING;
        }

        if ($this->languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_IN_PROGRESS) {
            return LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
        }

        if ($tmName) {
            return $this->t5MemoryConnector->getStatus($resource, $languageResource, $tmName);
        }

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);
        foreach ($memories as ['filename' => $name]) {
            $status = $this->t5MemoryConnector->getStatus($resource, $languageResource, $name);

            if ($status !== LanguageResourceStatus::AVAILABLE) {
                return $status;
            }
        }

        return LanguageResourceStatus::AVAILABLE;
    }

    private function updateSegmentInMemory(
        string $source,
        string $target,
        string $userName,
        string $context,
        int $timestamp,
        string $fileName,
        string $memoryName,
    ): void {
        [$source, $target] = $this->tmConversionService->convertPair(
            $source,
            $target,
            (int) $this->languageResource->getSourceLang(),
            (int) $this->languageResource->getTargetLang(),
        );

        $saveDifferentTargetsForSameSource = (bool) $this->config
            ->runtimeOptions
            ->LanguageResources
            ->t5memory
            ->saveDifferentTargetsForSameSource;

        $dto = new UpdateSegmentDTO(
            $source,
            $target,
            $fileName,
            $timestamp,
            $userName,
            $context,
        );
        $options = new UpdateOptions(
            useSegmentTimestamp: false,
            saveToDisk: true,
            saveDifferentTargetsForSameSource: $saveDifferentTargetsForSameSource,
            recheckOnUpdate: true,
        );

        try {
            $this->updateRetryService->updateWithRetryInMemory(
                $this->languageResource,
                $memoryName,
                $dto,
                $options,
                $this->getConfig(),
            );
        } catch (SegmentUpdateException $exception) {
            $this->logger->error('E1306', 'Failed to save segment to TM', [
                'languageResource' => $this->languageResource,
                'apiError' => $exception->getMessage(),
            ]);

            throw new \editor_Services_Connector_Exception('E1306');
        }
    }

    private function isLockingTimeoutOccurred(?object $error): bool
    {
        if (null === $error) {
            return false;
        }

        return $error->returnValue === 506;
    }

    /**
     * Helper function to get the metadata which should be shown in the GUI out of a single result
     *
     * @return \stdClass[]
     */
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
        ];
        $result = [];

        foreach ($nameToShow as $name) {
            if (property_exists($found, $name)) {
                $item = new \stdClass();
                $item->name = $name;
                $item->value = $found->{$name};
                if ($name === 'timestamp') {
                    $item->value = date('Y-m-d H:i:s T', strtotime($item->value));
                }
                $result[] = $item;
            }
        }

        return $result;
    }

    private function getBadGatewayException(string $tmName = ''): \editor_Services_Connector_Exception
    {
        $ecode = 'E1313';
        $error = $this->api->getError();
        $data = [
            'service' => $this->getResource()->getName(),
            'languageResource' => $this->languageResource ?? '',
            'tmName' => $tmName,
            'error' => $error,
        ];
        if (strpos($error->error ?? '', 'needs to be organized') !== false) {
            $ecode = 'E1314';
            $data['tm'] = $this->languageResource->getName();
        }

        if (strpos($error->error ?? '', 'too many open translation memory databases') !== false) {
            $ecode = 'E1333';
        }

        return new \editor_Services_Connector_Exception($ecode, $data);
    }

    // Need to move this region to a dedicated class while refactoring connector
    private function getMemoryNameById(int $memoryId): ?string
    {
        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        foreach ($memories as $memory) {
            if ($memory['id'] === $memoryId) {
                return $memory['filename'];
            }
        }

        // TODO add error code
        throw new \editor_Services_Connector_Exception('E1564', [
            'name' => $this->languageResource->getName(),
        ]);
    }

    private function areSegmentIdsGenerated(string $tmName): bool
    {
        $successful = $this->api->status($tmName);

        if (! $successful) {
            return false;
        }

        $result = $this->api->getResult();

        if (! isset($result->segmentIndex)) {
            // This means memory is not loaded into RAM
            // So call for a fake segment to force t5memory to load memory into RAM and call for status again
            $this->api->getEntry($tmName, 7, 1);
            $this->api->status($tmName);
            $result = $this->api->getResult();
        }

        return $result->segmentIndex > 0;
    }

    private function assertMemoryAvailable(string $memoryName): void
    {
        $status = $this->getStatus($this->resource, $this->languageResource, $memoryName);

        if ($status !== LanguageResourceStatus::AVAILABLE) {
            throw new \editor_Services_Connector_Exception('E1377', [
                'languageResource' => $this->languageResource,
                'status' => $status,
            ]);
        }
    }

    protected function getMaxRequestRetries(): int
    {
        return (int) $this->config->runtimeOptions->LanguageResources->t5memory->requestMaxRetries;
    }

    protected function getRetryDelaySeconds(): int
    {
        return (int) $this->config->runtimeOptions->LanguageResources->t5memory->requestRetryDelaySeconds;
    }
}

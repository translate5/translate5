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
use editor_Models_Task as Task;
use editor_Services_OpenTM2_Connector as T5MemoryConnector;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\DTO\DeleteBatchDTO;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;

/**
 * This is a temporary service partially copying functionality from the OpenTM2Connector
 */
class MaintenanceService extends \editor_Services_Connector_Abstract implements UpdatableAdapterInterface
{
    private const CONCORDANCE_SEARCH_NUM_RESULTS = 1;

    private \editor_Services_OpenTM2_HttpApi $api;

    /**
     * Using Xliff based tag handler here
     */
    protected $tagHandlerClass = \editor_Services_Connector_TagHandler_OpenTM2Xliff::class;

    /**
     * Just overwrite the class var hint here
     * @var \editor_Services_Connector_TagHandler_Xliff
     */
    protected $tagHandler;

    /**
     *  Is the connector generally able to support internal Tags for the translate-API
     * @var bool
     */
    protected $internalTagSupport = true;

    private T5MemoryConnector $t5MemoryConnector;

    public function __construct()
    {
        \editor_Services_Connector_Exception::addCodes([
            'E1314' => 'The queried OpenTM2 TM "{tm}" is corrupt and must be reorganized before usage!',
            'E1333' => 'The queried OpenTM2 server has to many open TMs!',
            'E1306' => 'Could not save segment to TM',
            'E1377' => 'Memory status: {status}. Please try again in a while.',
            'E1616' => 'T5Memory server version serving the selected memory is not supported',
            'E1611' => 't5memory: Requested segment not found. Probably it was deleted.',
            'E1612' => 't5memory: Found segment id differs from the requested one, ' .
                'probably it was deleted or edited meanwhile. Try to refresh your search.',
        ]);

        \ZfExtended_Logger::addDuplicatesByEcode('E1333', 'E1306', 'E1314');
        $this->t5MemoryConnector = new T5MemoryConnector();
        parent::__construct();
    }

    public function connectTo(
        LanguageResource $languageResource,
        $sourceLang,
        $targetLang,
    ): void {
        $this->api = \ZfExtended_Factory::get('editor_Services_OpenTM2_HttpApi');
        $this->api->setLanguageResource($languageResource);

        $this->tagHandler = \ZfExtended_Factory::get(
            \editor_Services_Connector_TagHandler_T5MemoryXliff::class,
            [[
                'gTagPairing' => false,
            ]]
        );
        $this->t5MemoryConnector->connectTo($languageResource, $sourceLang, $targetLang);
        parent::connectTo($languageResource, $sourceLang, $targetLang);
    }

    public function update(\editor_Models_Segment $segment, array $options = []): void {
        // TODO not used
    }

    public function updateTranslation(string $source, string $target, string $tmName = '')
    {
        // TODO not used
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
        $this->assertVersionFits();

        $source = $this->tagHandler->prepareQuery($source);
        $this->tagHandler->setInputTagMap($this->tagHandler->getTagMap());
        $target = $this->tagHandler->prepareQuery($target, false);
        $memoryName = $this->getWritableMemory();
        $time = $this->api->getDate($timestamp);

        $this->assertMemoryAvailable($memoryName);

        $this->updateSegmentInMemory(
            $source,
            $target,
            $userName,
            $context,
            $time,
            $fileName,
            $memoryName,
        );
    }

    /**
     * Update method was designed to work with editor_Models_Segment context
     * so this method was added to be able to update a memory entry without an editor_Models_Segment
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
        $this->assertVersionFits();

        $memoryName = $this->getMemoryNameById($memoryId);

        $this->assertMemoryAvailable($memoryName);

        $successful = $this->api->getEntry($memoryName, $segmentRecordKey, $segmentTargetKey);

        if (! $successful) {
            $this->logger->error('E1611', 'Requested segment not found', [
                'languageResource' => $this->languageResource,
            ]);

            throw new \editor_Services_Connector_Exception('E1611');
        }

        $result = $this->api->getResult();

        if ($segmentId !== $result->segmentId) {
            $this->logger->error(
                'E1612',
                'Found segment id differs from the requested one, probably it was deleted meanwhile',
                [
                    'languageResource' => $this->languageResource,
                ]
            );

            throw new \editor_Services_Connector_Exception('E1612');
        }

        $this->deleteEntry($memoryId, $segmentId, $segmentRecordKey, $segmentTargetKey);

        $source = $this->tagHandler->prepareQuery($source);
        $this->tagHandler->setInputTagMap($this->tagHandler->getTagMap());
        $target = $this->tagHandler->prepareQuery($target, false);
        $time = $this->api->getDate($timestamp);

        $this->updateSegmentInMemory(
            $source,
            $target,
            $userName,
            $context,
            $time,
            $fileName,
            $memoryName,
        );
    }

    public function deleteEntry(int $memoryId, int $segmentId, int $recordKey, int $targetKey): void
    {
        $this->assertVersionFits();

        $memoryName = $this->getMemoryNameById($memoryId);

        $this->assertMemoryAvailable($memoryName);

        $successful = $this->api->deleteEntry($memoryName, $segmentId, $recordKey, $targetKey);

        if (! $successful) {
            $this->logger->error('E1306', 'Failed to delete segment from memory', [
                'languageResource' => $this->languageResource,
                'apiError' => $this->api->getError(),
            ]);

            throw new \editor_Services_Connector_Exception('E1306', [
                'languageResource' => $this->languageResource,
                'error' => 'Failed to delete segment from memory',
            ]);
        }
    }

    public function deleteBatch(DeleteBatchDTO $deleteDto): bool
    {
        $this->assertVersionFits();

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        foreach ($memories as ['filename' => $tmName]) {
            $this->assertMemoryAvailable($tmName);

            $successful = $this->api->deleteBatch($tmName, $deleteDto);

            if (! $successful && $this->needsReorganizing($this->api->getError(), $tmName)) {
                $this->addReorganizeWarning();
                $this->reorganizeTm($tmName);

                $successful = $this->api->deleteBatch($tmName, $deleteDto);
            }

            if (! $successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));
            }
        }

        return true;
    }

    /**
     * Fuzzy search
     *
     * {@inheritDoc}
     */
    public function query(\editor_Models_Segment $segment): \editor_Services_ServiceResult
    {
        // TODO not used
        return new \editor_Services_ServiceResult();
    }

    /**
     * Concordance search
     *
     * {@inheritDoc}
     */
    public function search(
        string $searchString,
        $field = 'source',
        $offset = null,
        SearchDTO $searchDTO = null
    ): \editor_Services_ServiceResult {
        $this->assertVersionFits();

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

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        foreach ($memories as ['filename' => $tmName, 'id' => $id]) {
            // check if current memory was searched through in prev request
            if ('' !== $offsetTmId && $id < $offsetTmId) {
                continue;
            }

            $this->assertMemoryAvailable($tmName);

            $segmentIdsGenerated = $this->areSegmentIdsGenerated($tmName);
            $successful = $this->api->search($tmName, $tmOffset, self::CONCORDANCE_SEARCH_NUM_RESULTS, $searchDTO);

            if (
                ! $segmentIdsGenerated
                || (! $successful && $this->needsReorganizing($this->api->getError(), $tmName))
            ) {
                $this->addReorganizeWarning();
                $this->reorganizeTm($tmName);

                $successful = $this->api->search($tmName, $tmOffset, self::CONCORDANCE_SEARCH_NUM_RESULTS, $searchDTO);
            }

            if (! $successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            // In case we have at least one successful search, we reset the reorganize attempts
            $this->resetReorganizeAttempts($this->languageResource);

            /** @var ?object{results: array, NewSearchPosition: string} $result */
            $result = $this->api->getResult();

            if (empty($result) || empty($result->results)) {
                continue;
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
            $resultList->setNextOffset($result->NewSearchPosition ? ($id . ':' . $result->NewSearchPosition) : null);

            // if we get enough results then response them
            /** @var int $resultsCount */
            if (self::CONCORDANCE_SEARCH_NUM_RESULTS <= $resultsCount) {
                break;
            }
        }

        $results = array_merge(...$results);

        if (empty($results)) {
            $resultList->setNextOffset(null);

            return $resultList;
        }

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

    public function countSegments(SearchDTO $searchDTO): int
    {
        $this->assertVersionFits();

        $amount = 0;
        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);
        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        foreach ($memories as ['filename' => $tmName]) {
            $this->assertMemoryAvailable($tmName);

            $successful = $this->api->search($tmName, '', 0, $searchDTO);

            if (! $successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            $result = $this->api->getResult();
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
        return $this->t5MemoryConnector->getStatus($resource, $languageResource, $tmName);
    }

    private function updateSegmentInMemory(
        string $source,
        string $target,
        string $userName,
        string $context,
        string $time,
        string $fileName,
        string $memoryName,
    ): void {
        $successful = $this->api->update($source, $target, $userName, $context, $time, $fileName, $memoryName);

        if ($successful) {
            return;
        }

        $apiError = $this->api->getError();
        if ($this->isMemoryOverflown($apiError)) {
            $this->addOverflowWarning();

            $currentWritableMemoryName = $this->getWritableMemory();
            if ($memoryName === $currentWritableMemoryName) {
                $newName = $this->generateNextMemoryName($this->languageResource);
                $newName = $this->api->createEmptyMemory($newName, $this->languageResource->getSourceLangCode());
                $this->addMemoryToLanguageResource($this->languageResource, $newName);
            } else {
                $newName = $currentWritableMemoryName;
            }

            $successful = $this->api->update($source, $target, $userName, $context, $time, $fileName, $newName);
        }

        if ($this->needsReorganizing($apiError, $memoryName)) {
            $this->addReorganizeWarning();
            $this->reorganizeTm($memoryName);

            $successful = $this->api->update($source, $target, $userName, $context, $time, $fileName, $memoryName);
        }

        if (! $successful) {
            $apiError = $this->api->getError() ?? $apiError;
            $this->logger->error('E1306', 'Failed to save segment to TM', [
                'languageResource' => $this->languageResource,
                'apiError' => $apiError,
            ]);

            throw new \editor_Services_Connector_Exception('E1306');
        }
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

    /**
     * Updates the filename of the language resource instance with the filename coming from the TM system
     * @throws \Zend_Exception
     */
    private function addMemoryToLanguageResource(
        LanguageResource $languageResource,
        string $tmName,
    ): void {
        $prefix = \Zend_Registry::get('config')->runtimeOptions->LanguageResources->opentm2->tmprefix;
        if (! empty($prefix)) {
            //remove the prefix from being stored into the TM
            $tmName = str_replace('^' . $prefix . '-', '', '^' . $tmName);
        }

        $memories = $languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        $id = 0;
        foreach ($memories as &$memory) {
            $memory['id'] = $id++;
            $memory['readonly'] = true;
        }

        $memories[] = [
            'id' => $id,
            'filename' => $tmName,
            'readonly' => false,
        ];

        $languageResource->addSpecificData('memories', $memories);
        //saving it here makes the TM available even when the TMX import was crashed
        $languageResource->save();
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

    #region Reorganize TM
    // Need to move this region to a dedicated class while refactoring connector
    private const REORGANIZE_ATTEMPTS = 'reorganize_attempts';

    private const REORGANIZE_STARTED_AT = 'reorganize_started_at';

    private const MAX_REORGANIZE_TIME_MINUTES = 30;

    private const REORGANIZE_WAIT_TIME_SECONDS = 60;

    private const VERSION_0_4 = '0.4';

    private const VERSION_0_5 = '0.5';

    private const VERSION_0_6 = '0.6';

    private function needsReorganizing(\stdClass $error, string $tmName): bool
    {
        $errorCodes = explode(
            ',',
            $this->config->runtimeOptions->LanguageResources->t5memory->reorganizeErrorCodes
        );

        $errorSupposesReorganizing = (
            isset($error->code)
            && str_replace($errorCodes, '', $error->code) !== $error->code
        )
            || (isset($error->error) && $error->error === 500);

        // Check if error codes contains any of the values
        $needsReorganizing = $errorSupposesReorganizing && ! $this->isReorganizingAtTheMoment($tmName);

        if ($needsReorganizing && $this->isMaxReorganizeAttemptsReached($this->languageResource)) {
            $this->logger->warn(
                'E1314',
                'The queried TM returned error which is configured for automatic TM reorganization.' .
                'But maximum amount of attempts to reorganize it reached.',
                [
                    'apiError' => $this->api->getError(),
                ]
            );
            $needsReorganizing = false;
        }

        return $needsReorganizing;
    }

    public function reorganizeTm(?string $tmName = null): bool
    {
        if (null === $tmName) {
            $tmName = $this->getWritableMemory();
        }

        $this->languageResource->refresh();
        $this->increaseReorganizeAttempts($this->languageResource);
        $this->setReorganizeStatusInProgress($this->languageResource);
        $this->languageResource->save();

        $version = $this->getT5MemoryVersion();

        $reorganized = $this->api->reorganizeTm($tmName);

        if ($version !== self::VERSION_0_4) {
            $reorganized = $this->waitReorganizeFinished($tmName);
        }

        $this->languageResource->setStatus(
            $reorganized ? LanguageResourceStatus::AVAILABLE : LanguageResourceStatus::REORGANIZE_FAILED
        );

        $this->languageResource->save();

        return $reorganized;
    }

    public function isReorganizingAtTheMoment(?string $tmName = null): bool
    {
        $this->resetReorganizingIfNeeded();

        if ($this->getT5MemoryVersion() !== self::VERSION_0_4) {
            $status = $this->getStatus($this->resource, tmName: $tmName);

            return $status === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
        }

        return $this->languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
    }

    private function addReorganizeWarning(Task $task = null): void
    {
        $params = [
            'apiError' => $this->api->getError(),
        ];

        if (null !== $task) {
            $params['task'] = $task;
        }

        $this->logger->warn(
            'E1314',
            'The queried TM returned error which is configured for automatic TM reorganization',
            $params
        );
    }

    private function addOverflowWarning(Task $task = null): void
    {
        $params = [
            'name' => $this->languageResource->getName(),
            'apiError' => $this->api->getError(),
        ];

        if (null !== $task) {
            $params['task'] = $task;
        }

        $this->logger->warn(
            'E1603',
            'Language Resource [{name}] current writable memory is overflown, creating a new one',
            $params
        );
    }

    private function waitReorganizeFinished(?string $tmName = null): bool
    {
        $elapsedTime = 0;
        $sleepTime = 5;

        while ($elapsedTime < self::REORGANIZE_WAIT_TIME_SECONDS) {
            if (! $this->isReorganizingAtTheMoment($tmName)) {
                return true;
            }

            sleep($sleepTime);
            $elapsedTime += $sleepTime;
        }

        return false;
    }

    private function isMaxReorganizeAttemptsReached(?LanguageResource $languageResource): bool
    {
        if (null === $languageResource) {
            return false;
        }

        $currentAttempts = $languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS) ?? 0;
        $maxAttempts = $this->config->runtimeOptions->LanguageResources->t5memory->maxReorganizeAttempts;

        return $currentAttempts >= $maxAttempts;
    }

    private function increaseReorganizeAttempts(LanguageResource $languageResource): void
    {
        $languageResource->addSpecificData(
            self::REORGANIZE_ATTEMPTS,
            ($languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS) ?? 0) + 1
        );
    }

    private function resetReorganizeAttempts(LanguageResource $languageResource): void
    {
        if ($languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS) === null) {
            return;
        }

        // In some cases language resource is detached from DB
        $languageResource->refresh();
        $languageResource->removeSpecificData(self::REORGANIZE_ATTEMPTS);
        $languageResource->save();
    }

    private function getT5MemoryVersion(): string
    {
        $success = $this->api->resources();

        if (! $success) {
            return self::VERSION_0_4;
        }

        $resources = $this->api->getResult();

        return match (true) {
            str_starts_with($resources->Version ?? '', self::VERSION_0_5) => self::VERSION_0_5,
            str_starts_with($resources->Version ?? '', self::VERSION_0_6) => self::VERSION_0_6,
            default => self::VERSION_0_4,
        };
    }

    /**
     * Applicable only for t5memory 0.4.x
     */
    private function resetReorganizingIfNeeded(): void
    {
        $reorganizeStartedAt = $this->languageResource->getSpecificData(self::REORGANIZE_STARTED_AT);

        if (null === $reorganizeStartedAt) {
            return;
        }

        if ((new \DateTimeImmutable($reorganizeStartedAt))->modify(
            sprintf('+%d minutes', self::MAX_REORGANIZE_TIME_MINUTES)
        ) < new \DateTimeImmutable()
        ) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $this->languageResource->refresh();
            $this->languageResource->removeSpecificData(self::REORGANIZE_STARTED_AT);
            $this->languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
            $this->languageResource->save();
        }
    }

    /**
     * Applicable only for t5memory 0.4.x
     */
    private function setReorganizeStatusInProgress(LanguageResource $languageResource): void
    {
        $languageResource->setStatus(LanguageResourceStatus::REORGANIZE_IN_PROGRESS);
        $languageResource->addSpecificData(self::REORGANIZE_STARTED_AT, date(\DateTimeInterface::RFC3339));
    }
    #endregion Reorganize TM

    private function getWritableMemory(): string
    {
        foreach ($this->languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            if (! $memory['readonly']) {
                return $memory['filename'];
            }
        }

        throw new \editor_Services_Connector_Exception('E1564', [
            'name' => $this->languageResource->getName(),
        ]);
    }

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

    private function isMemoryOverflown(?object $error): bool
    {
        if (null === $error) {
            return false;
        }

        $errorCodes = explode(
            ',',
            $this->config->runtimeOptions->LanguageResources->t5memory->memoryOverflowErrorCodes
        );
        $errorCodes = array_map(fn ($code) => 'rc = ' . $code, $errorCodes);

        return isset($error->error)
            && str_replace($errorCodes, '', $error->error) !== $error->error;
    }

    private function generateNextMemoryName(LanguageResource $languageResource): string
    {
        $memories = $languageResource->getSpecificData('memories', parseAsArray: true);

        $pattern = '/_next-(\d+)/';

        $currentMax = 0;
        foreach ($memories as $memory) {
            if (! preg_match($pattern, $memory['filename'], $matches)) {
                return $memory['filename'] . '_next-1';
            }

            $currentMax = $currentMax > $matches[1] ? $currentMax : (int) $matches[1];
        }

        return preg_replace($pattern, '_next-' . ($currentMax + 1), $memories[0]['filename']);
    }

    private function areSegmentIdsGenerated(string $tmName): bool
    {
        $successful = $this->api->status($tmName);

        if (! $successful) {
            return false;
        }

        $result = $this->api->getResult();

        return isset($result->segmentIndex) && $result->segmentIndex > 0;
    }

    private function assertMemoryAvailable(string $memoryName): void
    {
        $status = $this->getStatus($this->resource, $this->languageResource, $memoryName);

        if ($status !== LanguageResourceStatus::AVAILABLE) {
            throw new \editor_Services_Connector_Exception('E1377', [
                'languageResource' => $this->languageResource,
                'status' => 'importing',
            ]);
        }
    }

    private function assertVersionFits(): void
    {
        $version = $this->getT5MemoryVersion();

        // TODO fix when export is merged
        if ($version !== self::VERSION_0_6) {
            throw new \editor_Services_Connector_Exception('E1616', [
                'languageResource' => $this->languageResource,
            ]);
        }
    }

    public function checkUpdatedSegment(SegmentModel $segment): void
    {
        // TODO: Implement checkUpdatedSegment() method.
    }
}

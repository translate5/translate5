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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Task as Task;
use editor_Services_Connector_TagHandler_Abstract as TagHandler;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\RescheduleUpdateNeededException;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Adapter\Export\ExportAdapterInterface;
use MittagQI\Translate5\LanguageResource\Adapter\Export\TmFileExtension;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\Adapter\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\ConstantApi;
use MittagQI\Translate5\T5Memory\Api\Response\MutationResponse as MutationApiResponse;
use MittagQI\Translate5\T5Memory\Api\Response\Response as ApiResponse;
use MittagQI\Translate5\T5Memory\Api\VersionedApiFactory;
use MittagQI\Translate5\T5Memory\CreateMemoryService;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use MittagQI\Translate5\T5Memory\Exception\UnableToCreateMemoryException;
use MittagQI\Translate5\T5Memory\ExportService;
use MittagQI\Translate5\T5Memory\FlushMemoryService;
use MittagQI\Translate5\T5Memory\ImportService;
use MittagQI\Translate5\T5Memory\MemoryNameGenerator;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\ReorganizeService;
use MittagQI\Translate5\T5Memory\RetryService;
use MittagQI\Translate5\T5Memory\VersionService;

/**
 * T5memory / OpenTM2 Connector
 *
 * IMPORTANT: see the doc/comments in MittagQI\Translate5\Service\T5Memory
 */
class editor_Services_OpenTM2_Connector extends editor_Services_Connector_Abstract implements UpdatableAdapterInterface, FileBasedInterface, ExportAdapterInterface
{
    private const CONCORDANCE_SEARCH_NUM_RESULTS = 1;

    private const VERSION_0_5 = '0.5';

    protected const TAG_HANDLER_CONFIG_PART = 't5memory';

    public const NEXT_SUFFIX = '_next-';

    private const SEGMENT_NR_CONTEXT_PREFIX = 'SegmentNr: ';

    /**
     * Connector
     * @var editor_Services_OpenTM2_HttpApi
     */
    protected $api;

    /**
     *  Is the connector generally able to support internal Tags for the translate-API
     * @var bool
     */
    protected $internalTagSupport = true;

    private readonly TmConversionService $conversionService;

    private readonly VersionService $versionService;

    private readonly PersistenceService $persistenceService;

    private readonly ExportService $exportService;

    private readonly ReorganizeService $reorganizeService;

    private readonly ImportService $importService;

    private readonly MemoryNameGenerator $memoryNameGenerator;

    private readonly CreateMemoryService $createMemoryService;

    private readonly RetryService $waitingService;

    private readonly ConstantApi $constantApi;

    private readonly FlushMemoryService $flushMemoryService;

    public function __construct()
    {
        editor_Services_Connector_Exception::addCodes([
            'E1314' => 'The queried t5memory TM "{tm}" is corrupt and must be reorganized before usage!',
            'E1333' => 'The queried t5memory server has to many open TMs!',
        ]);

        ZfExtended_Logger::addDuplicatesByEcode('E1333', 'E1306', 'E1314');

        parent::__construct();

        $this->conversionService = TmConversionService::create();
        $this->persistenceService = PersistenceService::create();
        $this->versionService = VersionService::create();
        $this->exportService = new ExportService(
            $this->logger,
            $this->versionService,
            $this->conversionService,
            VersionedApiFactory::create(),
            $this->persistenceService
        );

        $this->reorganizeService = ReorganizeService::create();
        $this->importService = ImportService::create();
        $this->memoryNameGenerator = new MemoryNameGenerator();
        $this->createMemoryService = CreateMemoryService::create();
        $this->waitingService = RetryService::create();
        $this->constantApi = ConstantApi::create();
        $this->flushMemoryService = FlushMemoryService::create();
    }

    public function connectTo(
        LanguageResource $languageResource,
        $sourceLang,
        $targetLang,
        $config = null,
    ): void {
        $this->api = ZfExtended_Factory::get('editor_Services_OpenTM2_HttpApi');
        $this->api->setLanguageResource($languageResource);

        parent::connectTo($languageResource, $sourceLang, $targetLang, $config);

        $this->tagHandler = $this->createTagHandler([
            'gTagPairing' => false,
            TagHandler::OPTION_KEEP_WHITESPACE_TAGS => $this->isSendingWhitespaceAsTagEnabled(),
        ]);
    }

    /**
     * @throws Zend_Exception
     */
    public function addTm(array $fileinfo = null, array $params = null): bool
    {
        //to ensure that we get unique TMs Names although of the above stripped content,
        // we add the LanguageResource ID and a prefix which can be configured per each translate5 instance
        $name = $this->memoryNameGenerator->generateTmFilename($this->languageResource);

        if (isset($params['createNewMemory'])) {
            $name = $this->memoryNameGenerator->generateNextMemoryName($this->languageResource);
        }

        // If we are adding a TMX file as LanguageResource, we must create an empty memory first.
        $validFileTypes = $this->getValidFiletypes();
        if (empty($validFileTypes['TMX'])) {
            throw new ZfExtended_NotFoundException('t5memory: Cannot addTm for TMX-file; valid file types are missing.');
        }

        $noFile = empty($fileinfo);
        $tmxUpload = ! $noFile
            && (
                in_array($fileinfo['type'], $validFileTypes['TMX'])
                || in_array($fileinfo['type'], $validFileTypes['ZIP'])
            )
            && preg_match('/(\.tmx|\.zip)$/', strtolower($fileinfo['name']));

        if ($noFile || $tmxUpload) {
            try {
                $tmName = $this->createMemoryService->createEmptyMemoryWithRetry($this->languageResource, $name);
            } catch (UnableToCreateMemoryException) {
                return false;
            }

            $this->persistenceService->addMemoryToLanguageResource($this->languageResource, $tmName);

            //if initial upload is a TMX file, we have to import it.
            if ($tmxUpload) {
                return $this->addAdditionalTm($fileinfo, [
                    'tmName' => $tmName,
                    'stripFramingTags' => $params['stripFramingTags'] ?? null,
                ]);
            }

            return true;
        }

        try {
            $tmName = $this->createMemoryService->createMemory(
                $this->languageResource,
                $name,
                $fileinfo['tmp_name'],
                $this->getStripFramingTagsValue($params)
            );
        } catch (\Exception $e) {
            $this->logger->error('E1304', 't5memory: could not create prefilled TM', [
                'languageResource' => $this->languageResource,
            ]);
            $this->logger->exception($e);

            return false;
        }

        $this->persistenceService->addMemoryToLanguageResource($this->languageResource, $tmName);

        return true;
    }

    /**
     * @return iterable<string>
     */
    private function getImportFilesFromUpload(?array $fileInfo): iterable
    {
        if (null === $fileInfo) {
            return yield from [];
        }

        $validator = new Zend_Validate_File_IsCompressed();
        if (! $validator->isValid($fileInfo['tmp_name'])) {
            return yield $fileInfo['tmp_name'];
        }

        $zip = new ZipArchive();
        if (! $zip->open($fileInfo['tmp_name'])) {
            $this->logger->error('E1596', 't5memory: Unable to open zip file from file-path:' . $fileInfo['tmp_name']);

            return yield from [];
        }

        $newPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($fileInfo['name'], PATHINFO_FILENAME);

        if (! $zip->extractTo($newPath)) {
            $this->logger->error('E1597', 't5memory: Content from zip file could not be extracted.');
            $zip->close();

            return yield from [];
        }

        $zip->close();

        foreach (editor_Utils::generatePermutations('tmx') as $patter) {
            yield from glob($newPath . DIRECTORY_SEPARATOR . '*.' . implode($patter)) ?: [];
        }
    }

    public function addAdditionalTm(array $fileinfo = null, array $params = null): bool
    {
        try {
            $this->importService->importTmx(
                $this->languageResource,
                $this->getImportFilesFromUpload($fileinfo),
                $this->getStripFramingTagsValue($params)
            );
        } catch (\Exception $e) {
            $this->logger->error('E1304', 't5memory: could not import TMX', [
                'languageResource' => $this->languageResource,
                'apiError' => $e->getMessage(),
            ]);
            $this->logger->exception($e);

            return false;
        }

        return true;
    }

    public function getValidFiletypes(): array
    {
        return [
            'ZIP' => ['application/zip'],
            'TM' => ['application/zip'],
            'TMX' => ['application/xml', 'text/xml'],
        ];
    }

    public function getValidExportTypes(): array
    {
        return TmFileExtension::getValidExportTypes();
    }

    public function getTm($mime)
    {
        $file = $this->exportService->export(
            $this->languageResource,
            TmFileExtension::fromMimeType($mime)
        );

        if (null === $file) {
            return '';
        }

        return file_get_contents($file);
    }

    public function update(editor_Models_Segment $segment, array $options = []): void
    {
        $tmName = $this->persistenceService->getWritableMemory($this->languageResource);

        if ($this->reorganizeService->isReorganizingAtTheMoment($this->languageResource, $tmName, $this->isInternalFuzzy())) {
            if ($options[UpdatableAdapterInterface::RESCHEDULE_UPDATE_ON_ERROR] ?? false) {
                throw new RescheduleUpdateNeededException();
            }

            throw new editor_Services_Connector_Exception('E1512', [
                'status' => LanguageResourceStatus::REORGANIZE_IN_PROGRESS,
                'service' => $this->getResource()->getName(),
                'languageResource' => $this->languageResource,
            ]);
        }

        $fileName = $this->getFileName($segment);
        $source = $this->tagHandler->prepareQuery($this->getQueryString($segment));
        $this->tagHandler->setInputTagMap($this->tagHandler->getTagMap());
        $target = $this->tagHandler->prepareQuery($segment->getTargetEdit(), false);
        $saveToDisk = $options[UpdatableAdapterInterface::SAVE_TO_DISK] ?? true;
        $saveToDisk = $saveToDisk && ! $this->isInternalFuzzy();
        $useSegmentTimestamp = $options[UpdatableAdapterInterface::USE_SEGMENT_TIMESTAMP] ?? false;
        $timestamp = $useSegmentTimestamp
            ? $this->api->getDate(strtotime($segment->getTimestamp()))
            : $this->api->getNowDate();
        $userName = $segment->getUserName();
        $context = $this->getSegmentContext($segment);

        $recheckOnUpdate = $options[UpdatableAdapterInterface::RECHECK_ON_UPDATE] ?? false;
        $dataSent = [
            'source' => $source,
            'target' => $target,
            'userName' => $userName,
            'context' => $context,
            'timestamp' => $timestamp,
            'fileName' => $fileName,
        ];

        $this->updateWithRetry(
            $source,
            $target,
            $userName,
            $context,
            $timestamp,
            $fileName,
            $tmName,
            $saveToDisk,
            $dataSent,
            $segment,
            $recheckOnUpdate
        );
    }

    public function getUpdateDTO(\editor_Models_Segment $segment, array $options = []): UpdateSegmentDTO
    {
        $fileName = $this->getFileName($segment);
        $source = $this->tagHandler->prepareQuery($this->getQueryString($segment));
        $this->tagHandler->setInputTagMap($this->tagHandler->getTagMap());
        $target = $this->tagHandler->prepareQuery($segment->getTargetEdit(), false);
        $useSegmentTimestamp = $options[UpdatableAdapterInterface::USE_SEGMENT_TIMESTAMP] ?? false;
        $timestamp = $useSegmentTimestamp
            ? $this->api->getDate(strtotime($segment->getTimestamp()))
            : $this->api->getNowDate();
        $userName = $segment->getUserName();
        $context = $this->getSegmentContext($segment);

        return new UpdateSegmentDTO(
            $segment->getTaskGuid(),
            (int) $segment->getId(),
            $source,
            $target,
            $fileName,
            $timestamp,
            $userName,
            $context,
        );
    }

    public function updateWithDTO(UpdateSegmentDTO $dto, array $options, editor_Models_Segment $segment): void
    {
        $tmName = $this->persistenceService->getWritableMemory($this->languageResource);

        if ($this->reorganizeService->isReorganizingAtTheMoment($this->languageResource, $tmName, $this->isInternalFuzzy())) {
            if ($options[UpdatableAdapterInterface::RESCHEDULE_UPDATE_ON_ERROR] ?? false) {
                throw new RescheduleUpdateNeededException();
            }

            throw new editor_Services_Connector_Exception('E1512', [
                'status' => LanguageResourceStatus::REORGANIZE_IN_PROGRESS,
                'service' => $this->getResource()->getName(),
                'languageResource' => $this->languageResource,
            ]);
        }

        $saveToDisk = $options[UpdatableAdapterInterface::SAVE_TO_DISK] ?? true;
        $saveToDisk = $saveToDisk && ! $this->isInternalFuzzy();

        $source = $dto->source;
        $target = $dto->target;
        $userName = $dto->userName;
        $context = $dto->context;
        $timestamp = $dto->timestamp;
        $fileName = $dto->fileName;
        $dataSent = [
            'source' => $source,
            'target' => $target,
            'userName' => $userName,
            'context' => $context,
            'timestamp' => $timestamp,
            'fileName' => $fileName,
        ];
        $recheckOnUpdate = $options[UpdatableAdapterInterface::RECHECK_ON_UPDATE] ?? false;

        $this->updateWithRetry(
            $source,
            $target,
            $userName,
            $context,
            $timestamp,
            $fileName,
            $tmName,
            $saveToDisk,
            $dataSent,
            $segment,
            $recheckOnUpdate
        );
    }

    private function updateWithRetry(
        string $source,
        string $target,
        string $userName,
        string $context,
        string $timestamp,
        string $fileName,
        string $tmName,
        bool $saveToDisk,
        array $dataSent,
        editor_Models_Segment $segment,
        bool $recheckOnUpdate,
    ): void {
        if ($this->languageResource->isConversionStarted()) {
            throw new editor_Services_Connector_Exception('E1512', [
                'status' => LanguageResourceStatus::CONVERTING,
                'service' => $this->getResource()->getName(),
                'languageResource' => $this->languageResource,
            ]);
        }

        $elapsedTime = 0;
        $maxWaitingTime = $this->waitingService->getMaxWaitingTimeSeconds();

        while ($elapsedTime < $maxWaitingTime) {
            $successful = $this->api->update(
                $source,
                $target,
                $userName,
                $context,
                $timestamp,
                $fileName,
                $tmName,
                $saveToDisk
            );

            if ($successful) {
                $this->checkUpdateResponse($dataSent, $this->api->getResult());
                $this->checkUpdatedSegmentIfNeeded($segment, $recheckOnUpdate);

                return;
            }

            $apiError = $this->api->getError();

            $response = MutationApiResponse::fromContentAndStatus(
                $this->api->getResponse()->getBody(),
                $this->api->getResponse()->getStatus()
            );

            if ($this->reorganizeService->needsReorganizing(
                $response,
                $this->languageResource,
                $tmName,
                $segment->getTask(),
                $this->isInternalFuzzy()
            )) {
                $this->reorganizeService->reorganizeTm($this->languageResource, $tmName, $this->isInternalFuzzy());
            } elseif ($response->isMemoryOverflown($this->config)) {
                if (! $response->isBlockOverflown($this->config)) {
                    $this->persistenceService->setMemoryReadonly(
                        $this->languageResource,
                        $tmName,
                        $this->isInternalFuzzy()
                    );
                }

                $newName = $this->persistenceService->getNextWritableMemory($this->languageResource, $tmName);

                if ($newName) {
                    $tmName = $newName;

                    continue;
                }

                $this->addOverflowLog($segment->getTask());
                $this->flushMemoryService->flush($this->languageResource, $tmName);
                $newName = $this->memoryNameGenerator->generateNextMemoryName($this->languageResource);

                try {
                    $newName = $this->createMemoryService->createEmptyMemoryWithRetry(
                        $this->languageResource,
                        $newName,
                    );
                } catch (UnableToCreateMemoryException) {
                    break;
                }

                $this->persistenceService->addMemoryToLanguageResource(
                    $this->languageResource,
                    $newName,
                    $this->isInternalFuzzy()
                );
                $tmName = $newName;
            } elseif ($this->isLockingTimeoutOccurred($apiError)) {
                // Wait before retrying
                sleep($this->waitingService->getRetryDelaySeconds());
            } else {
                // If no specific error handling is applicable, break the loop
                break;
            }

            $elapsedTime = $this->waitingService->getRetryDelaySeconds();
        }

        // send the error to the frontend
        editor_Services_Manager::reportTMUpdateError($apiError ?? null);

        $this->logger->error('E1306', 't5memory: could not save segment to TM', [
            'languageResource' => $this->languageResource,
            'segment' => $segment,
            'apiError' => $apiError ?? '',
        ]);

        throw new SegmentUpdateException();
    }

    private function isLockingTimeoutOccurred(?object $error): bool
    {
        if (null === $error) {
            return false;
        }

        return $error->returnValue === 506;
    }

    public function updateTranslation(string $source, string $target, string $tmName = '')
    {
        if (empty($tmName)) {
            $tmName = $this->persistenceService->getWritableMemory($this->languageResource);
        }

        // TODO why we don't process any errors here?
        $this->api->updateText($source, $target, $tmName);
    }

    /**
     * Fuzzy search
     *
     * {@inheritDoc}
     */
    public function query(editor_Models_Segment $segment): editor_Services_ServiceResult
    {
        $fileName = $this->getFileName($segment);
        $queryString = $this->getQueryString($segment);
        $resultList = $this->queryTms($queryString, $segment, $fileName);

        if (empty($resultList->getResult())) {
            return $resultList;
        }

        return $this->getResultListGrouped($resultList);
    }

    /**
     * returns the filename to a segment
     */
    protected function getFileName(editor_Models_Segment $segment): string
    {
        return editor_ModelInstances::file((int) $segment->getFileId())->getFileName();
    }

    /**
     * Helper function to get the metadata which should be shown in the GUI out of a single result
     *
     * @return stdClass[]
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
                $item = new stdClass();
                $item->name = $name;
                $item->value = $found->{$name};
                if ($name == 'timestamp') {
                    $item->value = date('Y-m-d H:i:s T', strtotime($item->value));
                }
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Concordance search
     *
     * {@inheritDoc}
     */
    public function search(string $searchString, $field = 'source', $offset = null): editor_Services_ServiceResult
    {
        $offsetTmId = null;
        $tmOffset = null;
        $recordKey = null;
        $targetKey = null;

        if (null !== $offset) {
            @[$offsetTmId, $recordKey, $targetKey] = explode(':', (string) $offset);
        }

        if ('' !== $offsetTmId && null === $recordKey && null === $targetKey) {
            throw new editor_Services_Connector_Exception('E1565', compact('offset'));
        }

        if (null !== $recordKey && null !== $targetKey) {
            $tmOffset = $recordKey . ':' . $targetKey;
        }

        $isSource = $field === 'source';

        $searchString = $this->tagHandler->prepareQuery($searchString, $isSource);

        $resultList = new editor_Services_ServiceResult();
        $resultList->setLanguageResource($this->languageResource);

        if ($this->languageResource->isConversionStarted()) {
            return $resultList;
        }

        $results = [];
        $resultsCount = 0;

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        foreach ($memories as ['filename' => $tmName, 'id' => $id]) {
            // check if current memory was searched through in prev request
            if ('' !== $offsetTmId && $id < $offsetTmId) {
                continue;
            }

            if ($this->reorganizeService->isReorganizingAtTheMoment($this->languageResource, $tmName, $this->isInternalFuzzy())) {
                continue;
            }

            $numResults = self::CONCORDANCE_SEARCH_NUM_RESULTS - $resultsCount;

            $successful = $this->api->concordanceSearch($searchString, $tmName, $field, $tmOffset, $numResults);

            $response = ApiResponse::fromContentAndStatus(
                $this->api->getResponse()->getBody(),
                $this->api->getResponse()->getStatus(),
            );

            if ($this->reorganizeService->needsReorganizing(
                $response,
                $this->languageResource,
                $tmName,
                null,
                $this->isInternalFuzzy()
            )) {
                $this->reorganizeService->reorganizeTm($this->languageResource, $tmName, $this->isInternalFuzzy());

                $successful = $this->api->concordanceSearch($searchString, $tmName, $field, $tmOffset, $numResults);
            }

            if (! $successful && $this->isLockingTimeoutOccurred($this->api->getError())) {
                $concordanceSearch =
                    fn () => $this->api->concordanceSearch($searchString, $tmName, $field, $tmOffset, $numResults)
                        ? [WaitCallState::Done, true]
                        : [WaitCallState::Retry, false];

                $successful = (bool) $this->waitingService->callAwaiting($concordanceSearch);
            }

            if (! $successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            // In case we have at least one successful search, we reset the reorganize attempts
            $this->reorganizeService->resetReorganizeAttempts($this->languageResource, $this->isInternalFuzzy());

            $result = $this->api->getResult();

            if (empty($result) || empty($result->results)) {
                continue;
            }

            $results[] = $result->results;
            $resultsCount += count($result->results);
            $resultList->setNextOffset($result->NewSearchPosition ? $id . ':' . $result->NewSearchPosition : null);

            // if we get enough results then response them
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
            $resultList->addResult($this->tagHandler->restoreInResult($result->target, $isSource));
            $resultList->setSource($this->tagHandler->restoreInResult($result->source, $isSource));
        }

        return $resultList;
    }

    /**
     * Search the resource for available translation. Where the source text is in
     * resource source language and the received results are in the resource target language
     *
     * {@inheritDoc}
     */
    public function translate(string $searchString)
    {
        //create dummy segment so we can use the lookup
        $dummySegment = ZfExtended_Factory::get(editor_Models_Segment::class);
        $dummySegment->init();

        return $this->queryTms($searchString, $dummySegment, 'source');
    }

    public function delete(): void
    {
        $successfullyDeleted = true;

        foreach ($this->languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            $successfullyDeleted = $successfullyDeleted && $this->deleteMemory($memory['filename']);
        }

        if (! $successfullyDeleted) {
            $this->throwBadGateway();
        }
    }

    public function deleteMemory(string $filename, ?callable $onSuccess = null): bool
    {
        $deleted = $this->api->delete($filename);

        if ($deleted) {
            $onSuccess && $onSuccess();

            return true;
        }

        $resp = $this->api->getResponse();

        if ($resp->getStatus() == 404
            || $resp->getStatus() == 500 && str_contains($resp->getBody(), 'not found(error 48)')) {
            $onSuccess && $onSuccess();

            // if the result was a 404, then there is nothing to delete,
            // so throw no error then and delete just locally
            return true;
        }

        return false;
    }

    /**
     * Throws a service connector exception
     * @throws editor_Services_Connector_Exception
     */
    private function throwBadGateway(): void
    {
        throw $this->getBadGatewayException();
    }

    private function getBadGatewayException(string $tmName = ''): editor_Services_Connector_Exception
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

        return new editor_Services_Connector_Exception($ecode, $data);
    }

    /**
     * @throws editor_Services_Exceptions_InvalidResponse
     */
    public function getStatus(
        editor_Models_LanguageResources_Resource $resource,
        LanguageResource $languageResource = null,
        ?string $tmName = null,
    ): string {
        $this->lastStatusInfo = '';

        // is may injected with the call
        if (! empty($languageResource)) {
            $this->languageResource = $languageResource;
        }

        // for the rare cases where no language-resource is present
        if (null === $this->languageResource) {
            return $this->constantApi->ping($resource->getUrl())
                ? LanguageResourceStatus::AVAILABLE
                : LanguageResourceStatus::ERROR;
        }

        if ($this->languageResource->isConversionStarted()) {
            return LanguageResourceStatus::CONVERTING;
        }

        // let's check the internal state before calling API for status as import worker might not have run yet
        if ($this->languageResource->getStatus() === LanguageResourceStatus::IMPORT) {
            return LanguageResourceStatus::IMPORT;
        }

        if (
            ! $this->versionService->isLRVersionGreaterThan(self::VERSION_0_5, $this->languageResource)
            && $this->reorganizeService->isReorganizingAtTheMoment($this->languageResource, $tmName, $this->isInternalFuzzy())
        ) {
            // Status import to prevent any other queries to TM to be performed
            return LanguageResourceStatus::IMPORT;
        }

        $tmName = $tmName ?: $this->persistenceService->getLastWritableMemory($this->languageResource);

        if (empty($tmName)) {
            $this->lastStatusInfo = 'The internal stored filename is invalid';

            return LanguageResourceStatus::NOCONNECTION;
        }

        // TODO remove after fully migrated to t5memory v0.5.x

        $status = $this->constantApi->getStatus(
            $this->languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName)
        );

        if ($status->successful()) {
            return $status->status;
        }

        $this->lastStatusInfo = $status->getErrorMessage();

        return LanguageResourceStatus::ERROR;
    }

    public function isEmpty(): bool
    {
        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        if (count($memories) === 0) {
            return true;
        }

        if (count($memories) !== 1) {
            return false;
        }

        // load the memory of the language resource
        $this->translate('');

        $name = $memories[0]['filename'];

        $status = $this->constantApi->getStatus(
            $this->languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($name)
        );

        if (LanguageResourceStatus::ERROR === $status->status) {
            return false;
        }

        $body = $status->getBody();

        return 'open' === ($body['status'] ?? null) && 0 === ($body['segmentIndex'] ?? null);
    }

    /**
     * Calculate the new matchrate value.
     * Check if the current match is of type context-match or exact-exact match
     *
     * @param int $matchRate
     * @param array $metaData
     * @param editor_Models_Segment $segment
     * @param string $filename
     *
     * @return integer
     */
    protected function calculateMatchRate($matchRate, $metaData, $segment, $filename)
    {
        if ($matchRate < 100) {
            return $matchRate;
        }

        $context = $this->getSegmentContext($segment);

        $isExacExac = false;
        $isContext = false;
        foreach ($metaData as $data) {
            //exact-exact match
            if ($data->name == "documentName" && $data->value == $filename) {
                $isExacExac = true;
            }

            //context metch
            if ($data->name == "context" && $data->value == $context) {
                $isContext = true;
            }
        }

        if ($isExacExac && $isContext) {
            return self::CONTEXT_MATCH_VALUE;
        }

        // exact match only for case when no res-name is set
        if ($isExacExac && empty($segment->meta()->getSegmentDescriptor())) {
            return self::EXACT_EXACT_MATCH_VALUE;
        }

        return $matchRate;
    }

    /**
     * Download and save the existing tm with "fuzzy" name. The new fuzzy connector will be returned.
     * @param int $analysisId
     * @return editor_Services_Connector_Abstract
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_NotFoundException
     * @throws editor_Services_Exceptions_NoService
     */
    public function initForFuzzyAnalysis($analysisId)
    {
        $ext = 'TM';
        $validExportTypes = $this->getValidExportTypes();

        if (empty($validExportTypes[$ext])) {
            throw new ZfExtended_NotFoundException('Can not download in format ' . $ext);
        }

        $fuzzyLanguageResource = clone $this->languageResource;

        //visualized name:
        $fuzzyLanguageResourceName = $this->renderFuzzyLanguageResourceName(
            $this->languageResource->getName(),
            $analysisId
        );
        $fuzzyLanguageResource->setName($fuzzyLanguageResourceName);
        $fuzzyLanguageResource->addSpecificData('memories', null);

        $this->api->setResource($fuzzyLanguageResource->getResource());

        $newTmFileName = $this->createMemoryService->createEmptyMemoryWithRetry(
            $this->languageResource,
            $this->memoryNameGenerator->generateTmFilename($fuzzyLanguageResource),
        );

        $this->persistenceService->addMemoryToLanguageResource(
            $fuzzyLanguageResource,
            $newTmFileName,
            true
        );

        //INFO: The resources logging requires resource with valid id.
        //$fuzzyLanguageResource->setId(null);

        $connector = ZfExtended_Factory::get(static::class);
        $connector->connectTo(
            $fuzzyLanguageResource,
            $this->languageResource->getSourceLang(),
            $this->languageResource->getTargetLang(),
            $this->getConfig()
        );
        // copy the current config (for task specific config)
        $connector->setConfig($this->getConfig());
        // copy the worker user guid
        $connector->setWorkerUserGuid($this->getWorkerUserGuid());
        $connector->isInternalFuzzy = true;
        // tell version service to not update LR data
        $connector->versionService->setInternalFuzzy(true);

        return $connector;
    }

    /**
     * Get the result list where the >=100 matches with the same target are grouped as 1 match.
     */
    private function getResultListGrouped(editor_Services_ServiceResult $resultList): editor_Services_ServiceResult
    {
        $allResults = $resultList->getResult();
        if (empty($allResults)) {
            return $resultList;
        }

        $showMultiple100PercentMatches = $this->config
            ->runtimeOptions
            ->LanguageResources
            ->opentm2
            ->showMultiple100PercentMatches;

        $other = [];
        $differentTargetResult = [];
        $document = [];
        $target = null;
        //filter and collect the results
        //all 100>= matches with same target will be collected
        //all <100 mathes will be collected
        //all documentName and documentShortName will be collected from matches >=100
        $filterArray = array_filter(
            $allResults,
            function ($result) use (
                &$other,
                &$document,
                &$target,
                &$differentTargetResult,
                $resultList,
                $showMultiple100PercentMatches
            ) {
                //collect lower than 100 matches to separate array
                if ($result->matchrate < 100) {
                    $other[] = $result;

                    return false;
                }
                //set the compare target
                if (! isset($target)) {
                    $target = $result->target;
                }

                //is with same target or show multiple id disabled collect >=100 match for later sorting
                if ($result->target == $target || ! $showMultiple100PercentMatches) {
                    $document[] = [
                        'documentName' => $resultList->getMetaValue($result->metaData, 'documentName'),
                        'documentShortName' => $resultList->getMetaValue($result->metaData, 'documentShortName'),
                    ];

                    return true;
                }
                //collect different target result
                $differentTargetResult[] = $result;

                return false;
            }
        );

        //sort by highest match-rate from the >=100 match results, when same match-rate sort by timestamp
        usort($filterArray, function ($item1, $item2) use ($resultList) {
            //FIXME UGLY UGLY
            // the whole existing code of reducing double 100% matches (getResultListGrouped) must be moved to the processing of the search results for the UI usage of matches
            // this is nothing which should be handled so deep inside of the connector
            // the connector should not make any decision about sorting or so, this is business logic on a higher level, a connector should be only about connecting...
            // if this is moved, there is no need to contain the isFuzzy check anymore since there is then no fuzzy usage anymore.
            $item1IsFuzzy = preg_match('#^translate5-unique-id\[[^\]]+\]$#', $item1->target);
            $item2IsFuzzy = preg_match('#^translate5-unique-id\[[^\]]+\]$#', $item2->target);

            if ($item1IsFuzzy && ! $item2IsFuzzy) {
                return 1;
            }

            if (! $item1IsFuzzy && $item2IsFuzzy) {
                return -1;
            }

            if ($item1->matchrate == $item2->matchrate) {
                $date1 = date($resultList->getMetaValue($item1->metaData, 'timestamp'));
                $date2 = date($resultList->getMetaValue($item2->metaData, 'timestamp'));

                return $date1 < $date2 ? 1 : -1;
            }

            return ($item1->matchrate < $item2->matchrate) ? 1 : -1;
        });

        if (! empty($filterArray)) {
            //get the highest >=100 match, and apply the documentName and documentShrotName from all >=100 matches
            $filterArray = $filterArray[0];
            foreach ($filterArray->metaData as $md) {
                if ($md->name == 'documentName') {
                    $md->value = implode(';', array_column($document, 'documentName'));
                }
                if ($md->name == 'documentShortName') {
                    $md->value = implode(';', array_column($document, 'documentShortName'));
                }
            }
        }

        //if it is single result, init it as array
        if (! is_array($filterArray)) {
            $filterArray = [$filterArray];
        }

        //merge all available results
        $result = array_merge($filterArray, $differentTargetResult);
        $result = array_merge($result, $other);

        $resultList->resetResult();
        $resultList->setResults($result);

        return $resultList;
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

    private function addOverflowLog(Task $task = null): void
    {
        $params = [
            'name' => $this->languageResource->getName(),
            'apiError' => $this->api->getError(),
        ];

        if (null !== $task) {
            $params['task'] = $task;
        }

        $this->logger->info(
            'E1603',
            'Language Resource [{name}] current writable memory is overflown, creating a new one',
            $params
        );
    }

    private function queryTms(
        string $queryString,
        editor_Models_Segment $segment,
        string $fileName,
    ): editor_Services_ServiceResult {
        $resultList = new editor_Services_ServiceResult();
        $resultList->setLanguageResource($this->languageResource);

        //if source is empty, t5memory will return an error, therefore we just return an empty list
        if (empty($queryString) && $queryString !== '0') {
            return $resultList;
        }

        if ($this->languageResource->isConversionStarted()) {
            return $resultList;
        }

        //Although we take the source fields from the t5memory answer below
        // we have to set the default source here to fill the be added internal tags
        $resultList->setDefaultSource($queryString);
        $query = $this->tagHandler->prepareQuery($queryString);

        $results = [];

        foreach ($this->languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            $tmName = $memory['filename'];

            if ($this->reorganizeService->isReorganizingAtTheMoment($this->languageResource, $tmName, $this->isInternalFuzzy())) {
                if (! $this->waitingService->canWaitLongTaskFinish()) {
                    continue;
                }

                $this->reorganizeService->waitReorganizeFinished(
                    $this->languageResource,
                    $memory,
                    $this->isInternalFuzzy(),
                );
            }

            $successful = $this->api->lookup($segment, $query, $fileName, $tmName);

            $response = ApiResponse::fromContentAndStatus(
                $this->api->getResponse()->getBody(),
                $this->api->getResponse()->getStatus(),
            );

            if ($this->reorganizeService->needsReorganizing(
                $response,
                $this->languageResource,
                $tmName,
                null,
                $this->isInternalFuzzy()
            )) {
                $this->reorganizeService->reorganizeTm($this->languageResource, $tmName, $this->isInternalFuzzy());
                $successful = $this->api->lookup($segment, $query, $fileName, $tmName);
            }

            if (! $successful && $this->isLockingTimeoutOccurred($this->api->getError())) {
                $lookup = fn () => $this->api->lookup($segment, $query, $fileName, $tmName)
                    ? [WaitCallState::Done, true]
                    : [WaitCallState::Retry, false];

                $successful = (bool) $this->waitingService->callAwaiting($lookup);
            }

            if (! $successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            // In case we have at least one successful lookup, we reset the reorganize attempts
            $this->reorganizeService->resetReorganizeAttempts($this->languageResource, $this->isInternalFuzzy());

            $result = $this->api->getResult();

            if ((int) ($result->NumOfFoundProposals ?? 0) === 0) {
                continue;
            }

            $results[] = $result->results;
        }

        if (empty($results)) {
            return $resultList;
        }

        $results = array_merge(...$results);

        foreach ($results as $found) {
            $target = $this->tagHandler->restoreInResult($found->target, false);
            $hasTargetErrors = $this->tagHandler->hasRestoreErrors();

            $source = $this->tagHandler->restoreInResult($found->source);
            $hasSourceErrors = $this->tagHandler->hasRestoreErrors();

            if ($hasTargetErrors || $hasSourceErrors) {
                //the source has invalid xml -> remove all tags from the result, and reduce the matchrate by 2%
                $found->matchRate = $this->reduceMatchrate($found->matchRate, 2);
            }

            $matchrate = $this->calculateMatchRate(
                $found->matchRate,
                $this->getMetaData($found),
                $segment,
                $fileName
            );
            $metaData = $this->getMetaData($found);
            $metaDataAssoc = array_column($metaData, 'value', 'name');
            $timestamp = 0;
            if (! empty($metaDataAssoc['timestamp'])) {
                $timestamp = (int) strtotime($metaDataAssoc['timestamp']);
            }
            $resultList->addResult($target, $matchrate, $metaData, $found->target, $timestamp);
            $resultList->setSource($source);
        }

        return $resultList;
    }

    public function export(string $mime): ?string
    {
        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        return $this->exportService->export(
            $this->languageResource,
            TmFileExtension::fromMimeType($mime, count($memories) > 1)
        );
    }

    private function checkUpdateResponse(array $request, object $response): void
    {
        // Temporary disable the check until it is fixed
        //        $match =
        //            $request['source'] === $response->source
        //            && $request['target'] === $response->target
        //            && mb_strtoupper($request['userName']) === $response->author
        //            && $request['context'] === $response->context
        ////            && $request['timestamp'] === $response->timestamp
        //            && $request['fileName'] === $response->documentName;
        //
        //        if (! $match) {
        //            $this->logger->error(
        //                'E1586',
        //                'Sent data does not match the response from t5memory in update call.',
        //                [
        //                    'languageResource' => $this->languageResource,
        //                    'request' => $request,
        //                    'response' => json_encode($response, JSON_PRETTY_PRINT),
        //                ]
        //            );
        //        }
    }

    private function checkUpdatedSegmentIfNeeded(editor_Models_Segment $segment, bool $recheckOnUpdate): void
    {
        if (! in_array(
            $this->getResource()->getUrl(),
            $this->config->runtimeOptions->LanguageResources->checkSegmentsAfterUpdate->toArray(),
            true
        )
            || ! $recheckOnUpdate
        ) {
            // Checking segment after update is disabled in config or in parameter, nothing to do
            return;
        }

        $this->checkUpdatedSegment($segment);
    }

    /**
     * Check if segment was updated properly
     * and if not - add a log record for that for debug purposes
     */
    public function checkUpdatedSegment(editor_Models_Segment $segment): void
    {
        $targetSent = $this->tagHandler->prepareQuery($segment->getTargetEdit(), false);

        $result = $this->query($segment);

        $logError = fn (string $reason) => $this->logger->error(
            'E1586',
            $reason,
            [
                'languageResource' => $this->languageResource,
                'segment' => $segment,
                'response' => json_encode($result->getResult(), JSON_PRETTY_PRINT),
                'target' => $targetSent,
            ]
        );

        $maxMatchRateResult = $result->getMaxMatchRateResult();

        // If there is no result at all, it means that segment was not saved to TM
        if (! $maxMatchRateResult) {
            $logError('Segment was not saved to TM');

            return;
        }

        // Just saved segment should have matchrate 103
        $matchRateFits = $maxMatchRateResult->matchrate === 103;

        // Decode html entities
        $targetReceived = html_entity_decode($maxMatchRateResult->rawTarget);
        // Decode unicode symbols
        $targetReceived = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $targetReceived);
        // Replacing \r\n to \n back because t5memory replaces \n to \r\n
        $targetReceived = str_replace("\r\n", "\n", $targetReceived);
        $targetSent = str_replace("\r\n", "\n", $targetSent);
        // Finally compare target that we've sent for saving with the one we retrieved from TM, they should be the same
        // html_entity_decode() is used because sometimes t5memory returns target with decoded
        // html entities regardless of the original target
        $targetIsTheSame = $targetReceived === $targetSent
            || html_entity_decode($targetReceived) === html_entity_decode($targetSent);

        $resultTimestamp = $result->getMetaValue($maxMatchRateResult->metaData, 'timestamp');
        $resultDate = DatetimeImmutable::createFromFormat('Y-m-d H:i:s T', $resultTimestamp);
        // Timestamp should be not older than 1 minute otherwise it is an old segment which wasn't updated
        $isResultFresh = $resultDate >= new DateTimeImmutable('-1 minute');

        if (! $matchRateFits || ! $targetIsTheSame || ! $isResultFresh) {
            $logError(match (false) {
                $matchRateFits => 'Match rate is not 103',
                $targetIsTheSame => 'Saved segment target differs with provided',
                $isResultFresh => 'Got old result',
                default => 'Unknown reason',
            });
        }
    }

    private function getStripFramingTagsValue(?array $params): StripFramingTags
    {
        return StripFramingTags::tryFrom($params['stripFramingTags'] ?? '') ?? StripFramingTags::None;
    }

    protected function getSegmentContext(editor_Models_Segment $segment): string
    {
        return $segment->meta()->getSegmentDescriptor()
            ?: self::SEGMENT_NR_CONTEXT_PREFIX . $segment->getSegmentNrInTask();
    }
}

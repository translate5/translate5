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
use GuzzleHttp\Client;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\RescheduleUpdateNeededException;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Adapter\Export\ExportAdapterInterface;
use MittagQI\Translate5\LanguageResource\Adapter\Export\ExportTmFileExtension;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\Adapter\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\RetryClient;
use MittagQI\Translate5\T5Memory\Api\VersionedApiFactory;
use MittagQI\Translate5\T5Memory\Api\VersionFetchingApi;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\ExportService;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\VersionService;

/**
 * T5memory / OpenTM2 Connector
 *
 * IMPORTANT: see the doc/comments in MittagQI\Translate5\Service\T5Memory
 */
class editor_Services_OpenTM2_Connector extends editor_Services_Connector_Abstract implements UpdatableAdapterInterface, FileBasedInterface, ExportAdapterInterface
{
    private const CONCORDANCE_SEARCH_NUM_RESULTS = 1;

    private const VERSION_0_6 = '0.6';

    public const NEXT_SUFFIX = '_next-';

    private const SEGMENT_NR_CONTEXT_PREFIX = 'SegmentNr: ';

    /**
     * Connector
     * @var editor_Services_OpenTM2_HttpApi
     */
    protected $api;

    /**
     * Using Xliff based tag handler here
     * @var string
     */
    protected $tagHandlerClass = 'editor_Services_Connector_TagHandler_OpenTM2Xliff';

    /**
     * Just overwrite the class var hint here
     * @var editor_Services_Connector_TagHandler_Xliff
     */
    protected $tagHandler;

    /**
     *  Is the connector generally able to support internal Tags for the translate-API
     * @var bool
     */
    protected $internalTagSupport = true;

    private TmConversionService $conversionService;

    private VersionService $versionService;

    private PersistenceService $persistenceService;

    private ExportService $exportService;

    public function __construct()
    {
        editor_Services_Connector_Exception::addCodes([
            'E1314' => 'The queried OpenTM2 TM "{tm}" is corrupt and must be reorganized before usage!',
            'E1333' => 'The queried OpenTM2 server has to many open TMs!',
        ]);

        ZfExtended_Logger::addDuplicatesByEcode('E1333', 'E1306', 'E1314');

        parent::__construct();

        $this->conversionService = TmConversionService::create();
        $this->persistenceService = new PersistenceService($this->config);
        $httpClient = new RetryClient(new Client());
        $this->versionService = new VersionService(new VersionFetchingApi($httpClient));
        $this->exportService = new ExportService(
            $this->logger,
            $this->versionService,
            $this->conversionService,
            new VersionedApiFactory($httpClient),
            $this->persistenceService
        );
    }

    public function connectTo(
        LanguageResource $languageResource,
        $sourceLang,
        $targetLang,
    ): void {
        $this->api = ZfExtended_Factory::get('editor_Services_OpenTM2_HttpApi');
        $this->api->setLanguageResource($languageResource);

        // TODO T5MEMORY: remove when OpenTM2 is out of production
        // t5 memory is not needing the OpenTM2 specific Xliff TagHandler, the default XLIFF TagHandler is sufficient
        if ($this->tagHandler instanceof editor_Services_Connector_TagHandler_OpenTM2Xliff) {
            $this->tagHandler = ZfExtended_Factory::get(
                editor_Services_Connector_TagHandler_T5MemoryXliff::class,
                [[
                    'gTagPairing' => false,
                ]]
            );
        }
        parent::connectTo($languageResource, $sourceLang, $targetLang);
    }

    /**
     * @throws Zend_Exception
     */
    public function addTm(array $fileinfo = null, array $params = null): bool
    {
        $sourceLang = $this->languageResource->getSourceLangCode();

        //to ensure that we get unique TMs Names although of the above stripped content,
        // we add the LanguageResource ID and a prefix which can be configured per each translate5 instance
        $name = $this->generateTmFilename($this->languageResource);

        if (isset($params['createNewMemory'])) {
            $name = $this->generateNextMemoryName($this->languageResource);
        }

        // If we are adding a TMX file as LanguageResource, we must create an empty memory first.
        $validFileTypes = $this->getValidFiletypes();
        if (empty($validFileTypes['TMX'])) {
            throw new ZfExtended_NotFoundException('OpenTM2: Cannot addTm for TMX-file; valid file types are missing.');
        }

        $noFile = empty($fileinfo);
        $tmxUpload = ! $noFile
            && (
                in_array($fileinfo['type'], $validFileTypes['TMX'])
                || in_array($fileinfo['type'], $validFileTypes['ZIP'])
            )
            && preg_match('/(\.tmx|\.zip)$/', strtolower($fileinfo['name']));

        if ($noFile || $tmxUpload) {
            $tmName = $this->createEmptyMemoryWithRetry($name, $sourceLang);

            if (null !== $tmName) {
                $this->addMemoryToLanguageResource($this->languageResource, $tmName);

                //if initial upload is a TMX file, we have to import it.
                if ($tmxUpload) {
                    return $this->addAdditionalTm($fileinfo, [
                        'tmName' => $tmName,
                        'stripFramingTags' => $params['stripFramingTags'] ?? null,
                    ]);
                }

                return true;
            }

            $this->logger->error('E1305', 'OpenTM2: could not create TM', [
                'languageResource' => $this->languageResource,
                'apiError' => $this->api->getError(),
            ]);

            return false;
        }

        //initial upload is a TM file
        if ($this->versionService->isLRVersionGreaterThan(self::VERSION_0_6, $this->languageResource)) {
            $tmName = $this->api->createMemoryWithFile(
                $name,
                $sourceLang,
                $fileinfo['tmp_name'],
                $this->getStripFramingTagsValue($params)
            );
        } else {
            $tmName = $this->api->createMemory($name, $sourceLang, file_get_contents($fileinfo['tmp_name']));
        }

        if ($tmName) {
            $this->addMemoryToLanguageResource($this->languageResource, $tmName);

            return true;
        }

        $this->logger->error('E1304', 'OpenTM2: could not create prefilled TM', [
            'languageResource' => $this->languageResource,
            'apiError' => $this->api->getError(),
        ]);

        return false;
    }

    /**
     * Updates the filename of the language resource instance with the filename coming from the TM system
     * @throws Zend_Exception
     */
    private function addMemoryToLanguageResource(
        LanguageResource $languageResource,
        string $tmName,
        bool $isInternalFuzzy = false,
    ): void {
        $prefix = Zend_Registry::get('config')->runtimeOptions->LanguageResources->opentm2->tmprefix;
        if (! empty($prefix)) {
            //remove the prefix from being stored into the TM
            $tmName = str_replace('^' . $prefix . '-', '', '^' . $tmName);
        }

        $memories = $languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        $memories[] = [
            'id' => count($memories) + 1,
            'filename' => $tmName,
            'readonly' => false,
        ];

        $languageResource->addSpecificData('memories', $memories);

        if (! $isInternalFuzzy) {
            //saving it here makes the TM available even when the TMX import was crashed
            $languageResource->save();
        }
    }

    private function setMemoryReadonly(
        LanguageResource $languageResource,
        string $tmName,
        bool $isInternalFuzzy = false,
    ): void {
        $memories = $languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        foreach ($memories as $key => $memory) {
            if ($memory['filename'] === $tmName) {
                $memories[$key]['readonly'] = true;
            }
        }

        $languageResource->addSpecificData('memories', $memories);

        if (! $isInternalFuzzy) {
            $languageResource->save();
        }
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
            $this->logger->error('E1596', 'OpenTM2: Unable to open zip file from file-path:' . $fileInfo['tmp_name']);

            return yield from [];
        }

        $newPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($fileInfo['name'], PATHINFO_FILENAME);

        if (! $zip->extractTo($newPath)) {
            $this->logger->error('E1597', 'OpenTM2: Content from zip file could not be extracted.');
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
        $result = true;

        foreach ($this->getImportFilesFromUpload($fileinfo) as $file) {
            try {
                $importFilename = $this->conversionService->convertTMXForImport(
                    $file,
                    (int) $this->languageResource->getSourceLang(),
                    (int) $this->languageResource->getTargetLang()
                );
            } catch (RuntimeException $e) {
                $this->logger->error(
                    'E1590',
                    'Conversion: Error in process of TMX file conversion',
                    [
                        'reason' => $e->getMessage(),
                        'languageResource' => $this->languageResource,
                    ]
                );
                $this->languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
                $this->languageResource->save();

                $result = false;

                continue;
            }

            $result = $result && $this->importTmxIntoMemory(
                $importFilename,
                $params['tmName'] ?? $this->persistenceService->getWritableMemory($this->languageResource),
                $this->getStripFramingTagsValue($params)
            );

            unlink($importFilename);
        }

        return $result;
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
        return ExportTmFileExtension::getValidExportTypes();
    }

    public function getTm($mime)
    {
        $file = $this->exportService->export(
            $this->languageResource,
            ExportTmFileExtension::fromMimeType($mime)
        );

        if (null === $file) {
            return '';
        }

        return file_get_contents($file);
    }

    public function update(editor_Models_Segment $segment, array $options = []): void
    {
        $tmName = $this->persistenceService->getWritableMemory($this->languageResource);

        if ($this->isReorganizingAtTheMoment($tmName)) {
            if ($options[UpdatableAdapterInterface::RESCHEDULE_UPDATE_ON_ERROR] ?? false) {
                throw new RescheduleUpdateNeededException();
            }

            throw new editor_Services_Connector_Exception('E1512', [
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

        if ($this->isReorganizingAtTheMoment($tmName)) {
            if ($options[UpdatableAdapterInterface::RESCHEDULE_UPDATE_ON_ERROR] ?? false) {
                throw new RescheduleUpdateNeededException();
            }

            throw new editor_Services_Connector_Exception('E1512', [
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
        $elapsedTime = 0;
        $maxWaitingTime = $this->getMaxWaitingTimeSeconds();

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

            if ($this->needsReorganizing($apiError, $tmName)) {
                $this->addReorganizeWarning($segment->getTask());
                $this->reorganizeTm($tmName);
            } elseif ($this->isMemoryOverflown($apiError)) {
                if (! $this->isBlockOverflown($apiError)) {
                    $this->setMemoryReadonly($this->languageResource, $tmName, $this->isInternalFuzzy());
                }

                $newName = $this->persistenceService->getNextWritableMemory($this->languageResource, $tmName);

                if ($newName) {
                    $tmName = $newName;

                    continue;
                }

                $this->addOverflowWarning($segment->getTask());
                $this->flushMemory($tmName);
                $newName = $this->generateNextMemoryName($this->languageResource);
                $newName = $this->createEmptyMemoryWithRetry($newName, $this->languageResource->getSourceLangCode());

                if (null === $newName) {
                    $this->logger->error('E1305', 'OpenTM2: could not create TM', [
                        'languageResource' => $this->languageResource,
                        'apiError' => $this->api->getError(),
                    ]);

                    break;
                }

                $this->addMemoryToLanguageResource($this->languageResource, $newName, $this->isInternalFuzzy());
                $tmName = $newName;
            } elseif ($this->isLockingTimeoutOccurred($apiError)) {
                // Wait before retrying
                sleep($this->getRetryDelaySeconds());
            } else {
                // If no specific error handling is applicable, break the loop
                break;
            }

            $elapsedTime = $this->getRetryDelaySeconds();
        }

        // send the error to the frontend
        editor_Services_Manager::reportTMUpdateError($apiError ?? null);

        $this->logger->error('E1306', 'OpenTM2: could not save segment to TM', [
            'languageResource' => $this->languageResource,
            'segment' => $segment,
            'apiError' => $apiError ?? '',
        ]);

        throw new SegmentUpdateException();
    }

    public function flush(): void
    {
        $tmName = $this->persistenceService->getWritableMemory($this->languageResource);
        $this->api->flush($tmName);
    }

    private function flushMemory(string $tmName): void
    {
        $this->api->flush($tmName);
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
        $resultList = $this->queryTm($queryString, $segment, $fileName);

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

        $results = [];
        $resultsCount = 0;

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        usort($memories, fn ($m1, $m2) => $m1['id'] <=> $m2['id']);

        foreach ($memories as ['filename' => $tmName, 'id' => $id]) {
            // check if current memory was searched through in prev request
            if ('' !== $offsetTmId && $id < $offsetTmId) {
                continue;
            }

            if ($this->isReorganizingAtTheMoment($tmName)) {
                continue;
            }

            $numResults = self::CONCORDANCE_SEARCH_NUM_RESULTS - $resultsCount;

            $successful = $this->api->concordanceSearch($searchString, $tmName, $field, $tmOffset, $numResults);

            if (! $successful && $this->needsReorganizing($this->api->getError(), $tmName)) {
                $this->addReorganizeWarning();
                $this->reorganizeTm($tmName);
                $successful = $this->api->concordanceSearch($searchString, $tmName, $field, $tmOffset, $numResults);
            }

            if (! $successful && $this->isLockingTimeoutOccurred($this->api->getError())) {
                $elapsedTime = 0;
                $maxWaitingTime = $this->getMaxWaitingTimeSeconds();

                while ($elapsedTime < $maxWaitingTime && ! $successful) {
                    sleep($this->getRetryDelaySeconds());
                    $elapsedTime += $this->getRetryDelaySeconds();

                    $successful = $this->api->concordanceSearch($searchString, $tmName, $field, $tmOffset, $numResults);
                }
            }

            if (! $successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            // In case we have at least one successful search, we reset the reorganize attempts
            $this->resetReorganizeAttempts($this->languageResource);

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

        return $this->queryTm($searchString, $dummySegment, 'source');
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
     * Replaces not allowed characters with "_" in memory names
     * @param string $name
     * @return string
     */
    private function filterName($name)
    {
        //since we are getting Problems on the OpenTM2 side with non ascii characters in the filenames,
        // we strip them all. See also OPENTM2-13.
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

        return preg_replace('/[^a-zA-Z0-9 _-]/', '_', $name);
        //original not allowed string list:
        //return str_replace("\\/:?*|<>", '_', $name);
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
        if (! isset($this->languageResource)) {
            //ping call
            $this->api = ZfExtended_Factory::get(editor_Services_OpenTM2_HttpApi::class);
            $this->api->setResource($resource);

            return $this->api->status(null) ? LanguageResourceStatus::AVAILABLE : LanguageResourceStatus::ERROR;
        }

        if ($this->languageResource->isConversionInProgress()) {
            return LanguageResourceStatus::CONVERTING;
        }

        // let's check the internal state before calling API for status as import worker might not have run yet
        if ($this->languageResource->getStatus() === LanguageResourceStatus::IMPORT) {
            return LanguageResourceStatus::IMPORT;
        }

        return $this->getStatusFromApi($this->languageResource, $tmName);
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

        if ($this->api->status($name)) {
            $result = $this->api->getResult();

            if (! is_object($result)) {
                return false;
            }

            $status = $result->status ?? '';

            return 'open' === $status && 0 === $result->segmentIndex;
        }

        return false;
    }

    private function getStatusFromApi(LanguageResource $languageResource, ?string $tmName): string
    {
        $name = $tmName ?: $this->persistenceService->getLastWritableMemory($languageResource);

        if (empty($name)) {
            $this->lastStatusInfo = 'The internal stored filename is invalid';

            return LanguageResourceStatus::NOCONNECTION;
        }

        // TODO remove after fully migrated to t5memory v0.5.x
        if (
            ! $this->versionService->isLRVersionGreaterThan(self::VERSION_0_5, $this->languageResource)
            && $this->isReorganizingAtTheMoment($name)
        ) {
            // Status import to prevent any other queries to TM to be performed
            return LanguageResourceStatus::IMPORT;
        }

        if ($this->api->status($name)) {
            $result = $this->api->getResult();

            return $this->processImportStatus(is_object($result) ? $result : null);
        }
        //down here the result contained an error, the json was invalid or HTTP Status was not 20X

        // FIXME this is currently not true, because t5memory returns 404 only in case memory does not exist
        // FIXME If it is not loaded, but exists on the disk the status will be 'available' - MITTAGQI-338
        //Warning: this evaluates to "available" in the GUI, see the following explanation:
        //a 404 response from the status call means:
        // - OpenTM2 is online
        // - the requested TM is currently not loaded, so there is no info about the existence
        // - So we display the STATUS_NOT_LOADED instead
        if ($this->api->getResponse()->getStatus() === 404) {
            $this->lastStatusInfo = 'Die Ressource ist generell verfügbar, '
                . 'stellt aber keine Informationen über das angefragte TM bereit, da dies nicht geladen ist.';

            // This will be not needed after migration to t5memory completed
            return LanguageResourceStatus::NOT_LOADED;
        }

        $error = $this->api->getError();

        if (empty($error->type)) {
            $this->lastStatusInfo = $error->error;
        } else {
            $this->lastStatusInfo = $error->type . ': ' . $error->error;
        }

        return LanguageResourceStatus::ERROR;
    }

    /**
     * processes the import state
     * Please note, method made public for testing purposes only,
     * should be changed to private after the class is refactored
     */
    public function processImportStatus(?stdClass $apiResponse): string
    {
        $status = $apiResponse ? ($apiResponse->status ?? '') : '';
        $tmxImportStatus = $apiResponse ? ($apiResponse->tmxImportStatus ?? '') : '';
        $reorganizeStatus = $apiResponse ? ($apiResponse->reorganizeStatus ?? '') : '';

        $lastStatusInfo = '';
        $result = LanguageResourceStatus::UNKNOWN;

        switch ($status) {
            // TM not found at all
            case 'not found':
                // We have no status 'not found' at the moment, so we use 'error' instead
                $result = LanguageResourceStatus::ERROR;

                break;

                // TM exists on a disk, but not loaded into memory
            case 'available':
                $result = LanguageResourceStatus::AVAILABLE;

                // TODO change this to STATUS_NOT_LOADED after discussed with the team
                //                $result = self::STATUS_NOT_LOADED;
                break;

                // TM exists and is loaded into memory
            case 'open':
                switch ($tmxImportStatus) {
                    case '':
                        $result = LanguageResourceStatus::AVAILABLE;

                        break;

                    case 'available':
                        if (isset($apiResponse->importTime) && $apiResponse->importTime === 'not finished') {
                            $result = LanguageResourceStatus::IMPORT;

                            break;
                        }

                        $result = LanguageResourceStatus::AVAILABLE;

                        break;

                    case 'import':
                        $lastStatusInfo = 'TMX wird importiert, TM kann trotzdem benutzt werden';
                        $result = LanguageResourceStatus::IMPORT;

                        break;

                    case 'error':
                    case 'failed':
                        $lastStatusInfo = $apiResponse->ErrorMsg
                            ?? $apiResponse->importErrorMsg
                            ?? $apiResponse->reorganizeErrorMsg
                            ?? 'Unknown error';
                        $result = LanguageResourceStatus::ERROR;

                        break;

                    default:
                        break;
                }

                switch ($reorganizeStatus) {
                    case 'reorganize':
                        $result = LanguageResourceStatus::REORGANIZE_IN_PROGRESS;

                        break;

                    case 'reorganize failed':
                        $result = LanguageResourceStatus::REORGANIZE_FAILED;

                        break;

                    default:
                        break;
                }

                break;

            case 'reorganize running':
                $result = LanguageResourceStatus::REORGANIZE_IN_PROGRESS;

                break;

            case 'import running':
                $result = LanguageResourceStatus::IMPORT;

                break;

            case 'waiting for loading':
                $result = LanguageResourceStatus::WAITING_FOR_LOADING;

                break;

            case 'loading':
                $result = LanguageResourceStatus::LOADING;

                break;

            case 'failed to open':
                $result = LanguageResourceStatus::FAILED_TO_OPEN;

                break;

            default:
                break;
        }

        $this->lastStatusInfo = $lastStatusInfo !== '' ? $lastStatusInfo : 'original OpenTM2 status ' . $status;

        return $result;
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

        $newTmFileName = $this->createEmptyMemoryWithRetry(
            $this->generateTmFilename($fuzzyLanguageResource),
            $this->languageResource->getSourceLangCode()
        );

        $this->addMemoryToLanguageResource($fuzzyLanguageResource, $newTmFileName, true);

        //INFO: The resources logging requires resource with valid id.
        //$fuzzyLanguageResource->setId(null);

        $connector = ZfExtended_Factory::get(static::class);
        $connector->connectTo(
            $fuzzyLanguageResource,
            $this->languageResource->getSourceLang(),
            $this->languageResource->getTargetLang()
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

    #region Reorganize TM
    // Need to move this region to a dedicated class while refactoring connector
    private const REORGANIZE_ATTEMPTS = 'reorganize_attempts';

    private const REORGANIZE_STARTED_AT = 'reorganize_started_at';

    // Applicable only for version < 0.5.x
    private const MAX_REORGANIZE_TIME_MINUTES = 30;

    private const REORGANIZE_WAIT_TIME_SECONDS = 60;

    private const VERSION_0_5 = '0.5';

    private function needsReorganizing(stdClass $error, string $tmName): bool
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
            $tmName = $this->persistenceService->getWritableMemory($this->languageResource);
        }

        if (! $this->isInternalFuzzy()) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $this->languageResource->refresh();
            $this->increaseReorganizeAttempts($this->languageResource);
            $this->setReorganizeStatusInProgress($this->languageResource);
            $this->languageResource->save();
        }

        $reorganized = $this->api->reorganizeTm($tmName);

        if ($this->versionService->isLRVersionGreaterThan(self::VERSION_0_5, $this->languageResource)) {
            $reorganized = $this->waitReorganizeFinished($tmName);
        }

        if (! $this->isInternalFuzzy()) {
            $this->languageResource->setStatus(
                $reorganized ? LanguageResourceStatus::AVAILABLE : LanguageResourceStatus::REORGANIZE_FAILED
            );

            $this->languageResource->save();
        }

        return $reorganized;
    }

    public function isReorganizingAtTheMoment(?string $tmName = null): bool
    {
        $this->resetReorganizingIfNeeded();

        if ($this->versionService->isLRVersionGreaterThan(self::VERSION_0_5, $this->languageResource)) {
            $status = $this->getStatus($this->resource, tmName: $tmName);

            return $status === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
        }

        return $this->languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
    }

    public function isReorganizeFailed(?string $tmName = null): bool
    {
        if ($this->versionService->isLRVersionGreaterThan(self::VERSION_0_5, $this->languageResource)) {
            return $this->getStatus(
                $this->resource,
                tmName: $tmName
            ) === LanguageResourceStatus::REORGANIZE_FAILED;
        }

        return $this->languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_FAILED;
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

        while ($elapsedTime < $this->getReorganizeWaitingTimeSeconds()) {
            if (! $this->isReorganizingAtTheMoment($tmName)) {
                return true;
            }

            sleep($sleepTime);
            $elapsedTime += $sleepTime;
        }

        return false;
    }

    private function getReorganizeWaitingTimeSeconds(): int
    {
        return $this->canWaitLongTaskFinish()
            ? self::REORGANIZE_WAIT_TIME_SECONDS * 60
            : self::REORGANIZE_WAIT_TIME_SECONDS;
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
        if ($this->isInternalFuzzy()) {
            return;
        }

        if ($languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS) === null) {
            return;
        }

        // In some cases language resource is detached from DB
        $languageResource->refresh();
        $languageResource->removeSpecificData(self::REORGANIZE_ATTEMPTS);
        $languageResource->save();
    }

    /**
     * Applicable only for t5memory 0.4.x
     */
    private function resetReorganizingIfNeeded(): void
    {
        $reorganizeStartedAt = $this->languageResource->getSpecificData(self::REORGANIZE_STARTED_AT);

        if (null === $reorganizeStartedAt || $this->isInternalFuzzy()) {
            return;
        }

        if ((new DateTimeImmutable($reorganizeStartedAt))->modify(
            sprintf('+%d minutes', self::MAX_REORGANIZE_TIME_MINUTES)
        ) < new DateTimeImmutable()
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
        $languageResource->addSpecificData(self::REORGANIZE_STARTED_AT, date(DateTimeInterface::RFC3339));
    }
    #endregion Reorganize TM

    /**
     * This is forced to be public, because part of its functionality is used outside of this class
     * Needs to be removed when refactoring connector
     */
    public function getApi(): editor_Services_OpenTM2_HttpApi
    {
        return $this->api;
    }

    private function queryTm(
        string $queryString,
        editor_Models_Segment $segment,
        string $fileName,
    ): editor_Services_ServiceResult {
        $resultList = new editor_Services_ServiceResult();
        $resultList->setLanguageResource($this->languageResource);

        //if source is empty, OpenTM2 will return an error, therefore we just return an empty list
        if (empty($queryString) && $queryString !== '0') {
            return $resultList;
        }

        //Although we take the source fields from the OpenTM2 answer below
        // we have to set the default source here to fill the be added internal tags
        $resultList->setDefaultSource($queryString);
        $query = $this->tagHandler->prepareQuery($queryString);

        $results = [];

        foreach ($this->languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            $tmName = $memory['filename'];

            if ($this->isReorganizingAtTheMoment($tmName)) {
                if (! $this->canWaitLongTaskFinish()) {
                    continue;
                }

                $this->waitReorganizeFinished();
            }

            $successful = $this->api->lookup($segment, $query, $fileName, $tmName);

            if (! $successful && $this->needsReorganizing($this->api->getError(), $tmName)) {
                $this->addReorganizeWarning($segment->getTask());
                $this->reorganizeTm($tmName);
                $successful = $this->api->lookup($segment, $query, $fileName, $tmName);
            }

            if (! $successful && $this->isLockingTimeoutOccurred($this->api->getError())) {
                $elapsedTime = 0;
                $maxWaitingTime = $this->getMaxWaitingTimeSeconds();

                while ($elapsedTime < $maxWaitingTime && ! $successful) {
                    sleep($this->getRetryDelaySeconds());
                    $elapsedTime += $this->getRetryDelaySeconds();

                    $successful = $this->api->lookup($segment, $query, $fileName, $tmName);
                }
            }

            if (! $successful) {
                $this->logger->exception($this->getBadGatewayException($tmName));

                continue;
            }

            // In case we have at least one successful lookup, we reset the reorganize attempts
            $this->resetReorganizeAttempts($this->languageResource);

            $result = $this->api->getResult();

            if ((int) $result->NumOfFoundProposals === 0) {
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

    private function isBlockOverflown(?object $error): bool
    {
        if (null === $error) {
            return false;
        }

        $errorCodes = ['5037'];
        $errorCodes = array_map(fn ($code) => 'rc = ' . $code, $errorCodes);

        return isset($error->error)
            && str_replace($errorCodes, '', $error->error) !== $error->error;
    }

    private function importTmxIntoMemory(
        string $importFilename,
        string $tmName,
        StripFramingTags $stripFramingTags,
    ): bool {
        $gotLock = false;
        $successful = false;

        while (! $gotLock) {
            if ($this->versionService->isLRVersionGreaterThan(self::VERSION_0_6, $this->languageResource)) {
                $successful = $this->api->importMemoryAsFile($importFilename, $tmName, $stripFramingTags);
            } else {
                $successful = $this->api->importMemory(file_get_contents($importFilename), $tmName, $stripFramingTags);
            }

            $error = $this->api->getError();

            if (! $successful && $this->isLockingTimeoutOccurred($error)) {
                sleep($this->getRetryDelaySeconds());

                continue;
            }

            if (! $successful && $this->needsReorganizing($error, $tmName)) {
                $this->addReorganizeWarning();
                $this->reorganizeTm($tmName);

                continue;
            }

            $gotLock = true;
        }

        if (! $successful) {
            $this->logger->error('E1303', 'OpenTM2: could not add TMX data to TM', [
                'languageResource' => $this->languageResource,
                'apiError' => $this->api->getError(),
            ]);
        }

        $this->waitForImportFinish($tmName);
        // At current point LR is in Import status to prevent race conditions
        // Here we resetting state to fetch the actual status from t5memory
        $this->languageResource->setStatus(LanguageResourceStatus::NOTCHECKED);
        $status = $this->getStatusFromApi($this->languageResource, $tmName);
        $statusResponse = $this->api->getResult();
        $error = $this->api->getError();

        // In case we've got memory overflow error we need to create another memory and import further
        if ($status === LanguageResourceStatus::ERROR && $this->isMemoryOverflown($error)) {
            $this->addOverflowWarning();
            $this->flushMemory($tmName);

            $newName = $this->generateNextMemoryName($this->languageResource);
            $newName = $this->createEmptyMemoryWithRetry($newName, $this->languageResource->getSourceLangCode());
            $this->addMemoryToLanguageResource($this->languageResource, $newName);

            // Filter TMX data from already imported segments
            $this->cutOffTmx(
                $importFilename,
                $this->getOverflowSegmentNumberFromStatus($statusResponse, $error->error)
            );

            // Import further
            return $this->importTmxIntoMemory($importFilename, $newName, $stripFramingTags);
        }

        return $successful && $status !== LanguageResourceStatus::ERROR;
    }

    private function cutOffTmx(string $importFilename, int $segmentToStartFrom): void
    {
        // suppress: namespace error : Namespace prefix t5 on n is not defined
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        $doc = new DOMDocument();
        $doc->load($importFilename);

        if (! $doc->hasChildNodes()) {
            $error = libxml_get_last_error();

            if (false !== $error) {
                throw new RuntimeException(
                    'Error while loading TMX file: ' . $error->message,
                    $error->code
                );
            }
        }

        // Create an XPath to query the document
        $xpath = new DOMXPath($doc);

        // Find all 'tu' elements
        $tuNodes = $xpath->query('/tmx/body/tu');

        // Remove 'tu' elements before the segment index
        for ($i = 0; $i < $segmentToStartFrom; $i++) {
            $tuNodes->item($i)->parentNode->removeChild($tuNodes->item($i));
        }

        $doc->save($importFilename);

        error_reporting($errorLevel);
    }

    private function getOverflowSegmentNumberFromStatus(stdClass $statusResponse, string $error): int
    {
        if (! isset($statusResponse->tmxSegmentCount) || 0 === (int) $statusResponse->tmxSegmentCount) {
            $this->logger->error(
                'E1313',
                't5memory responded with memory overflow error, ' .
                'but we were unable to distinguish the segment number for reimport',
                [
                    'languageResource' => $this->languageResource,
                ]
            );

            throw new editor_Services_Connector_Exception('E1313', [
                'error' => $error,
            ]);
        }

        return (int) $statusResponse->tmxSegmentCount;
    }

    private function waitForImportFinish(string $tmName): void
    {
        $elapsedTime = 0;
        $maxWaitingTime = $this->getMaxWaitingTimeSeconds();

        while ($elapsedTime < $maxWaitingTime) {
            try {
                if (! $this->api->status($tmName) && empty($this->api->getError())) {
                    sleep($this->getRetryDelaySeconds());
                    $elapsedTime += $this->getRetryDelaySeconds();

                    continue;
                }
            } catch (\ZfExtended_Zendoverwrites_Http_Exception $e) {
                $this->logger->error(
                    'E1313',
                    't5memory: We got some unexpected response. Look into system logs for details.',
                    [
                        'languageResource' => $this->languageResource,
                        'error' => $e->getMessage(),
                    ]
                );
                $this->logger->exception($e);

                sleep($this->getRetryDelaySeconds());
                $elapsedTime += $this->getRetryDelaySeconds();

                continue;
            }

            $result = $this->api->getResult();
            $status = $this->processImportStatus(is_object($result) ? $result : null);

            if ($status !== LanguageResourceStatus::IMPORT) {
                break;
            }

            sleep($this->getRetryDelaySeconds());
            $elapsedTime += $this->getRetryDelaySeconds();
        }
    }

    private function generateNextMemoryName(
        LanguageResource $languageResource,
        string $usedName = null,
        bool $forceNextSuffix = false,
    ): string {
        $pattern = sprintf('/%s(\d+)$/', self::NEXT_SUFFIX);

        if (null !== $usedName && preg_match($pattern, $usedName, $matches)) {
            return $this->generateTmFilename($languageResource) . self::NEXT_SUFFIX . ((int) $matches[1] + 1);
        }

        $memories = $languageResource->getSpecificData('memories', parseAsArray: true);

        if (empty($memories) && ! $forceNextSuffix) {
            return $this->generateTmFilename($languageResource);
        }

        $currentMax = 0;
        foreach ($memories ?: [] as $memory) {
            if (! preg_match($pattern, $memory['filename'], $matches)) {
                continue;
            }

            $currentMax = $currentMax > $matches[1] ? $currentMax : (int) $matches[1];
        }

        return $this->generateTmFilename($languageResource) . self::NEXT_SUFFIX . ($currentMax + 1);
    }

    public function export(string $mime): ?string
    {
        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        return $this->exportService->export(
            $this->languageResource,
            ExportTmFileExtension::fromMimeType($mime, count($memories) > 1)
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

    private function generateTmFilename(editor_Models_LanguageResources_LanguageResource $languageResource): string
    {
        return 'ID' . $languageResource->getId() . '-' . $this->filterName($languageResource->getName());
    }

    private function getMaxRequestRetries(): int
    {
        return (int) $this->config->runtimeOptions->LanguageResources->t5memory->requestMaxRetries;
    }

    private function getRetryDelaySeconds(): int
    {
        return (int) $this->config->runtimeOptions->LanguageResources->t5memory->requestRetryDelaySeconds;
    }

    private function getMaxWaitingTimeSeconds(): int
    {
        if ($this->canWaitLongTaskFinish()) {
            // 1 hour max waiting time
            return 3600;
        }

        return $this->getRetryDelaySeconds() * $this->getMaxRequestRetries();
    }

    /**
     * Shows if connector can wait for a long-running task (e.g. reorganize, import, export) to finish before do
     * fuzzy requests or other operations.
     */
    private function canWaitLongTaskFinish(): bool
    {
        // This should be moved to config, but this requires refactoring of the connectors
        // and introducing a connector factory instead of manager as it is implemented at the moment
        return defined('ZFEXTENDED_IS_WORKER_THREAD');
    }

    private function createEmptyMemoryWithRetry(string $name, string $sourceLang): ?string
    {
        $t5memoryName = null;
        $elapsedTime = 0;
        $maxWaitingTime = $this->getMaxWaitingTimeSeconds();

        while ($elapsedTime < $maxWaitingTime) {
            $t5memoryName = $this->api->createEmptyMemory($name, $sourceLang);

            if ($t5memoryName) {
                break;
            }

            $apiError = $this->api->getError();

            $this->logger->warn(
                'E1305',
                't5memory: Could not create empty memory - waiting for {elapsedTime} seconds.',
                [
                    'languageResource' => $this->languageResource,
                    'apiError' => $apiError,
                    'elapsedTime' => $elapsedTime,
                ]
            );

            sleep($this->getRetryDelaySeconds());
            $elapsedTime += $this->getRetryDelaySeconds();

            if (isset($apiError->error) && strpos($apiError->error, 'ERROR_MEM_NAME_EXISTS')) {
                $name = $this->generateNextMemoryName($this->languageResource, $name, true);
            }
        }

        return $t5memoryName;
    }

    protected function getSegmentContext(editor_Models_Segment $segment): string
    {
        return $segment->meta()->getSegmentDescriptor()
            ?: self::SEGMENT_NR_CONTEXT_PREFIX . $segment->getSegmentNrInTask();
    }
}

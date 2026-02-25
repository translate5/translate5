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
use editor_Services_T5Memory_Connector as T5MemoryConnector;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagService;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagServiceInterface;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Adapter\LanguagePairDTO;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\Plugins\TMMaintenance\Exception\BatchDeleteException;
use MittagQI\Translate5\Plugins\TMMaintenance\TagHandler\T5MemoryXliff;
use MittagQI\Translate5\Plugins\TMMaintenance\TagHandler\TagHandlerProvider;
use MittagQI\Translate5\T5Memory\Api\Response\FindDTO;
use MittagQI\Translate5\T5Memory\ConcordanceSearchService;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\StatusService;
use MittagQI\Translate5\T5Memory\UpdateRetryService;

/**
 * This is a temporary service partially copying functionality from the T5MemoryConnector
 */
class MaintenanceService extends \editor_Services_Connector_Abstract
{
    private const CONCORDANCE_SEARCH_NUM_RESULTS = 1;

    protected const TAG_HANDLER_CONFIG_PART = 't5memory';

    private \editor_Services_T5Memory_HttpApi $api;

    /**
     *  Is the connector generally able to support internal Tags for the translate-API
     * @var bool
     */
    protected $internalTagSupport = true;

    private T5MemoryConnector $t5MemoryConnector;

    private readonly ConvertT5MemoryTagServiceInterface $tmConversionService;

    private readonly UpdateRetryService $updateRetryService;

    private readonly PersistenceService $persistenceService;

    private readonly StatusService $statusService;

    private readonly ConcordanceSearchService $concordanceSearchService;

    public function __construct()
    {
        \editor_Services_Connector_Exception::addCodes([
            'E1314' => 'The queried T5Memory TM "{tm}" is corrupt and must be reorganized before usage!',
            'E1333' => 'The queried T5Memory server has to many open TMs!',
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
        $this->updateRetryService = UpdateRetryService::create();
        $this->persistenceService = PersistenceService::create();
        $this->statusService = StatusService::create();
        $this->concordanceSearchService = ConcordanceSearchService::createWithTagHandlerProvider(
            TagHandlerProvider::create(),
        );

        parent::__construct();
    }

    public function connectTo(
        LanguageResource $languageResource,
        LanguagePairDTO $languagePair,
        $config = null,
    ): void {
        $this->api = \ZfExtended_Factory::get('editor_Services_T5Memory_HttpApi');
        $this->api->setLanguageResource($languageResource);

        $this->t5MemoryConnector->connectTo($languageResource, $languagePair, $config);
        parent::connectTo($languageResource, $languagePair, $config);
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
        );
    }

    /**
     * Update method was designed to work with SegmentModel context
     * so this method was added to be able to update a memory entry without an SegmentModel
     */
    public function updateSegment(
        string $source,
        string $target,
        string $userName,
        string $context,
        int $timestamp,
        string $fileName,
    ): FindDTO {
        $source = $this->tagHandler->prepareQuery($source);
        $this->tagHandler->setInputTagMap($this->tagHandler->getTagMap());
        $target = $this->tagHandler->prepareQuery($target, false);

        return $this->updateSegmentInMemory(
            $source,
            $target,
            $userName,
            $context,
            $timestamp,
            $fileName,
        );
    }

    public function deleteBatch(SearchDTO $dto): bool
    {
        try {
            TuBatchDeleteWorker::queueWorker($this->languageResource, $dto);
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
        return $this->concordanceSearchService->query(
            $this->languageResource,
            $searchDTO,
            $offset,
            $this->getConfig(),
            $amountOfResults,
        );
    }

    public function countSegments(SearchDTO $searchDTO): int
    {
        return $this->concordanceSearchService->countSegments($this->languageResource, $searchDTO, $this->getConfig());
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
        return $this->statusService->getStatus($this->languageResource, $tmName);
    }

    private function updateSegmentInMemory(
        string $source,
        string $target,
        string $userName,
        string $context,
        int $timestamp,
        string $fileName,
    ): FindDTO {
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
            return $this->updateRetryService->updateWithRetry(
                $this->languageResource,
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
}

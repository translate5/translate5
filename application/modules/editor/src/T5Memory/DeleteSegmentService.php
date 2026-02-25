<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\T5Memory\Api\Response\DeleteEntryResponse;
use MittagQI\Translate5\T5Memory\Api\Response\FindDTO;
use MittagQI\Translate5\T5Memory\Api\Response\GetEntryResponse;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\Concordance\IdAttributeReplacer;
use MittagQI\Translate5\T5Memory\DTO\DeleteSegmentCheckOptions;
use MittagQI\Translate5\T5Memory\DTO\DeleteSegmentDTO;
use MittagQI\Translate5\T5Memory\DTO\FuzzyMatchDTO;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use MittagQI\Translate5\T5Memory\TagHandler\TagHandlerProvider;
use Zend_Config;

class DeleteSegmentService
{
    private const int MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly FuzzySearchService $fuzzySearchService,
        private readonly T5MemoryApi $t5MemoryApi,
        private readonly RetryService $retryService,
        private readonly ReorganizeService $reorganizeService,
        private readonly Zend_Config $config,
        private readonly PersistenceService $persistenceService,
        private readonly SegmentsCompare $segmentsCompare,
        private readonly ConcordanceSearchService $concordanceSearchService,
        private readonly IdAttributeReplacer $idAttributeReplacer,
        private readonly TagHandlerProvider $tagHandlerProvider,
    ) {
    }

    public static function create(): self
    {
        return new self(
            FuzzySearchService::createForDeleteSegmentService(),
            T5MemoryApi::create(),
            RetryService::create(),
            ReorganizeService::create(),
            \Zend_Registry::get('config'),
            PersistenceService::create(),
            SegmentsCompare::create(),
            ConcordanceSearchService::createForDeleteSegmentService(),
            new IdAttributeReplacer(),
            TagHandlerProvider::create(),
        );
    }

    /**
     * @throws Exception\ReorganizeException
     * @throws editor_Services_Connector_Exception
     */
    public function deleteDuplicates(LanguageResource $languageResource, UpdateSegmentDTO $updateDTO): void
    {
        $this->deleteDuplicatedEntries($languageResource, $updateDTO);
    }

    /**
     * @throws Exception\ReorganizeException
     * @throws editor_Services_Connector_Exception
     */
    private function deleteDuplicatedEntries(
        LanguageResource $languageResource,
        UpdateSegmentDTO $deleteDTO,
        int $attempt = 1,
    ): void {
        if ($attempt > self::MAX_ATTEMPTS) {
            throw new editor_Services_Connector_Exception('E1688', [
                'languageResource' => $languageResource,
                'error' => 'Cannot delete duplicates after ' . self::MAX_ATTEMPTS . ' attempts',
            ]);
        }

        $matches = $this->fuzzySearchService->query(
            $languageResource,
            $deleteDTO->source,
            $deleteDTO->context,
            $deleteDTO->fileName,
            fn ($matchRate, $meta, $docName) => $matchRate,
            $this->config,
            false,
            false,
        );

        $t5memoryUrl = $languageResource->getResource()->getUrl();

        $filterOptions = TmxFilterOptions::fromConfig($this->config);

        $toDelete = [];

        foreach ($matches as $match) {
            if ($match->matchrate < 100) {
                continue;
            }

            if (! $this->segmentsCompare->areSegmentsEqual($match->source, $deleteDTO->source)) {
                continue;
            }

            if ($match->timestamp > $deleteDTO->timestamp) {
                continue;
            }

            if (! $filterOptions->skipAuthor && $match->getMetaField('author') !== $deleteDTO->userName) {
                continue;
            }

            if (! $filterOptions->skipDocument && $match->getMetaField('documentName') !== $deleteDTO->fileName) {
                continue;
            }

            if (! $filterOptions->skipContext && $match->getMetaField('context') !== $deleteDTO->context) {
                continue;
            }

            if ($filterOptions->preserveTargets && ! $this->segmentsCompare->areSegmentsEqual($deleteDTO->target, $match->target)) {
                continue;
            }

            $toDelete[] = $match;
        }

        if (empty($toDelete)) {
            return;
        }

        $toDelete = $this->sortMatchesFromLastToFirst($toDelete);
        $reorganizeOptions = new ReorganizeOptions($filterOptions);

        foreach ($toDelete as $match) {
            if (! $this->isValidMatch($t5memoryUrl, $match)) {
                $this->deleteWithConcordance(
                    $languageResource,
                    $this->composeSearchDtoFromMatch($match, $languageResource),
                    $match->tmName,
                );

                continue;
            }

            $response = $this->deleteEntryWithRetry(
                $languageResource,
                $match->tmName,
                (int) $match->getMetaField('segmentId'),
                $match->getMetaField('internalKey'),
            );

            if ($response->successful()) {
                continue;
            }

            if ($this->reorganizeService->needsReorganizing($response, $languageResource, $match->tmName, false)) {
                $this->triggerReorganize($languageResource, $match->tmName, $reorganizeOptions);

                $this->deleteDuplicatedEntries($languageResource, $deleteDTO);

                break;
            }

            if ($response->segmentChanged()) {
                $this->deleteDuplicatedEntries($languageResource, $deleteDTO, $attempt + 1);

                break;
            }
        }

        $this->deleteDuplicatedEntries($languageResource, $deleteDTO, $attempt + 1);
    }

    /**
     * @throws editor_Services_Connector_Exception
     */
    public function deleteSegment(LanguageResource $languageResource, DeleteSegmentDTO $deleteDTO): void
    {
        $this->deleteSimilarSegments($languageResource, $deleteDTO, DeleteSegmentCheckOptions::exactCheck());
    }

    /**
     * @throws editor_Services_Connector_Exception
     */
    public function deleteSameSourceSegments(LanguageResource $languageResource, string $source): void
    {
        $deleteDTO = new DeleteSegmentDTO(
            source: $source,
            target: '',
            author: '',
            timestamp: '',
            documentName: '',
            context: '',
        );

        $this->deleteSimilarSegments($languageResource, $deleteDTO, DeleteSegmentCheckOptions::sameSourceCheck());
    }

    /**
     * @throws editor_Services_Connector_Exception
     */
    public function deleteSameSourceAndTargetSegments(
        LanguageResource $languageResource,
        string $source,
        string $target,
    ): void {
        $deleteDTO = new DeleteSegmentDTO(
            source: $source,
            target: $target,
            author: '',
            timestamp: '',
            documentName: '',
            context: '',
        );

        $this->deleteSimilarSegments(
            $languageResource,
            $deleteDTO,
            DeleteSegmentCheckOptions::sameSourceAndTargetCheck()
        );
    }

    private function deleteSimilarSegments(
        LanguageResource $languageResource,
        DeleteSegmentDTO $deleteDTO,
        DeleteSegmentCheckOptions $check,
        int $attempt = 1,
    ): void {
        if ($attempt > self::MAX_ATTEMPTS) {
            throw new editor_Services_Connector_Exception('E1688', [
                'languageResource' => $languageResource,
                'error' => 'Segment delete failed after ' . self::MAX_ATTEMPTS . ' attempts',
            ]);
        }

        $matches = $this->fuzzySearchService->query(
            $languageResource,
            $deleteDTO->source,
            $deleteDTO->context,
            $deleteDTO->documentName,
            fn ($matchRate, $meta, $docName) => $matchRate,
            $this->config,
            false,
            false,
        );

        $t5memoryUrl = $languageResource->getResource()->getUrl();

        /** @var FuzzyMatchDTO[] $toDelete */
        $toDelete = [];
        $retry = false;

        $tmsWithMatches = [];

        foreach ($matches as $match) {
            if ($match->matchrate < 100) {
                continue;
            }

            $tmsWithMatches[$match->tmName] = true;

            $internalKey = $match->getMetaField('internalKey');

            $entry = $this->t5MemoryApi->getEntry(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($match->tmName),
                $internalKey
            );

            if (! $this->isSameSegment($match, $entry)) {
                $entryMatch = FuzzyMatchDTO::fromEntry($entry, $match->tmName);

                if ($this->theOneToDelete($deleteDTO, $entryMatch, $check)) {
                    $toDelete[] = $entryMatch;

                    continue;
                }

                $retry = true;

                continue;
            }

            if ($this->theOneToDelete($deleteDTO, $match, $check)) {
                $toDelete[] = $match;
            }
        }

        if (empty($toDelete) && ! $retry) {
            // looks like we haven't found segment because of same source + target bug of t5memory
            if (! empty($tmsWithMatches) && $attempt === 1) {
                $searchDto = $this->composeSearchDtoFromDeleteDto($deleteDTO, $languageResource);

                foreach ($tmsWithMatches as $tmName => $_) {
                    $this->deleteWithConcordance($languageResource, $searchDto, $tmName);
                }
            }

            return;
        }

        $toDelete = $this->sortMatchesFromLastToFirst($toDelete);

        $reorganizeOptions = new ReorganizeOptions(TmxFilterOptions::fromConfig($this->config));

        /** @var FuzzyMatchDTO $match */
        foreach ($toDelete as $match) {
            if (! $this->isValidMatch($t5memoryUrl, $match)) {
                $this->deleteWithConcordance(
                    $languageResource,
                    $this->composeSearchDtoFromMatch($match, $languageResource),
                    $match->tmName,
                );

                continue;
            }

            $response = $this->deleteEntryWithRetry(
                $languageResource,
                $match->tmName,
                (int) $match->getMetaField('segmentId'),
                $match->getMetaField('internalKey'),
            );

            if ($response->successful()) {
                continue;
            }

            if ($this->reorganizeService->needsReorganizing($response, $languageResource, $match->tmName, false)) {
                $this->triggerReorganize($languageResource, $match->tmName, $reorganizeOptions);

                $this->deleteSimilarSegments($languageResource, $deleteDTO, $check);

                break;
            }

            if ($response->segmentChanged()) {
                $this->deleteSimilarSegments($languageResource, $deleteDTO, $check, $attempt + 1);

                break;
            }
        }

        $this->deleteSimilarSegments($languageResource, $deleteDTO, $check, $attempt + 1);
    }

    private function theOneToDelete(
        DeleteSegmentDTO $deleteDTO,
        FuzzyMatchDTO $match,
        DeleteSegmentCheckOptions $check,
    ): bool {
        if ($check->checkTarget && ! $this->segmentsCompare->areSegmentsEqual($deleteDTO->target, $match->target)) {
            return false;
        }

        if ($check->checkAuthor && $match->getMetaField('author') !== $deleteDTO->author) {
            return false;
        }

        if ($check->checkTimestamp && $match->timestamp !== strtotime($deleteDTO->timestamp)) {
            return false;
        }

        if ($check->checkDocumentName && $match->getMetaField('documentName') !== $deleteDTO->documentName) {
            return false;
        }

        if ($check->checkContext && $match->getMetaField('context') !== $deleteDTO->context) {
            return false;
        }

        return true;
    }

    private function isSameSegment(FuzzyMatchDTO $match, GetEntryResponse $entry): bool
    {
        if ($match->getMetaField('internalKey') !== $entry->getInternalKey()) {
            return false;
        }

        if ((int) $match->getMetaField('segmentId') !== $entry->getSegmentId()) {
            return false;
        }

        if (strtolower($match->getMetaField('author')) !== strtolower($entry->getAuthor())) {
            return false;
        }

        if (! $this->segmentsCompare->areSegmentsEqual($match->source, $entry->getSource())) {
            return false;
        }

        if (! $this->segmentsCompare->areSegmentsEqual($match->target, $entry->getTarget())) {
            return false;
        }

        return true;
    }

    private function sortMatchesFromLastToFirst(array $toDelete): array
    {
        usort(
            $toDelete,
            function (FuzzyMatchDTO $a, FuzzyMatchDTO $b) {
                $internalKeyA = $a->getMetaField('internalKey');
                [$recordKeyA, $targetKeyA] = explode(':', $internalKeyA);

                $internalKeyB = $b->getMetaField('internalKey');
                [$recordKeyB, $targetKeyB] = explode(':', $internalKeyB);

                if ($recordKeyA > $recordKeyB) {
                    return -1;
                }

                if ($recordKeyA < $recordKeyB) {
                    return 1;
                }

                return $targetKeyB <=> $targetKeyA;
            }
        );

        return $toDelete;
    }

    private function sortFindsFromLastToFirst(array $finds): array
    {
        usort(
            $finds,
            function (FindDTO $a, FindDTO $b) {
                [$recordKeyA, $targetKeyA] = explode(':', $a->internalKey);

                [$recordKeyB, $targetKeyB] = explode(':', $b->internalKey);

                if ($recordKeyA > $recordKeyB) {
                    return -1;
                }

                if ($recordKeyA < $recordKeyB) {
                    return 1;
                }

                return $targetKeyB <=> $targetKeyA;
            }
        );

        return $finds;
    }

    private function composeSearchDtoFromMatch(FuzzyMatchDTO $match, LanguageResource $languageResource): SearchDTO
    {
        [$source, $target] = $this->prepareSourceAndTargetForConcordance(
            $match->source,
            $match->target,
            $languageResource,
        );

        return SearchDTO::searchExactSegment(
            $source,
            $target,
            $match->getMetaField('author'),
            $match->getMetaField('documentName'),
            $match->getMetaField('context'),
        );
    }

    private function composeSearchDtoFromDeleteDto(DeleteSegmentDTO $deleteDto, LanguageResource $languageResource): SearchDTO
    {
        [$source, $target] = $this->prepareSourceAndTargetForConcordance(
            $deleteDto->source,
            $deleteDto->target,
            $languageResource,
        );

        return SearchDTO::searchExactSegment(
            $source,
            $target,
            $deleteDto->author,
            $deleteDto->documentName,
            $deleteDto->context,
        );
    }

    private function prepareSourceAndTargetForConcordance(
        string $source,
        string $target,
        LanguageResource $languageResource
    ): array {
        $tagHandler = $this->tagHandlerProvider->getTagHandler(
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getTargetLang(),
            $this->config,
        );

        $source = $tagHandler->prepareQuery($source, true);
        $target = $tagHandler->prepareQuery($target, false);

        $source = $this->idAttributeReplacer->replace($source);
        $target = $this->idAttributeReplacer->replace($target);

        return [$source, $target];
    }

    public function deleteWithConcordance(
        LanguageResource $languageResource,
        SearchDTO $search,
        ?string $tmName = null,
    ): void {
        $tmNames = null === $tmName
            ? array_column(
                $languageResource->getSpecificData('memories', true),
                'filename'
            )
            : [$tmName];

        foreach ($tmNames as $tm) {
            [$concordanceFinds, $nextSearchPosition] = $this->concordanceSearchService->queryTm(
                $languageResource,
                $tm,
                $search,
                null,
                100,
                $this->config,
            );

            $reorganizeOptions = new ReorganizeOptions(TmxFilterOptions::fromConfig($this->config));

            $concordanceFinds = $this->sortFindsFromLastToFirst($concordanceFinds);

            /** @var FindDTO $find */
            foreach ($concordanceFinds as $find) {
                $response = $this->deleteEntryWithRetry(
                    $languageResource,
                    $tm,
                    $find->segmentId,
                    $find->internalKey,
                );

                if ($this->reorganizeService->needsReorganizing($response, $languageResource, $tm, false)) {
                    $this->triggerReorganize($languageResource, $tm, $reorganizeOptions);

                    $this->deleteWithConcordance($languageResource, $search, $tm);

                    break;
                }
            }
        }
    }

    private function deleteEntryWithRetry(
        LanguageResource $languageResource,
        string $tmName,
        int $segmentId,
        string $internalKey,
    ): DeleteEntryResponse {
        /**
         * @return array{WaitCallState, DeleteEntryResponse|null}
         */
        $delete = function () use (
            $languageResource,
            $tmName,
            $internalKey,
            $segmentId,
        ) {
            [$recordKey, $targetKey] = explode(':', $internalKey);

            $response = $this->t5MemoryApi->deleteEntry(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName),
                (int) $recordKey,
                (int) $targetKey,
                $segmentId,
            );

            return $response->isLockingTimeoutOccurred()
                ? [WaitCallState::Retry, null]
                : [WaitCallState::Done, $response];
        };

        /** @var DeleteEntryResponse|null $response */
        $response = $this->retryService->callAwaiting($delete);

        if (null === $response) {
            throw new editor_Services_Connector_Exception('E1688', [
                'languageResource' => $languageResource,
                'error' => 'Segment delete failed after waiting for lock',
            ]);
        }

        return $response;
    }

    private function isValidMatch(string $t5memoryUrl, FuzzyMatchDTO $match): bool
    {
        $entryResponse = $this->t5MemoryApi->getEntry(
            $t5memoryUrl,
            $this->persistenceService->addTmPrefix($match->tmName),
            $match->getMetaField('internalKey'),
        );

        if ((int) $match->getMetaField('segmentId') !== $entryResponse->getSegmentId()) {
            return false;
        }

        if (! $this->segmentsCompare->areSegmentsEqual($match->source, $entryResponse->getSource())) {
            return false;
        }

        if (! $this->segmentsCompare->areSegmentsEqual($match->target, $entryResponse->getTarget())) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception\ReorganizeException
     */
    private function triggerReorganize(
        LanguageResource $languageResource,
        string $tmName,
        ReorganizeOptions $options,
    ): void {
        $this->reorganizeService->reorganizeTm($languageResource, $tmName, $options);
        $this->reorganizeService->resetReorganizeAttempts($languageResource, $tmName);
    }
}

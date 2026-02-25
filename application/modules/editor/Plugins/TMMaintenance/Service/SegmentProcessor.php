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

namespace MittagQI\Translate5\Plugins\TMMaintenance\Service;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Connector_Exception;
use MittagQI\Translate5\Integration\ActionLockService;
use MittagQI\Translate5\LanguageResource\Adapter\LanguagePairDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\CreateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteSimilarDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\GetListDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\UpdateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\Enum\BatchMode;
use MittagQI\Translate5\Plugins\TMMaintenance\Enum\SimilarDeleteType;
use MittagQI\Translate5\Plugins\TMMaintenance\TagHandler\TagHandlerProvider;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\Contract\TagHandlerProviderInterface;
use MittagQI\Translate5\T5Memory\DeleteSegmentService;
use MittagQI\Translate5\T5Memory\DTO\DeleteSegmentDTO;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO as T5SearchDTO;
use MittagQI\Translate5\T5Memory\Enum\SearchMode;
use MittagQI\Translate5\T5Memory\PrepareSegmentText;
use ZfExtended_Factory;
use ZfExtended_Utils;

final class SegmentProcessor
{
    public function __construct(
        private readonly DeleteSegmentService $deleteSegmentService,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly TagHandlerProviderInterface $tagHandlerProvider,
        private readonly \Zend_Config $config,
        private readonly ActionLockService $lockService,
        private readonly PrepareSegmentText $prepareSegmentText,
    ) {
    }

    public static function create(): self
    {
        return new self(
            DeleteSegmentService::create(),
            LanguageResourceRepository::create(),
            TagHandlerProvider::create(),
            \Zend_Registry::get('config'),
            ActionLockService::create(),
            PrepareSegmentText::create(),
        );
    }

    /**
     * @return array{items: array, metaData: array}
     *
     * @throws editor_Services_Connector_Exception
     */
    public function getList(GetListDTO $getListDto): array
    {
        $lock = $this->lockService->getReadLockWithId($getListDto->tmId);

        if (! $lock->acquireRead()) {
            throw new editor_Services_Connector_Exception('E1377', [
                'status' => 'Locked',
            ]);
        }

        try {
            $connector = $this->getConnector($getListDto->tmId);
            $totalAmount = 0;
            $limit = $getListDto->limit;
            $result = [];
            $offset = $getListDto->offset;
            $searchDto = $this->getSearchDto($getListDto);

            while ($totalAmount < $limit) {
                $resultList = $connector->concordanceSearch(
                    '',
                    '',
                    $offset,
                    $searchDto
                );

                $data = $resultList->getResult();
                $data = $this->reformatData($data);

                $data = array_map(static function (array $item) use ($getListDto) {
                    $item['metaData']['internalKey'] = $item['metaData']['partId'] . ':' . $item['metaData']['internalKey'];
                    $item['internalKey'] = $getListDto->tmId . ':' . $item['metaData']['internalKey'];
                    $item['id'] = ZfExtended_Utils::uuid();

                    return $item;
                }, $data);

                $offset = $resultList->getNextOffset();

                $totalAmount += count($data);
                $result[] = $data;

                if (null === $offset) {
                    break;
                }
            }

            return [
                'items' => array_merge(...$result),
                'metaData' => [
                    'offset' => $offset,
                ],
            ];
        } finally {
            $lock->release();
        }
    }

    public function countResults(GetListDTO $getListDTO): int
    {
        $lock = $this->lockService->getReadLockWithId($getListDTO->tmId);
        if (! $lock->acquireRead()) {
            throw new editor_Services_Connector_Exception('E1377', [
                'status' => 'Locked',
            ]);
        }

        try {
            $connector = $this->getConnector($getListDTO->tmId);
            $searchDto = $this->getSearchDto($getListDTO);

            return $connector->countSegments($searchDto);
        } finally {
            $lock->release();
        }
    }

    public function createSegment(CreateDTO $createDto): void
    {
        $lock = $this->lockService->getReadLockWithId($createDto->tmId);
        if (! $lock->acquire()) {
            throw new editor_Services_Connector_Exception('E1377', [
                'status' => 'Locked',
            ]);
        }

        try {
            $connector = $this->getConnector($createDto->tmId);
            $connector->createSegment(
                $createDto->source,
                $createDto->target,
                \ZfExtended_Authentication::getInstance()->getUser()?->getUserName(),
                $createDto->context,
                (new \DateTimeImmutable())->getTimestamp(),
                $createDto->documentName,
            );
        } finally {
            $lock->release();
        }
    }

    public function update(DeleteSegmentDTO $deleteSegmentDTO, UpdateDTO $updateDto): array
    {
        $lock = $this->lockService->getWriteLockWithId($updateDto->tmId);
        if (! $lock->acquire()) {
            throw new editor_Services_Connector_Exception('E1377', [
                'status' => 'Locked',
            ]);
        }

        // Get th language resource here to ensure it is loaded within the lock
        // so that its state is up to date with DB
        $languageResource = $this->languageResourceRepository->get($updateDto->tmId);

        try {
            $this->deleteSegmentService->deleteSegment($languageResource, $deleteSegmentDTO);

            $connector = $this->getConnector($updateDto->tmId);
            $result = $connector->updateSegment(
                $updateDto->source,
                $updateDto->target,
                \ZfExtended_Authentication::getInstance()->getUser()?->getUserName(),
                $updateDto->context,
                (new \DateTimeImmutable())->getTimestamp(),
                $updateDto->documentName,
            );

            $tagHandler = $this->tagHandlerProvider->getTagHandler(
                (int) $languageResource->getSourceLang(),
                (int) $languageResource->getTargetLang(),
                $this->config,
            );

            return [
                'source' => $tagHandler->restoreInResult($result->source),
                'target' => $tagHandler->restoreInResult($result->target, false),
                'internalKey' => $updateDto->tmId . ':' . $result->partId . ':' . $result->internalKey,
                'metaData' => [
                    'author' => $result->author,
                    'timestamp' => date('Y-m-d H:i:s T', strtotime($result->timestamp) ?: 0),
                    'documentName' => $result->documentName,
                    'context' => $result->context,
                    'segmentId' => $result->segmentId,
                    'internalKey' => $result->partId . ':' . $result->internalKey,
                    'sourceLang' => $result->sourceLang,
                    'targetLang' => $result->targetLang,
                ],
            ];
        } finally {
            $lock->release();
        }
    }

    public function deleteBatch(GetListDTO $dto, BatchMode $mode): void
    {
        $connector = $this->getConnector($dto->tmId);

        if ($mode === BatchMode::Batch) {
            $connector->deleteBatch($this->getSearchDto($dto));

            return;
        }

        $lock = $this->lockService->getWriteLockWithId($dto->tmId);
        if (! $lock->acquire()) {
            throw new editor_Services_Connector_Exception('E1377', [
                'status' => 'Locked',
            ]);
        }

        // language resource might have been updated while waiting for the lock,
        // so we have to get fresh one to ensure we have the latest data and status
        $languageResource = $this->languageResourceRepository->get($dto->tmId);

        $searchDto = $this->getSearchDto($dto);

        try {
            $this->deleteSegmentService->deleteWithConcordance($languageResource, $searchDto);
        } finally {
            $lock->release();
        }
    }

    public function deleteSegment(LanguageResource $languageResource, DeleteSegmentDTO $deleteDto): void
    {
        $lock = $this->lockService->getWriteLock($languageResource->getLangResUuid());
        if (! $lock->acquire()) {
            throw new editor_Services_Connector_Exception('E1377', [
                'status' => 'Locked',
            ]);
        }

        // language resource might have been updated while waiting for the lock,
        // so we have to get fresh one to ensure we have the latest data and status
        $languageResource = $this->languageResourceRepository->get((int) $languageResource->getId());

        try {
            $this->deleteSegmentService->deleteSegment($languageResource, $deleteDto);
        } finally {
            $lock->release();
        }
    }

    public function deleteSimilarSegments(
        LanguageResource $languageResource,
        DeleteSimilarDTO $similarDTO,
        \Zend_Config $config,
    ): void {
        $lock = $this->lockService->getWriteLock($languageResource->getLangResUuid());
        if (! $lock->acquire()) {
            throw new editor_Services_Connector_Exception('E1377', [
                'status' => 'Locked',
            ]);
        }

        // language resource might have been updated while waiting for the lock,
        // so we have to get fresh one to ensure we have the latest data and status
        $languageResource = $this->languageResourceRepository->get((int) $languageResource->getId());

        [$source, $target] = $this->prepareSegmentText->prepareText(
            $languageResource,
            $similarDTO->source,
            $similarDTO->target,
            $config,
        );

        try {
            match ($similarDTO->type) {
                SimilarDeleteType::SameSource => $this->deleteSegmentService->deleteSameSourceSegments(
                    $languageResource,
                    $source
                ),
                SimilarDeleteType::SameSourceAndTarget => $this->deleteSegmentService->deleteSameSourceAndTargetSegments(
                    $languageResource,
                    $source,
                    $target,
                ),
            };
        } finally {
            $lock->release();
        }
    }

    private function reformatData(array $data): array
    {
        $result = [];

        foreach ($data as $item) {
            $item = (array) $item;
            $metadata = [];

            foreach ($item['metaData'] as $metadataum) {
                $metadata[$metadataum->name] = $metadataum->value;
            }

            $item['metaData'] = $metadata;

            $result[] = $item;
        }

        return $result;
    }

    private function getConnector(int $languageResourceId): MaintenanceService
    {
        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $languageResource->load($languageResourceId);

        $connector = new MaintenanceService();
        $connector->connectTo(
            $languageResource,
            LanguagePairDTO::fromLanguageResource($languageResource),
        );

        return $connector;
    }

    private function getSearchDto(GetListDTO $getListDto): T5SearchDTO
    {
        $data = $getListDto->toArray();

        $data = $this->transformSearchData($data);

        return T5SearchDTO::fromArray($data);
    }

    private function transformSearchData(array $data): array
    {
        $modes = ['sourceMode', 'targetMode', 'authorMode', 'additionalInfoMode', 'documentMode', 'contextMode'];

        foreach ($modes as $mode) {
            $data[$mode] = $this->parseMode($data[$mode]);
        }

        $data['creationDateFrom'] = (new \DateTime($data['creationDateFrom'] ?: '1970-01-01'))->getTimestamp();
        $data['creationDateTo'] = (new \DateTime($data['creationDateTo'] ?: 'tomorrow'))->getTimestamp();

        return $data;
    }

    private function parseMode(?string $mode): SearchMode
    {
        return match ($mode) {
            'concordance' => SearchMode::Concordance,
            'exact' => SearchMode::Exact,
            default => SearchMode::Contains,
        };
    }
}

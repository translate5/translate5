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

use editor_Models_LanguageResources_LanguageResource;
use editor_Services_Connector_Exception;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\CreateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteBatchDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\GetListDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\UpdateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\Overwrites\T5MemoryXliff;
use MittagQI\Translate5\T5Memory\DTO\DeleteBatchDTO as T5DeleteBatchDTO;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO as T5SearchDTO;
use ZfExtended_Factory;
use ZfExtended_Utils;

final class SegmentProcessor
{
    /**
     * @return array{items: array, metaData: array}
     *
     * @throws editor_Services_Connector_Exception
     */
    public function getList(GetListDTO $getListDto): array
    {
        $connector = $this->getOpenTM2Connector($getListDto->tmId);
        $totalAmount = 0;
        $limit = $getListDto->limit;
        $result = [];
        $offset = $getListDto->offset;
        $searchDto = $this->getSearchDto($getListDto);

        while ($totalAmount < $limit) {
            $resultList = $connector->search(
                '',
                '',
                $offset,
                $searchDto
            );

            $data = $resultList->getResult();
            $data = $this->reformatData($data);

            $data = array_map(static function (array $item) use ($getListDto) {
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
    }

    public function countResults(GetListDTO $getListDTO): int
    {
        $connector = $this->getOpenTM2Connector($getListDTO->tmId);
        $searchDto = $this->getSearchDto($getListDTO);

        return $connector->countSegments($searchDto);
    }

    public function create(CreateDTO $createDto): void
    {
        $connector = $this->getOpenTM2Connector($createDto->tmId);
        $connector->createSegment(
            $createDto->source,
            $createDto->target,
            \ZfExtended_Authentication::getInstance()->getUser()?->getUserName(),
            $createDto->context,
            (new \DateTimeImmutable())->getTimestamp(),
            $createDto->documentName,
        );
    }

    public function update(UpdateDTO $updateDto): void
    {
        [$tmId, $memoryId, $recordKey, $targetKey] = explode(':', $updateDto->internalKey);

        $connector = $this->getOpenTM2Connector($updateDto->tmId);
        $connector->updateSegment(
            (int) $memoryId,
            $updateDto->id,
            (int) $recordKey,
            (int) $targetKey,
            $updateDto->source,
            $updateDto->target,
            \ZfExtended_Authentication::getInstance()->getUser()?->getUserName(),
            $updateDto->context,
            (new \DateTimeImmutable())->getTimestamp(),
            $updateDto->documentName,
        );
    }

    public function delete(DeleteDTO $deleteDto): void
    {
        [$tmId, $memoryId, $recordKey, $targetKey] = explode(':', $deleteDto->internalKey);

        $connector = $this->getOpenTM2Connector((int) $tmId);
        $connector->deleteEntry((int) $memoryId, $deleteDto->id, (int) $recordKey, (int) $targetKey);
    }

    public function deleteBatch(DeleteBatchDTO $deleteBatchDto): void
    {
        $connector = $this->getOpenTM2Connector($deleteBatchDto->tmId);
        $connector->deleteBatch($this->getDeleteBatchDto($deleteBatchDto));
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

    private function getOpenTM2Connector(int $languageResourceId): MaintenanceService
    {
        $languageResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
        $languageResource->load($languageResourceId);

        ZfExtended_Factory::addOverwrite(
            \editor_Services_Connector_TagHandler_T5MemoryXliff::class,
            T5MemoryXliff::class
        );

        $connector = new MaintenanceService();
        $connector->connectTo(
            $languageResource,
            $languageResource->getSourceLang(),
            $languageResource->getTargetLang()
        );

        return $connector;
    }

    private function getSearchDto(GetListDTO $getListDto): T5SearchDTO
    {
        $data = $getListDto->toArray();

        $data = $this->transformSearchData($data);

        return T5SearchDTO::fromArray($data);
    }

    private function getDeleteBatchDto(DeleteBatchDTO $deleteBatchDto): T5DeleteBatchDTO
    {
        $data = $deleteBatchDto->toArray();

        $data = $this->transformSearchData($data);

        return T5DeleteBatchDTO::fromArray($data);
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

    private function parseMode(?string $mode): string
    {
        return match ($mode) {
            'concordance' => 'concordance',
            'exact' => 'exact',
            default => 'contains',
        };
    }
}

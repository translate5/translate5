<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\Service;

use editor_Models_LanguageResources_LanguageResource;
use editor_Services_OpenTM2_Connector as Connector;
use JetBrains\PhpStorm\ArrayShape;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\CreateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\GetListDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\UpdateDTO;
use ZfExtended_Factory;

final class SegmentProcessor
{
    #[ArrayShape([
        'items' => 'array',
        'metaData' => 'array',
    ])]
    public function getList(GetListDTO $updateDto): array
    {
        $connector = $this->getOpenTM2Connector($updateDto->tmId);
        $totalAmount = 0;
        $limit = $updateDto->limit;
        $result = [];
        $offset = $updateDto->offset;

        while ($totalAmount < $limit) {
            $resultList = $connector->search(
                $updateDto->searchCriteria,
                $updateDto->searchField,
                $offset
            );

            $data = $resultList->getResult();
            $data = $this->reformatData($data);

            $data = array_map(static function (array $item) use ($updateDto) {
                $item['id'] = $updateDto->tmId . ':' . $item['metaData']['internalKey'];

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

    public function create(CreateDTO $createDto): void
    {
        $connector = $this->getOpenTM2Connector($createDto->tmId);
        $connector->createSegment(
            $createDto->source,
            $createDto->target,
            $createDto->documentName,
            $createDto->author,
            (new \DateTimeImmutable())->getTimestamp(),
            $createDto->context
        );
    }

    public function update(UpdateDTO $updateDto): void
    {
        [$tmId, $id, $recordKey, $targetKey] = explode(':', $updateDto->id);

        $connector = $this->getOpenTM2Connector($updateDto->tmId);
        $connector->updateSegment(
            (int) $id,
            $recordKey,
            $targetKey,
            $updateDto->source,
            $updateDto->target,
            $updateDto->documentName,
            $updateDto->author,
            (new \DateTimeImmutable($updateDto->timestamp))->getTimestamp(),
            $updateDto->context
        );
    }

    public function delete(DeleteDTO $deleteDto): void
    {
        [$tmId, $id, $recordKey, $targetKey] = explode(':', $deleteDto->id);

        $connector = $this->getOpenTM2Connector((int) $tmId);
        $connector->deleteEntry((int) $id, $recordKey, $targetKey);
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

    private function getOpenTM2Connector(int $languageResourceId): Connector
    {
        $languageResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
        $languageResource->load($languageResourceId);

        //TODO move to class
        ZfExtended_Factory::addOverwrite('editor_Services_Connector_TagHandler_Xliff', new class() extends \editor_Services_Connector_TagHandler_Xliff {
            //            public function prepareQuery(string $queryString, int $segmentId = -1): string
            //            {
            //                return $queryString;
            //            }

            public function restoreInResult(string $resultString, bool $isSource = true): ?string
            {
                $restoredResult = parent::restoreInResult($resultString);

                $pattern = '/<div class="([^"]*)\bignoreInEditor\b([^"]*)">/';
                $replacement = '<div class="$1$2">';
                // Normalize spaces in the class attribute
                $replacement = preg_replace('/\s+/', ' ', $replacement);
                // Replace ignoreInEditor class
                $updatedHtml = preg_replace($pattern, $replacement, $restoredResult);

                return preg_replace('/\s+/', ' ', $updatedHtml);
            }
        });

        $connector = new Connector();
        $connector->connectTo($languageResource, $languageResource->getSourceLang(), $languageResource->getTargetLang());

        return $connector;
    }
}

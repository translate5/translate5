<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\TMMaintenance\Service;

use editor_Models_LanguageResources_LanguageResource;
use editor_Models_Segment_Whitespace;
use editor_Services_Connector;
use editor_Services_Connector_TagHandler_Abstract;
use editor_Services_Manager;
use editor_Services_OpenTM2_HttpApi;
use JetBrains\PhpStorm\ArrayShape;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\GetListDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\UpdateDTO;
use ZfExtended_Factory;

class SegmentProcessor
{
    private editor_Models_Segment_Whitespace $whitespace;

    public function __construct()
    {
        $this->whitespace = ZfExtended_Factory::get(editor_Models_Segment_Whitespace::class);
    }

    // TODO rework this after id is implemented on t5memory side
    public function getOne(string $id): array
    {
        $idParts = explode('_', $id);
        $tmId = array_shift($idParts);
        $searchCriteria = $this->getWhitespace()->unprotectWhitespace(urldecode(array_shift($idParts)));

        $connector = $this->getOpenTM2Connector((int)$tmId);
        $resultList = $connector->search($searchCriteria);
        $data = $resultList->getResult();
        $data = $this->reformatData($data, (int)$tmId);

        return array_shift($data);
    }

    #[ArrayShape(['items' => "array", 'metaData' => "array"])]
    public function getList(GetListDTO $dto): array
    {
        $tmId = $dto->getTmId();

        $connector = $this->getOpenTM2Connector($tmId);

        $totalAmount = 0;
        $limit = $dto->getLimit();
        $result = [];
        $offset = $dto->getOffset();

        // > TODO remove once id on tm side is implemented
        $fakeIdIndex = 1;
        // < TODO remove once id on tm side is implemented

        while ($totalAmount < $limit) {
            $resultList = $connector->search(
                $dto->getSearchCriteria(),
                $dto->getSearchField(),
                $offset
            );

            $data = $resultList->getResult();
            $data = $this->reformatData($data, $tmId);
            $data = $this->replaceSymbols($data);

            // > TODO remove once id on tm side is implemented
            $data = array_map(static function (array $item) use ($tmId, &$fakeIdIndex) {
                $item['id'] = $tmId . '_' . urlencode($item['rawSource']) . '_' . ++$fakeIdIndex;

                return $item;
            }, $data);
            // < TODO remove once id on tm side is implemented

            $offset = $resultList->getNextOffset();

            $totalAmount += count($data);
            $result[] = $data;

            if (null === $offset) {
                break;
            }
        }

        return [
            'items' => array_merge(...$result),
            'metaData' => ['offset' => $offset],
        ];
    }

    public function update(UpdateDTO $dto): void
    {
        $whitespace = $this->getWhitespace();
        $api = $this->getApi($dto->getTm());
        try {
            $api->updateEntry($dto->getSource(), $whitespace->unprotectWhitespace($dto->getTarget()));
        } catch (\Exception $e) {
            // TODO error
        }
    }

    public function deleteAction(DeleteDTO $dto): void
    {
        $whitespace = $this->getWhitespace();
        $api = $this->getApi($dto->getTm());
        $api->deleteEntry($dto->getSource(), $whitespace->unprotectWhitespace($dto->getTarget()));
    }

    private function reformatData(array $data, int $tmId): array
    {
        $result = [];

        foreach ($data as $item) {
            $item = (array)$item;
            $metadata = [];

            foreach ($item['metaData'] as $metadataum) {
                $metadata[$metadataum->name] = $metadataum->value;
            }

            $item['metaData'] = $metadata;
            $item['tm'] = $tmId;

            $result[] = $item;
        }

        return $result;
    }

    private function replaceSymbols(array $data): array
    {
        $whitespace = $this->getWhitespace();
        $result = [];

        foreach ($data as $item) {
            foreach (['source', 'target', 'rawTarget'] as $field) {
                $item[$field] = $whitespace->protectWhitespace($item[$field], editor_Models_Segment_Whitespace::ENTITY_MODE_OFF);
            }

            $result[] = $item;
        }

        return $result;
    }

    private function getOpenTM2Connector(int $languageResourceId): editor_Services_Connector
    {
        /** @var editor_Models_LanguageResources_LanguageResource $languageResource */
        $languageResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
        $languageResource->load($languageResourceId);

        //TODO move to class
        ZfExtended_Factory::addOverwrite('editor_Services_Connector_TagHandler_Xliff', new class extends editor_Services_Connector_TagHandler_Abstract {
            public function prepareQuery(string $queryString, int $segmentId = -1): string
            {
                return $queryString;
            }

            public function restoreInResult(string $resultString, int $segmentId = -1): ?string
            {
                return $resultString;
            }
        });

        /** @var editor_Services_Manager $manager */
        $manager = ZfExtended_Factory::get(editor_Services_Manager::class);

        return $manager->getConnector($languageResource);
    }

    private function getApi(int $languageResourceId): editor_Services_OpenTM2_HttpApi
    {
        /** @var editor_Models_LanguageResources_LanguageResource $languageResource */
        $languageResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
        $languageResource->load($languageResourceId);

        $api = ZfExtended_Factory::get(editor_Services_OpenTM2_HttpApi::class);
        $api->setLanguageResource($languageResource);

        return $api;
    }

    private function getWhitespace(): editor_Models_Segment_Whitespace
    {
        return $this->whitespace;
    }
}

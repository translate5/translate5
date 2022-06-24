<?php

declare(strict_types=1);

class Editor_Plugins_Tmmaintenance_ApiController extends ZfExtended_RestController
{
    // TODO move somewhere
    private const JSON_DEFAULT_DEPTH = 512;

    private Zend_Session_Namespace $session;

    protected $entityClass = editor_Models_Segment::class;

    public function init()
    {
        parent::init();

        $this->session = new Zend_Session_Namespace('user');
    }

    #region Actions

    public function localesAction(): void
    {
        $data = [
            'locale' => $this->resolveLocale()
        ];

        $data['l10n'] = $this->readLocalization();

        $this->view->assign($data);
    }

    public function tmsAction(): void
    {
        /** @var editor_Models_LanguageResources_LanguageResource $model */
        $model = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');

        //get all resources for the customers of the user by language combination
        $resources = $model->loadByUserCustomerAssocs();

        $tms = array_map(
            static function (array $resource): array {
                return ['name' => $resource['name'], 'value' => $resource['id']];
            },
            $resources
        );

        $this->view->assign($tms);
    }

    public function getAction(): void
    {
        if (null !== $this->getRequest()?->getParam('segments')) {
            $this->getOne($this->getRequest()?->getParam('segments'));

            return;
        }

        $this->getList();
    }

    public function postAction(): void
    {
        $this->view->assign([]);
    }

    public function putAction(): void
    {
        $data = $this->jsonDecode($this->getRequest()?->getParam('data'));
        $api = $this->getApi((int)$data['tm']);
        try {
            $api->updateEntry($data['rawSource'], $data['rawTarget']);
        } catch (\Exception $e) {
            // TODO error
        }

        $this->view->assign([$data]);
    }

    public function deleteAction(): void
    {
        $data = $this->jsonDecode($this->getRequest()?->getParam('data'));
        $api = $this->getApi((int)$data['tm']);
        $api->deleteEntry($data['source'], $data['target']);

        $this->view->assign([]);
    }

    #endregion Actions

    private function getOne(string $id): void
    {
        $idParts = explode('_', $id);
        $tmId = array_shift($idParts);
        $searchCriteria = urldecode(substr($id, strlen($tmId) + 1));

        $connector = $this->getOpenTM2Connector((int)$tmId);
        $resultList = $connector->search($searchCriteria);
        $data = $resultList->getResult();
        $data = $this->reformatData($data, (int)$tmId);

        $this->view->assign([
            array_shift($data),
        ]);
    }

    private function getList(): void
    {
        $tmId = (int)$this->getRequest()?->getParam('tm');

        $connector = $this->getOpenTM2Connector($tmId);

        // TODO extract to somewhere
        $totalAmount = 0;
        $limit = (int)$this->getRequest()?->getParam('limit');
        $result = [];
        $offset = $this->getRequest()?->getParam('offset');

        // TODO remove once id on tm side is implemented
        $fakeIdIndex = 1;
        while ($totalAmount < $limit) {
            $resultList = $connector->search(
                $this->getRequest()?->getParam('searchCriteria'),
                $this->getRequest()?->getParam('searchField'),
                $offset
            );

            $data = $resultList->getResult();
            $data = $this->reformatData($data, $tmId);

            // TODO remove once id on tm side is implemented
            $data = array_map(function (array $item) use ($tmId, &$fakeIdIndex) {
                $item['id'] = $tmId . '_' . urlencode($item['rawSource']) . '_' . ++$fakeIdIndex;

                return $item;
            }, $data);

            $offset = $resultList->getNextOffset();

            $totalAmount += count($data);
            $result[] = $data;

            if (null === $offset) {
                break;
            }
        }

        $this->view->assign([
            'items' => array_merge(...$result),
            'metaData' => ['offset' => $offset],
        ]);
    }

    private function reformatData(array $data, int $tmId): array
    {
        $result = [];

        foreach ($data as $index => $item) {
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

    private function readLocalization(): array
    {
        $data = [];

        $fileContent = file_get_contents(__DIR__ . '/../locales/' . $this->session->data->locale . '.json');
        try {
            $data = $this->jsonDecode($fileContent);
        } catch (JsonException $exception) {
            // TODO do something
        }

        return $data;
    }

    private function resolveLocale(): string
    {
        $requestedLocale = (string)$this->getParam('locale');

        // TODO get locales from globally defined
        if ($this->getParam('locale')
            && in_array($requestedLocale, ['en', 'de'], true)
        ) {
            $this->session->data->locale = $requestedLocale;
        }

        return $this->session->data->locale;
    }

    private function getOpenTM2Connector(int $languageResourceId): editor_Services_Connector
    {
        /** @var editor_Models_LanguageResources_LanguageResource $languageResource */
        $languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
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
        $manager = ZfExtended_Factory::get('editor_Services_Manager');

        return $manager->getConnector($languageResource);
    }

    private function getApi(int $languageResourceId): editor_Services_OpenTM2_HttpApi
    {
        /** @var editor_Models_LanguageResources_LanguageResource $languageResource */
        $languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        $languageResource->load($languageResourceId);

        $api = ZfExtended_Factory::get('editor_Services_OpenTM2_HttpApi');
        $api->setLanguageResource($languageResource);

        return $api;
    }

    /**
     * @param string $data
     *
     * @return array
     *
     * @throws JsonException
     */
    private function jsonDecode(string $data): array
    {
        return json_decode($data, true, self::JSON_DEFAULT_DEPTH, JSON_THROW_ON_ERROR);
    }
}

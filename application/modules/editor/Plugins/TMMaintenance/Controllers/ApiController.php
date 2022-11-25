<?php

declare(strict_types=1);

use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\GetListDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\UpdateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\Helper\Json;
use MittagQI\Translate5\Plugins\TMMaintenance\Service\SegmentProcessor;

class Editor_Plugins_Tmmaintenance_ApiController extends ZfExtended_RestController
{
    private Zend_Session_Namespace $session;

    protected $entityClass = editor_Models_Segment::class;

    public function init(): void
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

        $this->assignView($data);
    }

    public function tmsAction(): void
    {
        /** @var editor_Models_LanguageResources_LanguageResource $model */
        $model = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);

        //get all resources for the customers of the user by language combination
        $resources = $model->loadByUserCustomerAssocs();

        $tms = array_map(
            static function (array $resource): array {
                return ['name' => $resource['name'], 'value' => $resource['id']];
            },
            $resources
        );

        $this->assignView($tms);
    }

    public function getAction(): void
    {
        // TODO seems to be not needed
//        $segmentId = $this->getRequest()?->getParam('segments');
//
//        if (null !== $segmentId) {
//            $this->assignView([
//                $this->getSegmentsProcessor()->getOne($segmentId),
//            ]);
//
//            return;
//        }

        $this->assignView(
            $this->getSegmentsProcessor()->getList(GetListDTO::fromRequest($this->getRequest()))
        );
    }

    // TODO this is never called
    public function postAction(): void
    {
        $this->assignView([]);
    }

    public function putAction(): void
    {
        $dto = UpdateDTO::fromRequest($this->getRequest());
        $this->getSegmentsProcessor()->update($dto);
//        $data = $this->getSegmentsProcessor()->getOne($dto->getId());
        $this->assignView([Json::decode($this->getRequest()?->getParam('data'))]);
    }

    public function deleteAction(): void
    {
        $dto = DeleteDTO::fromRequest($this->getRequest());
        $this->getSegmentsProcessor()->deleteAction($dto);

        $this->assignView([]);
    }

    #endregion Actions

    private function getSegmentsProcessor(): SegmentProcessor
    {
        return ZfExtended_Factory::get(SegmentProcessor::class);
    }

    private function readLocalization(): array
    {
        $data = [];

        $fileContent = file_get_contents(__DIR__ . '/../locales/' . $this->session->data->locale . '.json');
        try {
            $data = Json::decode($fileContent);
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

    private function assignView(array $data): void
    {
        $this->view->assign($data);
    }
}

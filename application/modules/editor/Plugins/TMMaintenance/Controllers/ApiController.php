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

use MittagQI\Translate5\Plugins\TMMaintenance\DTO\CreateDTO;
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
            'locale' => $this->resolveLocale(),
        ];

        $data['l10n'] = $this->readLocalization();

        $this->assignView($data);
    }

    public function tmsAction(): void
    {
        $model = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);

        //get all resources for the customers of the user by language combination
        $resources = $model->loadByUserCustomerAssocs([], [], [], [editor_Services_Manager::SERVICE_OPENTM2]);

        $tms = array_map(
            static function (array $resource): array {
                return [
                    'id' => $resource['id'],
                    'name' => $resource['name'],
                    'sourceLanguage' => $resource['sourceLangCode'],
                    'targetLanguage' => $resource['targetLangCode'],
                ];
            },
            $resources
        );

        $this->assignView(['items' => $tms]);
    }

    public function indexAction(): void
    {
        $this->assignView(
            $this->getSegmentsProcessor()->getList(GetListDTO::fromRequest($this->getRequest()))
        );
    }

    public function postAction(): void
    {
        $this->getSegmentsProcessor()->create(CreateDTO::fromRequest($this->getRequest()));
        $this->assignView([]);
    }

    public function putAction(): void
    {
        $this->getSegmentsProcessor()->update(UpdateDTO::fromRequest($this->getRequest()));
        $this->assignView([Json::decode($this->getRequest()->getParam('data'))]);
    }

    public function deleteAction(): void
    {
        $dto = DeleteDTO::fromRequest($this->getRequest());
        $this->getSegmentsProcessor()->delete($dto);

        $this->assignView([]);
    }

    #endregion Actions

    private function getSegmentsProcessor(): SegmentProcessor
    {
        return new SegmentProcessor();
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
        $requestedLocale = (string) $this->getParam('locale');

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

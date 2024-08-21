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

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\CreateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteBatchDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\GetListDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\UpdateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\Repository\LanguageResourceRepository;
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

    public function dataAction(): void
    {
        $data = [
            'locale' => $this->resolveLocale(),
            'l10n' => $this->readLocalization(),
        ];

        $user = ZfExtended_Authentication::getInstance()->getUser();
        if (! $user) {
            return;
        }

        $repository = new LanguageResourceRepository();

        if (in_array(Roles::TM_MAINTENANCE_ALL_CLIENTS, $user->getRoles(), true)) {
            $languageResources = $repository->getT5MemoryType();
        } else {
            $customers = $user->getCustomersArray();
            $languageResources = $repository->getT5MemoryTypeFilteredByCustomers(...$customers);
        }

        $data['tms'] = array_map(
            static function (array $resource): array {
                return [
                    'id' => $resource['id'],
                    'name' => $resource['name'],
                    'sourceLanguage' => $resource['sourceLangCode'],
                    'targetLanguage' => $resource['targetLangCode'],
                    'clients' => $resource['customers'],
                ];
            },
            $languageResources
        );

        $model = ZfExtended_Factory::get(editor_Models_Languages::class);
        $languages = $model->loadAllKeyValueCustom('id', 'rfc5646');
        $mapper = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguagesMapper::class);
        $data['languages'] = $mapper->map($languages);

        $this->assignView($data);
    }

    public function indexAction(): void
    {
        $this->assignView($this->getSegmentsProcessor()->getList(GetListDTO::fromRequest($this->getRequest())));
    }

    public function postAction(): void
    {
        $this->getSegmentsProcessor()->create(CreateDTO::fromRequest($this->getRequest()));
    }

    public function putAction(): void
    {
        $this->getSegmentsProcessor()->update(UpdateDTO::fromRequest($this->getRequest()));
        $this->assignView([
            json_decode($this->getRequest()->getParam('data'), true, flags: JSON_THROW_ON_ERROR),
        ]);
    }

    public function deleteAction(): void
    {
        $dto = DeleteDTO::fromRequest($this->getRequest());
        $this->getSegmentsProcessor()->delete($dto);
    }

    public function deletebatchAction(): void
    {
        $dto = DeleteBatchDTO::fromRequest($this->getRequest());
        $this->getSegmentsProcessor()->deleteBatch($dto);
    }

    public function readamountAction()
    {
        $dto = GetListDTO::fromRequest($this->getRequest());
        echo json_encode([
            'totalAmount' => $this->getSegmentsProcessor()->countResults($dto),
        ], JSON_THROW_ON_ERROR);
    }

    #endregion Actions

    private function getSegmentsProcessor(): SegmentProcessor
    {
        return new SegmentProcessor();
    }

    private function readLocalization(): array
    {
        $data = [];

        $fileContent = file_get_contents(
            sprintf(
                '%s/../locales/%s.json',
                __DIR__,
                isset($this->session->data) ? $this->session->data->locale : 'en'
            )
        );

        try {
            $data = json_decode($fileContent, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            trigger_error('Error decoding JSON file: ' . $exception->getMessage(), E_USER_WARNING);
        }

        return $data;
    }

    private function resolveLocale(): string
    {
        $requestedLocale = (string) $this->getParam('locale');

        if ($this->getParam('locale')
            && in_array($requestedLocale, ['en', 'de'], true)
            && isset($this->session->data)
        ) {
            $this->session->data->locale = $requestedLocale;
        }

        return $this->session->data->locale ?? $requestedLocale;
    }

    private function assignView(array $data): void
    {
        $this->view->assign($data);
    }
}

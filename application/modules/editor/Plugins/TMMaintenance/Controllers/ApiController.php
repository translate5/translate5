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
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\DeleteSimilarDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\GetListDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\DTO\UpdateDTO;
use MittagQI\Translate5\Plugins\TMMaintenance\Enum\BatchMode;
use MittagQI\Translate5\Plugins\TMMaintenance\Exception\ErrorException;
use MittagQI\Translate5\Plugins\TMMaintenance\Exception\InexistentSimilarDeleteTypeException;
use MittagQI\Translate5\Plugins\TMMaintenance\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Plugins\TMMaintenance\Service\SegmentProcessor;
use MittagQI\Translate5\T5Memory\Factory\DeleteSegmentDtoFactory;
use MittagQI\ZfExtended\Localization;

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
                    'isTaskTm' => (bool) $resource['isTaskTm'],
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
        try {
            $this->assignView(
                $this->getSegmentsProcessor()->getList(GetListDTO::fromRequest($this->getRequest()))
            );
        } catch (editor_Services_Connector_Exception $exception) {
            $this->transformException($exception);
        }
    }

    public function postAction(): void
    {
        try {
            $this->getSegmentsProcessor()->createSegment(CreateDTO::fromRequest($this->getRequest()));
        } catch (editor_Services_Connector_Exception $exception) {
            $this->transformException($exception);
        }
    }

    public function putAction(): void
    {
        $languageResourceRepository = \MittagQI\Translate5\Repository\LanguageResourceRepository::create();
        $segment = [];

        try {
            $data = $deleteData = json_decode($this->getRequest()->getParam('data'), true, flags: JSON_THROW_ON_ERROR);

            $deleteData['source'] = $data['originalState']['source'] ?? $data['source'];
            $deleteData['target'] = $data['originalState']['target'] ?? $data['target'];

            $languageResource = $languageResourceRepository->get((int) $data['languageResourceid']);

            $deleteDtoFactory = DeleteSegmentDtoFactory::create();
            $deleteDto = $deleteDtoFactory->getDeleteDTO($languageResource, $deleteData, \Zend_Registry::get('config'));

            $segment = $this->getSegmentsProcessor()->update(
                $deleteDto,
                UpdateDTO::fromRequest($this->getRequest()),
            );
        } catch (editor_Services_Connector_Exception $exception) {
            $this->transformException($exception);
        }

        $this->assignView([
            array_merge($data, $segment),
        ]);
    }

    public function deleteAction(): void
    {
        $data = json_decode($this->getRequest()->getParam('data'), true, flags: JSON_THROW_ON_ERROR);

        $languageResourceRepository = \MittagQI\Translate5\Repository\LanguageResourceRepository::create();

        $languageResource = $languageResourceRepository->get((int) $data['tm']);

        $deleteDtoFactory = DeleteSegmentDtoFactory::create();
        $deleteDto = $deleteDtoFactory->getDeleteDTO($languageResource, $data, \Zend_Registry::get('config'));

        try {
            $this->getSegmentsProcessor()->deleteSegment($languageResource, $deleteDto);
        } catch (editor_Services_Connector_Exception $exception) {
            $this->transformException($exception);
        }
    }

    public function deletesimilarAction(): void
    {
        $data = json_decode($this->getRequest()->getParam('data'), true, flags: JSON_THROW_ON_ERROR);

        $languageResourceRepository = \MittagQI\Translate5\Repository\LanguageResourceRepository::create();

        $languageResource = $languageResourceRepository->get((int) $data['tm']);

        try {
            $this->getSegmentsProcessor()->deleteSimilarSegments(
                $languageResource,
                DeleteSimilarDTO::fromRequest($this->getRequest()),
                \Zend_Registry::get('config'),
            );
        } catch (InexistentSimilarDeleteTypeException) {
            throw ErrorException::createResponse(
                'E1688',
                [
                    Localization::trans('Invalid deletion type specified.'),
                ],
            );
        } catch (editor_Services_Connector_Exception $exception) {
            $this->transformException($exception);
        }
    }

    public function deletebatchAction(): void
    {
        $dto = GetListDTO::fromRequest($this->getRequest());
        $mode = BatchMode::from($this->getRequest()->get('batchMode'));

        try {
            $this->getSegmentsProcessor()->deleteBatch($dto, $mode);
        } catch (editor_Services_Connector_Exception $exception) {
            $this->transformException($exception);
        }
    }

    public function readamountAction()
    {
        $dto = GetListDTO::fromRequest($this->getRequest());

        try {
            echo json_encode([
                'totalAmount' => $this->getSegmentsProcessor()->countResults($dto),
            ], JSON_THROW_ON_ERROR);
        } catch (editor_Services_Connector_Exception $exception) {
            $this->transformException($exception);
        }
    }

    #endregion Actions

    private function getSegmentsProcessor(): SegmentProcessor
    {
        return SegmentProcessor::create();
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

    /**
     * @throws editor_Services_Connector_Exception
     */
    private function transformException(editor_Services_Connector_Exception $exception): void
    {
        $errorCode = $exception->getErrorCode();

        throw match ($errorCode) {
            'E1377' => ErrorException::createResponse(
                $errorCode,
                [
                    Localization::trans('Memory status: {status}. Please try again in a while.'),
                ],
                [
                    'status' => $exception->getExtra('status'),
                ]
            ),
            'E1611' => ErrorException::createResponse(
                $errorCode,
                [
                    Localization::trans('The segment you are trying to save has already been deleted by another process.'),
                ],
            ),
            'E1612' => ErrorException::createResponse(
                $errorCode,
                [
                    Localization::trans(
                        'The segment could not be saved. <br />Please try to refresh your search. ' .
                        '<br />The found segment-id on the server differs from the requested one. <br />' .
                        'Probably the segment index changed on the server due to other operations.'
                    ),
                ],
            ),
            'E1688' => ErrorException::createResponse(
                $errorCode,
                [
                    Localization::trans(
                        'Could not delete segment. <br />Please refresh your search and try again. <br />' .
                        'Found segment id differs from the requested one. <br />' .
                        'Probably the segment index changed on the server due to other operations.'
                    ),
                ],
            ),
            'E1306' => ErrorException::createResponse(
                $errorCode,
                [
                    Localization::trans(
                        'The segment could not be saved to the TM. <br />Please try again later.<br />' .
                        'If this message persists, please try to refresh your search. <br />' .
                        'If the message still persists, please notify the system support.'
                    ),
                ],
            ),
            'E1742' => ErrorException::createResponse(
                $errorCode,
                [
                    'Segment zu lang für Abfragen auf t5memory',
                ],
            ),
            default => $exception,
        };
    }
}

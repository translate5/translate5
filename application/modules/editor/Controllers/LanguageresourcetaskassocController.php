<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\Event\LanguageResourceTaskAssociationChangeEvent;
use MittagQI\Translate5\LanguageResource\Event\LanguageResourceTaskAssociationChangeType;
use MittagQI\Translate5\LanguageResource\Provider\LanguageResourceProvider;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Repository\LanguageResourceRepository;

/**
 * Controller for the LanguageResources Associations
 */
class editor_LanguageresourcetaskassocController extends ZfExtended_RestController
{
    protected $entityClass = TaskAssociation::class; //→ _Taskassoc

    /**
     * @var TaskAssociation;
     */
    protected $entity;

    private LanguageResourceRepository $languageResourceRepository;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    public function init(): void
    {
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1050' => 'Referenced language resource not found.',
            'E1051' => 'Cannot remove language resource from task since task is used at the moment.',
        ], 'editor.languageresource.taskassoc');
        parent::init();

        $this->languageResourceRepository = new LanguageResourceRepository();
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function indexAction(): void
    {
        $filter = $this->entity->getFilter();
        if (! $filter->hasFilter('taskGuid', $taskGuid)) { //handle the rest default case
            $this->view->rows = $this->entity->loadAll();
            $this->view->total = $this->entity->getTotalCount();

            return;
        }

        $result = LanguageResourceProvider::create()->getAssocTasksWithResources($taskGuid->value, $filter);

        $this->view->rows = $result;
        $this->view->total = count($result);
    }

    /**
     * @throws ZfExtended_ErrorCodeException
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    public function postAction(): void
    {
        try {
            parent::postAction();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
            //duplicate entries are OK, since the user tried to create it,
            //but we have to load and return the already existing duplicate
            $this->entity->loadByTaskGuidAndTm($this->data->taskGuid, $this->data->languageResourceId);
            $this->view->rows = $this->entity->getDataObject();

            return;
        }

        EventDispatcher::create()->dispatch(
            new LanguageResourceTaskAssociationChangeEvent(
                $this->getLanguageResource((int) $this->entity->getLanguageResourceId()),
                $this->entity->getTaskGuid(),
                LanguageResourceTaskAssociationChangeType::Add,
            )
        );
    }

    public function putAction(): void
    {
        parent::putAction();

        EventDispatcher::create()->dispatch(
            new LanguageResourceTaskAssociationChangeEvent(
                $this->getLanguageResource((int) $this->entity->getLanguageResourceId()),
                $this->entity->getTaskGuid(),
                LanguageResourceTaskAssociationChangeType::Update,
            )
        );
    }

    /**
     * @throws ZfExtended_ErrorCodeException
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    public function deleteAction(): void
    {
        try {
            $this->entityLoad();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing since it was already deleted, and thats ok since user tried to delete it
            return;
        }

        $task = ZfExtended_Factory::get(editor_Models_Task::class);

        if ($task->isUsed($this->entity->getTaskGuid())) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1050', [
                'Die Aufgabe wird bearbeitet, die Sprachressource kann daher im Moment ' .
                'nicht von der Aufgabe entfernt werden!',
            ]);
        }

        $clone = clone $this->entity;
        $this->entity->delete();

        EventDispatcher::create()->dispatch(
            new LanguageResourceTaskAssociationChangeEvent(
                $this->getLanguageResource((int) $clone->getLanguageResourceId()),
                $clone->getTaskGuid(),
                LanguageResourceTaskAssociationChangeType::Remove,
            )
        );
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Services_Exceptions_NoService
     */
    protected function decodePutData(): void
    {
        parent::decodePutData();

        //this flag may not be set via API
        unset($this->data->autoCreatedOnImport);

        $languageResource = $this->getLanguageResource($this->data->languageResourceId);
        $resource = $languageResource->getResource();
        //segments can only be updated when resource is writable:
        $this->data->segmentsUpdateable = $resource->getWritable() && $this->data->segmentsUpdateable;
    }

    /**
     * @throws ZfExtended_ErrorCodeException
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    protected function getLanguageResource(int $languageResourceId): LanguageResource
    {
        try {
            return $this->languageResourceRepository->get($languageResourceId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw ZfExtended_Models_Entity_Conflict::createResponse(
                'E1050',
                [
                    'languageResourceId' => 'Die gewünschte Sprachressource gibt es nicht!',
                ],
                [
                    'languageresourceId' => $this->data->languageResourceId,
                ]
            );
        }
    }
}

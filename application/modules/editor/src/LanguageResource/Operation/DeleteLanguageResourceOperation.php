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

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\Operation;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_TermCollection_TermCollection;
use editor_Services_Manager;
use Exception;
use MittagQI\Translate5\CrossSynchronization\CrossLanguageResourceSynchronizationService;
use MittagQI\Translate5\LanguageResource\CleanupAssociation\Customer as CustomerAssocCleanup;
use MittagQI\Translate5\LanguageResource\CleanupAssociation\Task as TaskAssocCleanup;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use Zend_Db_Table_Exception;
use ZfExtended_ErrorCodeException;
use ZfExtended_EventManager;
use ZfExtended_Exception;
use ZfExtended_Factory;

/**
 * LanguageResource Remover - to separate delete-logic from LanguageResourceController,
 * this is all encapsulated in this class.
 * So the function can be used from all places inside the application.
 */
class DeleteLanguageResourceOperation
{
    public function __construct(
        private readonly CrossLanguageResourceSynchronizationService $crossLanguageResourceSynchronizationService,
        private readonly CustomerAssocService $customerAssocService,
        private readonly editor_Services_Manager $serviceManager,
        private readonly ZfExtended_EventManager $eventManager,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CrossLanguageResourceSynchronizationService::create(),
            CustomerAssocService::create(),
            ZfExtended_Factory::get(editor_Services_Manager::class),
            ZfExtended_Factory::get(ZfExtended_EventManager::class, [__CLASS__]),
        );
    }

    /**
     * Removes a languageResource completely
     *
     * @throws Zend_Db_Table_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Exception
     */
    public function delete(
        LanguageResource $languageResource,
        bool $forced = false,
        bool $deleteInResource = false
    ): void {
        // if the current entity is term collection, init the entity as term collection
        if ($languageResource->isTc()) {
            $collection = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);
            $collection->init($languageResource->toArray());
            $languageResource = $collection;
        }

        // wrap the deletion in a transaction to rollback if for
        // example the real file based resource can not be deleted
        $languageResource->db->getAdapter()->beginTransaction();

        try {
            $entity = clone $languageResource;

            $customersLeft = ($forced) ? [] : $languageResource->getCustomers();
            $this->checkOrCleanAssociations($languageResource, $forced, $customersLeft);

            $this->crossLanguageResourceSynchronizationService->deleteRelatedConnections(
                (int) $languageResource->getId()
            );
            $this->customerAssocService->separateByLanguageResource((int) $languageResource->getId());

            // delete the entity in the DB
            $languageResource->delete();
            // prevent nonsens
            $entity->lockRow();
            // if there are any services connected to this language-resource, they also must be deleted.
            $connector = $this->serviceManager->getConnector($entity);

            // try to delete the resource via the connector
            $deleteInResource && $connector->delete();
            // if this is successful we commit the DB delete
            $languageResource->db->getAdapter()->commit();
        } catch (Exception $e) {
            // if not we rollback and throw the original exception
            $languageResource->db->getAdapter()->rollBack();

            throw $e;
        }

        $this->eventManager->trigger('afterRemove', $this, [
            'languageResource' => $entity,
        ]);
    }

    /**
     * @throws Zend_Db_Table_Exception
     * @throws ZfExtended_ErrorCodeException
     */
    private function checkOrCleanAssociations(
        LanguageResource $languageResource,
        bool $doClean,
        array $customerIdsLeft
    ): void {
        // Moving the TaskAssocCleanup and CustomerAssocCleanup to its class dependencies will require
        // a lot of refactoring that is out of the scope of my current issue. Will be done later.
        // TODO Move to class dependencies
        $taskAssocCleanup = ZfExtended_Factory::get(TaskAssocCleanup::class, [$languageResource->getId()]);
        $customerAssocCleanup = ZfExtended_Factory::get(
            CustomerAssocCleanup::class,
            [$languageResource->getId(), $customerIdsLeft]
        );

        if ($doClean) {
            $taskAssocCleanup->cleanAssociation();
            $customerAssocCleanup->cleanAssociation();
        } else {
            $taskAssocCleanup->check();
            $customerAssocCleanup->check();
        }
    }
}

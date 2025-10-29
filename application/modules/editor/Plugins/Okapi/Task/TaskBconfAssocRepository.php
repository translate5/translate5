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

namespace MittagQI\Translate5\Plugins\Okapi\Task;

/**
 * Repository to manage task-bconf associations
 * A task either has a related bconfId OR a bconfInZip (bconf in task-data dir))
 */
final class TaskBconfAssocRepository
{
    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Will retrieve the assoc bound to the given task
     * Be aware for cases, where no assoc was found, an empty readonly-assoc is returned (all props null but taskGuid)
     * This is only to simplify the coding but means, this api will not tell, if an assoc exists
     */
    public function findForTask(string $taskGuid): TaskBconfAssocEntity
    {
        $entity = $this->findByTaskGuid($taskGuid);
        if (empty($entity->getId())) {
            $entity->lockRow();
        }

        return $entity;
    }

    /**
     * Creates a task-bconf association. If there already is one, is will be taken/overwritten
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function createForTask(string $taskGuid, int $bconfId = null, string $bconfInZip = null): TaskBconfAssocEntity
    {
        if (($bconfId === null && $bconfInZip === null) || ($bconfId !== null && $bconfInZip !== null)) {
            throw new \ZfExtended_Exception('A task-bconf-association must either have a bconfId or a bconfInZip');
        }
        $entity = $this->findByTaskGuid($taskGuid);
        if ($entity->getBconfId() !== $bconfId || $entity->getBconfInZip() !== $bconfInZip) {
            $entity->setBconfId($bconfId);
            $entity->setBconfInZip($bconfInZip);
            $entity->save();
        }

        return $entity;
    }

    private function findByTaskGuid(string $taskGuid): TaskBconfAssocEntity
    {
        $entity = new TaskBconfAssocEntity();

        try {
            $entity->loadRow('taskGuid = ?', $taskGuid);
        } catch (\ZfExtended_Models_Entity_NotFoundException) {
            $entity->init([
                'taskGuid' => $taskGuid,
            ]);
        }

        return $entity;
    }
}

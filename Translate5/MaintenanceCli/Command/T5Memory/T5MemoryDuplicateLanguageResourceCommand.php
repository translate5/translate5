<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Command\T5Memory;

use editor_Models_Task as Task;
use MittagQI\Translate5\LanguageResource\Operation\AssociateTaskOperation;
use MittagQI\Translate5\LanguageResource\Operation\CloneLanguageResourceOperation;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\LanguageResourceTaskAssocRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;

class T5MemoryDuplicateLanguageResourceCommand extends Translate5AbstractCommand
{
    private const NAME_SUFFIX = '_duplicate';

    private const ARGUMENT_TASK_ID = 'task-id';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('t5memory:create-duplicate-lr')
            ->setDescription(
                'For every TM, that is assigned as writeable to a task, and that is not a task-TM'
                . ' create an empty duplicate with the same name and suffix _duplicate'
            )
            ->addArgument(
                self::ARGUMENT_TASK_ID,
                InputArgument::OPTIONAL,
                'The task id to process'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $associateTaskOperation = AssociateTaskOperation::create();
        $cloneLanguageResourceOperation = CloneLanguageResourceOperation::create();

        $tasks = $this->getTasks();

        foreach ($tasks as $task) {
            $this->io->info(sprintf(
                'Processing task "%s [ID %d]"',
                $task->getTaskName(),
                (int) $task->getId()
            ));

            foreach ($this->getLanguageResources($task) as $languageResource) {
                if ($this->isAlreadyDuplicate($languageResource->getName())) {
                    continue;
                }

                $duplicateName = $this->generateDuplicateName(
                    (int) $languageResource->getId(),
                    $languageResource->getName()
                );

                if ($this->hasDuplicate($duplicateName)) {
                    $this->associateDuplicate($duplicateName, $task->getTaskGuid(), $associateTaskOperation);

                    continue;
                }

                $this->io->info(sprintf(
                    'Creating duplicate for language resource "%s [ID %d]"',
                    $languageResource->getName(),
                    (int) $languageResource->getId()
                ));

                $cloneLanguageResourceOperation->clone($languageResource, $duplicateName);
                $this->associateDuplicate($duplicateName, $task->getTaskGuid(), $associateTaskOperation);
            }
        }

        return self::SUCCESS;
    }

    private function associateDuplicate(
        string $duplicateName,
        string $taskGuid,
        AssociateTaskOperation $associateTaskOperation
    ): void {
        $this->io->info(sprintf(
            'Associating task with duplicate language resource "%s"',
            $duplicateName
        ));

        $duplicateId = $this->getDuplicateIdByName($duplicateName);

        try {
            $associateTaskOperation->associate($duplicateId, $taskGuid, true);
        } catch (\ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
            $this->io->info('Task already associated with this duplicate');
        }
    }

    /**
     * @return iterable<Task>
     */
    private function getTasks(): iterable
    {
        if ($this->input->getArgument(self::ARGUMENT_TASK_ID)) {
            return yield $this->getTask();
        }

        return yield from $this->getAllTasks();
    }

    /**
     * @return iterable<Task>
     */
    private function getAllTasks(): iterable
    {
        /** @var \Zend_Db_Table $db */
        $db = \Zend_Registry::get('db');
        $query = $db->select()
            ->from([
                'task' => 'LEK_task',
            ], 'task.id')
        ;

        $result = $db->fetchAll($query);

        $taskRepository = new TaskRepository();

        foreach ($result as $data) {
            $task = $taskRepository->get((int) $data['id']);

            if ($task->isProject()) {
                continue;
            }

            yield $task;
        }
    }

    private function getTask(): ?Task
    {
        $taskId = (int) $this->input->getArgument(self::ARGUMENT_TASK_ID);
        $taskRepository = new TaskRepository();

        try {
            $task = $taskRepository->get($taskId);
        } catch (\ZfExtended_Models_Entity_NotFoundException $e) {
            $this->io->error('Task not found');

            return null;
        }

        if ($task->isProject()) {
            $this->io->error('Task is a project');

            return null;
        }

        return $task;
    }

    private function getLanguageResources(Task $task): iterable
    {
        $languageResourceRepository = new LanguageResourceRepository();
        $languageResourceTaskAssocRepository = LanguageResourceTaskAssocRepository::create();
        $taskAssociations = $languageResourceTaskAssocRepository->getAllByTaskGuid($task->getTaskGuid());

        foreach ($taskAssociations as $taskAssociation) {
            if (! $taskAssociation['segmentsUpdateable']) {
                continue;
            }

            if ($taskAssociation['isTaskTm'] || $taskAssociation['isOriginalTaskTm']) {
                continue;
            }

            yield $languageResourceRepository->get((int) $taskAssociation['languageResourceId']);
        }
    }

    private function generateDuplicateName(int $id, string $name): string
    {
        return $name . '_' . $id . self::NAME_SUFFIX;
    }

    private function isAlreadyDuplicate(string $name): bool
    {
        return str_ends_with($name, self::NAME_SUFFIX);
    }

    private function hasDuplicate(string $name): bool
    {
        return null !== $this->getDuplicateIdByName($name);
    }

    private function getDuplicateIdByName(string $name): ?int
    {
        /** @var \Zend_Db_Table $db */
        $db = \Zend_Registry::get('db');
        $query = $db->select()
            ->from([
                'lr' => 'LEK_languageresources',
            ], 'lr.id')
            ->where('lr.name = ?', $name)
        ;

        $result = $db->fetchRow($query);

        return isset($result['id']) ? (int) $result['id'] : null;
    }
}

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
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\ReimportSegments;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\FilteringByNameTrait;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\T5MemoryLocalTmsTrait;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;
use ZfExtended_Factory as Factory;

class T5MemoryReimportTaskCommand extends Translate5AbstractCommand
{
    use FilteringByNameTrait;
    use T5MemoryLocalTmsTrait;

    private const ARGUMENT_UUID = 'uuid';
    private const OPTION_TM_NAME = 'tm-name';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('t5memory:reimport-task')
            ->setDescription('Reimport task segments into the t5memory')
            ->addArgument(
                self::ARGUMENT_UUID,
                InputArgument::OPTIONAL,
                'UUID of the memory reimport, if not given, you can select from a list'
            )
            ->addOption(
                self::OPTION_TM_NAME,
                null,
                InputArgument::OPTIONAL,
                'Language resource'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $tmUuid = $this->getTmUuid($input);

        if (null === $tmUuid) {
            return self::FAILURE;
        }

        $languageResource = Factory::get(LanguageResource::class);
        $languageResource->loadByUuid($tmUuid);

        $taskIds = $this->getTaskIdsForReimport($languageResource);

        foreach ($taskIds as $taskId) {
            $task = Factory::get(Task::class);
            $task->load($taskId);

            $reimport = new ReimportSegments($languageResource, $task);
            $reimport->reimport([ReimportSegments::FILTER_ONLY_EDITED => true]);
        }

        return self::SUCCESS;
    }

    private function getTaskIdsForReimport(LanguageResource $languageResource): array
    {
        $data = Factory::get(TaskAssociation::class)
            ->getTaskInfoForLanguageResources([$languageResource->getId()]);

        if (count($data) === 0) {
            $this->io->info('No tasks found for the given language resource');

            return [];
        }

        $list = ['all' => 'All tasks'];

        foreach ($data as $taskData) {
            $list[$taskData['taskId']] = $taskData['taskName'];
        }

        $question = new ChoiceQuestion('Please choose a Memory:', $list, 'all');
        $chosen = $this->io->askQuestion($question);

        return $chosen === 'all' ? array_column($data, 'taskId') : [$chosen];
    }

    protected function getInput(): InputInterface
    {
        return $this->input;
    }
}

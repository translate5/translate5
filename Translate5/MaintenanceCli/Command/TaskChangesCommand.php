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

namespace Translate5\MaintenanceCli\Command;

use editor_Models_Converter_SegmentsToXliffFactory as SegmentsToXliffFactoryAlias;
use editor_Models_Segment as Segment;
use editor_Models_TaskUserAssoc as TaskUserAssoc;
use editor_Models_Workflow as Workflow;
use editor_Models_Workflow_Step as WorkflowStep;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Factory as Factory;

/**
 * print the changes.xlf for a specific step of a task, if no step given
 */
class TaskChangesCommand extends TaskCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:changesxlf';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Print / save the changes.xlf to a separate file')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('A task-identifier must be given, '
                . 'if no workflow step is given as second argument a dialog asks for one.');

        $this->addArgument(
            'taskIdentifier',
            InputArgument::REQUIRED,
            TaskCommand::IDENTIFIER_DESCRIPTION
        );

        $this->addArgument(
            'workflowStep',
            InputArgument::OPTIONAL,
            'The workflow step for which the changes.xliff should be produced. '
            . 'If omitted a list to chose from is shown',
        );
    }

    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     * @throws \ReflectionException
     * @throws \Zend_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $task = static::findTaskFromArgument(
            $this->io,
            $input->getArgument('taskIdentifier'),
        );

        if ($task === null) {
            return self::FAILURE;
        }

        $workflowStep = $input->getArgument('workflowStep');

        if ($workflowStep === null) {
            $workflowStep = $this->askForWorkflowStep($task);
        }

        $stepNr = (int) $task->getWorkflowStep();
        $segment = Factory::get(Segment::class);

        $segments = $segment->getWorkflowStepSegments($task, $workflowStep, $stepNr);
        $config = $task->getConfig();

        $converter = SegmentsToXliffFactoryAlias::create($workflowStep, $config);

        define('ZFEXTENDED_IS_WORKER_THREAD', true); //unfortunately needed somewhere deep in generation...
        $this->io->writeln($converter->convert($task, $segments));

        return static::SUCCESS;
    }

    /**
     * @return array|mixed
     * @throws \ReflectionException
     */
    private function askForWorkflowStep(\editor_Models_Task $task): mixed
    {
        $workflow = Factory::get(Workflow::class);
        $workflow->loadByName($task->getWorkflow());

        $stepModel = Factory::get(WorkflowStep::class);
        $allSteps = $stepModel->loadByWorkflow($workflow);
        $allSteps = array_combine(array_column($allSteps, 'name'), $allSteps);

        foreach ($allSteps as &$step) {
            $step['jobCount'] = 0;
        }

        $jobLoader = Factory::get(TaskUserAssoc::class);
        $jobs = $jobLoader->loadAllOfATask($task->getTaskGuid());
        foreach ($jobs as $job) {
            $allSteps[$job['workflowStepName']]['jobCount']++;
        }

        return $this->io->choice(
            'For which step do you want to get the changes.xlf?',
            array_map(function ($step) {
                return $step['label'] . ' Jobs: (' . $step['jobCount'] . ')';
            }, $allSteps)
        );
    }
}

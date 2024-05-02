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

use editor_Models_Workflow;
use editor_Models_Workflow_Step;
use editor_Workflow_Exception;
use editor_Workflow_Manager;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class WorkflowStepCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'workflow:step';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Manipulates and lists workflow steps.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Manipulates and lists workflow steps.');

        $this->addArgument(
            'workflow',
            InputArgument::REQUIRED,
            'Workflow where to list / add / change workflow steps'
        );

        $this->addArgument(
            'step',
            InputArgument::OPTIONAL,
            'Workflow step name (NOT label) to show and add / update if any of the options are given'
        );

        $this->addOption(
            'label',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Set the label'
        );

        $this->addOption(
            'role',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Set the role'
        );

        $this->addOption(
            'position',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Set the position'
        );

        $this->addOption(
            'filtered',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Set the initially filtered flag'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @return int
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $this->writeTitle('Modify a workflow step');

        $workflow = ZfExtended_Factory::get(editor_Models_Workflow::class);
        $workflow->loadByName($input->getArgument('workflow'));

        $step = $input->getArgument('step');
        $stepModel = ZfExtended_Factory::get(editor_Models_Workflow_Step::class);
        $allSteps = $stepModel->loadByWorkflow($workflow);

        if (empty($step)) {
            $this->printStepList($allSteps);

            return self::SUCCESS;
        }

        $edit = false;
        foreach ($allSteps as $oneStep) {
            if ($oneStep['name'] == $step) {
                $stepModel->load($oneStep['id']);
                $edit = true;

                break;
            }
        }

        if ($edit) {
            $this->io->section('Update workflow step ' . $stepModel->getLabel() . ' (' . $stepModel->getName() . ')');
        } else {
            $this->io->section('Create new workflow step with name: ' . $step);
            $stepModel->setName($step);
            $stepModel->setWorkflowName($workflow->getName());
        }

        $stepModel->setLabel($this->getOrAskLabel($stepModel, $edit));
        $stepModel->setRole($this->getOrAskRole($workflow, $stepModel, $edit));
        $stepModel->setPosition($this->getOrAskPosition($stepModel, $edit));
        $stepModel->setFlagInitiallyFiltered($this->getOrAskFilter($stepModel, $edit));

        $stepModel->save();

        $this->io->success('Workflow step successfully saved');
        $this->printStepList($stepModel->loadByWorkflow($workflow));

        return self::SUCCESS;
    }

    /**
     * @throws ReflectionException
     * @throws editor_Workflow_Exception
     */
    private function getValidRoles(?editor_Models_Workflow $workflow): array
    {
        define('ZFEXTENDED_IS_WORKER_THREAD', true);
        $wfm = ZfExtended_Factory::get(editor_Workflow_Manager::class);

        return $wfm->get($workflow->getName())->getRoles();
    }

    private function printStepList(array $allSteps): void
    {
        foreach ($allSteps as &$oneStep) {
            unset($oneStep['workflowName']);
        }
        $this->io->section('List worflow steps');
        $this->io->table(WorkflowListCommand::STEP_HEADLINES, $allSteps);
    }

    private function getOrAskLabel(editor_Models_Workflow_Step $stepModel, bool $edit): string
    {
        $default = $edit ? $stepModel->getLabel() : null;

        return $this->input->getOption('label') ?: $this->io->ask('Workflow step label:', $default);
    }

    /**
     * @throws ReflectionException
     * @throws editor_Workflow_Exception
     */
    private function getOrAskRole(
        editor_Models_Workflow $workflow,
        editor_Models_Workflow_Step $stepModel,
        bool $edit
    ): string {
        $default = $edit ? $stepModel->getRole() : null;

        return $this->input->getOption('role') ?: $this->io->choice(
            'Workflow step role:',
            array_values($this->getValidRoles($workflow)),
            $default
        );
    }

    private function getOrAskPosition(editor_Models_Workflow_Step $stepModel, bool $edit): int
    {
        $default = $edit ? $stepModel->getPosition() : null;

        return (int) ($this->input->getOption('position')
            ?: $this->io->ask('Please enter a numeric position:', $default));
    }

    private function getOrAskFilter(editor_Models_Workflow_Step $stepModel, bool $edit): int
    {
        $default = $edit ? $stepModel->getLabel() : false;

        return (int) ($this->input->getOption('filtered') ?: $this->io->confirm('Set initial filtering:', $default));
    }
}

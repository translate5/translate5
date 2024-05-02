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

use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Models_Entity_NotFoundException;

class WorkflowListCommand extends Translate5AbstractCommand
{
    public const STEP_HEADLINES = [
        'id' => 'DB id:',
        'name' => 'Step name:',
        'label' => 'Step label:',
        'role' => 'Role:',
        'position' => 'Position:',
        'flagInitiallyFiltered' => 'Filtered:',
    ];

    public const ACTION_HEADLINES = [
        'id' => 'DB id:',
        'workflow' => false,
        'trigger' => 'Trigger',
        'inStep' => 'InStep',
        'byRole' => 'ByRole',
        'userState' => 'UserState',
        'actionClass' => 'ActionClass',
        'action' => 'Action',
        'parameters' => 'Parameters',
        'position' => 'Position',
        'description' => 'Description',
    ];

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'workflow:list';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Prints a list of available workflows with steps and actions')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Prints a list of available workflows with steps and actions');

        $this->addArgument(
            'workflow',
            InputArgument::OPTIONAL,
            'Workflow name to filter for that specific workflow to show the actions and other additional details'
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

        $this->writeTitle('Available Workflows');

        $workflows = \ZfExtended_Factory::get(\editor_Models_Workflow::class);
        $allWorkflows = $workflows->loadAll();
        $allWorkflows = array_combine(array_column($allWorkflows, 'name'), $allWorkflows);

        if ($filteredWorkflow = $input->getArgument('workflow')) {
            if (! array_key_exists($filteredWorkflow, $allWorkflows)) {
                $this->io->error('Given workflow invalid. Existing workflows: ' . join(', ', array_keys($allWorkflows)));

                return self::FAILURE;
            }
        }

        $steps = \ZfExtended_Factory::get(\editor_Models_Workflow_Step::class);
        $allSteps = $steps->loadAll();
        foreach ($allSteps as $step) {
            $wfName = $step['workflowName'];
            unset($step['workflowName']);
            $allWorkflows[$wfName]['steps'][] = $step;
        }

        foreach ($allWorkflows as $workflow) {
            if ($filteredWorkflow && $filteredWorkflow != $workflow['name']) {
                continue;
            }
            $this->io->section('Workflow: "' . $workflow['label'] . '" (name: ' . $workflow['name'] .
                '; id: ' . $workflow['id'] . ')');
            $this->io->table(self::STEP_HEADLINES, $workflow['steps']);
        }

        if ($filteredWorkflow) {
            $this->printDefaultsUsage($filteredWorkflow);
            $this->printActions($filteredWorkflow);
        }

        return self::SUCCESS;
    }

    /**
     * @throws ReflectionException
     */
    private function printDefaultsUsage(mixed $filteredWorkflow): void
    {
        $defaults = \ZfExtended_Factory::get(\editor_Models_UserAssocDefault::class);
        $s = $defaults->db->select()->where('workflow = ?', $filteredWorkflow);
        $s->from($defaults->db, [
            'customerId',
            'numrows' => 'count(*)',
        ]);
        $s->group('customerId');
        $usage = $defaults->db->fetchAll($s)->toArray();
        $totalCount = array_sum(array_column($usage, 'numrows'));
        if ($totalCount > 0) {
            $customer = \ZfExtended_Factory::get(\editor_Models_Customer_Customer::class);
            $customers = $customer->loadByIds(array_column($usage, 'customerId'));
            $custmersRows = ['']; //→ empty string newline
            foreach ($customers as $cust) {
                $custmersRows[] = $cust['id'] . ': ' . $cust['name'] . ' (' . $cust['number'] . ')';
            }
            $this->io->info('Workflow used in ' . $totalCount . ' task user defaults: ' . join("\n", $custmersRows));
        }
    }

    /**
     * @throws ReflectionException
     */
    private function printActions(mixed $filteredWorkflow): void
    {
        $this->io->section('Actions');
        $workflowActions = \ZfExtended_Factory::get(\editor_Models_Workflow_Action::class);
        $actions = $workflowActions->loadByWorkflow($filteredWorkflow);

        foreach ($actions as $action) {
            $filter = '';
            if (! empty($action['inStep']) || ! empty($action['byRole']) || ! empty($action['userState'])) {
                if (! empty($action['inStep'])) {
                    $filter .= 'inStep: ' . $action['inStep'];
                }
                if (! empty($action['byRole'])) {
                    $filter .= 'byRole: ' . $action['byRole'];
                }
                if (! empty($action['userState'])) {
                    $filter .= 'userState: ' . $action['userState'];
                }
                $filter = ' (' . $filter . ')';
            }

            $trigger = '<info>' . $action['trigger'] . '</info>' . $filter;
            $actionPrint = ' → ' . $action['actionClass']
                . '::' . $action['action'] . ' (id: ' . $action['id'] . ', pos: ' . $action['position'] . ')';

            $this->io->writeln($trigger);
            $this->io->writeln($actionPrint);

            if (! empty($action['description'])) {
                $this->io->writeln('  ' . $action['description']);
            }
            if (! empty($action['parameters'])) {
                $this->io->writeln('  conf: ' . str_replace("\n", '', $action['parameters']));
            }
        }
    }
}

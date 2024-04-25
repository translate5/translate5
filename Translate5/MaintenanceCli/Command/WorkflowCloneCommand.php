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
use editor_Models_Workflow_Action;
use editor_Models_Workflow_Step;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Db_Statement;
use Zend_Db_Statement_Exception;
use Zend_Db_Table_Abstract;
use Zend_Db_Table_Exception;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class WorkflowCloneCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'workflow:clone';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Clones a workflow completly.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Clones a workflow completly.');

        $this->addArgument(
            'workflowSource',
            InputArgument::REQUIRED,
            'Workflow name to be cloned',
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

        $this->writeTitle('Clone Workflow');

        //TODO: make also available as optional switch
        $newWorkflowLabel = $this->io->ask('Please provide the UI label of the target workflow:');
        $newWorkflowName = $this->io->ask('Please provide the technical name of the target workflow:');

        $workflow = ZfExtended_Factory::get(editor_Models_Workflow::class);
        $workflow->loadByName($input->getArgument('workflowSource'));
        $newWorkflow = ZfExtended_Factory::get(editor_Models_Workflow::class);
        $newWorkflow->setName($newWorkflowName);
        $newWorkflow->setLabel($newWorkflowLabel);
        $newWorkflow->save();
        $this->copySteps($newWorkflowName, $workflow);
        $this->copyActions($newWorkflowName, $workflow);
        $this->copyUserDefaults($newWorkflowName, $workflow);
        $this->io->success('Cloned workflow ' . $workflow->getLabel() . ' into ' . $newWorkflow->getLabel());

        return self::SUCCESS;
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Table_Exception
     * @throws Zend_Db_Statement_Exception
     */
    private function copySteps(mixed $newWorkflowName, editor_Models_Workflow $workflow): void
    {
        $steps = ZfExtended_Factory::get(editor_Models_Workflow_Step::class);
        $stmt = $this->cloneData($steps->db, $newWorkflowName, $workflow, 'workflowName');
        $this->io->success('Copied successfully ' . $stmt->rowCount() . ' workflow steps');
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Exception
     */
    private function copyActions(mixed $newWorkflowName, editor_Models_Workflow $workflow): void
    {
        $actions = ZfExtended_Factory::get(editor_Models_Workflow_Action::class);
        $stmt = $this->cloneData($actions->db, $newWorkflowName, $workflow);
        $this->io->success('Copied successfully ' . $stmt->rowCount() . ' workflow actions');
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Exception
     */
    private function copyUserDefaults(mixed $newWorkflowName, ?editor_Models_Workflow $workflow): void
    {
        //FIXME workflow table fks! fixen

        $userDefaults = ZfExtended_Factory::get(\editor_Models_UserAssocDefault::class);
        $stmt = $this->cloneData($userDefaults->db, $newWorkflowName, $workflow);
        $this->io->success('Copied successfully ' . $stmt->rowCount() . ' workflow user defaults');
    }

    /**
     * @throws Zend_Db_Table_Exception
     */
    private function cloneData(
        Zend_Db_Table_Abstract $db,
        mixed $newWorkflowName,
        editor_Models_Workflow $workflow,
        string $fieldName = 'workflow'
    ): Zend_Db_Statement {
        $fieldName = '`' . $fieldName . '`';
        $stepCols = array_map(fn ($col) => '`' . $col . '`', $db->info($db::COLS));
        $stepCols = array_combine($stepCols, $stepCols);

        $dbName = $db->info($db::NAME);
        unset($stepCols['`id`']);
        $stepCols[$fieldName] = $db->getAdapter()->quote($newWorkflowName) . ' as ' . $fieldName;

        $sql = 'INSERT INTO ' . $dbName . ' (' . join(',', array_keys($stepCols)) . ') ' .
            ' SELECT ' . join(', ', $stepCols) .
            ' FROM ' . $dbName .
            ' WHERE ' . $fieldName . ' = ?';

        return $db->getAdapter()->query($sql, [$workflow->getName()]);
    }
}

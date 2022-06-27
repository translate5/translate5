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

use editor_Workflow_Actions_Abstract;
use editor_Workflow_Actions_Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class DevelopmentTriggerworkflowCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:triggerworkflow';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Development: Triggers a workflow action / notification, identified by class name and function')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Triggers a workflow action by name.');

        $this->addArgument('name',
            InputArgument::REQUIRED,
            'Class name of the action / notification to be triggered. '
        );

        $this->addArgument('method',
            InputArgument::REQUIRED,
            'Method name of the action / notification to be triggered. '
        );

        $this->addOption(
            name: 'taskid',
            shortcut: 't',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The task ID of the task to be used as current task'
        );

        $this->addOption(
            name: 'options',
            shortcut: 'o',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The action parameters / options given as JSON string as it would be stored in the LEK_workflow_action table'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $name = $this->input->getArgument('name');
        $method = $this->input->getArgument('method');
        $options = $this->input->getOption('options');

        /** @var editor_Workflow_Actions_Abstract $instance */
        $instance = ZfExtended_Factory::get($name);

        //the action config as needed for calculation before triggering it
        $config = new editor_Workflow_Actions_Config();

        if($taskId = $input->getOption('taskid')) {
            $config->task = ZfExtended_Factory::get('editor_Models_Task');
            $config->task->load($taskId);
        }

        $instance->init($config);

        if($options) {
            $config->parameters = json_decode($options);
            if(json_last_error() > 0) {
                $this->io->error('Given JSON options could not be parsed: '.json_last_error_msg());
                return 1;
            }
            call_user_func([$instance, $method], $config->parameters);
        }
        else {
            call_user_func([$instance, $method]);
        }

        return 0;
    }
}

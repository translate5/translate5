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

use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use editor_Plugins_FrontEndMessageBus_Bus;
use editor_Plugins_FrontEndMessageBus_Init;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Zend_Db_Statement_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Db_ErrorLog;
use ZfExtended_Models_SystemRequirement_Result;
use ZfExtended_Models_SystemRequirement_Validator;
use ZfExtended_Models_Worker;
use ZfExtended_Plugin_Manager;


class StatusCommand extends Translate5AbstractCommand
{
    CONST SECTION_LENGTH = 40;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'status';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Prints a instance status.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Tool to print a brief system status.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        //FIXME for a new user:loginlog command:
        // select created,login,status from Zf_login_log where login in (XXX) order by login, created;
        // select login, count(*) cnt from Zf_login_log where login in ('XXX') group by login;

        //FIXME we bekomme ich die Connections vom messagebus here? Task and Job Summary

        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $this->writeTitle('Translate5 status overview');

        $this->writeSection('System Check', $this->systemCheck());
        $this->writeSection('Worker', $this->workerSummary());
        $this->writeSection('Connected Sessions', $this->messageBus());
        $this->writeSection('Task/Jobs');
        $this->writeTaskAndJobs();
        $this->io->text('');
        $this->writeSection('Last Log (Errors / Warnings)');
        $this->writeLastErrors();

        return 0;
    }

    protected function writeSection(string $title, string $data = '') {
        $this->io->text(str_pad('<options=bold>'.OutputFormatter::escape($title).'</>', self::SECTION_LENGTH, ' ', STR_PAD_RIGHT).': '.OutputFormatter::escape($data));
    }

    protected function messageBus(): string {
        $pluginmanager = Zend_Registry::get('PluginManager');
        /* @var $pluginmanager ZfExtended_Plugin_Manager */
        if(! $pluginmanager->isActive('FrontEndMessageBus')) {
            return '<fg=red;options=bold>Plugin FrontEndMessageBus NOT ACTIVE</>';
        }
        /* @var $bus editor_Plugins_FrontEndMessageBus_Bus */
        $bus = ZfExtended_Factory::get('editor_Plugins_FrontEndMessageBus_Bus', [editor_Plugins_FrontEndMessageBus_Init::CLIENT_VERSION]);
        $exception = null;
        $bus->setExceptionHandler(function(Throwable $e) use (&$exception){
            $exception = $e;
        });
        $count = count($bus->getConnectionSessions()?->instanceResult ?? []);
        if(empty($exception)) {
            return '<fg=green;options=bold>'.$count.'</>';
        }
        //the real error is in the previous exception, if that is empty take the thrown one.
        $exception = $exception->getPrevious() ?? $exception;
        return '<fg=red;options=bold>Error in communication with MessageBus: </><options=bold>'.OutputFormatter::escape($exception->getMessage()).'</>';
    }

    /**
     * Prints a table with tasks status information
     */
    protected function writeTaskAndJobs() {
        /** @var editor_Models_Task $task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $summary = $task->getSummary();
        if(empty($summary)) {
            $this->io->info('No tasks available!');
        } else {
            $this->writeTable($summary);
        }

        /** @var editor_Models_TaskUserAssoc $job */
        $job = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        $summary = $job->getSummary();

        if(empty($summary)) {
            $this->io->info('No jobs available!');
        } else {
            $this->writeTable($summary);
        }
    }

    protected function workerSummary(): string {
        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $worker ZfExtended_Models_Worker */

        $workerSummary = $worker->getSummary();
        $workerSumText = [
            'running' => $workerSummary[$worker::STATE_RUNNING],
            'waiting' => $workerSummary[$worker::STATE_WAITING],
            'scheduled' => $workerSummary[$worker::STATE_SCHEDULED],
            'defunct' => $workerSummary[$worker::STATE_DEFUNCT],
        ];

        $result = [];
        foreach($workerSumText as $id => $value) {
            if($id == 'defunct' && $value > 0) {
                $result[] = '<fg=red;options=bold>'.$id.': '.$value.'</>';
            }
            elseif($id !== 'defunct' && $value > 0) {
                $result[] = '<fg=yellow;options=bold>'.$id.': '.$value.'</>';
            }
            else {
                $result[] = '<fg=green;options=bold>'.$id.': '.$value.'</>';
            }
        }

        return join(', ', $result);
    }

    protected function systemCheck(): string {
        $validator = new ZfExtended_Models_SystemRequirement_Validator(false);
        /* @var $validator ZfExtended_Models_SystemRequirement_Validator */
        $results = $validator->validate();

        $error =  0;
        $warning =  0;
        $ok =  0;

        foreach($results as $module => $oneResult) {
            /* @var $validator ZfExtended_Models_SystemRequirement_Result */
            if($oneResult->hasError()) {
                $error++;
            }
            elseif($oneResult->hasWarning()) {
                $warning++;
            }
            else {
                $ok++;
            }
        }
        if($error === 0 && $warning === 0) {
            $shortResult = '<fg=green;options=bold>all ok ('.$ok.' checks)</>';
        }
        elseif ($error === 0) {
            $shortResult = '<fg=yellow;options=bold>not optimal: '.$warning.' warning(s)</>, call translate5.sh system:check command';
        }
        else {
            $shortResult = '<fg=red;options=bold>problematic: '.$error.' error(s)';
            if($warning > 0){
                $shortResult .= ', '.$warning.' warning(s)';
            }
            $shortResult .= '</>, call translate5.sh system:check command';
        }
        return $shortResult;
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    protected function writeLastErrors()
    {
        $log = new ZfExtended_Models_Db_ErrorLog();
        $foo = $log->getAdapter()->query('select id, created, duplicates, level, eventCode, message, domain from Zf_errorlog where level < 8 order by id desc limit 5;');
        foreach($foo->fetchAll() as $row) {
            $idBlock = '(# '.$row['id'];
            if($row['duplicates'] > 0) {
                $idBlock .= ' <options=bold>*'.$row['duplicates'].'</>';
            }
            $idBlock .= ') ';
            $this->io->text('  '.$row['created'].' '.
                LogCommand::LEVELS[$row['level']].' <options=bold>'.$row['eventCode'].'</> '.$idBlock.
                OutputFormatter::escape((string) $row['domain']).' → '.
                OutputFormatter::escape((string)str_replace("\n", ' ', $row['message'])));
        }
    }
}

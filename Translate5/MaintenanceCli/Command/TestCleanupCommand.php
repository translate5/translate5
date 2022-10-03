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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class TestCleanupCommand extends Translate5AbstractTestCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'test:cleanup';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('API-Tests: Removes all workers, unlocks all locked tasks and cleans incomplete bconf\'s to clean test residuals.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Removes all workers from the worker-table, unlocks all locked tasks and cleans incomplete bconf\'s to clean test residuals when ceveloping tests');

        $this->addOption(
            'application-database-cleanup',
            'a',
            InputOption::VALUE_NONE,
            'Clean the production/application database instead of the test database');

    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->initInputOutput($input, $output);

        // may we should clean the application/production DB
        if($this->input->getOption('application-database-cleanup')){
            if($this->io->confirm('Are you sure you want to erase all workers, unlock all tasks and erase incomplete bconf\'s on your application/production system ?')){
                $this->initTranslate5();
                $this->databaseCleanup();
                return 0;
            } else {
                return 0;
            }
        }
        $this->initTranslate5('test');
        $this->databaseCleanup();
        return 0;
    }

    private function databaseCleanup()
    {
        $config = \Zend_Registry::get('config');
        /* @var $config \Zend_Config */
        // clean worker table
        $workerDb = new \ZfExtended_Models_Db_Worker();
        $workerDb->getAdapter()->delete($workerDb->info(\Zend_Db_Table_Abstract::NAME), '1=1');
        // unlock locked tasks
        $taskDb = new \editor_Models_Db_Task();
        $cols = [ 'lockedInternalSessionUniqId' => null, 'locked' => null, 'lockingUser' => null ];
        $taskDb->getAdapter()->update($taskDb->info(\Zend_Db_Table_Abstract::NAME), $cols,'1=1');
        // remove oakapi bconf's without data folders. Note, this does not change task-bconf-assocs
        $okapiDataDir = rtrim(\editor_Plugins_Okapi_Bconf_Entity::getUserDataDir(), '/').'/';
        $okapiDb = new \editor_Plugins_Okapi_Db_Bconf();
        $okapiFilterDb = new \editor_Plugins_Okapi_Db_BconfFilter();
        $count = 0;
        foreach($okapiDb->fetchAll() as $row){
            $okapiId = $row->id;
            if(!is_dir($okapiDataDir)){
                $okapiFilterDb->delete('bconfId = \''.$okapiId.'\'');
                $row->delete();
                $count++;
            }
        }
        $this->io->success('Emptied worker table, unlocked locked tasks and removed '.$count.' incomplete bconf\'s from database "'.$config->resources->db->params->dbname.'"');
    }
}

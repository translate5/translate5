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

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class UserInfoCommand extends UserAbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'user:info';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Returns information about one or more users in translate5.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Returns information about one or more users in translate5.');
        
        $this->addArgument('identifier', InputArgument::REQUIRED, 'Either a numeric user ID, a user GUID (with or without curly braces), a login or part of a login when providing % placeholders, or an e-mail.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();
        $identifier = $this->input->getArgument('identifier');
        
        $uuid = new \ZfExtended_Validate_Uuid();
        $guid = new \ZfExtended_Validate_Guid();
        
        $userModel = \ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel \ZfExtended_Models_User */
        
        if(is_numeric($identifier)) {
            $this->writeTitle('Searching one user with ID "'.$identifier.'"');
            $userModel->load($identifier);
            $this->printOneUser($userModel->getDataObject());
            $this->printAdditionalInfo($userModel->getDataObject());
            return 0;
        }
        
        if($uuid->isValid($identifier)){
            $identifier = '{'.$identifier.'}';
            $this->writeTitle('Searching one user with GUID "'.$identifier.'"');
            $userModel->loadByGuid($identifier);
            $this->printOneUser($userModel->getDataObject());
            $this->printAdditionalInfo($userModel->getDataObject());
            return 0;
        }
        if($guid->isValid($identifier)){
            $this->writeTitle('Searching one user with GUID "'.$identifier.'"');
            $userModel->loadByGuid($identifier);
            $this->printOneUser($userModel->getDataObject());
            $this->printAdditionalInfo($userModel->getDataObject());
            return 0;
        }
        $this->writeTitle('Searching users with login or e-mail "'.$identifier.'"');
        $users = $userModel->loadAllByLoginPartOrEMail($identifier);
        $isOne = count($users) === 1;
        foreach($users as $user) {
            $this->printOneUser((object) $user);
            if($isOne) {
                $this->printAdditionalInfo((object) $user);
            }
            $this->io->text('');
        }
        return 0;
    }

    protected function printAdditionalInfo(\stdClass $user) {
        $this->printLoginLog($user->userGuid);
        $this->printSessions($user->id);
        $this->printTasksUsed($user->userGuid);
    }

    protected function printTasksUsed(string $userGuid) {
        $jobDb = \ZfExtended_Factory::get('editor_Models_Db_TaskUserAssoc');
        /* @var $jobDb \editor_Models_Db_TaskUserAssoc */
        $jobs = $jobDb->fetchAll($jobDb->select()
            ->where('userGuid = ?', $userGuid)
            ->where('usedState is not null')
        )->toArray();

        if(empty($jobs)) {
            return;
        }
        else {
            $this->io->section('Current opened jobs - should not be more as one');
        }
        foreach($jobs as $job) {
            $this->writeAssoc($job);
        }
    }

    protected function printSessions(int $userId) {
        $sessionDb = \ZfExtended_Factory::get('ZfExtended_Models_Db_Session');
        /* @var $sessionDb \ZfExtended_Models_Db_Session */
        $sessions = $sessionDb->fetchAll($sessionDb->select()
            ->where('userId = ?', $userId)
        )->toArray();
        if(empty($sessions)) {
            $this->io->info('No current sessions');
        }
        else {
            $this->io->section('Current sessions (session_id, last modfied)');
        }
        foreach($sessions as $session) {
            $this->io->text(substr($session['session_id'],0, 4).'... '.date('Y-m-d H:i:s', $session['modified']));
        }
    }
}

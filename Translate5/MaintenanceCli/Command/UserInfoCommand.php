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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Db_Select_Exception;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Db_Session;
use ZfExtended_Models_Invalidlogin;
use ZfExtended_Models_LoginLog;

class UserInfoCommand extends UserAbstractCommand
{
    // the name of the command (the part after "bin/console")
    public const ARG_IDENTIFIER = 'identifier';

    protected static $defaultName = 'user:info';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Returns information about one or more users in translate5.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Returns information about one or more users in translate5.');

        $this->addIdentifierArgument(self::ARG_IDENTIFIER);
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @throws ReflectionException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->findUsers(self::ARG_IDENTIFIER);

        $isOne = count($users) === 1;
        foreach ($users as $user) {
            $this->printOneUser($user);
            if ($isOne) {
                $this->printAdditionalInfo($user);
            }
            $this->io->text('');
        }

        return 0;
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Select_Exception
     */
    protected function printAdditionalInfo(\stdClass $user): void
    {
        $this->printLoginLog($user->login);
        $this->printSessions($user->id);
        $this->printTasksUsed($user->userGuid);
    }

    /**
     * prints the login log from latest to oldes, amount limited to the limit parameter
     * @throws ReflectionException
     * @throws Zend_Db_Select_Exception
     */
    protected function printLoginLog(string $login, int $limit = 5): void
    {
        $loginLog = ZfExtended_Factory::get(ZfExtended_Models_LoginLog::class);
        //logs must be loaded by login and not guid, since guid is only logged for successful logins
        $logs = $loginLog->loadByLogin($login, $limit);

        if (empty($logs)) {
            $this->io->info('Not logged in yet.');
        } else {
            $this->io->section('Last 5 logins (timestamp, status, way):');
        }

        foreach ($logs as $log) {
            $this->io->text($log['created'] . ' ' . $log['status'] . ' ' . $log['way']);
        }

        $invalidLogin = ZfExtended_Factory::get(ZfExtended_Models_Invalidlogin::class);
        $invalidLogins = $invalidLogin->loadInvalidLogins($login);

        if (! empty($invalidLogins)) {
            $this->io->section('Invalid logins (timestamp, login) - '
                . ($invalidLogin->hasMaximumInvalidations($login) ? 'ALREADY LOGIN LOCKED!' : 'not locked yet') . ':');
            foreach ($invalidLogins as $log) {
                $this->io->text($log['created'] . ' ' . $log['login']);
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function printTasksUsed(string $userGuid): void
    {
        $jobDb = \ZfExtended_Factory::get('editor_Models_Db_TaskUserAssoc');
        /* @var $jobDb \editor_Models_Db_TaskUserAssoc */
        $jobs = $jobDb->fetchAll(
            $jobDb->select()
                ->where('userGuid = ?', $userGuid)
                ->where('usedState is not null')
        )->toArray();

        if (empty($jobs)) {
            return;
        } else {
            $this->io->section('Current opened jobs - should not be more as one');
        }
        foreach ($jobs as $job) {
            $this->writeAssoc($job);
        }
    }

    protected function printSessions(int $userId): void
    {
        $sessionDb = new ZfExtended_Models_Db_Session();
        $sessions = $sessionDb->fetchAll(
            $sessionDb->select()
                ->where('userId = ?', $userId)
        )->toArray();
        if (empty($sessions)) {
            $this->io->info('No current sessions');
        } else {
            $this->io->section('Current sessions (session_id, last modfied)');
        }
        foreach ($sessions as $session) {
            $this->io->text(substr($session['session_id'], 0, 4) . '... ' . $session['modified']);
        }
    }
}

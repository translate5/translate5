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

use editor_Plugins_FrontEndMessageBus_Bus;
use editor_Plugins_FrontEndMessageBus_Init;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Zend_Db_Table_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Db_Session as Session;
use ZfExtended_Models_LoginLog as LoginLog;
use ZfExtended_Models_Worker;
use ZfExtended_Plugin_Manager;

class UserOnlineCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'user:online';

    protected function configure(): void
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Prints which users are online.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Prints which users are online.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @throws ReflectionException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //FIXME for a new user:loginlog command:
        // select created,login,status from Zf_login_log where login in (XXX) order by login, created;
        // select login, count(*) cnt from Zf_login_log where login in ('XXX') group by login;

        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $this->writeTitle('Translate5 Users online (+ Worker Summary and Login Log count)');
        $this->writeWorkerSummary();
        $this->writeTodaysLogins();
        $this->writeSessionList();

        return self::SUCCESS;
    }

    protected function writeWorkerSummary(): void
    {
        $worker = new ZfExtended_Models_Worker();

        $workerSummary = $worker->getSummary();
        $workerSumText = [
            'running' => $workerSummary[$worker::STATE_RUNNING],
            'waiting' => $workerSummary[$worker::STATE_WAITING],
            'scheduled' => $workerSummary[$worker::STATE_SCHEDULED],
            'delayed' => $workerSummary[$worker::STATE_DELAYED],
            'prepared' => $workerSummary[$worker::STATE_PREPARE],
        ];

        $result = [];
        foreach ($workerSumText as $id => $value) {
            if ($id == 'running' && $value > 0) {
                $result[] = '<fg=red;options=bold>' . $id . ': ' . $value . '</>';
            } elseif ($id !== 'running' && $value > 0) {
                $result[] = '<fg=yellow;options=bold>' . $id . ': ' . $value . '</>';
            } else {
                $result[] = '<fg=green;options=bold>' . $id . ': ' . $value . '</>';
            }
        }

        $this->io->section('Worker overview:');
        $this->io->writeln(' ' . join(', ', $result));
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    private function writeSessionList(): void
    {
        $this->io->section('Sessions: ');

        $sessionDb = ZfExtended_Factory::get(Session::class);
        $s = $sessionDb->select()
            ->where('not userId is null')
            ->where('modified >= NOW() - INTERVAL 1 DAY');
        $todaysUserSessions = $sessionDb->fetchAll($s)->toArray();

        $connections = $this->loadConnectionsFromMessageBus();

        $table = $this->io->createTable();
        $table->setHeaders(['Modified', 'UserId', 'WebSocket Connection']);

        foreach ($todaysUserSessions as $item) {
            if (is_null($connections)) {
                $connection = 'Error in talking to MessageBus (disabled?)';
            } else {
                $connection = in_array($item['session_id'], $connections) ? 'Yes' : 'No';
            }
            $table->addRow([$item['modified'], $item['userId'], $connection]);
        }

        $table->render();
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Table_Exception
     */
    private function writeTodaysLogins(): void
    {
        $this->io->section('Logins: ');
        $loginlog = ZfExtended_Factory::get(LoginLog::class);
        $logins = $loginlog->loadLastGrouped();
        if (empty($logins)) {
            $this->io->writeln('<fg=yellow;options=bold>No logins yet</>');

            return;
        }
        $result = [];
        foreach ($logins as $day => $cnt) {
            if (count($result) > 3) {
                break;
            }
            if ($cnt === $loginlog::GROUP_COUNT) {
                $cnt = '>' . $cnt;
            }
            $result[] = $day . ': <options=bold>' . $cnt . '</>';
        }
        $this->io->writeln(' ' . join('; ', $result));
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    private function loadConnectionsFromMessageBus(): ?array
    {
        $pluginmanager = Zend_Registry::get('PluginManager');
        /* @var $pluginmanager ZfExtended_Plugin_Manager */
        if (! $pluginmanager->isActive('FrontEndMessageBus')) {
            return null;
        }
        /* @var $bus editor_Plugins_FrontEndMessageBus_Bus */
        $bus = ZfExtended_Factory::get(editor_Plugins_FrontEndMessageBus_Bus::class, [
            editor_Plugins_FrontEndMessageBus_Init::CLIENT_VERSION,
        ]);
        $exception = null;
        $bus->setExceptionHandler(function (Throwable $e) use (&$exception) {
            $exception = $e;
        });
        if (! empty($exception)) {
            return null;
        }

        return (array) $bus->getConnectionSessions()?->instanceResult ?? [];
    }
}

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
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Models_Installer_Maintenance;

class MaintenanceCommand extends Translate5AbstractCommand
{
    private const DATE_FORMAT = 'Y-m-d H:i (O)';

    protected static $defaultName = 'maintenance:status';

    protected ZfExtended_Models_Installer_Maintenance $mm;

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Returns information about the maintenance mode.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Returns information about the maintenance mode.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('maintenance mode');

        $this->mm = new ZfExtended_Models_Installer_Maintenance();
        if ($this->mm->isInIni()) {
            $this->io->error([
                'There is some maintenance configuration in the installation.ini, ',
                'please remove it for proper usage of this tool!',
            ]);

            return 1;
        }

        $this->_execute();

        $this->showStatus();

        return 0;
    }

    protected function _execute()
    {
        //for status do nothing
    }

    /**
     * @throws ReflectionException
     */
    protected function announce(string $time, string $msg): void
    {
        $result = $this->mm->announce($time, $msg);
        if (! empty($result['error'])) {
            $this->io->error($result['error']);
        }
        if (! empty($result['warning'])) {
            $this->io->warning($result['warning']);
        }
        if (! empty($result['sent'])) {
            $this->io->success('Send maintenance announcement mails to:');
            $this->output->writeln($result['sent']);
        }
        $this->io->text('');
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     */
    protected function showStatus(): void
    {
        $conf = $this->mm->status();

        if ($this->isPorcelain) {
            if ($this->mm->isActive()) {
                $this->output->write('Maintenance mode ACTIVE since ' . $this->mm->status()->startDate);
            } elseif ($this->mm->isNotified()) {
                $loginLock = $this->mm::isLoginLock() ? ' login lock reached' : '';
                $this->output->write('Maintenance mode SCHEDULED for ' . $this->mm->status()->startDate . ' ' . $loginLock);
            } else {
                $this->output->write('Maintenance mode DISABLED');
            }
            $this->output->writeln('');

            return;
        }

        if (empty($conf->startDate)) {
            $msg = ["  <info>Maintenance mode:</> <fg=green;options=bold>disabled!</>"];
            if (! empty($conf->message)) {
                $msg[] = '<info>GUI Message active:</> <options=bold>' . $conf->message . '</>';
            }
            $msg[] = '';
            $this->io->text($msg);
            $this->printNotes();

            return;
        }

        if ($this->mm->isActive()) {
            $this->io->text("<info>Maintenance mode:</> <fg=red;options=bold>active!</>");
        } elseif ($this->mm->isNotified()) {
            $this->io->text("<info>Maintenance mode:</> <fg=yellow;options=bold>notified!</>");
        }

        $startTimeStamp = strtotime($conf->startDate);
        $this->output->writeln([
            '',
            '            <info>start:</> ' . date(self::DATE_FORMAT, $startTimeStamp),
            '     <info>start notify:</> ' . date(self::DATE_FORMAT, $startTimeStamp - ((int) $conf->timeToNotify * 60)),
            '       <info>login lock:</> ' . date(self::DATE_FORMAT, $startTimeStamp - ((int) $conf->timeToLoginLock * 60)),
            '          <info>message:</> ' . $conf->message,
            '        <info>receivers:</> ' . $conf->announcementMail,
            '',
        ]);

        $this->printNotes();
    }
}

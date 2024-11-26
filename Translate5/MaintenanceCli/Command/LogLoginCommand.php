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
declare(strict_types=1);

namespace Translate5\MaintenanceCli\Command;

use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Db_Table_Select;
use Zend_Exception;
use Zend_Validate_Exception;
use ZfExtended_Factory as Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_LoginLog as LoginLog;
use ZfExtended_Models_User as User;
use ZfExtended_Validate_Guid;

class LogLoginCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'log:login';

    /**
     * Tracking the last found id for reach run with --follow
     * @var integer
     */
    protected int $lastFoundId = 0;

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Query the login log')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Tool to query and investigate the translate5 login log.');

        $this->addArgument('userInfo', InputArgument::OPTIONAL, 'user login / id / userGuid');

        $this->addOption(
            'follow',
            'f',
            InputOption::VALUE_NONE,
            'Show the most recent log entries, and continuously print new entries as they are appended to the log. '
            . 'Do not show a summary.'
        );

        $this->addOption(
            'since',
            's',
            InputOption::VALUE_REQUIRED,
            'Shows log data since the given point in time (strtotime parsable string).'
        );

        $this->addOption(
            'until',
            'u',
            InputOption::VALUE_REQUIRED,
            'Shows log data until the given point in time (strtotime parsable string). '
            . 'If the parameter starts with a "+" it is automatically added to the since date.'
        );

        $this->addOption(
            'last',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Shows only the last X log entries (default 5).',
            false
        );
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws Zend_Validate_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('Query Login Log');

        $log = new LoginLog();

        $userIds = $this->parseArgumentToUserIds();

        //defining always the --follow loop but break it, if not using following
        while (true) {
            $s = $log->db->select()->order('id DESC');

            if ($userIds !== null) {
                $s->where('userId IN ?', $userIds);
            }
            $this->parseDateToSelect($s);

            $limit = $input->getOption('last');
            if ($limit !== false) { // if === false, then it was not given at all
                $s->limit($limit ?? 5); //if $limit is null, then it was given empty, so defaulting to 5
            }

            if ($input->getOption('follow')) {
                //on first run we respect limit, after that not anymore to get all logs in the 2 second gap
                if ($this->lastFoundId > 0) {
                    $s->reset($s::LIMIT_COUNT);
                }
                $s->where('id > ?', $this->lastFoundId);
                $this->processResults($log, $s);
                sleep(2);
            } else {
                $this->processResults($log, $s);

                return 0;
            }
        }
    }

    /**
     * searches for log entries and process them
     */
    protected function processResults(LoginLog $log, Zend_Db_Table_Select $s): void
    {
        echo $s->assemble();
        $rows = $log->db->fetchAll($s)->toArray();
        $rows = array_reverse($rows);
        if (! $this->input->getOption('follow')) {
            $this->io->section('Found log entries:');
        }
        $table = $this->io->createTable();
        $table->setHeaders([
            'Created',
            'Login',
            'UserGuid',
            'Status',
            'Way',
        ]);
        foreach ($rows as $item) {
            $table->addRow([
                $item['created'],
                $item['login'],
                $item['userGuid'],
                $item['status'],
                $item['way'],
            ]);
        }
        if (isset($item)) {
            $this->lastFoundId = (int) $item['id'];
        }
        $table->render();
    }

    /**
     * parses and adds the date filters
     */
    protected function parseDateToSelect(Zend_Db_Table_Select $s): bool
    {
        $result = false;
        if ($since = $this->input->getOption('since')) {
            $since = strtotime($since);
            if ($since === false) {
                $this->io->warning('The given --since|-s time can not be parsed to a valid date - ignored!');
            } else {
                $since = date('Y-m-d H:i:s', $since);
                $s->where('created >= ?', $since);
                $result = true;
            }
        }
        if ($until = $this->input->getOption('until')) {
            $until = trim($until);
            if (str_contains($until, '+')) {
                $until = $this->input->getOption('since') . ' ' . $until;
            }
            $until = strtotime($until);
            if ($until === false) {
                $this->io->warning('The given --until|-u time can not be parsed to a valid date - ignored!');
            } else {
                $until = date('Y-m-d H:i:s', $until);
                $s->where('created <= ?', $until);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @return int[]|null
     * @throws ReflectionException
     * @throws Zend_Validate_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function parseArgumentToUserIds(): ?array
    {
        $identifier = $this->input->getArgument('userInfo');
        if (! $identifier) {
            return null;
        }
        $guid = new ZfExtended_Validate_Guid();

        $userModel = Factory::get(User::class);

        if (is_numeric($identifier)) {
            $this->writeTitle('Searching one user with ID "' . $identifier . '"');
            $userModel->load($identifier);

            return [$userModel->getId()];
        }

        $guidToTest = '{' . trim($identifier, '{}') . '}';
        if ($guid->isValid($guidToTest)) {
            $this->writeTitle('Searching one user with GUID "' . $guidToTest . '"');
            $userModel->loadByGuid($guidToTest);

            return [$userModel->getId()];
        }

        $this->writeTitle('Searching users with login or e-mail "' . $identifier . '"');
        $users = $userModel->loadAllByLoginPartOrEMail($identifier);

        return array_column($users, 'id');
    }
}

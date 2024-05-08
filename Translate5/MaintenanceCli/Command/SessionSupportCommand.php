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

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SessionSupportCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'auth:support';

    protected function configure()
    {
        $this->setAliases(['support']);

        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Returns a URL to authenticate passwordless as the support-user.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Generates a new session for the support-user, optionally parses a given link/url for task & segment-nr.');

        $this->addArgument('path', InputArgument::OPTIONAL, 'An URL or path to fetch task/segment-nr from.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandData = [
            // the command name is passed as first argument
            'command' => 'auth:impersonate',
            'login' => 'mittagqi'
        ];
        $path = $input->getArgument('path'); // sth. like http://translate5.local/editor/taskid/2023/#task/2023/37/edit
        $taskId = null;

        // parse task-id & segment-id out of the passed link/path
        if (!empty($path) && str_contains($path, 'editor/')) {
            if (str_contains($path, '/editor/')) {
                $parts = explode('/editor/', $path);
                $path = $parts[1];
            }
            $parts = explode('/', str_replace('//', '/', $path));
            $numParts = count($parts);
            for ($i = 0; $i < $numParts; $i++) {

                if ($parts[$i] === 'taskid' && $numParts > $i + 1 && is_numeric($parts[$i + 1])) {

                    $taskId = $parts[$i + 1];
                    $commandData['task'] = $taskId;

                } else if ($taskId !== null && $parts[$i] === 'edit' && $i > 0 && is_numeric($parts[$i - 1])) {

                    $commandData['--segment-nr'] = $parts[$i - 1];
                }
            }
        }

        return $this->getApplication()->doRun(new ArrayInput($commandData), $output);
    }
}

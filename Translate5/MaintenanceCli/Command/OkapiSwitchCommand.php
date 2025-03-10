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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OkapiSwitchCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'okapi:switch';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Switches the used OKAPI-version for the installation.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Switches the used OKAPI-version for the installation by selecting one of the configured versions.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandData = [
            // the command name is passed as first argument
            'command' => 'okapi:update',
            '--select' => null,
        ];

        return $this->getApplication()->doRun(new ArrayInput($commandData), $output);
    }
}

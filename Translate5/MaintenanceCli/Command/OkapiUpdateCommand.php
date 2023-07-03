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

use LogicException;
use MittagQI\Translate5\Plugins\Okapi\ConfigMaintenance;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class OkapiUpdateCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'okapi:update';

    protected function configure()
    {
        $this
            ->setDescription('Updates the configured okapi server to be used')
            ->setHelp('Sets the given name as default okapi server.
If no name is given, use the last one from the configured server list.');

        $this->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'The name of the Okapi instance to be configured as default, if omitted take the last one'
        );

        $this->addOption(
            'clean-customers',
            'c',
            InputOption::VALUE_NONE,
            'By default custom configurations for customers are not changed.
            With that option they are removed completely.'
        );

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

        $this->writeTitle('Update Okapi server used by default');

        $name = $this->input->getArgument('name');
        $config = new ConfigMaintenance();
        $serverNames = array_keys($config->getServerList());

        if (empty($name)) {
            $name = end($serverNames);
        } elseif (!in_array($name, $serverNames)) {
            throw new LogicException('Given okapi server name "' . $name . '" does not exist! ');
        }

        if ($input->getOption('clean-customers')) {
            //removes all customer specific okapi servers
            $config->cleanUpNotUsed(['idonotexist' => 'meeither']);
        }

        $config->updateServerUsed($name);
        $config->updateServerUsedDefaults($config->getServerList());

        $this->io->success('Set used okapi config to: ' . $name);
        if ($input->getOption('clean-customers')) {
            $this->io->success('Cleaned customers!');
        }

        return self::SUCCESS;
    }
}

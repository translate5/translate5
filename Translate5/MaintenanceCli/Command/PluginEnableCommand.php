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

class PluginEnableCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'plugin:enable';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Activate one or more plug-ins.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Tool to activate installed translate5 plugins.');

        $this->addOption(
            '--default-plugins',
            null,
            InputOption::VALUE_NONE,
            'Enable plugins having static $enabledByDefault = true'
        );

        $this->addArgument(
            'plugins',
            InputArgument::IS_ARRAY,
            'One or more plug-in names to be activated. Use command plugin:list to get a full list.'
        );
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
        $this->writeTitle('Activated Translate5 Plug-In');

        /* @var $pluginmanager \ZfExtended_Plugin_Manager */
        $pluginmanager = \Zend_Registry::get('PluginManager');

        // If we should activate all plugins having static $enabledByDefault === true
        if ($input->getOption('default-plugins')) {
            // Activate plugins
            $log = $pluginmanager->activateEnabledByDefault();

            // Print heading
            $this->io->section(array_shift($log));

            // Print activated plugins list
            foreach ($log as $line) {
                $this->io->writeln($line);
            }
        } else {
            // Get list of plugins explicitly provided with the command
            $plugins = $this->input->getArgument('plugins');

            // If list is empty
            if (empty($plugins)) {
                // Print error
                $this->io->error('Please specify at least one plug-in or --default-plugins option');

                // Exit with code 1
                return 1;
            }

            // Foreach plugin
            foreach ($plugins as $plugin) {
                // Try to activate
                if ($pluginmanager->setActive($plugin, true)) {
                    $this->io->success('Activated plug-in ' . $plugin);

                    // Warn if unsuccessful
                } else {
                    $this->io->error('Could not activate plug-in ' . $plugin . '. Wrong plug-in name specified? Call command plugin:list.');
                }
            }
        }

        return 0;
    }
}

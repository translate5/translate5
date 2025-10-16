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

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Models_Installer_DbUpdater;
use ZfExtended_Plugin_Manager;
use ZfExtended_Utils;

class PluginRemoveCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'plugin:remove';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Remove a plugin! DANGEROUS!')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Removes a plugin from disk - executes DB de-installers too.');

        $this->addArgument(
            'plugin',
            InputArgument::REQUIRED,
            'One plug-in names to be removed from installation.'
        );

        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Don\'t ask for confirmation before deletion',
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
        $this->initTranslate5();
        $this->writeTitle('Remove plugin');

        if (\ZfExtended_Utils::isDevelopment()) {
            $this->io->error('Hey thats a command for production only! A dev should remove plugins manually via git!');

            return self::FAILURE;
        }

        $pluginmanager = \Zend_Registry::get('PluginManager');
        /* @var $pluginmanager \ZfExtended_Plugin_Manager */
        $plugin = $this->input->getArgument('plugin');

        //NEVER remove active plugins to prevent events to be registered for that plugin
        if ($pluginmanager->isActive($plugin)) {
            $this->io->error('Plugin "' . $plugin . '" is active and MUST be deactivated in a separate CLI call');

            return self::FAILURE;
        }

        $pluginFolder = $this->getPluginFolder($pluginmanager, $plugin);
        if ($pluginFolder === null) {
            $this->io->error('No plugin folder for plugin "' . $plugin . '" found!');

            return self::FAILURE;
        }

        if ($this->input->getOption('force')
            || $this->io->confirm('Are you sure you want to remove plugin folder "' . $pluginFolder . '"?')) {
            $dbUpdater = new ZfExtended_Models_Installer_DbUpdater();
            $result = $dbUpdater->deinstallPlugin($plugin);
            if ($result['newProcessed'] === $result['new']) {
                $this->io->error('Errors in deinstalling plugin SQLs - must be now fixed manually!');
                $this->io->error($dbUpdater->getErrors());

                return self::FAILURE;
            }

            ZfExtended_Utils::recursiveDelete($pluginFolder);
            $this->io->success('Plugin removed!');
        }

        return self::SUCCESS;
    }

    /**
     * @throws ReflectionException
     */
    private function getPluginFolder(ZfExtended_Plugin_Manager $pluginManager, string $plugin): ?string
    {
        $availablePlugins = $pluginManager->getAvailable();
        foreach ($availablePlugins as $availablePlugin => $cls) {
            if (strtolower($availablePlugin) === strtolower($plugin)) {
                $ref = new ReflectionClass($cls);

                return dirname($ref->getFileName());
            }
        }

        return null;
    }
}

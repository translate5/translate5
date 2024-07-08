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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginListCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'plugin:list';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('List all installed plugins.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Tool to list all installed translate5 plugins.');

        $this->addOption(
            '--as-json',
            null,
            InputOption::VALUE_NONE,
            'return the summary just as json'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isJson = false;
        if ($input->getOption('as-json')) {
            $isJson = true;
            $this->isPorcelain = true;
            $output->setDecorated(false);
            $input->setInteractive(false);
        }

        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $pluginmanager = \Zend_Registry::get('PluginManager');
        /* @var $pluginmanager \ZfExtended_Plugin_Manager */
        $plugins = $pluginmanager->getAvailable();
        ksort($plugins);
        $pluginmanager->bootstrap();
        $activePlugins = $pluginmanager->getActive();
        $rows = [];

        if ($isJson) {
            foreach ($plugins as $plugin => $cls) {
                $rows[] = [
                    'plugin' => $plugin,
                    'active' => in_array($cls, $activePlugins),
                    'type' => $cls::getType(),
                    'enabledByDefault' => $cls::isEnabledByDefault(),
                    'description' => $cls::getDescription(),
                ];
            }
            $output->write(json_encode($rows));

            return self::SUCCESS;
        }

        $this->writeTitle('Installed Translate5 Plug-Ins.');

        foreach ($plugins as $plugin => $cls) {
            $desc = $cls::getDescription();
            $type = $cls::getType();
            /* @var \ZfExtended_Plugin_Abstract $cls */
            $enabledByDefault = $cls::isEnabledByDefault() ? '<options=bold>on</>' : 'off';
            switch ($type) {
                case \ZfExtended_Plugin_Abstract::TYPE_CLIENT_SPECIFIC:
                    $type = '<fg=magenta>' . $type . '</>';

                    break;
                case \ZfExtended_Plugin_Abstract::TYPE_PRIVATE:
                    $type = '<fg=bright-magenta>' . $type . '</>';

                    break;
                default:
                    break;
            }
            if (in_array($cls, $activePlugins)) {
                $rows[] = ['<info>' . $plugin . '</info>', '<info>active</info>', $enabledByDefault, $type, $desc];
            } else {
                $rows[] = [$plugin, 'disabled', $enabledByDefault, $type, $desc];
            }
        }
        $this->io->table(['Plugin', 'Status', 'Default', 'Type', 'Description'], $rows);

        return 0;
    }
}

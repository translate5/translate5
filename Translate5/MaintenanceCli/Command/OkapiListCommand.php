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

use MittagQI\Translate5\Plugins\Okapi\ConfigMaintenance;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

//FIXME let me come from the plugin - implement https://symfony.com/doc/current/console/lazy_commands.html first
class OkapiListCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'okapi:list';

    protected function configure()
    {
        $this
            ->setDescription('List all configured okapi server\'s and their usage')
            ->setHelp('List all configured Okapi Server and their usage.');

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
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $config = new ConfigMaintenance();
        $usage = $config->getSummary();

        if ($input->getOption('as-json')) {
            $output->write(json_encode($usage));
            return self::SUCCESS;
        }

        $this->writeTitle($this->getDescription());
        if ($this->isPorcelain) {
            $this->output->writeln('');
        } else {
            $this->io->writeln('Set as choose-able defaults:');
            $defaults = $config->getServerUsedDefaults($selected);
            foreach ($defaults as &$default) {
                if ($default === $selected) {
                    $default = '<info>' . $default . '</info>';
                }
            }
            $this->io->writeln(' ' . join(', ', $defaults));
        }

        $table = [];
        foreach ($usage as $serverName => $data) {
            $table[] = [
                'name' => $serverName,
                'url' => $data['url'] ?? '- na -',
                'task count' => $data['taskUsageCount'] ?? 0,
                'customer config (IDs)' => join(',', $data['customerIdUsage']) ?? ''
            ];
        }
        $this->writeTable($table);

        return self::SUCCESS;
    }
}

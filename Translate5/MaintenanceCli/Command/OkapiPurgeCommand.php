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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class OkapiPurgeCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'okapi:purge';

    protected function configure()
    {
        $this
            ->setDescription('Cleans the configured okapi versions')
            ->setHelp('Removes all unused Okapi versions, keeps the last one assuming to be the latest not used yet.');

        $this->addArgument(
            'keep',
            InputArgument::OPTIONAL,
            'The server name to be kept also if unused - if given the last one might be removed if unused'
        );

        $this->addArgument(
            'server-name',
            InputArgument::OPTIONAL,
            'The server name to be deleted (if unused)'
        );

        $this->addOption(
            name: 'no-keep',
            mode: InputOption::VALUE_NONE,
            description: 'Delete also the latest entry if unused'
        );

        $this->addOption(
            name: 'only-offline',
            mode: InputOption::VALUE_NONE,
            description: 'Purges only the entries being offline, gnores "no-keep" and "keep" options'
        );

        $this->addOption(
            name: 'sort',
            mode: InputOption::VALUE_NONE,
            description: 'Sort the entries by version'
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

        $this->writeTitle('Purge Okapi servers');

        $keep = $this->input->getArgument('keep');
        $serverToDelete = $this->input->getArgument('server-name');
        $keepLast = ! $this->input->getOption('no-keep');
        $sort = $this->input->getOption('sort');
        $onlyOffline = $this->input->getOption('only-offline');

        $config = new ConfigMaintenance();
        $summary = $config->getSummary();
        $serverList = $onlyOffline ?
            $config->purgeOffline($sort) :
            $config->purge($summary, $keep, $sort, $keepLast, $serverToDelete);

        foreach ($summary as $name => $data) {
            if (empty($serverList[$name])) {
                $this->io->writeln('removed ' . $name . ': ' . ($data['url'] ?? '- na -'));
            }
        }

        $result = [];
        foreach ($serverList as $name => $server) {
            $result[] = $name . ': ' . $server;
        }

        $this->io->success('Purged to ' . join(', ', $result));

        return self::SUCCESS;
    }
}

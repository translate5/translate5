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

use MittagQI\Translate5\Export\QueuedExportCleanUpService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class TaskExportCleanCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:export:clean';

    protected function configure(): void
    {
        $this->setDescription('Clean old artefacts from data/Export directory')
            ->setHelp('Clean old artefacts from data/Export directory');

        $this->addOption(
            'execute',
            null,
            InputOption::VALUE_NONE,
            'Execute the clean-up, by default only dry-run to find out what is deleted.'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $execute = $input->getOption('execute');

        $exportCleanUpService = new QueuedExportCleanUpService();
        $filesAffected = $exportCleanUpService->cleanUp(! $execute);
        if ($execute) {
            $this->io->section('Content of DATA/Export deleted:');
        } else {
            $this->io->section('Content of DATA/Export to be deleted:');
        }
        if (empty($filesAffected)) {
            $this->io->info('No Export files to be deleted');
        } else {
            $this->io->writeln($filesAffected);
        }

        return self::SUCCESS;
    }
}

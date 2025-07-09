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

use MittagQI\Translate5\Terminology\CleanupCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

//FIXME https://github.com/bamarni/symfony-console-autocomplete

class TbxCleanCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'tbx:clean';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('cleans old TBX files')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Removes old TBX files left over from TBX imports');

        $this->addOption(
            'delete-data',
            'd',
            InputOption::VALUE_NONE,
            'deletes the files'
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

        $dryRun = ! $input->getOption('delete-data');
        $this->writeTitle('Cleaning up TBX data ' . ($dryRun ? '- DRY RUN - would delete files' : 'deleted files'));

        $collectionModel = new \editor_Models_TermCollection_TermCollection();
        $collections = $collectionModel->loadAllEntities();

        $nothingDeleted = true;
        foreach ($collections as $collection) {
            $cleanup = new CleanupCollection($collection);
            $deleted = $cleanup->checkAndClean($dryRun);
            if (count($deleted) > 0) {
                $this->io->section('from Term Collection #' . $collection->getId() . ' - ' . $collection->getName());
                $this->io->writeln($deleted);
                $nothingDeleted = false;
            }
        }

        if ($nothingDeleted) {
            $this->io->warning('Nothing to delete');
        }

        return self::SUCCESS;
    }
}

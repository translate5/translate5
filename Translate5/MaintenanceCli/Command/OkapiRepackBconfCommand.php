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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class OkapiRepackBconfCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'okapi:repackbconf';

    protected function configure()
    {
        $this
            ->setDescription('Re-packs the BCONF of the given id or all')
            ->setHelp('Re-packs the BCONF of the given id or all');

        $this->addArgument(
            'id',
            InputArgument::OPTIONAL,
            'Database id of the bconf to pack'
        );

        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Re-packs all BCONFs of the instance'
        );

        $this->addOption(
            'outdated',
            'o',
            InputOption::VALUE_NONE,
            'Mimics a repack of an outdated version'
        );
    }

    /**
     * Execute the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $this->writeTitle('Re-pack BCONF(s)');

        $ids = [];
        $bconfEntity = new BconfEntity();
        if (! empty($input->getArgument('id'))) {
            $id = (int) $input->getArgument('id');

            try {
                $bconfEntity->load($id);
                $ids[] = $id;
            } catch (Throwable $e) {
                $this->io->error('There is no BCONF with id ' . $id);

                return self::FAILURE;
            }
        }

        if (empty($ids) && $this->input->getOption('all')) {
            foreach ($bconfEntity->loadAll() as $row) {
                $ids[] = (int) $row['id'];
            }
        }

        if (empty($ids)) {
            $this->io->error('You must either provide a bconf-id or use the --all option');

            return self::FAILURE;
        }

        // now repack
        $names = [];
        $isOutdatedRepack = $this->input->hasOption('outdated');
        foreach ($ids as $id) {
            $bconfEntity = new BconfEntity();
            $bconfEntity->load($id);
            $bconfEntity->pack($isOutdatedRepack);
            $names[] = $bconfEntity->getName();
        }

        $this->io->success('Re-packed: "' . implode("\",\n   \"", $names) . '"');

        return self::SUCCESS;
    }
}

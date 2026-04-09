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
use Translate5\MaintenanceCli\L10n\L10nConfiguration;
use Translate5\MaintenanceCli\L10n\L10nReimporter;

class L10nReimportCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'l10n:reimport';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription(
                'Reimports the localizations from a localization-task.' .
                'It is expected, that the localization-task is exported under it\'s original name and stored in ' .
                '/data/' . L10nConfiguration::DATA_DIR . ' or the localization-files are stored in locale-folders ' .
                '(/de, /en, ...) in this location .'
            )

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp(
                'Reimports the localizations from a localization-task.' .
                'It is expected, that the localization-task is exported under it\'s original name and stored in ' .
                '/data/' . L10nConfiguration::DATA_DIR . ' or the localization-files are stored in locale-folders ' .
                '(/de, /en, ...) in this location .'
            );

        $this->addOption(
            'create',
            'c',
            InputOption::VALUE_NONE,
            'If used, non existing ZXLIFF-localization-files will be created'
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

        $this->writeTitle('Translate5 L10n maintenance - reimporting localizations');
        $doCreate = $input->getOption('create');

        try {
            $reimport = new L10nReimporter();
            $reimport->process($doCreate);
            $reimportedFiles = $reimport->getReimportedPathes();
        } catch (\MittagQI\ZfExtended\FileWriteException $e) {
            $this->io->error($e->getMessage() . "\n" . self::CODEFILE_WRITE_ERROR);

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return self::FAILURE;
        }

        if ($reimport->hasErrors()) {
            $this->io->error($reimport->getErrors());
        }

        $numZxliff = count($reimportedFiles['zxliff']);
        $numJson = count($reimportedFiles['json']);

        if (($numZxliff + $numJson) > 0) {
            $this->io->success('Reimported ' . ($numZxliff + $numJson) . ' localization-files');
            if ($numZxliff > 0) {
                $this->io->newLine(1);
                $rows = [];
                foreach ($reimportedFiles['zxliff'] as $file) {
                    $rows[] = [$file];
                }
                $this->io->table(['Reimported ' . $numZxliff . ' ZXLIFF files:'], $rows);
            }
            if ($numZxliff > 0) {
                $this->io->newLine(1);
                $rows = [];
                foreach ($reimportedFiles['json'] as $file) {
                    $rows[] = [$file];
                }
                $this->io->table(['Reimported ' . $numJson . ' JSON files:'], $rows);
            }

            return self::SUCCESS;
        }

        $this->io->error('No files could be found to reimport in /data/' . L10nConfiguration::DATA_DIR);

        return self::FAILURE;
    }
}

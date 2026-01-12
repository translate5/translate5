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

use MittagQI\ZfExtended\Localization;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\L10n\L10nConfiguration;
use Translate5\MaintenanceCli\L10n\L10nUpdater;

class L10nExtractCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'l10n:extract';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription(
                'Extracts the localization from the current source-code. Make sure, ' .
                'all sym-links for private plugins are set up, otherwise they will not be extracted'
            )

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp(
                'Extracts the localization from the current source-code. Make sure, ' .
                'all sym-links for private plugins are set up, otherwise they will not be extracted'
            );

        $this->addOption(
            'update',
            'u',
            InputOption::VALUE_NONE,
            'The XLIFF files in the code will be updated with the extracted strings.'
        );

        $this->addOption(
            'export',
            'e',
            InputOption::VALUE_NONE,
            'An export-package will be created in /data/' . L10nConfiguration::DATA_DIR
        );

        $this->addOption(
            'amend-missing',
            'a',
            InputOption::VALUE_NONE,
            'If present, the OLD .xliff files in the locales-folder will be used as secondary source ' .
            'to find missing translations. This is helpful to integrate added translations from older branches.'
        );

        $this->addOption(
            'fill-untranslated',
            'f',
            InputOption::VALUE_NONE,
            'All untranslated strings will have the source as target. Only in conjunction with --locale'
        );

        $this->addOption(
            'mark-untranslated',
            'm',
            InputOption::VALUE_NONE,
            'All untranslated strings will have the target "' . L10nConfiguration::UNTRANSLATED .
            '" (instead of the key). Only in conjunction with --locale'
        );

        $this->addOption(
            'locale',
            'l',
            InputOption::VALUE_REQUIRED,
            'Defines the locale to mark/fill.'
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

        $this->writeTitle('Translate5 L10n maintenance - extracting strings');

        $isUpdate = $input->getOption('update');
        $isExport = $input->getOption('export');

        $doAmendMissing = $input->getOption('amend-missing');
        $fillUntranslated = $input->getOption('fill-untranslated');
        $markUntranslated = $input->getOption('mark-untranslated');
        $locale = $input->getOption('locale');

        if (! $isUpdate && ! $isExport) {
            $this->io->error('An option is required. Use --update or --export');

            return self::FAILURE;
        }

        if ($markUntranslated && $fillUntranslated) {
            $this->io->error('The options --fill-untranslated and --mark-untranslated contradict each other.');

            return self::FAILURE;
        }

        if (($markUntranslated || $fillUntranslated) && empty($locale)) {
            $this->io->error('The options --fill-untranslated and --mark-untranslated require a locale to be given.');

            return self::FAILURE;
        }

        if (($markUntranslated || $fillUntranslated) &&
            $locale !== Localization::PRIMARY_LOCALE &&
            ! in_array($locale, Localization::SECONDARY_LOCALES, true)
        ) {
            $this->io->error('The given locale is no valid primary or secondary locale.');

            return self::FAILURE;
        }

        $extraction = new L10nUpdater(
            $isUpdate,
            $isExport,
            $doAmendMissing,
            $markUntranslated,
            $fillUntranslated,
            $locale
        );
        $extraction->process();

        if ($isUpdate) {
            $this->io->success('Extracted and updated the XLIFFs.');
        }

        if ($isExport) {
            $this->io->success('Created an export-package in /data/' . L10nConfiguration::DATA_DIR . '.');
        }

        if ($extraction->hasBrokenMatches()) {
            $rows = [];
            foreach ($extraction->getBrokenMatches() as $match) {
                $rows[] = [$match];
            }
            $this->io->table(['Some translations could not be properly extracted'], $rows);
        }

        return self::SUCCESS;
    }
}

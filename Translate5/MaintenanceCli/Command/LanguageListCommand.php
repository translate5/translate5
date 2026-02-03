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

use editor_Models_Languages as Languages;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * List all languages in translate5,
 * intention behind is to create automatically config files for example for language tool integration
 */
class LanguageListCommand extends UserAbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'language:list';

    protected function configure(): void
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Returns a list of all supported (configured) languages of translate5.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Returns a list of all supported (configured) languages of translate5. '
                . 'By default only name, rfc5646 and LCID is shown. If an argument is given, '
                . 'a plain integer loads a single language by id, otherwise all columns are searched.');

        $this->addArgument(
            'query',
            InputArgument::OPTIONAL,
            'Language id as integer or a search term to match against language columns'
        );

        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Shows all data fields'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @throws ReflectionException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $languages = ZfExtended_Factory::get(Languages::class);
        $query = $input->getArgument('query');
        if ($query !== null && ctype_digit($query)) {
            try {
                $languageRow = $languages->loadById((int) $query);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $this->io->warning('No language found for id ' . $query . '.');

                return self::FAILURE;
            }
            $languages = [$languageRow->toArray()];
        } else {
            $languages = $this->loadAllLanguages($languages, $query);
        }
        $rows = [];
        $all = $input->getOption('all');
        foreach ($languages as $language) {
            if (! $all) {
                $rows[] = [
                    $language['langName'],
                    $language['rfc5646'],
                    $language['lcid'],
                    $this->formatVisibility($language['hidden']),
                ];

                continue;
            }
            $rows[] = [
                $language['id'],
                $language['langName'],
                $language['rfc5646'],
                $language['lcid'],
                $language['iso3166Part1alpha2'],
                $language['sublanguage'],
                $language['iso6393'],
                $this->formatVisibility($language['hidden']),
            ];
        }
        if ($all) {
            $this->io->table([
                'Internal ID',
                'Name',
                'rfc5646',
                'LCID',
                'iso3166Part1alpha2',
                'sublanguage',
                'iso6393',
                'Visibility',
            ], $rows);
        } else {
            $this->io->table(['Name', 'rfc5646', 'LCID', 'Visibility'], $rows);
        }

        return 0;
    }

    private function formatVisibility($hidden): string
    {
        return ((int) $hidden === 1) ? 'hidden' : 'visible';
    }

    private function loadAllLanguages(?Languages $language, mixed $query): array
    {
        $languages = $language->loadAll();
        if ($query !== null && $query !== '') {
            $needle = strtolower($query);
            $languages = array_values(array_filter($languages, function (array $language) use ($needle) {
                foreach ($language as $value) {
                    if (! is_scalar($value)) {
                        continue;
                    }
                    if (str_contains(strtolower((string) $value), $needle)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        return $languages;
    }
}

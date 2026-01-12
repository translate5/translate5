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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class L10nUpdateCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'l10n:update';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription(
                'Extracts and updates the localization from the current source-code. Make sure, ' .
                'all sym-links for private plugins are set up, otherwise they will not be extracted correctly'
            )

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp(
                'Extracts and updates the localization from the current source-code. Make sure, ' .
                'all sym-links for private plugins are set up, otherwise they will not be extracted correctly'
            );

        $this->addOption(
            'mark-missing',
            'm',
            InputOption::VALUE_OPTIONAL,
            'The missing translations of the  given locale will be marked. If no locale is provided, “' .
            Localization::FALLBACK_LOCALE . '” is used.'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandData = [
            // the command name is passed as the first argument
            'command' => 'l10n:extract',
            '--update' => null,
            '--amend-missing' => null,
        ];

        if ($input->hasParameterOption(['--mark-missing', '-m'], true)) {
            $locale = $input->getOption('mark-missing');
            if (empty($locale)) {
                $locale = Localization::FALLBACK_LOCALE;
            }
            $commandData['--mark-untranslated'] = null;
            $commandData['--locale'] = $locale;
        }

        return $this->getApplication()->doRun(new ArrayInput($commandData), $output);
    }
}

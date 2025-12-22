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
use Translate5\MaintenanceCli\L10n\L10nXliffZXliffConverter;

class L10nUpgradeClientSpecificCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'l10n:upgrade-clientspecific';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription(
                'Upgrades existing client-specific localizations to the new "zxliff"-format'
            )

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp(
                'Upgrades existing client-specific localizations to the new "zxliff"-format'
            );

        $this->addOption(
            'report-only',
            'r',
            InputOption::VALUE_NONE,
            'If set only unonverted files will be reported'
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

        $this->writeTitle('Translate5 L10n maintenance - upgrade client-specific localizations');
        $reportOnly = (bool) $this->input->getOption('report-only');

        $converter = new L10nXliffZXliffConverter(APPLICATION_ROOT . '/client-specific/locales');
        if ($reportOnly) {
            $warnings = $converter->upgrade(false);
            if (count($warnings) > 0) {
                $this->io->warning($warnings);
            } else {
                $this->io->success('No localization files need to be upgraded');
            }
        } else {
            $messages = $converter->upgrade();
            $this->io->success($messages);
        }

        return self::SUCCESS;
    }
}

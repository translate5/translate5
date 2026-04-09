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
use Translate5\MaintenanceCli\L10n\L10nExchanger;

class L10nExchangeSourcesCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'l10n:exchange-sources';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription(
                'Exchanges all sources to the current localized english targets'
            )

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp(
                'Extracts the localization from the current source-code and exchanges them for the current ' .
                'localized english targets. Do do this, the following prequesites are neccessary: ' . "\n" .
                '* the english localizations must be complete (no missing translations)' . "\n" .
                '* the english localizations must not contain normal quotes (" or \')' . "\n" .
                '* multiple strings with the same content MUST have the same quote/delimiter' . "\n" .
                'Always test the conversion before writing it (with option -u) until there are no errors anymore!'
            );

        $this->addOption(
            'write-updated-files',
            'w',
            InputOption::VALUE_NONE,
            'The XLIFF files and the source-code will be updated with the new sources.'
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

        $this->writeTitle('Translate5 L10n maintenance - extracting and exchanging sources');

        $doWrite = $input->getOption('write-updated-files');

        try {
            $exchanger = new L10nExchanger($doWrite);
            $exchanger->process();
        } catch (\MittagQI\ZfExtended\FileWriteException $e) {
            $this->io->error($e->getMessage() . "\n" . self::CODEFILE_WRITE_ERROR);

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return self::FAILURE;
        }

        $this->io->success('Successfully changed all localizations-strings in the code and in the XLIFFs to the current english targets');

        if ($exchanger->hasBrokenMatches()) {
            $this->io->newLine();
            $this->io->warning('There were warnings in the process that may need to be evaluated:');
            $this->io->write(implode("\n", $exchanger->getBrokenMatches()));
            $this->io->newLine();
        }

        return self::SUCCESS;
    }
}

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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\L10n\L10nHelper;
use Translate5\MaintenanceCli\L10n\XliffFormatter;

class L10nRemoveCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'l10n:remove';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Removes a string from the localization xliff-files.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Removes a string from the localization xliff-files.');

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'The text to be deleted from the xliff files.'
        );

        $this->addOption(
            'module',
            'm',
            InputOption::VALUE_REQUIRED,
            'The module where the string should be removed from. This can be "editor",' .
            ' "default", "library" or a plugin-name. Defaults to "editor"'
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

        $this->writeTitle('Translate5 L10n maintenance - removing strings');

        $source = $input->getArgument('source');
        $module = $input->getOption('module');
        if (empty($module)) {
            $module = 'editor';
        }

        $xliffs = L10nHelper::evaluateXliffModule($module);
        if (empty($xliffs)) {
            $this->io->error('No xliff files found for module or plugin ' . $module);

            return self::FAILURE;
        }

        $removed = 0;

        foreach ($xliffs as $xliff) {
            $formatter = new XliffFormatter($xliff);
            $removed += $formatter->remove($source);
        }

        if ($removed === 0) {
            $this->io->warning('No strings have been found and removed');
        } else {
            $this->io->success('Removed ' . $removed . ' strings from ' . count($xliffs) . ' xliff files');
        }

        return 0;
    }
}

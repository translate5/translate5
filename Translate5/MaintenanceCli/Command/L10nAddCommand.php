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
use Translate5\MaintenanceCli\L10n\XliffFile;

class L10nAddCommand extends Translate5AbstractCommand {
    
        // the name of the command (the part after "bin/console")
    protected static $defaultName = 'l10n:add';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('TODO.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('TODO.');

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'The text to be added to the xliff file.'
        );

        $this->addOption(
            'path',
            'p',
            InputOption::VALUE_REQUIRED,
            'The path to the xliff files, if omitted defaulting to the editor/locales files.'
        );

        $this->addOption(
            'replace',
            'r',
            InputOption::VALUE_REQUIRED,
            'Replace the trans-unit identified by --after instead of appending to it.'
        );

        $this->addOption(
            'after',
            'a',
            InputOption::VALUE_REQUIRED,
            'The source content after which the new content should be added.'
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
        
        $this->writeTitle('Translate5 L10n maintenance - removing translations');

        //TODO to be loaded from XliffLocation
        $files = [
            'application/modules/editor/locales/de.xliff',
            'application/modules/editor/locales/en.xliff',
        ];

        foreach($files as $file) {
            $file = new \SplFileInfo($file);
            if(! $file->isFile()) {
                $this->io->error('Not found: '.$file);
                continue;
            }
            $this->io->section((string) $file);
            $xlf = new XliffFile($file);
            $source = $input->getArgument('source');
            $after = $input->getOption('after');
            if($input->getOption('replace')) {
                $count = $xlf->replace($after, $input->getArgument('source'));
                $this->writeAssoc([
                    "Source added" => $source,
                    "Replaced" => $source,
                    "Times" => $count,
                ]);
            }
            else {
                $count = $xlf->add($input->getArgument('source'), null, $after);
                $out = [
                    "Source added" => $source,
                    "After" => $after,
                    "Times" => $count,
                ];
                if(is_null($after)) {
                    unset($out['After']);
                }
                $this->writeAssoc($out);
            }
        }

        return 0;
    }
}
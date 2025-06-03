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

/**
 * converts and cleans given TMX files as defined in TS-1040
 */
class TmxFixArayaCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'tmx:araya-fix';

    protected bool $inSegment = false;

    protected $elements = [];

    protected $currentElement = 'source'; // toggles betweeen 'source' and 'target'

    protected function configure()
    {
        $this
            ->setDescription('Helper tool to convert TMX files exported from Araya')
            ->setHelp('Helper tool to convert TMX files exported from Araya');

        $this->addArgument('file', InputArgument::REQUIRED, 'The Araya TMX file to be converted.');

        $this->addOption(
            'devtest',
            't',
            InputOption::VALUE_NONE,
            'DevTest-Interface'
        );

        $this->addOption(
            'analyze',
            'a',
            InputOption::VALUE_NONE,
            'Analyzes the used content tags (ph it bpt ept)'
        );

        $this->addOption(
            'write',
            'w',
            InputOption::VALUE_NONE,
            'writes the output back to a new file (same name with .cleaned.tmx suffix) instead to stdout'
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

        $file = $this->input->getArgument('file');
        if (! is_file($file)) {
            $this->io->error("Given filepath does not point to a file!");

            return 1;
        }
        if (! is_readable($file)) {
            $this->io->error("Given file is not readable!");

            return 1;
        }
        if (stripos($file, '.tmx') === false) {
            $this->io->error("Given file is no TMX file!");

            return 1;
        }

        try {
            $fixexport = new \MittagQI\Translate5\Tools\Tmx\ConvertFromAraya();
            $data = $fixexport->parse(file_get_contents($file));
        } catch (\ZfExtended_ErrorCodeException $e) {
            $logger = \Zend_Registry::get('logger');
            $ev = $logger->exception($e, returnEvent: true);
            $this->io->error($ev);

            return 1;
        }

        if ($input->getOption('write')) {
            $file = str_ireplace('.tmx$', '', $file . '$') . '.cleaned.tmx';
            $bytes = file_put_contents($file, $data);
            $this->io->info($bytes . ' bytes written to file ' . basename($file));
        } else {
            echo $data;
        }

        return 0;
    }
}

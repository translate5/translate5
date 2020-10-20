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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Exception\LogicException;

class DevelopmentGithookCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:githook';
    
    /**
     * @var InputInterface
     */
    protected $input;
    
    /**
     * @var OutputInterface
     */
    protected $output;
    
    /**
     * @var SymfonyStyle
     */
    protected $io;
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Development: Executes code checks invoked by git hooks. The checks are implemented in here.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Executes code checks invoked by git hooks. The checks are implemented in here.');

        $this->addArgument('hook',
            InputArgument::REQUIRED,
            'Needed: git hook to be triggered.'
        );
        
        $this->addArgument('arg',
            InputArgument::OPTIONAL,
            'Additional arguments'
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

        switch($input->getArgument('hook')) {
            case 'commit-msg':
                return $this->commitMsg($input->getArgument('arg'));
        }
        
        return 0;
    }
    
    protected function commitMsg($commitMsg) {
        if(empty($commitMsg)) {
            $this->io->error('No commit message given!');
            return 1;
        }
        if(!preg_match('/^[A-Z]+-[0-9]+: .*/', $commitMsg)) {
            $this->io->error('Commit message does not start with issue key: "KEY-123: "!');
            return 1;
        }
        return 0;
    }
}

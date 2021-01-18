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
use Symfony\Component\Console\Exception\InvalidArgumentException;

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

        $hook = $input->getArgument('hook');
        switch($hook) {
            case 'commit-msg':
                return $this->commitMsg($input->getArgument('arg'));
            case 'pre-commit':
                return $this->preCommit();
            default:
                throw new InvalidArgumentException('Git hook '.$hook.' is not implemented!');
        }
        
        return 0;
    }
    
    protected function preCommit() {
        //check phtml modifications and produce a warning.
        $output = [];
        exec('git diff-index --name-only --cached HEAD --', $output);
        
        if(empty($output)) {
            return 0;
        }
        foreach($output as $line) {
            if(preg_match('/\.phtml$/i', $line)) {
                $this->io->warning([
                    'PHTML file modified!',
                    'Inform client-specific users about the change if needed (via special release note in issue)!',
                    'Consider implementing a precondition check or alter script which checks for the existence of your change in the client-specific overwrite!'
                ]);
            }
            if(preg_match('/\.sql$/i', $line)) {
                if(!$this->sqlCreateTable($line)) {
                    return 1;
                }
            }
        }
        return 0;
    }
    
    protected function sqlCreateTable($file) {
        $content = file_get_contents($file);
        $matches = null;
        $result = true;

        //if we have no charset at all, this is fine
        if(!preg_match_all('/CHARSET[\s]*=[\s]*([^,;]+)/i', $content, $matches)) {
            return true;
        }
        
        foreach($matches[1] as $part) {
            $partList = explode(' ', $part);
            $charset = reset($partList);
            //if the found charset string is not starting with utf8mb4 this is wrong
            if($charset !== 'utf8mb4') {
                $this->io->error('Wrong charset "'.$charset.'" used instead of utf8mb4 in file '.$file);
                $result = false;
            }
            elseif(stripos($part, ' collate ') === false) {
                $this->io->error(['A charset '.$charset.' was provided but the collation is missing.', 'Provide both or omit both!','Alter file: '.$file]);
                $result = false;
            }
        }
        
        return $result;
    }
    
    protected function commitMsg($commitMsgFile) {
        $commitMsg = trim(file_get_contents($commitMsgFile));
        if(empty($commitMsg)) {
            $this->io->error('No commit message given!');
            return 1;
        }

        //valid non issue based commit messages:
        if($commitMsg == 'changelog and submodules') {
            return 0;
        }
        if(!preg_match('/^Merge remote-tracking branch /i', $commitMsg)) {
            return 0;
        }
        if(!preg_match('/^Merge branch /i', $commitMsg)) {
            return 0;
        }
        if(!preg_match('/^Merge .* [A-Z]+-[0-9]+ /i', $commitMsg)) {
            return 0;
        }
        if(!preg_match('/^[A-Z]+-[0-9]+: .*/', $commitMsg)) {
            $this->io->error('Commit message does not start with issue key: "KEY-123: "!');
            return 1;
        }
        return 0;
    }
}

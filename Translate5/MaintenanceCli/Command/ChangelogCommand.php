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
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;


class ChangelogCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'changelog';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Show the last changelog entries.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Tool to list the latest changelog entries.');
        
        $this->addOption(
            'important',
            'i',
            InputOption::VALUE_NONE,
            'Show the important release notes only.');

        $this->addOption(
            'summary',
            's',
            InputOption::VALUE_NONE,
            'Show only a summary');
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

        $content = file_get_contents(APPLICATION_ROOT.'/docs/CHANGELOG.md');


        $summary = $input->getOption('summary');

        if($input->getOption('important')) {
            $this->writeTitle('Translate5 important release Notes:');
            $firstPos = mb_strpos($content, "\n### Important Notes:") + 21;
            $nextPos = mb_strpos($content, "\n### ", $firstPos + 5);
            $content = substr($content, $firstPos, $nextPos - $firstPos);
            $this->io->warning($content);
            return 0;
        }
        
        $this->writeTitle('Translate5 latest change log:');
        $firstPos = mb_strpos($content, "\n## [");
        $nextPos = mb_strpos($content, "\n## [", $firstPos + 5);
        $content = substr($content, $firstPos, $nextPos - $firstPos);
        $chunks = preg_split('/^(#[#]+)\s*(.*)$/m', $content, flags: PREG_SPLIT_DELIM_CAPTURE);
        $chunk = array_shift($chunks);
        $isImportant = false;
        while(!is_null($chunk)) {
            $chunk = array_shift($chunks);
            if(is_null($chunk)) {
                return 0;
            }
            switch ($chunk) {
                case '##':
                    $isImportant = false;
                    $this->io->title(array_shift($chunks));
                    continue 2;
                break;
                case '###':
                    $head = array_shift($chunks);
                    $isImportant = $head === 'Important Notes:';
                    $this->io->section($head);
                    continue 2;
                case '####':
                    $head = array_shift($chunks);
                    if($isImportant) {
                        $this->io->warning($head);
                    } else {
                        $this->io->text($head);
                    }
                    continue 2;
                break;
            }
            $chunk = trim($chunk);
            if(strlen($chunk) > 0) {
                $matches = null;
                if($summary && preg_match_all('#\*\*\[([^]]+)\]\(([^)]+)\):(.+)\*\* <br>#', $chunk, $matches)) {
                    foreach($matches[1] as $idx => $key) {
                        $url = $matches[2][$idx];
                        $subject = $matches[3][$idx];
                        $this->io->text('<info>'.$key.'</info> <options=bold>'.$subject.'</>');
                    }
                }
                else {
                    $chunk = preg_replace('#\*\*\[([^]]+)\]\(([^)]+)\):(.+)\*\* <br>#', "<info>$1</info> <options=bold>$3</> \n <fg=gray>$2</>" , $chunk);
                    $this->io->text($chunk);
                }
                //<fg=yellow;options=bold>not optimal</>
            }
        }
        return 0;
    }
}

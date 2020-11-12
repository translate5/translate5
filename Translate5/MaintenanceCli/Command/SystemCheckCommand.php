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


class SystemCheckCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'system:check';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Checks the system requirements.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Tool to check the system requirements and system health.');
        
        $this->addArgument('module', InputArgument::OPTIONAL, 'Runs only a specific check module.');
        
        $this->addOption(
            'pre-installation',
            null,
            InputOption::VALUE_NONE,
            'In installation mode only the basic environment check is called and the Zend Application is not initialized');
            
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        require_once 'library/ZfExtended/Models/SystemRequirement/Validator.php';
        require_once 'library/ZfExtended/Models/SystemRequirement/Modules/Abstract.php';
        require_once 'library/ZfExtended/Models/SystemRequirement/Result.php';
        
        $isInstallation = $input->getOption('pre-installation');
        
        $this->initInputOutput($input, $output);
        if(!$isInstallation) {
            $this->initTranslate5();
            $this->writeTitle('Translate5 system health check');
        }
        
        $result = 0;
        $validator = new \ZfExtended_Models_SystemRequirement_Validator($isInstallation);
        /* @var $validator \ZfExtended_Models_SystemRequirement_Validator */
        $results = $validator->validate();
        foreach($results as $module => $oneResult) {
            /* @var $validator \ZfExtended_Models_SystemRequirement_Result */
            if($oneResult->hasError()) {
                $shortResult = '<fg=red;options=bold>problematic</>';
            }
            elseif($oneResult->hasWarning()) {
                $shortResult = '<fg=yellow;options=bold>not optimal</>';
            }
            else {
                $shortResult = '<fg=green;options=bold>all ok</>';
            }
            $this->io->text(str_pad($oneResult->name, 30, ' ', STR_PAD_RIGHT).': '.$shortResult);
            if($oneResult->hasError()) {
                $this->io->error($oneResult->error);
            }
            if($oneResult->hasWarning()) {
                $this->io->warning($oneResult->warning);
            }
            if($oneResult->hasInfo()) {
                $this->io->note($oneResult->info);
            }
            if($oneResult->hasError() || $oneResult->hasWarning()) {
                $this->io->text($oneResult->badSummary);
                $this->io->writeln('');
            }
        }
        
        return $result;
    }
}

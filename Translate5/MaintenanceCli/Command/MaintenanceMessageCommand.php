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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceMessageCommand extends MaintenanceCommand {
    
        // the name of the command (the part after "bin/console")
    protected static $defaultName = 'maintenance:message';
    
    protected function configure()
    {
        $this->setAliases(['mnt:message', 'mnt:msg']);
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Sets just a message in the maintenance announce message box to application users.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Sets just a message in the maintenance announce message box to application users. Call without parameters to clear the message.');
        
        $this->addArgument('message',
            InputArgument::OPTIONAL,
            'Provide a message to show to the users.'
            );
    }
    
    protected function _execute() {
        $this->mm->message($this->input->getArgument('message') ?? '');
    }
}

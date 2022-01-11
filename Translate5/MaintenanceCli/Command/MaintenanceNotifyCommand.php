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

class MaintenanceNotifyCommand extends Translate5AbstractCommand {
    
        // the name of the command (the part after "bin/console")
    protected static $defaultName = 'maintenance:notify';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Sends a message directly to all connected users (if messagebus enabled) which opens a popup to the user then.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Sends a message directly to all connected users (if messagebus enabled) which opens a popup to the user then.');

        $this->addArgument('message', InputArgument::REQUIRED, 'The message send to the users, tags are stripped, not HTML is allowed.');
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

        //FIXME Problem: der hier hat eine andere Serverid als der Webserver!
        $bus = \ZfExtended_Factory::get('editor_Plugins_FrontEndMessageBus_Bus', [\editor_Plugins_FrontEndMessageBus_Init::CLIENT_VERSION]);
        $message = $input->getArgument('message');
        $bus->notifyUser($message);

        $this->writeTitle('maintenance: sending to all connected users:');
        $this->io->write($message);
        return 0;
    }
}

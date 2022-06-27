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
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Logger;

class DevelopmentEcodeCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:ecode';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Development: Returns the next free ecode and blocks it globally (via our server)')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Returns the next free ecode and blocks it globally (via our server)');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $config = Zend_Registry::get('config');
        if(empty($config->development->ecodekey)) {
            $this->io->error([
                'Your system is not configured yet to use that feature!',
                '1. Set the security key in your installation.ini: development.ecodekey = XXX',
                '2. Add the EcodeWriter to your installation.ini:',
                "   resources.ZfExtended_Resource_Logger.writer.ecode.type = 'EcodeWriter'"
            ]);
            return 1;
        }

        $newkey = file_get_contents('https://intern.translate5.net/event-code-generator/ecode-gen.php?key='.$config->development->ecodekey);
        if(!is_numeric($newkey)) {
            $this->io->error($newkey);
            return 1;
        }
        $this->io->success('Your new Ecode: E'.$newkey);
        /** @var ZfExtended_Logger $logger */
        $logger = Zend_Registry::get('logger');
        $logger->info('E'.$newkey, 'SET ME BY USING ME! {TEST}', ['TEST'=>"X"]);

        //FIXME check script if there are ecodes duplicated and if there are more as allowed | characters per line.

        return 0;
    }
}

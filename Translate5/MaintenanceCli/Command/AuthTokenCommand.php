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
use ZfExtended_Auth_Token_Entity;
use ZfExtended_Factory;

class AuthTokenCommand extends Translate5AbstractCommand {
    
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'auth:apptoken:add';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Generates auth token used for authentication in translate5')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Generates auth token used for authentication in translate5. The command will generate and display
        the token witch can be used for authenticating in translate5');

        $this->addArgument(
            'login',
            InputArgument::REQUIRED,
            'User login for whom this authentication token will be generate for'
        );

        $this->addArgument(
            'desc',
            InputArgument::REQUIRED,
            'Description for the generated token'
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
        
        $this->writeTitle('Translate5 authentication token');

        $login = $input->getArgument('login');
        $desc = $input->getArgument('desc');

        $auth = ZfExtended_Factory::get('ZfExtended_Auth_Token_Entity');
        /** @var ZfExtended_Auth_Token_Entity $auth */

        $token = $auth->create($login, $desc);

        $this->writeAssoc([
            'Token' => $token
        ]);

        return 0;
    }
}
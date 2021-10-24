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

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


abstract class UserAbstractCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'user:info';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Returns information about one or more users in translate5.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Returns information about one or more users in translate5.');
        
        $this->addArgument('identifier', InputArgument::REQUIRED, 'Either a numeric user ID, a user GUID (with or without curly braces), a login or part of a login when providing % placeholders, or an e-mail.');
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
        $identifier = $this->input->getArgument('identifier');
        
        $uuid = new \ZfExtended_Validate_Uuid();
        $guid = new \ZfExtended_Validate_Guid();
        
        $userModel = \ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel \ZfExtended_Models_User */
        
        if(is_numeric($identifier)) {
            $this->writeTitle('Searching one user with ID "'.$identifier.'"');
            $userModel->load($identifier);
            $this->printOneUser($userModel->getDataObject());
            return 0;
        }
        
        if($uuid->isValid($identifier)){
            $identifier = '{'.$identifier.'}';
            $this->writeTitle('Searching one user with GUID "'.$identifier.'"');
            $userModel->loadByGuid($identifier);
            $this->printOneUser($userModel->getDataObject());
            return 0;
        }
        if($guid->isValid($identifier)){
            $this->writeTitle('Searching one user with GUID "'.$identifier.'"');
            $userModel->loadByGuid($identifier);
            $this->printOneUser($userModel->getDataObject());
            return 0;
        }
        $this->writeTitle('Searching users with login or e-mail "'.$identifier.'"');
        $users = $userModel->loadAllByLoginPartOrEMail($identifier);
        foreach($users as $user) {
            $this->printOneUser((object) $user);
        }
        return 0;
    }

    /**
     * prints the login log from latest to oldes, amount limited to the limit parameter
     * @param string $userGuid
     * @param int $limit
     */
    protected function printLoginLog(string $userGuid, int $limit = 5) {
        $loginLog = \ZfExtended_Factory::get('ZfExtended_Models_LoginLog');
        /* @var $loginLog \ZfExtended_Models_LoginLog */
        $logs = $loginLog->loadByUserGuid($userGuid, $limit);

        if(empty($logs)) {
            $this->io->info('Not logged in yet.');
        }
        else {
            $this->io->section('Last 5 logins (timestamp, status, way):');
        }

        foreach($logs as $log) {
            $this->io->text($log['created'].' '.$log['status'].' '.$log['way']);
        }
    }

    protected function printOneUser(\stdClass $data) {
        $out = [
            '       <info>ID:</info> '.$data->id,
            ' <info>Username:</info> '.OutputFormatter::escape((string) $data->firstName.' '.$data->surName),
            '   <info>Gender:</info> '.OutputFormatter::escape((string) $data->gender),
            '    <info>login:</info> '.OutputFormatter::escape((string) $data->login),
            '   <info>E-Mail:</info> '.OutputFormatter::escape((string) $data->email),
            '     <info>GUID:</info> '.OutputFormatter::escape((string) $data->userGuid),
            '    <info>Roles:</info> '.OutputFormatter::escape((string) $data->roles),
            '   <info>Locale:</info> '.OutputFormatter::escape((string) $data->locale),
        ];
        
        if(!empty($data->parentIds)) {
            $out[] = '<info>Parent IDs:</info> '.OutputFormatter::escape((string) $data->parentIds);
        }
        if(!empty($data->customers)) {
            $out[] = '<info>Customers:</info> '.OutputFormatter::escape((string) $data->customers);
        }
        
        if(!empty($data->openIdIssuer)) {
            $out[] = '<info>Open ID Issuer:</info> '.OutputFormatter::escape((string) $data->openIdIssuer);
        }
        $out[] = '';

        $this->io->text($out);
        if(!$data->editable) {
            $this->io->warning('User is not editable!');
        }
    }
}

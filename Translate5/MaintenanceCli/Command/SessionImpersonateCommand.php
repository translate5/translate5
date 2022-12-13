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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class SessionImpersonateCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'auth:impersonate';
    
    protected function configure()
    {
        $this->setAliases(['session:impersonate']);
        
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Returns a URL to authenticate password less as the given user.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Generates a new session for the given user and returns a URL to use that session in a browser.');
        
        $this->addArgument('login', InputArgument::REQUIRED, 'The login (username) to generate a session for.');
        
        $this->addOption(
            'segment-id',
            's',
            InputOption::VALUE_REQUIRED,
            'Give a segment ID to generate a URL pointing directly to that segment.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        Application::$startSession = true;
        $this->initTranslate5();
        $login = $this->input->getArgument('login');
        $this->writeTitle('Impersonate as user "'.$login.'"');

        $auth = \ZfExtended_Authentication::getInstance();
        if(! $auth->authenticateByLogin($login)) {
            $this->io->error('User '.$login.' not found.');
            return 1;
        }
        
        
        $session = new \Zend_Session_Namespace();
        $locale = $auth->getUser()->getLocale();
        if(\Zend_Locale::isLocale($locale)){
            $session->locale = $locale;
        } else {
            $session->locale = \Zend_Registry::get('config')->runtimeOptions->defaultLanguage;
        }
        
        $sessionDb = \ZfExtended_Factory::get('ZfExtended_Models_Db_Session');
        $sessionId = session_id();
        $token = $sessionDb->updateAuthToken($sessionId);
        $this->io->text([
            '<info>Impersonate as:</info> <options=bold>'.$auth->getUser()->getUsernameLong().'</>',
            '    <info>Session Id:</info> '.$sessionId,
            '       <info>User Id:</info> '.$auth->getUser()->getId(),
            '        <info>E-Mail:</info> '.$auth->getUser()->getEmail(),
            '',
            'Navigate to the following URL in your browser to authenticate as the desired User: ',
            '  <options=bold>'.\Zend_Registry::get('config')->runtimeOptions->server->protocol.$this->makeUrlPath($token).'</>',
            ''
        ]);
        
        $userSession = new \Zend_Session_Namespace('user');
        //set a flag to identify that this session was started by API
        $userSession->loginByApiAuth = true;
        return 0;
    }
    
    protected function makeUrlPath(string $token) {
        $url = $this->translate5->getHostname().'/editor?sessionToken='.$token;
        $segmentId = $this->input->getOption('segment-id');
        if(empty($segmentId)) {
            return $url;
        }
        
        $segment = \ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment \editor_Models_Segment */
        $segment->load($segmentId);
        
        $task = \ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task \editor_Models_Task */
        $task->loadByTaskGuid($segment->getTaskGuid());
        
        return $url.'#task/'.$task->getId().'/'.$segment->getSegmentNrInTask().'/edit';
    }
}

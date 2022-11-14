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
use Symfony\Component\Console\Input\InputArgument;


class SystemMailtestCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'system:mailtest';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Sends a test e-mail to the given address as argument.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Sends a test e-mail to the given address as argument.');

        $this->addArgument('email', InputArgument::REQUIRED, 'Receiver of the test e-mail.');
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
        $this->writeTitle('Check e-mail setup');
        $email = $this->input->getArgument('email');

        $config = \Zend_Registry::get('config');
        /* @var $config \Zend_Config */

        if($config->runtimeOptions->sendMailDisabled) {
            $this->io->error('config runtimeOptions->sendMailDisabled is enabled - no mail send is possible!');
            return self::FAILURE;
        }

        $configCollection = $this->flattenConfig($config->resources->mail->toArray(), 'resources.mail.');
        $configCollection[] = ['runtimeOptions.sendMailLocally' => $config->runtimeOptions->sendMailLocally];
        $configCollection[] = ['runtimeOptions.sendMailDisabled' => $config->runtimeOptions->sendMailDisabled];
        $configCollection[] = ['runtimeOptions.defines.EMAIL_REGEX' => $config->runtimeOptions->defines->EMAIL_REGEX];
        $configCollection[] = ['runtimeOptions.translation.applicationLocale' => $config->runtimeOptions->translation->applicationLocale];
        $configCollection[] = ['runtimeOptions.translation.fallbackLocale' => $config->runtimeOptions->translation->fallbackLocale];
        $configCollection = array_merge($configCollection, $this->flattenConfig($config->runtimeOptions->mail->generalBcc->toArray(), 'runtimeOptions.mail.generalBcc.'));

        $this->io->section('relevant configuration');
        call_user_func_array([$this->io, 'definitionList'], $configCollection);

        if($config->runtimeOptions->sendMailLocally) {
            $this->io->warning('deprecated config runtimeOptions->sendMailLocally used!');
        }

        $this->io->section('Send test e-mail to "'.$email.'"');

        $mail = new \ZfExtended_Mailer('utf-8');
        $mail->setSubject('Translate5 test E-Mail - from '.$config->runtimeOptions->server->name);
        $mail->setBodyText('This is a test e-mail to check if the mail configuration of translate5 is working.');
        $mail->addTo($email);
        $mail->send();
        if(is_null($mail->getLastError())) {
            $this->io->success('Test e-mail sent!');
            return self::SUCCESS;
        }
        else {
            $this->io->error(['Error on sending e-mail: ', (string)$mail->getLastError()]);
            return self::FAILURE;
        }
    }

    protected function flattenConfig(array $data, string $name = ''): array {
        $collection = [];
        foreach($data as $idx => $value) {
            if(is_array($value)) {
                $collection = array_merge($this->flattenConfig($value, $name.$idx.'.'), $collection);
            }
            else {
                $collection[] = [$name.$idx => $value];
            }
        }
        return $collection;
    }
}

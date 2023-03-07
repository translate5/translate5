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
use Symfony\Component\Console\Input\InputOption;
use MittagQI\Translate5\Service\Services;
use Zend_Exception;



class ServiceCheckCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'service:check';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Checks all configured services (base & plugins) if they are setup & working correctly.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Checks all configured services (base & plugins) if they are setup & working correctly');

        $this->addOption(
            'service',
            's',
            InputOption::VALUE_REQUIRED,
            'Specify the service to check'
        );
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

        $config = \Zend_Registry::get('config');
        // load all configured plugins
        $pluginmanager = \Zend_Registry::get('PluginManager');
        $pluginmanager->bootstrap();

        $this->writeTitle('Translate5 service check');

        $serviceName = $input->getOption('service');

        if(empty($serviceName)){
            foreach(Services::getAllServices($config) as $service){
                $service->serviceCheck($this->io);
            }
        } else {
            $service = Services::findService($config, $serviceName);
            if(empty($service)){
                $this->io->warning('The service "'.$serviceName.'" could not be found for the current configuration');
            } else {
                $service->serviceCheck($this->io);
            }
        }
        return self::SUCCESS;
    }
}

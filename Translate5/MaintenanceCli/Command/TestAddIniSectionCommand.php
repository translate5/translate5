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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Test\Config;

class TestAddIniSectionCommand extends Translate5AbstractTestCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'test:addinisection';
    
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('API-Tests: Transfers important configs to the installation.ini\'s test-section.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Transfers important configs from the application database to the test-section in the installation.ini.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $this->initInputOutput($input, $output);
        $this->initTranslate5(); // this needs to run in the normal application environment !

        $section = '[test:application]';
        $installationIniPath = APPLICATION_ROOT.'/application/config/installation.ini';
        $installationIni = file_get_contents($installationIniPath);
        if(!$installationIni){
            $this->io->error('No installation.ini found!');
            return Command::FAILURE;
        }
        // normalizing section seperator, just to be sure
        $installationIni = str_replace("\r", '', $installationIni);
        $installationIni = preg_replace('/ *\[ *test *: *application *\] */i', $section, $installationIni);

        // if the installation.ini already contains a test section we ask if we should override it and if yes dismiss it
        if(str_contains($installationIni, $section)){
            if($this->io->confirm('The installation.ini already has a '.$section.' section, should it be overwritten?')){
                // dismiss the current test section
                $parts = explode($section, $installationIni);
                $installationIni = rtrim($parts[0], "\n");
            } else {
                return Command::SUCCESS;
            }
        }

        // set testSettings (if not already there) to [application]
        if(!str_contains($installationIni, 'testSettings')){
            $installationIni .= implode("\n", [
                "\n",
                ';test settings, this enables api-tests via command for the instance',
                'testSettings.testsAllowed = 1',
                'testSettings.isApiTest = 0'
            ]);
        } else {
            // if there, we make sure they're correct them
            $installationIni = preg_replace('/ *testSettings.testsAllowed *= *[0,1]*/i', 'testSettings.testsAllowed = 1', $installationIni);
            $installationIni = preg_replace('/ *testSettings.isApiTest *= *[0,1]*/i', 'testSettings.isApiTest = 0', $installationIni);
            // ... and complement if neccessary
            if(!str_contains($installationIni, 'testSettings.testsAllowed = 1')){
                $installationIni .= "\n".'testSettings.testsAllowed = 1';
            }
            if(!str_contains($installationIni, 'testSettings.isApiTest = 0')){
                $installationIni .= "\n".'testSettings.isApiTest = 0';
            }
        }
        // add test-section [test:application]
        // retrieve application db-name
        $baseIndex = \ZfExtended_BaseIndex::getInstance();
        $config = $baseIndex->initApplication()->getOption('resources');
        $testDbname = Config::createTestDatabaseName($config['db']['params']['dbname']);
        // add seperator and base configurations
        $installationIni .= "\n\n\n".$section."\n";
        // create test-db-name with a fixed scheme
        $installationIni .= 'resources.db.params.dbname = "'.$testDbname.'"'."\n";
        // add application db-name as different param, it must still be accessible when overridden
        $installationIni .= 'testSettings.applicationDbName = "'.$config['db']['params']['dbname'].'"'."\n";
        // a configuration-option that retrieves if we are API-testing
        $installationIni .= 'testSettings.isApiTest = 1'."\n";

        // save installation ini back
        file_put_contents($installationIniPath, $installationIni);

        // feedback
        $this->io->success('The '.$section.'-section has been appended to installation.ini with the test-db "'.$testDbname.'".');

        return Command::SUCCESS;
    }
}

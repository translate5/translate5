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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestApplytestsqlCommand extends Translate5AbstractTestCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'test:applytestsql';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('API-Tests: Applies the test alter SQL files to the local DB. Needed in fresh installations where tests should be called directly, like docker environments or new development installations')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Applies all test alter SQLs and sets the user passwords correctly');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->initInputOutput($input, $output);

        // may we should clean the application/production DB
        $this->initTranslate5();

        // Create the tables from scratch
        $updater = new \ZfExtended_Models_Installer_DbUpdater();
        //add the test SQL path
        $updater->addAdditonalSqlPath(APPLICATION_PATH . '/modules/editor/testcases/database/');
        // init DB
        if ($updater->importAll() && !$updater->hasErrors()) {
            // encrypt test-user passworts
            \editor_Utils::initDemoAndTestUserPasswords();
            // add needed plugins
            // FIXME $this->initPlugins(); ??? or by auto discovery???
            // add the needed configs
            // FIXME $this->initConfiguration($configs); that must be bootstrapped differently!
            $this->io->success('Imported all SQLs from testcases/database and hashed the test user passwords1');
            return self::SUCCESS;
        }
        if ($updater->hasErrors()) {
            $this->io->error($updater->getErrors());
        }
        if ($updater->hasWarnings()) {
            $this->io->error($updater->getWarnings());
        }

        return self::FAILURE;
    }
}

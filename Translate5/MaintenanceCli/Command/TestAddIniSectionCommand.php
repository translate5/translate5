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
use Translate5\MaintenanceCli\Test\Config;

class TestAddIniSectionCommand extends Translate5AbstractCommand
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
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $section = '[test:application]';
        $installationIniPath = APPLICATION_ROOT.'/application/config/installation.ini';
        $installationIni = file_get_contents($installationIniPath);
        if(!$installationIni){
            $this->io->error('No installation.ini found!');
            return 0;
        }
        // normalizing seperator, just to be sure
        preg_replace('/[ *test *: *application *]/i', $section, $installationIni);

        // if the installation.ini already contains a test section we ask if we should override it and if yes dismiss it
        if(str_contains($installationIni, $section)){
            if($this->io->confirm('The installation.ini already has a '.$section.' section, should it be overwritten?')){
                $parts = explode($section, $installationIni);
                $installationIni = rtrim($parts[0], "\n");
            } else {
                return 0;
            }
        }
        // add seperator and base configurations
        $installationIni .= "\n\n\n".$section."\n";
        $installationIni .= 'resources.db.params.dbname = "'.Config::DATABASE_NAME.'"'."\n"; // fixed DB-name
        $written = 0;
        $missing = 0;

        // now write the values from the DB to the installation.ini
        $config = new \editor_Models_Config();
        foreach(Config::CONFIGS as $name => $value){
            if($value === null){ // value should be taken from existing config
                $dbValue = $config->getCurrentValue($name);
                if($dbValue === null || $dbValue === ''){
                    $installationIni .= '; '.$name.' = ? TODO: not found in application DB, set manually'."\n"; // value not found: user needs to take action
                    $missing++;
                } else {
                    if($dbValue !== 'true' && $dbValue !== 'false' && !ctype_digit($dbValue)){
                        $dbValue = str_contains($dbValue, '"') ? '\''.str_replace('\'', '\\\'', $dbValue).'\'' : '"'.$dbValue.'"';
                    }
                    $installationIni .= $name.' = '.$dbValue."\n";
                    $written++;
                }
            }
        }
        // save installation ini back
        file_put_contents($installationIniPath, $installationIni);

        $this->io->info('The '.$section.'-section has been appended to installation.ini, '.$written.' configs have been added.');
        if($missing > 0){
            $this->io->warning($missing.' configs have not been found in the application DB. Please set them manually!');
        }
        return 0;
    }
}

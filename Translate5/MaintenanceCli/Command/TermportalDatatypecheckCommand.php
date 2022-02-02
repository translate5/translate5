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
use Symfony\Component\Console\Question\Question;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;


class TermportalDatatypecheckCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'termportal:datatypecheck';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Checks the integrity of the term datatypes against the content in the attributes table. This is necessary due TRANSLATE-2797.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Shows inconsistent term datatypes compared against to the term attributes.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $this->io->title('Translate5 termportal: Shows inconsistent term datatypes.');

        $checker = new \editor_Models_Terminology_DataTypeConsistencyCheck();
        $invalidDataTypes = $checker->checkAttributesAgainstDataTypes();
        if(empty($invalidDataTypes)){
            $this->io->success('Datatypes are consistent against term attributes.');
        }else {
            $headers = array_keys(reset($invalidDataTypes));
            $this->io->section('Inconsistent datatypes against the term attributes:');
            $this->io->table($headers, $invalidDataTypes);
        }

        $invalidAgainstDefault = $checker->checkDataTypesAgainstDefault();

        if(empty($invalidAgainstDefault['notFound']) && empty($invalidAgainstDefault['differentContent'])) {
            $this->io->success('Datatypes are consistent against the defined reference datatypes.');
        }
        else {
            if(!empty($invalidAgainstDefault['notFound'])) {
                $this->io->section('The following defaults are missing :');
                $this->io->text(print_r($invalidAgainstDefault['notFound'],1));
            }
            if(!empty($invalidAgainstDefault['differentContent'])) {
                $this->io->section('The following datatypes are different to the defaults:');
                $this->io->text(print_r($invalidAgainstDefault['differentContent'],1));
            }
        }

        return 0;
    }
}

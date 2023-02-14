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

        // Get duplicated attrs separately for each level
        $duplicate = $checker->checkAttributeDuplicates();

        // Foreach level
        foreach ($duplicate as $level => $info) {

            if (empty($info)) {
                $this->io->success("There are no duplicated attributes found at $level-level");
            } else {
                $this->io->section("The following duplicated attributes are found at $level-level");
                $headers = array_keys(reset($info));
                $this->io->table($headers, $info);
            }
        }

        // Show first 10 term-level attributes having no termTbxId
        if (empty($noTermTbxId = $checker->noTermTbxId())) {
            $this->io->success("There are no term-level attributes having no termTbxId");
        } else {
            $this->io->section("First 10 of term-level attributes have no termTbxId");
            $this->io->table(array_keys(reset($noTermTbxId)), $noTermTbxId);
        }

        // Get all cases when attributes have same dataTypeId but different type
        if (empty($sameDataTypeIdDiffType = $checker->sameDataTypeIdDiffType())) {
            $this->io->success("There are no attributes having same dataTypeId but different type");
        } else {
            $this->io->section("Cases when attributes have same dataTypeId but different type");
            $this->io->table(array_keys(reset($sameDataTypeIdDiffType)), $sameDataTypeIdDiffType);
        }

        // Get all cases when attributes have same type but different elementName
        if (empty($sameTypeDiffElementName = $checker->sameTypeDiffElementName())) {
            $this->io->success("There are no attributes having same type but different elementName");
        } else {
            $this->io->section("All cases when attributes have same type but different elementName");
            $this->io->table(array_keys(reset($sameTypeDiffElementName)), $sameTypeDiffElementName);
        }

        // Get all cases when attributes exist on unexpected levels
        if (empty($sameTypeUnexpectedLevel = $checker->sameTypeUnexpectedLevel())) {
            $this->io->success("There are no attributes existing on unexpected levels");
        } else {
            $this->io->section("All cases when attributes exist on unexpected levels");
            $this->io->table(array_keys(reset($sameTypeUnexpectedLevel)), $sameTypeUnexpectedLevel);
        }

        // Get all cases when datatypes have same type but different label
        if (empty($sameTypeDiffLabel = $checker->sameTypeDiffLabelOrLevel())) {
            $this->io->success("There are no datatypes having same type but different label or level");
        } else {
            $this->io->section("All cases when datatypes have same type but different label or level");
            $this->io->table(array_keys(reset($sameTypeDiffLabel)), $sameTypeDiffLabel);
        }

        return 0;
    }
}

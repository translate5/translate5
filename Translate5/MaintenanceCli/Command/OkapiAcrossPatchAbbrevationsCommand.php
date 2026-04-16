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

declare(strict_types=1);

namespace Translate5\MaintenanceCli\Command;

use MittagQI\Translate5\Across\AcrossLanguageSettingsParser;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\Srx;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class OkapiAcrossPatchAbbrevationsCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'okapi:across:patchabbrevations';

    protected function configure()
    {
        $this
            ->setDescription('Patches the abbrevations from an across settings-file onto an OKAPI SRX file')
            ->setHelp(
                'Patches the abbrevations from an across settings-file onto an OKAPI SRX file:' . "\n" .
                '* An Across Settings file (usually LanguageSettings.xml, must be *.xml)  need to be present into data/across/' . "\n" .
                '* An OKAPI Segmentation file (usually languages.srx, must be *.srx)  need to be present into data/across/' . "\n" .
                'By calling this command the Abbrevetion rules are then transfered from the Across Settings to the OKAPI Segmentation file. ' . "\n" .
                'The original SRX will be saved with the extension ".original" and the patched SRX will have the original name. ' . "\n" .
                'Be aware, that new rules will be added on the top of the rules-list, what might has to be adjusted manually if needed.'
            );

        $this->addOption(
            'debug',
            'd',
            InputOption::VALUE_NONE,
            'Debugs additional information to the error-log'
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

        $this->writeTitle('Patch OKAPI Segmentation file with Across Translation Settings');

        $doDebug = $this->input->getOption('debug');
        $workDir = APPLICATION_DATA . '/across/';

        $xmlFiles = glob($workDir . '/*.{xml,XML}', GLOB_BRACE);
        $srxFiles = glob($workDir . '/*.{srx,SRX}', GLOB_BRACE);
        $xmlFile = ($xmlFiles !== false && count($xmlFiles) > 0) ? $xmlFiles[0] : null;
        $srxFile = ($srxFiles !== false && count($srxFiles) > 0) ? $srxFiles[0] : null;

        if ($xmlFile === null) {
            $this->io->error('Could not find an Across settings (*.xml) file');

            return self::FAILURE;
        }

        if ($srxFile === null) {
            $this->io->error('Could not find an OKAPI Segmentation (*.srx) file');

            return self::FAILURE;
        }

        $srx = null;

        try {
            $srx = new Srx($srxFile);
            if (! $srx->validate()) {
                throw new \Exception($srx->getValidationError());
            }
        } catch (\Throwable $e) {
            $this->io->error('The SRX seems invalid: ' . $e->getMessage());
        }

        $acrossSettings = new AcrossLanguageSettingsParser($xmlFile);
        if (! $acrossSettings->isValid()) {
            $this->io->error('The across settings seem invalid: ' . $acrossSettings->getValidationError());
        }

        $added = [];
        foreach ($acrossSettings->getLanguages() as $locale => $language) {
            $abbrevations = $language->getTerms();
            if (count($abbrevations) > 0) {
                try {
                    $error = $srx->addAcrossAbbrevationsForLanguage($locale, $abbrevations, $doDebug);
                    if ($error === null) {
                        $added[] = $locale;
                    } else {
                        $this->io->warning('A problem occured adding abbrevations for "' . $locale . '": ' . $error);
                    }
                } catch (\Throwable $e) {
                    $this->io->error('Error adding the abbrevations for "' . $locale . '": ' . $e->getMessage());

                    return self::FAILURE;
                }
            }
        }

        copy($srxFile, $srxFile . '.original');
        $srx->flush();

        $this->io->success(
            'Added abbrevations to the SRX-files for the following locales: "' .
            implode('", "', $added) . '"'
        );

        return self::SUCCESS;
    }
}

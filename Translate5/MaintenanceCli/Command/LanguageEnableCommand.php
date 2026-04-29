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

use editor_Models_Languages;
use MittagQI\Translate5\Language\LanguageVisibility;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

class LanguageEnableCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'language:enable';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Enables a language.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Enables a language - ensure that you know what you are doing when using that command!');

        $this->addArgument(
            'langId',
            InputArgument::REQUIRED,
            'RFC5646 language shortcut (de, de-DE) or Languages.id'
        );
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $languageId = $input->getArgument('langId');

        if ($languageId === null) {
            return self::FAILURE;
        }

        $byRfc5646 = ! ctype_digit($languageId);
        $language = new editor_Models_Languages();

        try {
            if ($byRfc5646) {
                $language->loadByRfc5646($languageId);
            } else {
                $language->loadById((int) $languageId);
            }
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $this->io->error('Language not found: ' . $languageId);

            return self::FAILURE;
        }

        if ($language->getHidden() === '0') {
            $this->io->warning('Language is enabled already: ' . $languageId . ' - ' . $language->getLangName());

            return self::SUCCESS;
        }

        if ($byRfc5646 && strlen($languageId) === 2) {
            $languageIds = LanguageVisibility::updateGroupVisibility($languageId, false);
        } else {
            $language->setHidden(false);
            $language->save();
            $languageIds = [$languageId . ' - ' . $language->getLangName()];
        }

        $this->io->success(
            'Language' . (count($languageIds) > 1 ? 's' : '') . ' enabled: ' . implode(',', $languageIds)
        );

        return self::SUCCESS;
    }
}

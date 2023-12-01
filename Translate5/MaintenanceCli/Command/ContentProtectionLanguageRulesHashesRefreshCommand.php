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
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHash;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class ContentProtectionLanguageRulesHashesRefreshCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'content-protection:language-rules-hashes:refresh';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('Content protection: Refresh language rules hashes');

        $repository = new ContentProtectionRepository();

        $dbMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $select = $dbMapping->select()
            ->from(['mapping' => $dbMapping->info($dbMapping::NAME)], ['distinct(languageId)']);

        $languageIds = array_column($dbMapping->fetchAll($select)->toArray(), 'languageId');

        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $languageRulesHash = ZfExtended_Factory::get(LanguageRulesHash::class);

        foreach ($languageIds as $languageId) {
            $language->load($languageId);

            try {
                $languageRulesHash->loadByLanguageId((int) $languageId);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                // if not found we simply create new
                $languageRulesHash->init();
                $languageRulesHash->setLanguageId((int)$languageId);
            }

            $languageRulesHash->setHash($repository->getRulesHashBy($language));
            $languageRulesHash->save();
        }

        return 0;
    }
}
